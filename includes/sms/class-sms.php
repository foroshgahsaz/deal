<?php
/**
 * Agent
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_SMS {
	
	public static function init() {
		$operator = wp_cardealer_get_option( 'phone_approve_operator', 'twilio' );
		if ( $operator == 'aws' ) {
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/libraries/aws/aws-autoloader.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/class-sms-aws.php';
		} else {
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/libraries/twilio/src/Twilio/autoload.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/class-sms-twilio.php';
		}

		require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/class-geolocation.php';
		require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/class-otp-handler.php';
	}

	public static function request_otp(){

		
		//If phone field is empty
		if( ( !isset( $_POST['phone'] ) || !trim( $_POST['phone'] ) ) ){
			$return = [
				'status' => false,
				'msg' => __( 'Phone field cannot be empty', 'wp-cardealer' ),
			];
			return $return;
		}

		//Check for phone code
		if( ( !isset( $_POST['phone-cc'] ) || !$_POST['phone-cc'] ) && wp_cardealer_get_option('phone_approve_show_country_code') === 'on' ){
			$return = [
				'status' => false,
				'msg' => __( 'Please select country code', 'wp-cardealer' ),
			];
			return $return;
		}

		$phone_no 	= isset( $_POST['phone'] ) ? sanitize_text_field( trim( $_POST['phone'] ) ) : '';
		$phone_code = isset( $_POST['phone-cc'] ) ? sanitize_text_field( $_POST['phone-cc'] ): '';

		
		if( !$phone_code ) {
			$phone_code = wp_cardealer_get_option('phone_approve_default_country_code') === 'geolocation' && WP_CarDealer_SMS_Geolocation::get_phone_code() ? WP_CarDealer_SMS_Geolocation::get_phone_code() : wp_cardealer_get_option('phone_approve_default_country_code_custom');
		}

		$phone_otp_data = WP_CarDealer_SMS_Otp_Handler::get_otp_data();

		if( !is_array( $phone_otp_data ) ){
			$phone_otp_data = array();
		}

		$form_validation = apply_filters( 'wp_cardealer_phone_form_validation', new WP_Error(), $phone_code, $phone_no, $phone_otp_data );

		if( $form_validation->get_error_code() ){
			$return = [
				'status' => false,
				'msg' => $form_validation->get_error_message(),
			];
			return $return;
		}

		if( !$phone_no || !$phone_code ){
			$return = [
				'status' => false,
				'msg' => __( 'Please select country code', 'wp-cardealer' ),
			];
			return $return;
		}


		// $user = wp_cardealer_get_user_by_phone( $phone_no, $phone_code );

		// if ( $user ) {

		// 	//Register form
		// 	$loginNotice  =  __( 'Sorry, this phone number is already in use.', 'wp-cardealer' );
		// 	$loginNotice .= defined( 'XOO_EL_VERSION' ) ? '<span class="xoo-el-login-tgr">'.__( 'Please login', 'wp-cardealer' ).'</span>' : __( 'Please login', 'wp-cardealer' );

		// 	$return = [
		// 		'status' => 'error',
		// 		'msg' => $loginNotice,
		// 	];
		// 	wp_send_json($return);
		// }
		
		//If phone has been verified, return
		if( $phone_no && isset( $phone_otp_data[ 'phone_no' ] ) && $phone_otp_data['phone_no'] === $phone_no && isset( $phone_otp_data[ 'phone_code' ] ) && $phone_otp_data['phone_code'] === $phone_code && isset( $phone_otp_data['verified'] ) && $phone_otp_data['verified'] ){
			
			return array(
				'status' 	=> true,
				'otp_sent' 	=> 1,
				'phone' 	=> $phone_code.$phone_no,
				'phone_no' 	=> $phone_no,
				'phone_code'=> $phone_code,
				'error' 	=> 0,
				'msg' 	=> sprintf( __( 'Please enter the OTP sent to <br> <strong>%s</strong>', 'wp-cardealer' ), $phone_code.$phone_no ),
			);
		}


		//Send OTP SMS only if its ajax call.
		if( !wp_doing_ajax() ){
			$return = [
				'status' => false,
				'msg' => __( 'Please verify your mobile number', 'wp-cardealer' ),
			];
			wp_send_json($return);
		};


		$otp = WP_CarDealer_SMS_Otp_Handler::sendOTPSMS( $phone_code, $phone_no );

		if( is_wp_error( $otp ) ){
			$return = [
				'status' => false,
				'msg' => $otp->get_error_message(),
			];
			return $return;
		}

		do_action( 'wp_cardealer_request_otp_sent', $phone_code, $phone_no, $phone_otp_data );

		return array(
			'status' 	=> true,
			'otp_sent' 	=> 1,
			'phone' 	=> $phone_code.$phone_no,
			'phone_no' 	=> $phone_no,
			'phone_code'=> $phone_code,
			'error' 	=> 0,
			'msg' 	=> sprintf( __( 'Please enter the OTP sent to <br> <strong>%s</strong>', 'wp-cardealer' ), $phone_code.$phone_no ),
		);

	

	}


}

WP_CarDealer_SMS::init();