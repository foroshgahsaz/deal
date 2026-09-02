<?php
/**
 * Standalone tests for the price filter bounds and server side conversion.
 *
 * Measurement on the affected site traced the slow homepage to a stepped loop
 * in the theme's price slider template:
 *
 *   format_price_callers price_range_slider.php:144<-class-abstract-filter.php:434
 *   price_keys samples=0|1|1, 5.778E+14|1|1, 5000000000|1|1
 *   range_field_keys sample=listing_price|price|filter-price|0|2700000000|قیمت|0
 *
 * One listing carries a price of 2,700,000,000 which sets the filter's upper
 * bound. Converting that bound at a rate of 214,000 gives 5.778e14, and the
 * template walks it in steps of 5e9, which is 115,560 iterations formatting two
 * prices each: the 231,145 calls that were observed. With the feature off the
 * bound stays below a single step, so the loop never runs.
 *
 * Run: php tests/test-price-filter-bounds.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

$GLOBALS['wpcd_test_options'] = array();

function add_action() {}

function add_filter() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function __( $text ) {
	return $text;
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function is_admin() {
	return false;
}

function wp_doing_ajax() {
	return false;
}

function absint( $value ) {
	return abs( (int) $value );
}

function get_option( $name, $default = false ) {
	return 'wp_cardealer_navasan_usd_rate' === $name ? 214000 : $default;
}

function get_transient( $name ) {
	return false;
}

function wp_cardealer_get_option( $key = '', $default = false ) {
	return isset( $GLOBALS['wpcd_test_options'][ $key ] ) && '' !== $GLOBALS['wpcd_test_options'][ $key ]
		? $GLOBALS['wpcd_test_options'][ $key ]
		: $default;
}

require_once dirname( __DIR__ ) . '/includes/class-profiler.php';
require_once dirname( __DIR__ ) . '/includes/class-mixes.php';
require_once dirname( __DIR__ ) . '/includes/class-price.php';
require_once dirname( __DIR__ ) . '/includes/class-navasan.php';

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

function assert_true( $actual, $message ) {
	assert_same( true, $actual, $message );
}

function use_settings( $overrides ) {
	$GLOBALS['wpcd_test_options'] = array_merge(
		array(
			'currency'                => 'USD',
			'currency_position'       => 'before',
			'money_decimals'          => 0,
			'enable_multi_currencies' => 'no',
			'custom_symbol'           => '$',
			'enable_usd_to_toman'     => 'yes',
			'navasan_api_key'         => 'test-key',
		),
		$overrides
	);

	WP_CarDealer_Price::flush_runtime_cache();
	WP_CarDealer_Navasan::reset_request_rate_cache();
}

// The observed bad row, and the step the theme template walks in.
$bad_bound = 2700000000;
$step      = 5000000000;

// Client mode must not convert server side, because the browser does it.
use_settings( array( 'navasan_frontend_convert' => 'client' ) );

assert_same( 214000.0, WP_CarDealer_Navasan::get_usd_toman_rate(), 'the rate is available' );
assert_same( true, WP_CarDealer_Navasan::use_client_side_conversion(), 'client mode is active' );
assert_same(
	(float) $bad_bound,
	(float) WP_CarDealer_Price::convert_price_exchange( $bad_bound ),
	'client mode leaves a filter bound in the stored currency'
);

// Server mode still converts, which is the whole point of that mode.
use_settings( array( 'navasan_frontend_convert' => 'server' ) );

assert_same( false, WP_CarDealer_Navasan::use_client_side_conversion(), 'server mode is active' );
assert_same(
	577800000000000.0,
	(float) WP_CarDealer_Price::convert_price_exchange( $bad_bound ),
	'server mode converts a filter bound to Toman'
);

// With the feature off nothing is converted either way.
use_settings( array( 'enable_usd_to_toman' => 'no' ) );
assert_same(
	(float) $bad_bound,
	(float) WP_CarDealer_Price::convert_price_exchange( $bad_bound ),
	'a disabled feature leaves the bound alone'
);

// A single mistyped listing price must not set the filter's upper bound.
use_settings( array( 'navasan_frontend_convert' => 'client' ) );

$ceiling = WP_CarDealer_Price::get_filter_price_ceiling();

assert_true( $ceiling > 0, 'there is a ceiling for filter bounds' );
assert_true( $bad_bound > $ceiling, 'the observed bad row is above the ceiling' );
assert_same( $ceiling, WP_CarDealer_Price::clamp_filter_price_max( $bad_bound ), 'an implausible bound is clamped' );

// Ordinary bounds must pass through untouched.
assert_same( 250000.0, WP_CarDealer_Price::clamp_filter_price_max( 250000 ), 'a realistic bound is untouched' );
assert_same( $ceiling, WP_CarDealer_Price::clamp_filter_price_max( $ceiling ), 'a bound exactly at the ceiling is untouched' );
assert_same( 0.0, WP_CarDealer_Price::clamp_filter_price_max( 'abc' ), 'a non numeric bound becomes zero' );
assert_same( 0.0, WP_CarDealer_Price::clamp_filter_price_max( null ), 'a missing bound becomes zero' );

// Together, the two fixes have to collapse the loop the theme runs.
function stepped_iterations( $bound, $step ) {
	$bound = (float) WP_CarDealer_Price::convert_price_exchange( WP_CarDealer_Price::clamp_filter_price_max( $bound ) );

	return (int) floor( $bound / $step );
}

use_settings( array( 'navasan_frontend_convert' => 'client' ) );
$client_iterations = stepped_iterations( $bad_bound, $step );

use_settings( array( 'navasan_frontend_convert' => 'server' ) );
$server_iterations = stepped_iterations( $bad_bound, $step );

use_settings( array( 'enable_usd_to_toman' => 'no' ) );
$off_iterations = stepped_iterations( $bad_bound, $step );

printf(
	"\nstepped loop iterations for the observed data  ->  off=%d client=%d server=%d  (was 115,560)\n\n",
	$off_iterations,
	$client_iterations,
	$server_iterations
);

assert_same( $off_iterations, $client_iterations, 'client mode costs exactly what having the feature off costs' );
assert_true( $client_iterations < 10, 'client mode no longer produces a runaway loop' );
assert_true( $server_iterations < 5000, 'server mode is bounded even with a mistyped listing price' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
