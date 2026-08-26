<?php
/**
 * USD to Toman conversion via BrsApi gold/currency web service.
 *
 * Listing prices are stored in USD. Frontend output is converted to Toman
 * so any theme using the plugin formatters shows Tomans without theme changes.
 *
 * @link https://brsapi.ir/free-api-gold-currency-webservice/
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Navasan {

	const TRANSIENT_RATE = 'wp_cardealer_navasan_usd_toman_rate';
	const OPTION_LAST_RESULT = 'wp_cardealer_navasan_last_result';
	const DEFAULT_ITEM = 'USD';
	const DEFAULT_CACHE_MINUTES = 60;
	const API_URL = 'https://Api.BrsApi.ir/Market/Gold_Currency.php';

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
	 * BrsApi currency symbol used as the USD market rate. Default is USD.
	 *
	 * @return string
	 */
	public static function get_usd_item() {
		$item = wp_cardealer_get_option( 'navasan_usd_item', self::DEFAULT_ITEM );

		if ( empty( $item ) || ! is_string( $item ) ) {
			return self::DEFAULT_ITEM;
		}

		$legacy = array(
			'usd_sell'          => 'USD',
			'usd_buy'           => 'USD',
			'usd'               => 'USD',
			'mex_usd_sell'      => 'USD',
			'mob_usd'           => 'USD',
			'harat_naghdi_sell' => 'USD',
		);
		$lower = strtolower( $item );
		if ( isset( $legacy[ $lower ] ) ) {
			return $legacy[ $lower ];
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
	 * Fetch the latest rate from BrsApi and cache it.
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
	 * Persist a successful BrsApi payload.
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
				'name'       => isset( $result['name'] ) ? $result['name'] : '',
				'date'       => isset( $result['date'] ) ? $result['date'] : '',
				'timestamp'  => isset( $result['timestamp'] ) ? (int) $result['timestamp'] : time(),
				'unit'       => isset( $result['unit'] ) ? $result['unit'] : 'تومان',
				'fetched_at' => current_time( 'mysql' ),
			),
			false
		);
	}

	/**
	 * Call BrsApi latest gold/currency endpoint.
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
				'message' => __( 'BrsApi API key is empty.', 'wp-cardealer' ),
			);
		}

		$url = add_query_arg(
			array(
				'key' => $api_key,
			),
			self::API_URL
		);

		$url = apply_filters( 'wp_cardealer_navasan_latest_url', $url, $api_key, $item );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'sslverify'   => true,
				'redirection' => 0,
				'headers'     => array(
					'Accept' => 'application/json',
				),
				'user-agent'  => 'WP-CarDealer/' . ( defined( 'WP_CARDEALER_PLUGIN_VERSION' ) ? WP_CARDEALER_PLUGIN_VERSION : '1.0' ),
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

		if ( $code === 401 || $code === 403 ) {
			return array(
				'success' => false,
				'message' => __( 'BrsApi API key is invalid or the request was blocked.', 'wp-cardealer' ),
				'code'    => $code,
			);
		}

		if ( $code !== 200 ) {
			$message = __( 'Unable to fetch the USD rate from BrsApi.', 'wp-cardealer' );
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				$message = $data['message'];
			} elseif ( is_array( $data ) && ! empty( $data['message_error'] ) ) {
				$message = $data['message_error'];
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
				'message' => __( 'BrsApi did not return a valid USD rate.', 'wp-cardealer' ),
			);
		}

		$parsed['success'] = true;

		return $parsed;
	}

	/**
	 * Parse BrsApi JSON into a Toman-per-USD rate.
	 *
	 * Free endpoint shape:
	 * { "gold": [...], "currency": [ { "symbol": "USD", "price": 200500, "unit": "تومان" }, ... ] }
	 *
	 * @param mixed  $data
	 * @param string $item
	 * @return array
	 */
	public static function extract_rate_from_payload( $data, $item = self::DEFAULT_ITEM ) {
		$result = array(
			'rate'      => 0,
			'item'      => $item,
			'name'      => '',
			'date'      => '',
			'timestamp' => 0,
			'change'    => '',
			'unit'      => 'تومان',
		);

		$rows = self::flatten_brs_items( $data );
		if ( empty( $rows ) ) {
			return $result;
		}

		$match = null;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['symbol'] ) ) {
				continue;
			}
			if ( strcasecmp( (string) $row['symbol'], (string) $item ) === 0 ) {
				$match = $row;
				break;
			}
		}

		if ( ! is_array( $match ) || ! isset( $match['price'] ) || ! is_numeric( $match['price'] ) ) {
			return $result;
		}

		$unit = isset( $match['unit'] ) ? (string) $match['unit'] : 'تومان';
		$rate = self::price_to_toman( (float) $match['price'], $unit );
		if ( $rate <= 0 ) {
			return $result;
		}

		$date = isset( $match['date'] ) ? (string) $match['date'] : '';
		$time = isset( $match['time'] ) ? (string) $match['time'] : '';

		$result['rate']      = $rate;
		$result['item']      = (string) $match['symbol'];
		$result['name']      = isset( $match['name'] ) ? (string) $match['name'] : '';
		$result['date']      = trim( $date . ( $time !== '' ? ' ' . $time : '' ) );
		$result['timestamp'] = isset( $match['time_unix'] ) ? (int) $match['time_unix'] : 0;
		$result['change']    = isset( $match['change_value'] ) ? $match['change_value'] : '';
		$result['unit']      = 'تومان';

		return $result;
	}

	/**
	 * Collect gold/currency/cryptocurrency rows from a BrsApi payload.
	 *
	 * @param mixed $data
	 * @return array
	 */
	public static function flatten_brs_items( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		$rows = array();
		foreach ( array( 'gold', 'currency', 'cryptocurrency' ) as $section ) {
			if ( isset( $data[ $section ] ) && is_array( $data[ $section ] ) ) {
				foreach ( $data[ $section ] as $row ) {
					if ( is_array( $row ) ) {
						$rows[] = $row;
					}
				}
			}
		}

		if ( ! empty( $rows ) ) {
			return $rows;
		}

		if ( isset( $data[0] ) && is_array( $data[0] ) ) {
			return $data;
		}

		return array();
	}

	/**
	 * Normalize a BrsApi price to Toman. Pro docs use Rial; free API uses Toman.
	 *
	 * @param float  $price
	 * @param string $unit
	 * @return float
	 */
	public static function price_to_toman( $price, $unit ) {
		if ( ! is_numeric( $price ) || (float) $price <= 0 ) {
			return 0;
		}

		$price = (float) $price;
		$unit  = trim( (string) $unit );

		if ( $unit === 'ریال' || strcasecmp( $unit, 'rial' ) === 0 || strcasecmp( $unit, 'irr' ) === 0 ) {
			return $price / 10;
		}

		return $price;
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
	 * USD market sources supported by BrsApi gold/currency.
	 *
	 * @return array
	 */
	public static function get_usd_item_options() {
		return array(
			'USD'       => __( 'US Dollar (USD)', 'wp-cardealer' ),
			'USDT_IRT'  => __( 'Tether (USDT_IRT)', 'wp-cardealer' ),
		);
	}
}

WP_CarDealer_Navasan::init();
