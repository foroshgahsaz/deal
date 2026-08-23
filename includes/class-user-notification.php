<?php
/**
 * User Notification
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_User_Notification {
	
	public static function init() {

		add_action( 'wjbp_ajax_wp_cardealer_ajax_remove_notify',  array(__CLASS__, 'process_remove_notification') );

		// for private message
		add_action('wp-private-message-after-add-message', array( __CLASS__, 'add_private_message_nnotify'), 10, 3 );
	}

	public static function add_notification($args) {
		
		$args = wp_parse_args( $args, array(
			'user_id' => 0,
			'unique_id' => uniqid(),
			'viewed' => 0,
            'time' => current_time('timestamp'),
            'type' => '',
            'application_id' => 0,
            'employer_id' => 0,
            'listing_id' => 0,
		));

		extract( $args );

		if ( empty($user_id) ) {
			return;
		}

		$notifications = get_user_meta($user_id, '_user_notifications', true);;
        $notifications = !empty($notifications) ? $notifications : array();

        $new_notifications = array_merge( array($unique_id => $args), $notifications );

		update_user_meta($user_id, '_user_notifications', $new_notifications);
	}

	public static function process_remove_notification() {
		$return = array();
		if (  !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-remove-notify-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$unique_id = !empty($_POST['unique_id']) ? $_POST['unique_id'] : '';

		$user_id = get_current_user_id();
		


		$notifications = self::get_notifications($user_id);
		if ( !empty($notifications[$unique_id]) ) {
			unset($notifications[$unique_id]);
			update_user_meta($user_id, '_user_notifications', $notifications);

			$return = array( 'status' => true, 'msg' => esc_html__('The notification was successfully removed', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		} else {
			$return = array( 'status' => false, 'msg' => esc_html__('The notification dosen\'t exist', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
	}

	public static function get_notifications($user_id) {
		
		if ( empty($user_id) ) {
			return;
		}

		$notifications = get_user_meta($user_id, '_user_notifications', true);
        $notifications = !empty($notifications) ? $notifications : array();

        return $notifications;
	}

	public static function get_not_seen_notifications($user_id) {
		$notifications = self::get_notifications($user_id);
		if ( empty($notifications) ) {
			return;
		}
		$return = [];
		foreach ( $notifications as $key => $notify ) {
			if ( isset($notify['viewed']) ) {
				$return[] = $notify;
			}
		}
        return $return;
	}
	
	public static function remove_notification($user_id, $unique_id = '') {
		$notifications = self::get_notifications($user_id);
		if ( empty($notifications) ) {
			return true;
		}
		if ( !empty($notifications[$unique_id]) ) {
			unset($notifications[$unique_id]);
		} else {
			return false;
		}

		update_user_meta($user_id, '_user_notifications', $notifications);

        return true;
	}
	
	public static function add_private_message_nnotify($message_id, $recipient, $user_id) {
		
		$notify_args = array(
			'user_id' => $recipient,
            'type' => 'new_private_message',
            'sender_user_id' => $user_id,
            'message_id' => $message_id,
		);
		WP_CarDealer_User_Notification::add_notification($notify_args);
	}

	public static function display_notify($args) {
		$type = !empty($args['type']) ? $args['type'] : '';
		switch ($type) {
			case 'approved_listing':
				$listing_id = !empty($args['listing_id']) ? $args['listing_id'] : '';
				$html = sprintf(__('Your listing <a href="%s" target="_blank">%s</a> has been approved', 'wp-cardealer'), get_permalink($listing_id), get_the_title($listing_id));
				break;
			
			case 'new_private_message':
				$sender_user_id = !empty($args['sender_user_id']) ? $args['sender_user_id'] : '';
				
				$user = get_userdata( $sender_user_id );
				if ( is_object($user) ) {
					$html = sprintf(__('A new private message from %s.', 'wp-cardealer'), $user->display_name );
				}
				break;
			case 'new_favorite':
				$listing_id = !empty($args['listing_id']) ? $args['listing_id'] : '';
				$html = sprintf(__('Someone favorites your listing <a href="%s" target="_blank">%s</a>', 'wp-cardealer'), get_permalink($listing_id), get_the_title($listing_id));
				break;
			case 'new_review':
				$listing_id = !empty($args['listing_id']) ? $args['listing_id'] : '';
				$html = sprintf(__('Someone leave a review your listing <a href="%s" target="_blank">%s</a>', 'wp-cardealer'), get_permalink($listing_id), get_the_title($listing_id));
				break;
			default:
				$html = '';
				break;
		}

		return apply_filters( 'wp-cardealer-display-notify', $html, $args);
	}
	
}

WP_CarDealer_User_Notification::init();