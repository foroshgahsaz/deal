<?php
/**
 * Standalone tests for the per-request caching of price helpers.
 *
 * These helpers are rebuilt on every rendered price, so a listing archive used
 * to repeat hundreds of option reads and rebuild ~160 entry currency arrays.
 *
 * Run: php tests/test-price-cache.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

$GLOBALS['wpcd_test_option_reads'] = array();
$GLOBALS['wpcd_test_options']      = array(
	'currency'                => 'USD',
	'currency_position'       => 'before',
	'money_decimals'          => 0,
	'enable_multi_currencies' => 'no',
	'shorten_thousand'        => array( 'enable' => 'on', 'key' => 'K' ),
);

function add_action() {}

function add_filter() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function __( $text ) {
	return $text;
}

function wp_cardealer_get_option( $key = '', $default = false ) {
	if ( ! isset( $GLOBALS['wpcd_test_option_reads'][ $key ] ) ) {
		$GLOBALS['wpcd_test_option_reads'][ $key ] = 0;
	}
	$GLOBALS['wpcd_test_option_reads'][ $key ]++;

	return isset( $GLOBALS['wpcd_test_options'][ $key ] ) && '' !== $GLOBALS['wpcd_test_options'][ $key ]
		? $GLOBALS['wpcd_test_options'][ $key ]
		: $default;
}

function option_reads( $key ) {
	return isset( $GLOBALS['wpcd_test_option_reads'][ $key ] ) ? $GLOBALS['wpcd_test_option_reads'][ $key ] : 0;
}

require_once dirname( __DIR__ ) . '/includes/class-profiler.php';
require_once dirname( __DIR__ ) . '/includes/class-price.php';

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

WP_CarDealer_Price::flush_runtime_cache();

// Simulate an archive page rendering many prices.
$first_divisors = WP_CarDealer_Price::get_shorten_divisors();
for ( $i = 0; $i < 250; $i++ ) {
	WP_CarDealer_Price::get_shorten_divisors();
}

assert_same( 1, option_reads( 'shorten_thousand' ), 'shorten divisors read their options once per request' );
assert_same( $first_divisors, WP_CarDealer_Price::get_shorten_divisors(), 'shorten divisors keep returning the same data' );

for ( $i = 0; $i < 250; $i++ ) {
	WP_CarDealer_Price::get_currencies_settings();
}

assert_same( 1, option_reads( 'multi_currencies' ), 'currency settings are built once per request' );

for ( $i = 0; $i < 250; $i++ ) {
	WP_CarDealer_Price::get_current_currency();
}

assert_same( 1, option_reads( 'enable_multi_currencies' ), 'the active currency is resolved once per request' );

$symbols = WP_CarDealer_Price::get_currency_symbols();
assert_same( true, isset( $symbols['USD'] ), 'currency symbols still resolve' );
assert_same( $symbols, WP_CarDealer_Price::get_currency_symbols(), 'currency symbols are reused, not rebuilt' );

$currencies = WP_CarDealer_Price::get_currencies();
assert_same( true, isset( $currencies['USD'] ), 'currency names still resolve' );
assert_same( $currencies, WP_CarDealer_Price::get_currencies(), 'currency names are reused, not rebuilt' );

assert_same( '&#36;', WP_CarDealer_Price::currency_symbol( 'USD' ), 'a single symbol still resolves through the cache' );

// Saving settings must invalidate everything.
$GLOBALS['wpcd_test_options']['currency'] = 'EUR';
WP_CarDealer_Price::flush_runtime_cache();

assert_same( 'EUR', WP_CarDealer_Price::get_current_currency(), 'flushing the cache picks up changed settings' );
assert_same( 2, option_reads( 'enable_multi_currencies' ), 'the currency is resolved again after a flush' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
