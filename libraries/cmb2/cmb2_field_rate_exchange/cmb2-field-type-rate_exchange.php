<?php
/**
 * CMB2 File
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_CMB2_Field_Rate_Exchange {

	public static function init() {
		add_filter( 'cmb2_render_wp_cardealer_rate_exchange', array( __CLASS__, 'render_map' ), 10, 5 );
		add_filter( 'cmb2_sanitize_wp_cardealer_rate_exchange', array( __CLASS__, 'sanitize_map' ), 10, 4 );

		// Ajax endpoints.
		add_action( 'wpcd_ajax_wp_cardealer_ajax_rate_exchange',  array(__CLASS__, 'process_rate_exchange') );

		// compatible handlers.
		add_action( 'wp_ajax_wp_cardealer_ajax_rate_exchange',  array(__CLASS__, 'process_rate_exchange') );
	}

	/**
	 * Render field
	 */
	public static function render_map( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		
		self::setup_admin_scripts();

		$html = '<div class="rate-exchange-wrapper">';

		$html .= $field_type_object->input( array(
				'type'       => 'text',
				'name'       => $field->args( '_name' ),
				'id'       => $field->args( '_name' ),
				'value'      => $field_escaped_value,
				'class'      => 'wp-cardealer-rate-exchange-input',
				'desc'       => '',
			) );
		
		$html .= '<button class="button wp-cardealer-rate-exchange-btn" type="button">'.esc_html__('Update Rate', 'wp-cardealer').'</button>';
		$html .= '</div>';

		echo $html;
	}

	public static function sanitize_map( $override_value, $value, $object_id, $field_args ) {
		return $value;
	}

	public static function process_rate_exchange() {
		$return = array();
		$default_currency = !empty($_POST['default_currency']) ? $_POST['default_currency'] : '';
		$current_currency = !empty($_POST['current_currency']) ? $_POST['current_currency'] : '';
		if ( empty($default_currency) || empty($current_currency) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please choose a currency.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$api_key = wp_cardealer_get_option('exchangerate_api_key');
		$req_url = 'https://v6.exchangerate-api.com/v6/'.$api_key.'/latest/'.$default_currency;
		$remote = wp_remote_get( $req_url );

        if ( is_wp_error( $remote ) ) {
            return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'wp-cardealer' ) );
        }

        if ( 200 != wp_remote_retrieve_response_code( $remote ) ) {
        	return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'wp-cardealer' ) );
        }
        $data = json_decode($remote['body'], true);
        $conversion_rates = $data['conversion_rates'];
        // echo "<pre>".print_r($conversion_rates,1); die;
        if ( empty($conversion_rates[$current_currency]) ) {
        	$return = array( 'status' => false, 'msg' => esc_html__('The currency is not available.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
        }
        
        $return = array( 'status' => true,
        	'msg' => esc_html__('error.', 'wp-cardealer'),
        	'rate' => $conversion_rates[$current_currency]
        );

	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function setup_admin_scripts() {
		wp_register_script( 'rate-exchange-script', plugins_url( 'js/script.js', __FILE__ ), array(), '1.0' );
		wp_localize_script( 'rate-exchange-script', 'wp_job_board_rate_exchange_opts', array(
			'ajaxurl_endpoint' => WP_CarDealer_Ajax::get_endpoint(),
		));
		wp_enqueue_script('rate-exchange-script');
	}
}

WP_CarDealer_CMB2_Field_Rate_Exchange::init();