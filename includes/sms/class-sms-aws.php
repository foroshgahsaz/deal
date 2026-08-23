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


use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;

class WP_CarDealer_SMS_Aws {
	
	protected static $_instance = null;
	private $credentials;

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
		$access_key = wp_cardealer_get_option( 'phone_approve_aws_access_key' );
		$secret_key = wp_cardealer_get_option( 'phone_approve_aws_secret_access_key' );
		$this->credentials = new Credentials(
			$access_key,
			$secret_key
		);

	}

	public function sendSMS( $phone, $message ){
		$SnSclient = new SnsClient([
		    'credentials' 	=> $this->credentials,
		    'region' 		=> 'us-east-1',
		    'version' 		=> 'latest'
		]);

		try {
		    $result = $SnSclient->publish([
		        'Message' => $message,
		        'PhoneNumber' => $phone,
		    ]);
		} catch (AwsException $e) {
		    // output error message if fails
		    return new WP_Error( 'operator-error', $e->getMessage() );
		} 

	}

}
