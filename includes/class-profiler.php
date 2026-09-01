<?php
/**
 * Request profiler used to locate frontend performance regressions.
 *
 * Activated per request with ?wpcd_profile=1 so it costs nothing in normal traffic.
 * The report is emitted as an HTML comment and to the PHP error log.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Profiler {

	/**
	 * @var bool|null
	 */
	private static $enabled = null;

	/**
	 * @var float
	 */
	private static $started_at = 0.0;

	/**
	 * Aggregated measurements keyed by segment label.
	 *
	 * @var array<string, array{calls:int, time:float, cache:int, depth:int, reentrant:int}>
	 */
	private static $segments = array();

	/**
	 * Open measurements keyed by segment label.
	 *
	 * @var array<string, array{time:float, cache:int}>
	 */
	private static $open = array();

	public static function init() {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::$started_at = microtime( true );

		add_action( 'wp_footer', array( __CLASS__, 'render' ), 99999 );
		add_action( 'shutdown', array( __CLASS__, 'log' ), 99999 );
	}

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		if ( null === self::$enabled ) {
			self::$enabled = isset( $_GET['wpcd_profile'] ) && '1' === $_GET['wpcd_profile'];
		}

		return self::$enabled;
	}

	/**
	 * Record a hit for a very hot call site where timing overhead is not worth it.
	 *
	 * @param string $label
	 * @return void
	 */
	public static function count( $label ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! isset( self::$segments[ $label ] ) ) {
			self::$segments[ $label ] = array(
				'calls'     => 0,
				'time'      => 0.0,
				'cache'     => 0,
				'depth'     => 0,
				'reentrant' => 0,
			);
		}

		self::$segments[ $label ]['calls']++;
	}

	/**
	 * Begin (or nest into) a measured segment.
	 *
	 * @param string $label
	 * @return void
	 */
	public static function start( $label ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! isset( self::$segments[ $label ] ) ) {
			self::$segments[ $label ] = array(
				'calls'     => 0,
				'time'      => 0.0,
				'cache'     => 0,
				'depth'     => 0,
				'reentrant' => 0,
			);
		}

		self::$segments[ $label ]['calls']++;

		// Only the outermost call is timed, otherwise recursion double counts.
		if ( self::$segments[ $label ]['depth'] > 0 ) {
			self::$segments[ $label ]['reentrant']++;
			self::$segments[ $label ]['depth']++;
			return;
		}

		self::$segments[ $label ]['depth'] = 1;
		self::$open[ $label ]              = array(
			'time'  => microtime( true ),
			'cache' => self::get_cache_hits(),
		);
	}

	/**
	 * Close a measured segment.
	 *
	 * @param string $label
	 * @return void
	 */
	public static function stop( $label ) {
		if ( ! self::is_enabled() || empty( self::$segments[ $label ]['depth'] ) ) {
			return;
		}

		self::$segments[ $label ]['depth']--;

		if ( self::$segments[ $label ]['depth'] > 0 || ! isset( self::$open[ $label ] ) ) {
			return;
		}

		self::$segments[ $label ]['time']  += microtime( true ) - self::$open[ $label ]['time'];
		self::$segments[ $label ]['cache'] += self::get_cache_hits() - self::$open[ $label ]['cache'];

		unset( self::$open[ $label ] );
	}

	/**
	 * Total object cache reads served so far this request.
	 *
	 * @return int
	 */
	public static function get_cache_hits() {
		if ( ! isset( $GLOBALS['wp_object_cache'] ) || ! is_object( $GLOBALS['wp_object_cache'] ) ) {
			return 0;
		}

		$cache = $GLOBALS['wp_object_cache'];
		$total = 0;

		foreach ( array( 'cache_hits', 'cache_misses' ) as $property ) {
			if ( isset( $cache->$property ) && is_numeric( $cache->$property ) ) {
				$total += (int) $cache->$property;
			}
		}

		return $total;
	}

	/**
	 * @return string
	 */
	public static function build_report() {
		if ( ! self::is_enabled() ) {
			return '';
		}

		$lines = array(
			sprintf(
				'total=%sms peak_mem=%sMB cache_reads=%s queries=%s',
				round( ( microtime( true ) - self::$started_at ) * 1000, 1 ),
				round( memory_get_peak_usage( true ) / 1024 / 1024, 1 ),
				number_format( self::get_cache_hits() ),
				isset( $GLOBALS['wpdb']->num_queries ) ? (int) $GLOBALS['wpdb']->num_queries : 0
			),
		);

		foreach ( self::$segments as $label => $data ) {
			$lines[] = sprintf(
				'%s calls=%s time=%sms cache_reads=%s reentrant=%s',
				$label,
				number_format( $data['calls'] ),
				round( $data['time'] * 1000, 1 ),
				number_format( $data['cache'] ),
				number_format( $data['reentrant'] )
			);
		}

		return implode( ' | ', apply_filters( 'wp_cardealer_profiler_report_lines', $lines ) );
	}

	/**
	 * @return void
	 */
	public static function render() {
		if ( ! self::is_enabled() ) {
			return;
		}

		echo "\n<!-- wpcd-profile " . esc_html( self::build_report() ) . " -->\n";
	}

	/**
	 * @return void
	 */
	public static function log() {
		if ( ! self::is_enabled() ) {
			return;
		}

		error_log( 'wpcd-profile ' . self::build_report() );
	}
}

WP_CarDealer_Profiler::init();
