<?php
/**
 * Cache
 *
 * @package    wp-job-board-pro
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Cache_Helper {

	/**
	 * Initializes cache hooks.
	 */
	public static function init() {
		add_action( 'save_post', [ __CLASS__, 'flush_get_listings_cache' ] );
		add_action( 'delete_post', [ __CLASS__, 'flush_get_listings_cache' ] );
		add_action( 'trash_post', [ __CLASS__, 'flush_get_listings_cache' ] );
		add_action( 'wp_cardealer_my_listing_do_action', [ __CLASS__, 'my_listing_do_action' ] );
		add_action( 'set_object_terms', [ __CLASS__, 'set_term' ], 10, 4 );
		add_action( 'edited_term', [ __CLASS__, 'edited_term' ], 10, 3 );
		add_action( 'create_term', [ __CLASS__, 'edited_term' ], 10, 3 );
		add_action( 'delete_term', [ __CLASS__, 'edited_term' ], 10, 3 );
		add_action( 'transition_post_status', [ __CLASS__, 'maybe_clear_count_transients' ], 10, 3 );
	}

	/**
	 * Flushes the cache.
	 *
	 * @param int|WP_Post $post_id
	 */
	public static function flush_get_listings_cache( $post_id ) {
		if ( 'listing' === get_post_type( $post_id ) ) {
			self::get_transient_version( 'get_listings', true );
		}
	}

	public static function my_listing_do_action( $action ) {
		if ( 'mark_filled' === $action || 'mark_not_filled' === $action ) {
			self::get_transient_version( 'get_listings', true );
		}
	}

	public static function set_term( $object_id = '', $terms = '', $tt_ids = '', $taxonomy = '' ) {
		self::get_transient_version( 'wpcd_get_' . sanitize_text_field( $taxonomy ), true );
	}

	public static function edited_term( $term_id = '', $tt_id = '', $taxonomy = '' ) {
		self::get_transient_version( 'wpcd_get_' . sanitize_text_field( $taxonomy ), true );
	}

	/**
	 * Gets transient version.
	 *
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			self::delete_version_transients( $transient_value );
			set_transient( $transient_name, $transient_value = time() );
		}
		return $transient_value;
	}

	private static function delete_version_transients( $version ) {
		global $wpdb;

		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Only used when object caching is disabled.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s;", '\_transient\_%' . $version ) );
		}
	}

	/**
	 * Maybe remove pending count transients
	 *
	 */
	public static function maybe_clear_count_transients( $new_status, $old_status, $post ) {
		global $wpdb;

		$post_types = apply_filters( 'wpcd_count_cache_supported_post_types', [ 'listing' ], $new_status, $old_status, $post );

		// Only proceed when statuses do not match, and post type is supported post type.
		if ( $new_status === $old_status || ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		$valid_statuses = apply_filters( 'wpcd_count_cache_supported_statuses', [ 'pending' ], $new_status, $old_status, $post );

		$rlike = [];
		// New status transient option name.
		if ( in_array( $new_status, $valid_statuses, true ) ) {
			$rlike[] = "^_transient_wpcd_{$new_status}_{$post->post_type}_count_user_";
		}
		// Old status transient option name.
		if ( in_array( $old_status, $valid_statuses, true ) ) {
			$rlike[] = "^_transient_wpcd_{$old_status}_{$post->post_type}_count_user_";
		}

		if ( empty( $rlike ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetches dynamic list of cached counts.
		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM $wpdb->options WHERE option_name RLIKE %s",
				implode( '|', $rlike )
			)
		);

		// For each transient...
		foreach ( $transients as $transient ) {
			// Strip away the WordPress prefix in order to arrive at the transient key.
			$key = str_replace( '_transient_', '', $transient );
			// Now that we have the key, use WordPress core to the delete the transient.
			delete_transient( $key );
		}

		// Sometimes transients are not in the DB, so we have to do this too:.
		wp_cache_flush();
	}

	/**
	 * Get Listings Count from Cache
	 *
	 */
	public static function get_listings_count( $post_type = 'listing', $status = 'pending', $force = false ) {

		// Get user based cache transient.
		$user_id   = get_current_user_id();
		$transient = "wpcd_{$status}_{$post_type}_count_user_{$user_id}";

		// Set listings_count value from cache if exists, otherwise set to 0 as default.
		$cached_count = get_transient( $transient );
		$status_count = $cached_count ? $cached_count : 0;

		// $cached_count will be false if transient does not exist.
		if ( false === $cached_count || $force ) {
			$count_posts = wp_count_posts( $post_type, 'readable' );
			// Default to 0 $status if object does not have a value.
			$status_count = isset( $count_posts->$status ) ? $count_posts->$status : 0;
			set_transient( $transient, $status_count, DAY_IN_SECONDS * 7 );
		}

		return $status_count;
	}
}

WP_CarDealer_Cache_Helper::init();
