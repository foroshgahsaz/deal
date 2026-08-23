<?php
/**
 * Navasan USD to Toman conversion.
 *
 * Listing prices are stored in USD. Frontend output is converted to Toman
 * via the Navasan latest-price API so any theme using the plugin formatters
 * shows Tomans without theme changes.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Navasan {

	const TRANSIENT_RATE = 'wp_cardealer_navasan_usd_toman_rate';
	const OPTION_LAST_RESULT = 'wp_cardealer_navasan_last_result';
	const DEFAULT_ITEM = 'usd_sell';
	const DEFAULT_CACHE_MINUTES = 60;
	const API_URL = 'http://api.navasan.tech/latest/';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'schedule_refresh' ) );
		add_action( 'wp_cardealer_navasan_refresh_rate', array( __CLASS__, 'refresh_rate' ) );

		add_action( 'wp_ajax_wp_cardealer_navasan_test_token', array( __CLASS__, 'ajax_test_token' ) );
		add_action( 'wpcd_ajax_wp_cardealer_navasan_test_token', array( __CLASS__, 'ajax_test_token' ) );

		add_action( 'update_option_wp_cardealer_settings', array( __CLASS__, 'maybe_flush_rate_cache' ), 10, 2 );
	}

	/**
	 * Conversion is active when enabled in settings and an API key exists.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( wp_cardealer_get_option( 'enable_usd_to_toman', 'yes' ) !== 'yes' ) {
			return false;
		}

		$api_key = self::get_api_key();

		return $api_key !== '';
	}

	/**
	 * @return string
	 */
	public static function get_api_key() {
		$key = wp_cardealer_get_option( 'navasan_api_key', '' );

		if ( ! is_string( $key ) ) {
			return '';
		}

		return trim( $key );
	}

	/**
	 * Navasan item used as the USD market rate. Default is Tehran sell.
	 *
	 * @return string
	 */
	public static function get_usd_item() {
		$item = wp_cardealer_get_option( 'navasan_usd_item', self::DEFAULT_ITEM );

		if ( empty( $item ) || ! is_string( $item ) ) {
			return self::DEFAULT_ITEM;
		}

		return $item;
	}

	/**
	 * @return int
	 */
	public static function get_cache_seconds() {
		$minutes = absint( wp_cardealer_get_option( 'navasan_cache_minutes', self::DEFAULT_CACHE_MINUTES ) );

		if ( $minutes < 5 ) {
			$minutes = 5;
		}

		if ( $minutes > 1440 ) {
			$minutes = 1440;
		}

		return $minutes * MINUTE_IN_SECONDS;
	}

	/**
	 * @return string
	 */
	public static function get_toman_symbol() {
		return apply_filters( 'wp_cardealer_navasan_toman_symbol', __( 'تومان', 'wp-cardealer' ) );
	}

	/**
	 * Cached USD→Toman rate. Falls back to the last successful fetch.
	 *
	 * @return float
	 */
	public static function get_usd_toman_rate() {
		if ( ! self::is_enabled() ) {
			return 0;
		}

		$cached = get_transient( self::TRANSIENT_RATE );
		if ( is_numeric( $cached ) && (float) $cached > 0 ) {
			return (float) $cached;
		}

		$fetched = self::refresh_rate();
		if ( $fetched > 0 ) {
			return $fetched;
		}

		$last = get_option( self::OPTION_LAST_RESULT, array() );
		if ( ! empty( $last['rate'] ) && is_numeric( $last['rate'] ) && (float) $last['rate'] > 0 ) {
			return (float) $last['rate'];
		}

		return 0;
	}

	/**
	 * Convert a USD amount to Toman using the live (cached) rate.
	 *
	 * @param mixed $usd
	 * @return float|mixed
	 */
	public static function convert_usd_to_toman( $usd ) {
		if ( ! is_numeric( $usd ) ) {
			return $usd;
		}

		$rate = self::get_usd_toman_rate();

		return self::convert_amount( $usd, $rate );
	}

	/**
	 * Pure conversion helper (testable without WordPress I/O).
	 *
	 * @param mixed $usd
	 * @param mixed $rate
	 * @return float
	 */
	public static function convert_amount( $usd, $rate ) {
		if ( ! is_numeric( $usd ) || ! is_numeric( $rate ) || (float) $rate <= 0 ) {
			return is_numeric( $usd ) ? (float) $usd : 0;
		}

		$toman = round( (float) $usd * (float) $rate );

		return (float) apply_filters( 'wp_cardealer_navasan_converted_amount', $toman, $usd, $rate );
	}

	/**
	 * Stored listing price as entered in USD.
	 *
	 * @param int $post_id
	 * @return mixed
	 */
	public static function get_stored_usd_price( $post_id ) {
		return get_post_meta( $post_id, WP_CARDEALER_LISTING_PREFIX . 'price', true );
	}

	/**
	 * Fetch the latest rate from Navasan and cache it.
	 *
	 * @param string $api_key Optional override (settings form test).
	 * @param string $item    Optional override.
	 * @return float
	 */
	public static function refresh_rate( $api_key = '', $item = '' ) {
		$result = self::fetch_latest_rate( $api_key, $item );

		if ( empty( $result['success'] ) || empty( $result['rate'] ) ) {
			return 0;
		}

		self::store_rate_result( $result );

		return (float) $result['rate'];
	}

	/**
	 * Persist a successful Navasan payload.
	 *
	 * @param array $result
	 * @return void
	 */
	public static function store_rate_result( $result ) {
		$rate = (float) $result['rate'];
		set_transient( self::TRANSIENT_RATE, $rate, self::get_cache_seconds() );
		update_option(
			self::OPTION_LAST_RESULT,
			array(
				'rate'       => $rate,
				'item'       => isset( $result['item'] ) ? $result['item'] : self::get_usd_item(),
				'date'       => isset( $result['date'] ) ? $result['date'] : '',
				'timestamp'  => isset( $result['timestamp'] ) ? (int) $result['timestamp'] : time(),
				'fetched_at' => current_time( 'mysql' ),
			),
			false
		);
	}

	/**
	 * Call Navasan latest-price API.
	 *
	 * @param string $api_key
	 * @param string $item
	 * @return array
	 */
	public static function fetch_latest_rate( $api_key = '', $item = '' ) {
		if ( $api_key === '' ) {
			$api_key = self::get_api_key();
		}

		if ( $item === '' ) {
			$item = self::get_usd_item();
		}

		if ( $api_key === '' ) {
			return array(
				'success' => false,
				'message' => __( 'Navasan API token is empty.', 'wp-cardealer' ),
			);
		}

		$url = add_query_arg(
			array(
				'api_key' => $api_key,
				'item'    => $item,
			),
			self::API_URL
		);

		$url = apply_filters( 'wp_cardealer_navasan_latest_url', $url, $api_key, $item );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'sslverify'  => false,
				'user-agent' => 'WP-CarDealer-Navasan/' . ( defined( 'WP_CARDEALER_PLUGIN_VERSION' ) ? WP_CARDEALER_PLUGIN_VERSION : '1.0' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code === 401 ) {
			return array(
				'success' => false,
				'message' => __( 'Navasan API token is invalid.', 'wp-cardealer' ),
			);
		}

		if ( $code !== 200 ) {
			$message = __( 'Unable to fetch the USD rate from Navasan.', 'wp-cardealer' );
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				$message = $data['message'];
			}

			return array(
				'success' => false,
				'message' => $message,
				'code'    => $code,
			);
		}

		$parsed = self::extract_rate_from_payload( $data, $item );
		if ( $parsed['rate'] <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Navasan did not return a valid USD rate.', 'wp-cardealer' ),
			);
		}

		$parsed['success'] = true;

		return $parsed;
	}

	/**
	 * Parse Navasan JSON into a rate. Handles both `{item:{value:...}}` and `{value:...}`.
	 *
	 * @param mixed  $data
	 * @param string $item
	 * @return array
	 */
	public static function extract_rate_from_payload( $data, $item = self::DEFAULT_ITEM ) {
		$result = array(
			'rate'      => 0,
			'item'      => $item,
			'date'      => '',
			'timestamp' => 0,
			'change'    => '',
		);

		if ( ! is_array( $data ) ) {
			return $result;
		}

		$row = null;
		if ( isset( $data[ $item ] ) && is_array( $data[ $item ] ) ) {
			$row = $data[ $item ];
		} elseif ( isset( $data['value'] ) ) {
			$row = $data;
		} else {
			foreach ( $data as $maybe_row ) {
				if ( is_array( $maybe_row ) && isset( $maybe_row['value'] ) ) {
					$row = $maybe_row;
					break;
				}
			}
		}

		if ( ! is_array( $row ) || ! isset( $row['value'] ) || ! is_numeric( $row['value'] ) ) {
			return $result;
		}

		$result['rate']      = (float) $row['value'];
		$result['date']      = isset( $row['date'] ) ? (string) $row['date'] : '';
		$result['timestamp'] = isset( $row['timestamp'] ) ? (int) $row['timestamp'] : 0;
		$result['change']    = isset( $row['change'] ) ? $row['change'] : '';

		return $result;
	}

	/**
	 * @return array
	 */
	public static function get_last_result() {
		$last = get_option( self::OPTION_LAST_RESULT, array() );

		return is_array( $last ) ? $last : array();
	}

	public static function schedule_refresh() {
		if ( ! self::is_enabled() ) {
			$timestamp = wp_next_scheduled( 'wp_cardealer_navasan_refresh_rate' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'wp_cardealer_navasan_refresh_rate' );
			}
			return;
		}

		if ( ! wp_next_scheduled( 'wp_cardealer_navasan_refresh_rate' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'wp_cardealer_navasan_refresh_rate' );
		}
	}

	public static function maybe_flush_rate_cache( $old_value, $new_value ) {
		if ( ! is_array( $old_value ) ) {
			$old_value = array();
		}
		if ( ! is_array( $new_value ) ) {
			$new_value = array();
		}
		$keys = array( 'navasan_api_key', 'navasan_usd_item', 'enable_usd_to_toman' );
		foreach ( $keys as $key ) {
			$old = isset( $old_value[ $key ] ) ? $old_value[ $key ] : '';
			$new = isset( $new_value[ $key ] ) ? $new_value[ $key ] : '';
			if ( $old !== $new ) {
				delete_transient( self::TRANSIENT_RATE );
				break;
			}
		}
	}

	public static function ajax_test_token() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'wp-cardealer' ) ) );
		}

		check_ajax_referer( 'wp_cardealer_navasan_test', 'nonce' );

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$item    = isset( $_POST['item'] ) ? sanitize_text_field( wp_unslash( $_POST['item'] ) ) : self::DEFAULT_ITEM;

		$result = self::fetch_latest_rate( $api_key, $item );

		if ( empty( $result['success'] ) || empty( $result['rate'] ) ) {
			wp_send_json_error(
				array(
					'message' => isset( $result['message'] ) ? $result['message'] : __( 'Request failed.', 'wp-cardealer' ),
				)
			);
		}

		self::store_rate_result( $result );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: rate, 2: currency item */
					__( 'Connection successful. Current rate: %1$s Toman per 1 USD (%2$s).', 'wp-cardealer' ),
					number_format_i18n( $result['rate'] ),
					$result['item']
				),
				'rate'    => $result['rate'],
				'date'    => isset( $result['date'] ) ? $result['date'] : '',
			)
		);
	}

	/**
	 * USD market sources supported by Navasan latest API.
	 *
	 * @return array
	 */
	public static function get_usd_item_options() {
		return array(
			'usd_sell'        => __( 'Tehran USD sell (usd_sell)', 'wp-cardealer' ),
			'usd_buy'         => __( 'Tehran USD buy (usd_buy)', 'wp-cardealer' ),
			'usd'             => __( 'US dollar (usd)', 'wp-cardealer' ),
			'mex_usd_sell'    => __( 'National Exchange USD sell (mex_usd_sell)', 'wp-cardealer' ),
			'mob_usd'         => __( 'NIMA/exchange USD (mob_usd)', 'wp-cardealer' ),
			'harat_naghdi_sell' => __( 'Herat USD sell (harat_naghdi_sell)', 'wp-cardealer' ),
		);
	}
}

WP_CarDealer_Navasan::init();
