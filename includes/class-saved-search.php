<?php
/**
 * Listing Saved Search
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Saved_Search {
	public static function init() {
		add_action( 'wp_cardealer_email_daily_notices', array( __CLASS__, 'send_saved_search_notice' ) );

		// Ajax endpoints.
		add_action( 'wpcd_ajax_wp_cardealer_ajax_add_saved_search',  array(__CLASS__,'process_add_saved_search') );

		add_action( 'wpcd_ajax_wp_cardealer_ajax_remove_saved_search',  array(__CLASS__,'process_remove_saved_search') );


		// compatible handlers.
		add_action( 'wp_ajax_wp_cardealer_ajax_add_saved_search',  array(__CLASS__,'process_add_saved_search') );
		add_action( 'wp_ajax_nopriv_wp_cardealer_ajax_add_saved_search',  array(__CLASS__,'process_add_saved_search') );

		add_action( 'wp_ajax_wp_cardealer_ajax_remove_saved_search',  array(__CLASS__,'process_remove_saved_search') );
		add_action( 'wp_ajax_nopriv_wp_cardealer_ajax_remove_saved_search',  array(__CLASS__,'process_remove_saved_search') );
	}

	public static function get_email_frequency() {
		$email_frequency = apply_filters( 'wp-cardealer-saved-search-email-frequency', array(
			'daily' => array(
				'label' => __('Daily', 'wp-cardealer'),
				'days' => '1',
			),
			'weekly' => array(
				'label' => __('Weekly', 'wp-cardealer'),
				'days' => '7',
			),
			'fortnightly' => array(
				'label' => __('Fortnightly', 'wp-cardealer'),
				'days' => '15',
			),
			'monthly' => array(
				'label' => __('Monthly', 'wp-cardealer'),
				'days' => '30',
			),
			'biannually' => array(
				'label' => __('Biannually', 'wp-cardealer'),
				'days' => '182',
			),
			'annually' => array(
				'label' => __('Annually', 'wp-cardealer'),
				'days' => '365',
			),
		));
		return $email_frequency;
	}

	public static function send_saved_search_notice() {
		
		$email_frequency_default = self::get_email_frequency();
		if ( $email_frequency_default ) {
			foreach ($email_frequency_default as $key => $value) {
				if ( !empty($value['days']) ) {
					$meta_query = array(
						'relation' => 'OR',
						array(
							'key' => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'send_email_time',
							'compare' => 'NOT EXISTS',
						)
					);
					$current_time = apply_filters( 'wp-cardealer-saved-search-current-'.$key.'-time', date( 'Y-m-d', strtotime( '-'.intval($value['days']).' days', current_time( 'timestamp' ) ) ) );
					$meta_query[] = array(
						'relation' => 'AND',
						array(
							'key' => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'send_email_time',
							'value' => $current_time,
							'compare' => '<=',
						),
						array(
							'key' => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'email_frequency',
							'value' => $key,
							'compare' => '=',
						),
					);

					$query_args = apply_filters( 'wp-cardealer-saved-search-query-args', array(
						'post_type' => 'saved_search',
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'fields' => 'ids',
						'meta_query' => $meta_query
					));

					$saved_searches = new WP_Query($query_args);
					if ( !empty($saved_searches->posts) ) {
						foreach ($saved_searches->posts as $post_id) {
							$saved_search_query = get_post_meta($post_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'saved_search_query', true);
							
							$params = $saved_search_query;
							if ( !empty($saved_search_query) && !is_array($saved_search_query) ) {
								$params = json_decode($saved_search_query, true);
							}

							$query_args = array(
								'post_type' => 'listing',
							    'post_status' => 'publish',
							    'posts_per_page' => 1,
							    'fields' => 'ids'
							);
							$listings = WP_CarDealer_Query::get_posts($query_args, $params);
							$count_listings = $listings->found_posts;
							$saved_search_title = get_the_title($post_id);
							// send email action
							$listing = get_post($post_id);
							$email_from = get_option( 'admin_email', false );
							
							$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email_from, $email_from );
							
							$author_id = get_post_field( 'post_author', $post_id );
							$email_to = get_the_author_meta('user_email', $author_id);

							$subject = WP_CarDealer_Email::render_email_vars(array('saved_search_title' => $saved_search_title), 'saved_search_notice', 'subject');

							$email_frequency = get_post_meta($post_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'email_frequency', true);
							if ( !empty($email_frequency_default[$email_frequency]['label']) ) {
								$email_frequency = $email_frequency_default[$email_frequency]['label'];
							}
							$listings_saved_search_url = WP_CarDealer_Mixes::get_listings_page_url();
							if ( !empty($params) ) {
								foreach ($params as $key => $value) {
									if ( is_array($value) ) {
										$listings_saved_search_url = remove_query_arg( $key.'[]', $listings_saved_search_url );
										foreach ($value as $val) {
											$listings_saved_search_url = add_query_arg( $key.'[]', $val, $listings_saved_search_url );
										}
									} else {
										$listings_saved_search_url = add_query_arg( $key, $value, remove_query_arg( $key, $listings_saved_search_url ) );
									}
								}
							}
							$content_args = apply_filters( 'wp-cardealer-saved-search-email-content-args', array(
								'saved_search_title' => $saved_search_title,
								'listings_found' => $count_listings,
								'email_frequency_type' => $email_frequency,
								'listings_saved_search_url' => $listings_saved_search_url
							));
							$content = WP_CarDealer_Email::render_email_vars($content_args, 'saved_search_notice', 'content');
										
							WP_CarDealer_Email::wp_mail( $email_to, $subject, $content, $headers );
							$current_time = date( 'Y-m-d', current_time( 'timestamp' ) );
							delete_post_meta($post_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'send_email_time');
							add_post_meta($post_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX.'send_email_time', $current_time);
						}
					}
				}
			}
		}
		
	}

	public static function process_add_saved_search() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-add-saved-search-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = get_current_user_id();

		$errors = self::validate_add_saved_search();
		if ( !empty($errors) ) {
			$return = array( 'status' => false, 'msg' => implode(', ', $errors) );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$name = !empty($_POST['name']) ? $_POST['name'] : '';
		
		$post_args = array(
            'post_title' => $name,
            'post_type' => 'saved_search',
            'post_content' => '',
            'post_status' => 'publish',
            'user_id' => $user_id
        );
		$post_args = apply_filters('wp-cardealer-add-saved-search-data', $post_args);
		
		do_action('wp-cardealer-before-add-saved-search');

        // Insert the post into the database
        $saved_search_id = wp_insert_post($post_args);
        if ( $saved_search_id ) {
	        $email_frequency = !empty($_POST['email_frequency']) ? $_POST['email_frequency'] : '';
	        update_post_meta($saved_search_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'email_frequency', $email_frequency);

	        $saved_search_query = array();
			if ( ! empty( $_POST ) && is_array( $_POST ) ) {
				foreach ( $_POST as $key => $value ) {
					if ( strrpos( $key, 'filter-', -strlen( $key ) ) !== false ) {
						$saved_search_query[$key] = $value;
					}
				}
			}
	        if ( !empty($saved_search_query) ) {
	        	update_post_meta($saved_search_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'saved_search_query', $saved_search_query);	
	        }
	        
	        do_action('wp-cardealer-after-add-saved-search', $saved_search_id);

	        $return = array( 'status' => true, 'msg' => esc_html__('Add listing save search successfully.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Add listing save search error.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function validate_add_saved_search() {
		$name = !empty($_POST['name']) ? $_POST['name'] : '';
		if ( empty($name) ) {
			$return[] = esc_html__('Name is required.', 'wp-cardealer');
		}
		$email_frequency = !empty($_POST['email_frequency']) ? $_POST['email_frequency'] : '';
		if ( empty($email_frequency) ) {
			$return[] = esc_html__('Email frequency is required.', 'wp-cardealer');
		}
		return $return;
	}

	public static function process_remove_saved_search() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-remove-saved-search-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to remove listing saved search.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$saved_search_id = !empty($_POST['saved_search_id']) ? $_POST['saved_search_id'] : '';

		if ( empty($saved_search_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$is_allowed = WP_CarDealer_Mixes::is_allowed_to_remove( get_current_user_id(), $saved_search_id );

		if ( ! $is_allowed ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not remove this listing saved search.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		if ( wp_delete_post( $saved_search_id ) ) {
	        $return = array( 'status' => true, 'msg' => esc_html__('Remove listing saved search successfully.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Remove listing saved search error.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}
}

WP_CarDealer_Saved_Search::init();