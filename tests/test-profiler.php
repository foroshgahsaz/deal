<?php
/**
 * Standalone tests for the request profiler.
 *
 * Run: php tests/test-profiler.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$_GET['wpcd_profile'] = '1';

function add_action() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function esc_html( $text ) {
	return $text;
}

/**
 * Minimal stand-in for WP_Object_Cache so cache read deltas can be asserted.
 */
class WPCD_Test_Object_Cache {
	public $cache_hits   = 0;
	public $cache_misses = 0;
}

$GLOBALS['wp_object_cache'] = new WPCD_Test_Object_Cache();

require_once dirname( __DIR__ ) . '/includes/class-profiler.php';

$failed = 0;
$passed = 0;

function assert_same( $expected, $actual, $message ) {
	global $failed, $passed;
	if ( $expected === $actual ) {
		$passed++;
		echo "PASS  {$message}\n";
		return;
	}
	$failed++;
	echo "FAIL  {$message}\n";
	echo '      expected: ' . var_export( $expected, true ) . "\n";
	echo '      actual:   ' . var_export( $actual, true ) . "\n";
}

function assert_contains( $needle, $haystack, $message ) {
	global $failed, $passed;
	if ( strpos( $haystack, $needle ) !== false ) {
		$passed++;
		echo "PASS  {$message}\n";
		return;
	}
	$failed++;
	echo "FAIL  {$message}\n";
	echo '      missing: ' . $needle . "\n";
	echo '      in:      ' . $haystack . "\n";
}

assert_same( true, WP_CarDealer_Profiler::is_enabled(), 'profiling turns on with ?wpcd_profile=1' );

// A flat segment records one call and attributes cache reads to it.
WP_CarDealer_Profiler::start( 'flat' );
$GLOBALS['wp_object_cache']->cache_hits += 7;
WP_CarDealer_Profiler::stop( 'flat' );

$report = WP_CarDealer_Profiler::build_report();
assert_contains( 'flat calls=1', $report, 'records a single flat call' );
assert_contains( 'cache_reads=7 reentrant=0', $report, 'attributes cache reads to the segment' );

// Cache reads outside a segment must not be attributed to it.
$GLOBALS['wp_object_cache']->cache_hits += 100;
WP_CarDealer_Profiler::start( 'outside' );
WP_CarDealer_Profiler::stop( 'outside' );

$report = WP_CarDealer_Profiler::build_report();
assert_contains( 'outside calls=1 time=', $report, 'unrelated work stays out of the segment' );
assert_contains( 'outside calls=1 time=0ms cache_reads=0', $report, 'segment excludes cache reads made before it started' );

// Recursion is counted but never double timed.
WP_CarDealer_Profiler::start( 'recursive' );
WP_CarDealer_Profiler::start( 'recursive' );
WP_CarDealer_Profiler::start( 'recursive' );
$GLOBALS['wp_object_cache']->cache_hits += 5;
WP_CarDealer_Profiler::stop( 'recursive' );
WP_CarDealer_Profiler::stop( 'recursive' );
WP_CarDealer_Profiler::stop( 'recursive' );

$report = WP_CarDealer_Profiler::build_report();
assert_contains( 'recursive calls=3', $report, 'counts every recursive entry' );
assert_contains( 'reentrant=2', $report, 'reports the nested re-entries that indicate recursion' );

// Counters work without timing.
WP_CarDealer_Profiler::count( 'hot_path' );
WP_CarDealer_Profiler::count( 'hot_path' );

$report = WP_CarDealer_Profiler::build_report();
assert_contains( 'hot_path calls=2', $report, 'counts hot call sites' );
assert_contains( 'cache_reads=112', $report, 'reports total cache reads for the request' );

// Misses count towards total reads as well.
$GLOBALS['wp_object_cache']->cache_misses += 3;
assert_same( 115, WP_CarDealer_Profiler::get_cache_hits(), 'total reads include hits and misses' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
