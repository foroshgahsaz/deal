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
	const TRANSIENT_REFRESH_LOCK = 'wp_cardealer_navasan_refresh_lock';
	const OPTION_RATE = 'wp_cardealer_navasan_usd_rate';
	const OPTION_LAST_RESULT = 'wp_cardealer_navasan_last_result';
	const DEFAULT_ITEM = 'USD';
	const DEFAULT_CACHE_MINUTES = 60;
	const API_URL = 'https://Api.BrsApi.ir/Market/Gold_Currency.php';
	const API_TIMEOUT = 8;
	const REFRESH_LOCK_SECONDS = 300;

	/**
	 * Per-request memoization so listing archives do not hit the DB dozens of times.
	 *
	 * @var float|null
	 */
	private static $request_rate_cache = null;

	/**
	 * @var bool|null
	 */
	private static $is_enabled_cache = null;

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
		if ( null !== self::$is_enabled_cache ) {
			return self::$is_enabled_cache;
		}

		if ( wp_cardealer_get_option( 'enable_usd_to_toman', 'yes' ) !== 'yes' ) {
			self::$is_enabled_cache = false;
			return false;
		}

		self::$is_enabled_cache = self::get_api_key() !== '';
		return self::$is_enabled_cache;
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
	 * Cached USD→Toman rate. Reads local cache only — never calls BrsApi during page render.
	 *
	 * @return float
	 */
	public static function get_usd_toman_rate() {
		if ( null !== self::$request_rate_cache ) {
			return self::$request_rate_cache;
		}

		if ( ! self::is_enabled() ) {
			self::$request_rate_cache = 0.0;
			return self::$request_rate_cache;
		}

		self::$request_rate_cache = self::resolve_cached_rate(
			get_transient( self::TRANSIENT_RATE ),
			self::get_stored_rate()
		);

		return self::$request_rate_cache;
	}

	/**
	 * Pick the best locally available rate without calling the API.
	 *
	 * @param mixed $transient
	 * @param mixed $fallback
	 * @return float
	 */
	public static function resolve_cached_rate( $transient, $fallback ) {
		if ( is_numeric( $transient ) && (float) $transient > 0 ) {
			return (float) $transient;
		}

		if ( is_numeric( $fallback ) && (float) $fallback > 0 ) {
			return (float) $fallback;
		}

		return 0.0;
	}

	/**
	 * @return float
	 */
	public static function get_stored_rate() {
		$rate = get_option( self::OPTION_RATE, 0 );
		if ( is_numeric( $rate ) && (float) $rate > 0 ) {
			return (float) $rate;
		}

		return self::get_fallback_rate();
	}

	/**
	 * @return float
	 */
	public static function get_fallback_rate() {
		$last = get_option( self::OPTION_LAST_RESULT, array() );
		if ( ! is_array( $last ) || empty( $last['rate'] ) ) {
			return 0.0;
		}

		return is_numeric( $last['rate'] ) ? (float) $last['rate'] : 0.0;
	}

	/**
	 * Restore the transient when we still have a valid last-known rate.
	 *
	 * @param float $rate
	 * @return void
	 */
	public static function backfill_transient_from_rate( $rate ) {
		$rate = (float) $rate;
		if ( $rate <= 0 ) {
			return;
		}

		set_transient( self::TRANSIENT_RATE, $rate, self::get_cache_seconds() );
	}

	/**
	 * Queue a deferred refresh for WP-Cron. Does not spawn HTTP or call BrsApi now.
	 *
	 * @return void
	 */
	public static function schedule_immediate_refresh() {
		if ( get_transient( self::TRANSIENT_REFRESH_LOCK ) ) {
			return;
		}

		set_transient( self::TRANSIENT_REFRESH_LOCK, 1, self::REFRESH_LOCK_SECONDS );

		$hook = 'wp_cardealer_navasan_refresh_rate';
		if ( wp_next_scheduled( $hook ) ) {
			return;
		}

		wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), $hook );
	}

	/**
	 * Remote fetch is allowed only for cron/admin test flows.
	 *
	 * @return bool
	 */
	public static function should_fetch_rate_synchronously() {
		if ( wp_doing_cron() ) {
			return true;
		}

		if ( wp_doing_ajax() ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
			if ( in_array( $action, array( 'wp_cardealer_navasan_test_token', 'wpcd_ajax_wp_cardealer_navasan_test_token' ), true ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'wp_cardealer_navasan_allow_sync_fetch', false );
	}

	/**
	 * @return void
	 */
	public static function reset_request_rate_cache() {
		self::$request_rate_cache = null;
		self::$is_enabled_cache   = null;
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
		self::$request_rate_cache = (float) $result['rate'];

		return self::$request_rate_cache;
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
		update_option( self::OPTION_RATE, $rate, true );
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
				'message' => 'کلید وب‌سرویس خالی است.',
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
				'timeout'     => self::API_TIMEOUT,
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
				'message' => 'کلید وب‌سرویس نامعتبر است یا درخواست مسدود شد.',
				'code'    => $code,
			);
		}

		if ( $code !== 200 ) {
			$message = 'دریافت نرخ دلار از وب‌سرویس ممکن نشد.';
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
				'message' => 'وب‌سرویس نرخ دلار معتبری برنگرداند.',
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
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'wp_cardealer_navasan_refresh_rate' );
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
		$changed = false;
		foreach ( $keys as $key ) {
			$old = isset( $old_value[ $key ] ) ? $old_value[ $key ] : '';
			$new = isset( $new_value[ $key ] ) ? $new_value[ $key ] : '';
			if ( $old !== $new ) {
				$changed = true;
				break;
			}
		}

		if ( ! $changed ) {
			return;
		}

		delete_transient( self::TRANSIENT_RATE );
		delete_transient( self::TRANSIENT_REFRESH_LOCK );
		self::reset_request_rate_cache();
		self::schedule_immediate_refresh();
	}

	public static function ajax_test_token() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'اجازهٔ این کار را ندارید.' ) );
		}

		check_ajax_referer( 'wp_cardealer_navasan_test', 'nonce' );

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$item    = isset( $_POST['item'] ) ? sanitize_text_field( wp_unslash( $_POST['item'] ) ) : self::DEFAULT_ITEM;

		$result = self::fetch_latest_rate( $api_key, $item );

		if ( empty( $result['success'] ) || empty( $result['rate'] ) ) {
			wp_send_json_error(
				array(
					'message' => isset( $result['message'] ) ? $result['message'] : 'درخواست ناموفق بود.',
				)
			);
		}

		self::store_rate_result( $result );

		wp_send_json_success(
			array(
				'message' => sprintf(
					'اتصال برقرار شد. نرخ فعلی: %1$s تومان به‌ازای هر ۱ دلار (%2$s).',
					number_format_i18n( $result['rate'] ),
					self::get_usd_item_label( $result['item'] )
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
			'USD'      => 'دلار آمریکا',
			'USDT_IRT' => 'تتر',
		);
	}

	/**
	 * Persian label for a BrsApi currency symbol.
	 *
	 * @param string $item
	 * @return string
	 */
	public static function get_usd_item_label( $item ) {
		$options = self::get_usd_item_options();
		if ( isset( $options[ $item ] ) ) {
			return $options[ $item ];
		}

		$legacy = array(
			'usd_sell'          => 'USD',
			'usd_buy'           => 'USD',
			'usd'               => 'USD',
			'mex_usd_sell'      => 'USD',
			'mob_usd'           => 'USD',
			'harat_naghdi_sell' => 'USD',
		);
		$lower = strtolower( (string) $item );
		if ( isset( $legacy[ $lower ] ) && isset( $options[ $legacy[ $lower ] ] ) ) {
			return $options[ $legacy[ $lower ] ];
		}

		return 'دلار آمریکا';
	}
}

WP_CarDealer_Navasan::init();
