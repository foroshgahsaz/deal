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

	const MODE_SEGMENTS = '1';
	const MODE_HOOKS    = 'hooks';

	/**
	 * @var bool|null
	 */
	private static $enabled = null;

	const CALLER_SAMPLE_HEAD  = 40;
	const CALLER_SAMPLE_EVERY = 500;

	/**
	 * Execution counts keyed by hook name, populated only in hook mode.
	 *
	 * @var array<string, int>
	 */
	private static $hooks = array();

	/**
	 * Sampled call sites keyed by segment label then "file:line".
	 *
	 * @var array<string, array<string, int>>
	 */
	private static $callers = array();

	/**
	 * How many times each segment has been offered for caller sampling.
	 *
	 * @var array<string, int>
	 */
	private static $caller_seen = array();

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

		if ( self::is_hook_mode() ) {
			add_action( 'all', array( __CLASS__, 'count_hook' ) );
		}

		add_action( 'wp_footer', array( __CLASS__, 'render' ), 99999 );
		add_action( 'shutdown', array( __CLASS__, 'log' ), 99999 );
	}

	/**
	 * @return string
	 */
	public static function get_mode() {
		return isset( $_GET['wpcd_profile'] ) ? (string) $_GET['wpcd_profile'] : '';
	}

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		if ( null === self::$enabled ) {
			self::$enabled = in_array( self::get_mode(), array( self::MODE_SEGMENTS, self::MODE_HOOKS ), true );
		}

		return self::$enabled;
	}

	/**
	 * Hook mode counts every action and filter fired, which reveals runaway loops
	 * anywhere in the request, including the theme and page builders.
	 *
	 * @return bool
	 */
	public static function is_hook_mode() {
		return self::MODE_HOOKS === self::get_mode();
	}

	/**
	 * @return void
	 */
	public static function count_hook() {
		$hook = current_filter();

		if ( ! isset( self::$hooks[ $hook ] ) ) {
			self::$hooks[ $hook ] = 0;
		}

		self::$hooks[ $hook ]++;
	}

	/**
	 * Hooks that fired most often this request.
	 *
	 * @param int $limit
	 * @return array<string, int>
	 */
	public static function get_busiest_hooks( $limit = 15 ) {
		$hooks = self::$hooks;
		arsort( $hooks );

		return array_slice( $hooks, 0, max( 1, (int) $limit ), true );
	}

	/**
	 * Sample which file and line called a segment.
	 *
	 * Capturing a backtrace on every call of a segment that runs hundreds of
	 * thousands of times would itself dominate the measurement, so early calls
	 * are recorded and then only every nth call after that. That is enough to
	 * identify a dominant caller.
	 *
	 * @param string $label
	 * @return void
	 */
	public static function sample_caller( $label ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! isset( self::$caller_seen[ $label ] ) ) {
			self::$caller_seen[ $label ] = 0;
		}

		$seen = ++self::$caller_seen[ $label ];

		if ( $seen > self::CALLER_SAMPLE_HEAD && 0 !== $seen % self::CALLER_SAMPLE_EVERY ) {
			return;
		}

		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 4 );

		// Frame 0 is this method, frame 1 is the instrumented function itself.
		$frame  = isset( $trace[2] ) ? $trace[2] : end( $trace );
		$origin = isset( $frame['file'] ) ? basename( dirname( $frame['file'] ) ) . '/' . basename( $frame['file'] ) : 'unknown';
		$origin .= isset( $frame['line'] ) ? ':' . $frame['line'] : '';

		if ( ! isset( self::$callers[ $label ][ $origin ] ) ) {
			self::$callers[ $label ][ $origin ] = 0;
		}

		self::$callers[ $label ][ $origin ]++;
	}

	/**
	 * @param string $label
	 * @param int    $limit
	 * @return array<string, int>
	 */
	public static function get_top_callers( $label, $limit = 5 ) {
		if ( empty( self::$callers[ $label ] ) ) {
			return array();
		}

		$callers = self::$callers[ $label ];
		arsort( $callers );

		return array_slice( $callers, 0, max( 1, (int) $limit ), true );
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
	 * How many times a segment ran this request.
	 *
	 * @param string $label
	 * @return int
	 */
	public static function get_calls( $label ) {
		return isset( self::$segments[ $label ]['calls'] ) ? (int) self::$segments[ $label ]['calls'] : 0;
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

			$callers = self::get_top_callers( $label );
			if ( ! empty( $callers ) ) {
				$formatted = array();
				foreach ( $callers as $origin => $count ) {
					$formatted[] = $origin . '=' . number_format( $count );
				}
				$lines[] = $label . '_callers ' . implode( ' ', $formatted );
			}
		}

		if ( self::is_hook_mode() ) {
			$busiest = array();
			foreach ( self::get_busiest_hooks() as $hook => $count ) {
				$busiest[] = $hook . '=' . number_format( $count );
			}
			$lines[] = 'busiest_hooks ' . implode( ' ', $busiest );
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
