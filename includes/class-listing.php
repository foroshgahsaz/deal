<?php
/**
 * Listing Listing
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Listing {
	
	public static function init() {
		// loop
		add_action( 'wp_cardealer_before_listing_archive', array( __CLASS__, 'display_listings_results_filters' ), 5 );
		add_action( 'wp_cardealer_before_listing_archive', array( __CLASS__, 'display_listings_count_results' ), 10 );

		add_action( 'wp_cardealer_before_listing_archive', array( __CLASS__, 'display_listings_orderby_start' ), 15 );
		add_action( 'wp_cardealer_before_listing_archive', array( __CLASS__, 'display_listings_orderby' ), 25 );
		add_action( 'wp_cardealer_before_listing_archive', array( __CLASS__, 'display_listings_orderby_end' ), 100 );
		
		// Ajax endpoints.
		// download attachtment
		add_action('wpcd_ajax_wp_cardealer_ajax_download_attachment', array( __CLASS__, 'process_download_attachment' ) );

		add_action( 'wpcd_ajax_wp_cardealer_get_listing_chart', array( __CLASS__, 'get_chart_data' ) );

		// compatible handlers.
		// download attachtment
		add_action('wp_ajax_wp_cardealer_ajax_download_attachment', array( __CLASS__, 'process_download_attachment' ) );
		add_action('wp_ajax_nopriv_wp_cardealer_ajax_download_attachment', array( __CLASS__, 'process_download_attachment' ) );

		add_action( 'wp_ajax_wp_cardealer_get_listing_chart', array( __CLASS__, 'get_chart_data' ) );
		add_action( 'wp_ajax_nopriv_wp_cardealer_get_listing_chart', array( __CLASS__, 'get_chart_data' ) );
	}

	public static function get_post_meta($post_id, $key, $single = true) {
		return get_post_meta($post_id, WP_CARDEALER_LISTING_PREFIX.$key, $single);
	}

	/**
	 * Formatted listing price. Themes call this helper; conversion to Toman
	 * happens inside the price formatter so theme files do not need changes.
	 *
	 * @param int $post_id
	 * @return string|false
	 */
	public static function get_price_html( $post_id ) {
		$obj = WP_CarDealer_Listing_Meta::get_instance( $post_id );
		return $obj->get_price_html();
	}
	
	// add product viewed
	public static function track_listing_view() {
	    if ( ! is_singular( 'listing' ) ) {
	        return;
	    }

	    global $post;

	    $today = date('Y-m-d', time());
	    $views_by_date = get_post_meta($post->ID, '_views_by_date', true);

	    if( $views_by_date != '' || is_array($views_by_date) ) {
	        if (!isset($views_by_date[$today])) {
	            if ( count($views_by_date) > 60 ) {
	                array_shift($views_by_date);
	            }
	            $views_by_date[$today] = 1;
	        } else {
	            $views_by_date[$today] = intval($views_by_date[$today]) + 1;
	        }
	    } else {
	        $views_by_date = array();
	        $views_by_date[$today] = 1;
	    }
	    $views = get_post_meta($post->ID, WP_CARDEALER_LISTING_PREFIX.'views', true);
	    if ( empty($views) ) {
	    	$views = 1;
	    } else {
	    	$views++;
	    }

	    update_post_meta($post->ID, '_views_by_date', $views_by_date);
	    update_post_meta($post->ID, '_recently_viewed', $today);
	    update_post_meta($post->ID, WP_CARDEALER_LISTING_PREFIX.'views', $views);
	}

	public static function send_admin_expiring_notice() {
		global $wpdb;

		if ( !wp_cardealer_get_option('admin_notice_expiring_listing') ) {
			return;
		}
		$days_notice = wp_cardealer_get_option('admin_notice_expiring_listing_days');

		$listing_ids = self::get_expiring_listings($days_notice);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				// send email here.
				$listing = get_post($listing_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email_from, $email_from );
				$email_to = get_option( 'admin_email', false );
				$subject = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_expiring_listing', 'subject');
				$content = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_expiring_listing', 'content');
				
				WP_CarDealer_Email::wp_mail( $email_to, $subject, $content, $headers );
			}
		}
	}

	public static function send_author_expiring_notice() {
		global $wpdb;

		if ( !wp_cardealer_get_option('user_notice_expiring_listing') ) {
			return;
		}
		$days_notice = wp_cardealer_get_option('user_notice_expiring_listing_days');

		$listing_ids = self::get_expiring_listings($days_notice);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				// send email here.
				$listing = get_post($listing_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email_from, $email_from );
				$email_to = get_the_author_meta( 'user_email', $listing->post_author );
				$subject = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'user_notice_expiring_listing', 'subject');
				$content = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'user_notice_expiring_listing', 'content');
				
				WP_CarDealer_Email::wp_mail( $email_to, $subject, $content, $headers );
				
			}
		}
	}

	public static function get_expiring_listings($days_notice) {
		global $wpdb;
		
		$prefix = WP_CARDEALER_LISTING_PREFIX;

		$notice_before_ts = current_time( 'timestamp' ) + ( DAY_IN_SECONDS * $days_notice );
		$listing_ids          = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = %s
			AND postmeta.meta_value = %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'listing'
			",
			$prefix.'expiry_date',
			date( 'Y-m-d', $notice_before_ts )
		) );

		return $listing_ids;
	}

	public static function check_for_expired_listings() {
		global $wpdb;

		$prefix = WP_CARDEALER_LISTING_PREFIX;
		
		// Change status to expired.
		$listing_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
				LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
				WHERE postmeta.meta_key = %s
				AND postmeta.meta_value > 0
				AND postmeta.meta_value < %s
				AND posts.post_status = 'publish'
				AND posts.post_type = 'listing'",
				$prefix.'expiry_date',
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				$listing_data                = array();
				$listing_data['ID']          = $listing_id;
				$listing_data['post_status'] = 'expired';
				wp_update_post( $listing_data );
			}
		}

		// Delete old expired listings.
		if ( apply_filters( 'wp_cardealer_delete_expired_listings', false ) ) {
			$listing_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID FROM {$wpdb->posts} as posts
					WHERE posts.post_type = 'listing'
					AND posts.post_modified < %s
					AND posts.post_status = 'expired'",
					date( 'Y-m-d', strtotime( '-' . apply_filters( 'wp_cardealer_delete_expired_listings_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
				)
			);

			if ( $listing_ids ) {
				foreach ( $listing_ids as $listing_id ) {
					wp_trash_post( $listing_id );
				}
			}
		}
	}

	/**
	 * Deletes old previewed listings after 30 days to keep the DB clean.
	 */
	public static function delete_old_previews() {
		global $wpdb;

		// Delete old expired listings.
		$listing_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'listing'
				AND posts.post_modified < %s
				AND posts.post_status = 'preview'",
				date( 'Y-m-d', strtotime( '-' . apply_filters( 'wp_cardealer_delete_old_previews_listings_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
			)
		);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				wp_delete_post( $listing_id, true );
			}
		}
	}

	public static function listing_statuses() {
		return apply_filters(
			'wp_cardealer_listing_statuses',
			array(
				'draft'           => _x( 'Draft', 'post status', 'wp-cardealer' ),
				'expired'         => _x( 'Expired', 'post status', 'wp-cardealer' ),
				'preview'         => _x( 'Preview', 'post status', 'wp-cardealer' ),
				'pending'         => _x( 'Pending approval', 'post status', 'wp-cardealer' ),
				'pending_approve' => _x( 'Pending approval', 'post status', 'wp-cardealer' ),
				'pending_payment' => _x( 'Pending payment', 'post status', 'wp-cardealer' ),
				'publish'         => _x( 'Active', 'post status', 'wp-cardealer' ),
			)
		);
	}

	public static function is_listing_status_changing( $from_status, $to_status ) {
		return isset( $_POST['post_status'] ) && isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] !== $_POST['post_status'] && ( null === $from_status || $from_status === $_POST['original_post_status'] ) && $to_status === $_POST['post_status'];
	}

	public static function calculate_listing_expiry( $listing_id ) {
		$duration = absint( wp_cardealer_get_option( 'submission_duration' ) );
		$duration = apply_filters( 'wp-cardealer-calculate-listing-expiry', $duration, $listing_id);

		if ( $duration ) {
			return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
		}

		return '';
	}
	
	public static function is_featured( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$featured = self::get_post_meta( $post_id, 'featured', true );
		$return = $featured ? true : false;
		return apply_filters( 'wp-cardealer-listing-is-featured', $return, $post_id );
	}
	
	public static function process_download_attachment() {
	    $attachment_id = isset($_GET['file_id']) ? $_GET['file_id'] : '';
	    $attachment_id = absint($attachment_id);

	    $error_page_url = home_url('/404-error');

	    if ( $attachment_id > 0 ) {

	        $file_post = get_post($attachment_id);
	        $file_path = get_attached_file($attachment_id);

	        if ( !$file_post || !$file_path || !file_exists($file_path) ) {
	            wp_redirect($error_page_url);
	        } else {
	        	
	            header('Content-Description: File Transfer');
	            header("Expires: 0");
				header("Cache-Control: no-cache, no-store, must-revalidate"); 
				header('Cache-Control: pre-check=0, post-check=0, max-age=0', false); 
				header("Pragma: no-cache");	
				header("Content-type: " . $file_post->post_mime_type);
				header('Content-Disposition:attachment; filename="'. basename($file_path) .'"');
				header("Content-Type: application/force-download");
				header('Content-Length: ' . @filesize($file_path));

	            @readfile($file_path);
	            exit;
	        }
	    } else {
	        wp_redirect($error_page_url);
	    }

	    die;
	}

	public static function get_chart_data() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-listing-chart-nonce' ) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( empty($_REQUEST['listing_id']) ) {
			$return = array( 'status' => 'error', 'html' => esc_html__('Listing not found', 'wp-cardealer') );
			echo wp_json_encode($return);
		   	exit;
		}
		$listing_id = $_REQUEST['listing_id'];
		$return = array(
			'stats_labels' => self::get_traffic_labels($listing_id),
			'stats_values' => self::get_traffic_data($listing_id),
			'stats_view' => esc_html__('Views', 'wp-cardealer'),
			'chart_type' => apply_filters('wp-cardealer-listing-stats-type', 'line'),
			'bg_color' => apply_filters('wp-cardealer-listing-stats-bg-color', 'rgb(255, 99, 132)'),
	        'border_color' => apply_filters('wp-cardealer-listing-stats-border-color', 'rgb(255, 99, 132)'),
		);
		echo json_encode($return);
		die();
	}

	public static function get_traffic_labels( $listing_id ) {
		$nb_days = !empty($_REQUEST['nb_days']) ? $_REQUEST['nb_days'] : 15;
	    $number_days = apply_filters('wp-cardealer-get-traffic-data-nb-days', $nb_days);
	    if( empty($number_days) ) {
	        $number_days = 15;
	    }
	    $number_days--;

	    $array_labels = array();
		for ($i=$number_days; $i >= 0; $i--) { 
			$date = strtotime(date("Y-m-d", strtotime("-".$i." day")));
			$array_labels[] = date_i18n(get_option('date_format'), $date);
		}

	    return $array_labels;
	}

	public static function get_traffic_data($listing_id) {
		$nb_days = !empty($_REQUEST['nb_days']) ? $_REQUEST['nb_days'] : 15;
	    $number_days = apply_filters('wp-cardealer-get-traffic-data-nb-days', $nb_days);
	    if( empty($number_days) ) {
	        $number_days = 15;
	    }
	    $number_days--;

	    $views_by_date = get_post_meta( $listing_id, '_views_by_date', true );
	    if ( !is_array( $views_by_date ) ) {
	        $views_by_date = array();
	    }

	    $array_values = array();
		for ($i=$number_days; $i >= 0; $i--) { 
			$date = date("Y-m-d", strtotime("-".$i." day"));
			if ( isset($views_by_date[$date]) ) {
				$array_values[] = $views_by_date[$date];
			} else {
				$array_values[] = 0;
			}
		}

	    return $array_values;
	}

	public static function display_listings_results_filters() {
		$filters = WP_CarDealer_Abstract_Filter::get_filters();

		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/results-filters', array('filters' => $filters));
	}

	public static function display_listings_count_results($wp_query) {
		$total = $wp_query->found_posts;
		$per_page = $wp_query->query_vars['posts_per_page'];
		$current = max( 1, $wp_query->get( 'paged', 1 ) );
		$args = array(
			'total' => $total,
			'per_page' => $per_page,
			'current' => $current,
		);

		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/results-count', $args);
	}

	public static function display_listings_save_search() {
		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/listings-save-search-form');
	}

	public static function display_listings_orderby() {
		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/orderby');
	}

	public static function display_listings_orderby_start() {
		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/orderby-start');
	}

	public static function display_listings_orderby_end() {
		echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/orderby-end');
	}
}
WP_CarDealer_Listing::init();