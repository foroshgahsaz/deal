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


use Twilio\Rest\Client;

class WP_CarDealer_SMS_Twilio{

	protected static $_instance = null;
	private $account_sid, $auth_token, $senders_number;

	public function __construct(){
		$this->set_credentials();
	}

	public static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	private function set_credentials(){
		
		$this->account_sid 		= wp_cardealer_get_option('phone_approve_twilio_account_sid');
		$this->auth_token 		= wp_cardealer_get_option('phone_approve_twilio_auth_token');
		$this->senders_number 	= wp_cardealer_get_option('phone_approve_twilio_sender_number');	
	}

	public function sendSMS( $phone, $message ){

		$client = new Client(
			$this->account_sid,
			$this->auth_token
		);


		try {
		    $client->messages->create(
		    // Where to send a text message (your cell phone?)
			    $phone,
			    array(
			        'from' => $this->senders_number,
			        'body' => $message
			    )
			);
		} catch (Exception $e) {
		    // output error message if fails
		    return new WP_Error( 'operator-error', $e->getMessage() );
		}

	}

}