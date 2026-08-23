<?php
/**
 * Price
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Email {
	
	public static $emails_vars;

	public static function init() {
		// Ajax endpoints.
		add_action( 'wpcd_ajax_wp_cardealer_ajax_contact_form',  array(__CLASS__,'process_send_contact') );
		add_action( 'wpcd_ajax_wp_cardealer_ajax_make_an_offer_form',  array(__CLASS__,'process_send_make_an_offer') );
		add_action( 'wpcd_ajax_wp_cardealer_ajax_schedule_test_drive_form',  array(__CLASS__,'process_send_schedule_test_drive') );
		
	}

	public static function wp_mail( $author_email, $subject, $content, $headers, $attachments = null) {
		if ( !preg_match( '%<html[>\s].*</html>%is', $content ) ) {
			$header = apply_filters( 'wp-cardealer-mail-html-header',
				'<!doctype html>
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset='.get_bloginfo( 'charset' ).'" />
			<title>' . esc_html( $subject ) . '</title>
			</head>
			<body>
			', $subject );

			$footer = apply_filters( 'wp-cardealer-mail-html-footer',
						'</body>
			</html>' );

			$content = $header . wpautop( $content ) . $footer;
		}
		
		return wp_mail( $author_email, $subject, $content, $headers, $attachments );
	}

	public static function process_send_contact() {
		$is_form_filled = ! empty( $_POST['email'] ) && ! empty( $_POST['name'] ) && ! empty( $_POST['message'] );
		if ( WP_CarDealer_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_CarDealer_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$is_form_filled = false;
			}
		}

		$subject = wp_cardealer_get_option('listing_contact_form_notice_subject');
		$content = wp_cardealer_get_option('listing_contact_form_notice_content');
		if ( empty($content) ) {
			$content = self::get_email_default_content('listing_contact_form_notice_content');
		}

		if ( !empty($_POST['post_id']) ) {
			$author_id = get_post_field ('post_author', sanitize_text_field($_POST['post_id']) );

			$listing_title = get_the_title($_POST['post_id']);
			$listing_url = get_permalink($_POST['post_id']);

			$subject = str_replace('{{listing_title}}', $listing_title, $subject);
			$content = str_replace('{{listing_title}}', $listing_title, $content);
			$content = str_replace('{{listing_url}}', $listing_url, $content);

			$author_email = get_the_author_meta( 'user_email' , $author_id );

		} elseif ( !empty($_POST['user_id']) ) {
			$author_id = sanitize_text_field($_POST['user_id']);

			$author_email = get_the_author_meta( 'user_email' , $author_id );
		}

		if ( empty($author_id) ) {
			$is_form_filled = false;
		}

		if ( $is_form_filled && !empty($author_email) ) {
			
	        $email = sanitize_text_field( $_POST['email'] );
	        $phone = sanitize_text_field( $_POST['phone'] );
	        $user_name = sanitize_text_field( $_POST['name'] );
	        $message = sanitize_text_field( $_POST['message'] );

	        $subject = str_replace('{{user_name}}', $user_name, $subject);

	        $content = str_replace('{{user_name}}', $user_name, $content);
	        $content = str_replace('{{website_url}}', home_url(), $content);
	        $content = str_replace('{{website_name}}', get_bloginfo( 'name' ), $content);
	        $content = str_replace('{{email}}', $email, $content);
	        $content = str_replace('{{phone}}', $phone, $content);
	        $content = str_replace('{{message}}', $message, $content);
	        
	        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email, $email );
	        
	        $result = false;
			$result = WP_CarDealer_Email::wp_mail( $author_email, $subject, $content, $headers );

			do_action('wp-cardealer-after-process-send-contact', $author_email, $subject, $content, $headers);
	        if ( $result ) {
	        	$return = array( 'status' => true, 'msg' => esc_html__('Your message has been successfully sent.', 'wp-cardealer') );
	        } else {
	        	$return = array( 'status' => false, 'msg' => esc_html__('An error occurred when sending an email.', 'wp-cardealer') );
	        }
	    } else {
	    	$return = array( 'status' => false, 'msg' => esc_html__('Form has been not filled correctly.', 'wp-cardealer') );
	    }
	    echo wp_json_encode($return);
	   	exit;
	}

	public static function process_send_make_an_offer() {
		$is_form_filled = ! empty( $_POST['email'] ) && ! empty( $_POST['name'] ) && ! empty( $_POST['price'] ) && !empty($_POST['post_id']);
		if ( WP_CarDealer_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_CarDealer_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$is_form_filled = false;
			}
		}

		$author_id = get_post_field ('post_author', sanitize_text_field($_POST['post_id']) );

		$author_email = get_the_author_meta( 'user_email' , $author_id );

		if ( empty($author_id) ) {
			$is_form_filled = false;
		}

		if ( $is_form_filled && !empty($author_email) ) {
			
			$subject = wp_cardealer_get_option('listing_make_an_offer_notice_subject');
			$content = wp_cardealer_get_option('listing_make_an_offer_notice_content');
			if ( empty($content) ) {
				$content = self::get_email_default_content('listing_make_an_offer_notice_content');
			}

			$listing_title = get_the_title($_POST['post_id']);
			$listing_url = get_permalink($_POST['post_id']);

			$subject = str_replace('{{listing_title}}', $listing_title, $subject);
			$subject = str_replace('{{user_name}}', $user_name, $subject);

			
	        $email = sanitize_text_field( $_POST['email'] );
	        $phone = sanitize_text_field( $_POST['phone'] );
	        $user_name = sanitize_text_field( $_POST['name'] );
	        $price = sanitize_text_field( $_POST['price'] );

	        
	        $content = str_replace('{{listing_title}}', $listing_title, $content);
			$content = str_replace('{{listing_url}}', $listing_url, $content);
	        $content = str_replace('{{user_name}}', $user_name, $content);
	        $content = str_replace('{{website_url}}', home_url(), $content);
	        $content = str_replace('{{website_name}}', get_bloginfo( 'name' ), $content);
	        $content = str_replace('{{email}}', $email, $content);
	        $content = str_replace('{{phone}}', $phone, $content);
	        $content = str_replace('{{price}}', $price, $content);
	        
	        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email, $email );
	        
	        $result = false;
			$result = WP_CarDealer_Email::wp_mail( $author_email, $subject, $content, $headers );

			do_action('wp-cardealer-after-process-send-make-an-offer', $author_email, $subject, $content, $headers);
	        if ( $result ) {
	        	$return = array( 'status' => true, 'msg' => esc_html__('Your message has been successfully sent.', 'wp-cardealer') );
	        } else {
	        	$return = array( 'status' => false, 'msg' => esc_html__('An error occurred when sending an email.', 'wp-cardealer') );
	        }
	    } else {
	    	$return = array( 'status' => false, 'msg' => esc_html__('Form has been not filled correctly.', 'wp-cardealer') );
	    }
	    echo wp_json_encode($return);
	   	exit;
	}

	public static function process_send_schedule_test_drive() {
		$is_form_filled = ! empty( $_POST['email'] ) && ! empty( $_POST['name'] ) && ! empty( $_POST['date'] ) && !empty($_POST['post_id']);
		if ( WP_CarDealer_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_CarDealer_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$is_form_filled = false;
			}
		}

		$author_id = get_post_field ('post_author', sanitize_text_field($_POST['post_id']) );

		$author_email = get_the_author_meta( 'user_email' , $author_id );

		if ( empty($author_id) ) {
			$is_form_filled = false;
		}

		if ( $is_form_filled && !empty($author_email) ) {
			
			$subject = wp_cardealer_get_option('listing_schedule_test_drive_notice_subject');
			$content = wp_cardealer_get_option('listing_schedule_test_drive_notice_content');
			if ( empty($content) ) {
				$content = self::get_email_default_content('listing_schedule_test_drive_notice_content');
			}

			$listing_title = get_the_title($_POST['post_id']);
			$listing_url = get_permalink($_POST['post_id']);

			$subject = str_replace('{{listing_title}}', $listing_title, $subject);
			$subject = str_replace('{{user_name}}', $user_name, $subject);

			
	        $email = sanitize_text_field( $_POST['email'] );
	        $phone = sanitize_text_field( $_POST['phone'] );
	        $user_name = sanitize_text_field( $_POST['name'] );
	        $date = sanitize_text_field( $_POST['date'] );

	        
	        $content = str_replace('{{listing_title}}', $listing_title, $content);
			$content = str_replace('{{listing_url}}', $listing_url, $content);
	        $content = str_replace('{{user_name}}', $user_name, $content);
	        $content = str_replace('{{website_url}}', home_url(), $content);
	        $content = str_replace('{{website_name}}', get_bloginfo( 'name' ), $content);
	        $content = str_replace('{{email}}', $email, $content);
	        $content = str_replace('{{phone}}', $phone, $content);
	        $content = str_replace('{{date}}', $date, $content);
	        
	        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email, $email );
	        
	        $result = false;
			$result = WP_CarDealer_Email::wp_mail( $author_email, $subject, $content, $headers );

			do_action('wp-cardealer-after-process-send-make-an-offer', $author_email, $subject, $content, $headers);
	        if ( $result ) {
	        	$return = array( 'status' => true, 'msg' => esc_html__('Your message has been successfully sent.', 'wp-cardealer') );
	        } else {
	        	$return = array( 'status' => false, 'msg' => esc_html__('An error occurred when sending an email.', 'wp-cardealer') );
	        }
	    } else {
	    	$return = array( 'status' => false, 'msg' => esc_html__('Form has been not filled correctly.', 'wp-cardealer') );
	    }
	    echo wp_json_encode($return);
	   	exit;
	}

	public static function emails_vars() {
		self::$emails_vars = apply_filters( 'wp-cardealer-emails-vars', array(
			'admin_notice_add_new_listing' => array(
				'subject' => array( 'listing_title' ),
				'content' => array( 'listing_title', 'listing_type', 'listing_publish_date', 'listing_expiry_date', 'listing_featured', 'listing_category', 'listing_make', 'listing_model', 'status', 'listing_url', 'author', 'website_url', 'website_name' )
			),
			'admin_notice_updated_listing' => array(
				'subject' => array( 'listing_title' ),
				'content' => array( 'listing_title', 'listing_type', 'listing_publish_date', 'listing_expiry_date', 'listing_featured', 'listing_category', 'listing_make', 'listing_model', 'status', 'listing_url', 'author', 'website_url', 'website_name' )
			),
			'admin_notice_expiring_listing' => array(
				'subject' => array( 'listing_title' ),
				'content' => array( 'listing_title', 'listing_type', 'listing_publish_date', 'listing_expiry_date', 'listing_featured', 'listing_category', 'listing_make', 'listing_model', 'status', 'listing_url', 'website_url', 'website_name', 'listing_admin_edit_url' )
			),
			'user_notice_expiring_listing' => array(
				'subject' => array( 'listing_title' ),
				'content' => array( 'listing_title', 'listing_type', 'listing_publish_date', 'listing_expiry_date', 'listing_featured', 'listing_category', 'listing_make', 'listing_model', 'status', 'listing_url', 'website_url', 'website_name', 'dashboard_url', 'my_listings' )
			),
			'saved_search_notice' => array(
				'subject' => array( 'saved_search_title' ),
				'content' => array( 'saved_search_title', 'listings_found', 'website_url', 'website_name', 'email_frequency_type', 'listings_saved_search_url' )
			),
			'contact_form_notice' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'message', 'email', 'phone', 'website_url', 'website_name' )
			),
			'listing_contact_form_notice' => array(
				'subject' => array( 'user_name', 'listing_title' ),
				'content' => array( 'user_name', 'listing_title', 'listing_url', 'message', 'email', 'phone', 'website_url', 'website_name' )
			),

			'listing_make_an_offer_notice' => array(
				'subject' => array( 'user_name', 'listing_title' ),
				'content' => array( 'user_name', 'listing_title', 'listing_url', 'email', 'phone', 'price', 'website_url', 'website_name' )
			),
			'listing_schedule_test_drive_notice' => array(
				'subject' => array( 'user_name', 'listing_title' ),
				'content' => array( 'user_name', 'listing_title', 'listing_url', 'email', 'phone', 'date', 'website_url', 'website_name' )
			),
			'user_register_auto_approve' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'login_url', 'website_url', 'website_name' )
			),
			'user_register_need_approve' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'approve_url', 'website_url', 'website_name' )
			),
			'user_register_approved' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'login_url', 'dashboard_url', 'website_url', 'website_name' )
			),
			'user_register_denied' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'website_url', 'website_name' )
			),
			'user_reset_password' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'new_password', 'website_url', 'website_name' )
			),
		));
		return self::$emails_vars;
	}

	public static function display_email_vars($key, $type = 'subject') {
		self::emails_vars();
		$output = '';
		if ( !empty(self::$emails_vars[$key][$type]) ) {
			$i = 1;
			foreach (self::$emails_vars[$key][$type] as $value) {
				$output .= '{{'.$value.'}}'.($i < count(self::$emails_vars[$key][$type]) ? ', ' : '');
				$i++;
			}
		}
		return $output;
	}

	public static function render_email_vars($args, $key, $type = 'subject') {
		self::emails_vars();
		$output = wp_cardealer_get_option($key.'_'.$type);
		if ( empty($output) && $type == 'content' ) {
			$output = self::get_email_default_content($key);
		}
		if ( !empty(self::$emails_vars[$key][$type]) ) {
			$vars = self::$emails_vars[$key][$type];
			foreach ($vars as $var) {
				if ( strpos($output, '{{'.$var.'}}') !== false ) {
					if ( isset($args[$var]) ) {
						$value = $args[$var];
					} elseif ( is_callable( array('WP_CarDealer_Email', $var) ) ) {
						$value = call_user_func( array('WP_CarDealer_Email', $var), $args );
					} else {
						$value = apply_filters('wp-cardealer-render-email-var-'.$var, '', $args);
					}
					$output = str_replace('{{'.$var.'}}', $value, $output);
				}
			}
		}
		return apply_filters( 'wp-cardealer-render-emails-vars', $output, $args, $key, $type );
	}

	public static function get_email_default_content($key) {
		$return = '';
		if ( !empty($key) ) {
			$name = 'html-'.str_replace('_', '-', $key);
            if ( file_exists( WP_CARDEALER_PLUGIN_DIR . "includes/email-templates-default/{$name}.php" ) ) {
				ob_start();
	                load_template(WP_CARDEALER_PLUGIN_DIR . "includes/email-templates-default/{$name}.php", false);
	            $return = ob_get_clean();
			}
		}
		return trim($return);
	}

	public static function listing_title($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->post_title) ) {
			$output = $args['listing']->post_title;
		}
		return $output;
	}

	public static function listing_type($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$terms = get_the_terms( $args['listing']->ID, 'listing_type' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				$k = count( $terms );
				foreach ($terms as $term) {
					$k -= 1;
					if ( $k == 0 ) {
						$output .= '<a class="type-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>';
					} else {
						$output .= '<a class="type-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>, ';
					}
		    	}
			}
		}
		return $output;
	}

	public static function listing_category($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$terms = get_the_terms( $args['listing']->ID, 'listing_category' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				$k = count( $terms );
				foreach ($terms as $term) {
					$k -= 1;
					if ( $k == 0 ) {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>';
					} else {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>, ';
					}
		    	}
			}
		}
		return $output;
	}

	public static function listing_make($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$terms = get_the_terms( $args['listing']->ID, 'listing_make' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				$k = count( $terms );
				foreach ($terms as $term) {
					$k -= 1;
					if ( $k == 0 ) {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>';
					} else {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>, ';
					}
		    	}
			}
		}
		return $output;
	}

	public static function listing_model($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$terms = get_the_terms( $args['listing']->ID, 'listing_model' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				$k = count( $terms );
				foreach ($terms as $term) {
					$k -= 1;
					if ( $k == 0 ) {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>';
					} else {
						$output .= '<a class="location-listing" href="'.get_term_link($term).'" >'.esc_html($term->name).'</a>, ';
					}
		    	}
			}
		}
		return $output;
	}

	public static function listing_publish_date($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$output = get_the_date(get_option('date_format'), $args['listing']->ID);
		}
		return $output;
	}

	public static function listing_expiry_date($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$meta_obj = WP_CarDealer_Listing_Meta::get_instance($args['listing']->ID);

			$expiry_date = $meta_obj->get_post_meta( 'expiry_date' );
			if ( $expiry_date ) {
				$expiry_date = strtotime($expiry_date);
				$output = date_i18n(get_option('date_format'), $expiry_date);
			}
		}
		return $output;
	}

	public static function listing_featured($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->ID) ) {
			$meta_obj = WP_CarDealer_Listing_Meta::get_instance($args['listing']->ID);

			$featured = $meta_obj->get_post_meta( 'featured' );
			if ( $featured ) {
				$output = esc_html__('Yes', 'wp-cardealer');
			} else {
				$output = esc_html__('No', 'wp-cardealer');
			}
		}
		return $output;
	}

	public static function status($args) {
		$output = '';
		if ( isset($args['listing']) && !empty($args['listing']->post_status) ) {
			$post_status = get_post_status_object( $args['listing']->post_status );
			if ( !empty($post_status->label) ) {
				$output = $post_status->label;
			} else {
				$output = $post_status->post_status;
			}
		}
		return $output;
	}

	public static function listing_url($args) {
		$output = '';
		if ( !empty($args['listing']) ) {
			$output = get_permalink($args['listing']);
		}
		return $output;
	}

	public static function website_url($args) {
		$output = home_url();
		
		return $output;
	}

	public static function website_name($args) {
		$output = get_bloginfo( 'name' );
		
		return $output;
	}

	public static function dashboard_url($args) {
		$output = '';
		$page_id = wp_cardealer_get_option('user_dashboard_page_id');
		if ( !empty($page_id) ) {
			$output = get_permalink($page_id);
		} else {
			$output = home_url();
		}
		return $output;
	}

	public static function login_url($args) {
		$page_id = wp_cardealer_get_option('login_register_page_id');
		if ( !empty($page_id) ) {
			$output = get_permalink($page_id);
		} else {
			$output = home_url();
		}
		return $output;
	}
	
	public static function my_listings($args) {
		$output = '';
		$my_listings_page_id = wp_cardealer_get_option('my_listings_page_id');
		$output = get_permalink($my_listings_page_id);
		return $output;
	}

	public static function listing_admin_edit_url($args) {
		$output = '';
		if ( !empty($args['listing']) ) {
			$output = admin_url( sprintf( 'post.php?post=%d&amp;action=edit', $args['listing']->ID ) );
		}
		return $output;
	}

	public static function author($args) {
		$output = '';
		if ( !empty($args['listing']) && !empty($args['listing']->post_author) ) {
			$output = get_the_author_meta( 'display_name', $args['listing']->post_author );
		}
		return $output;
	}
	
	public static function approve_url($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->ID) ) {
			$approve_user_page_id = wp_cardealer_get_option('approve_user_page_id');
			$admin_url = get_permalink($approve_user_page_id);

			$user_id = $args['user_obj']->ID;
            $code = get_user_meta($user_id, 'account_approve_key', true);
			$output = add_query_arg(array('action' => 'wp_cardealer_approve_user', 'user_id' => $user_id, 'approve-key' => $code), $admin_url);
		}
		return $output;
	}

	public static function user_name($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->display_name) ) {
			$output = $args['user_obj']->display_name;
		}
		return $output;
	}
	
	public static function user_email($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->user_email) ) {
			$output = $args['user_obj']->user_email;
		}
		return $output;
	}

}

WP_CarDealer_Email::init();