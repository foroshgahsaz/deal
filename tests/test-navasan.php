<?php
/**
 * Standalone tests for Navasan USD→Toman helpers.
 *
 * Run: php tests/test-navasan.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

function add_action() {}
function add_filter() {}
function apply_filters( $tag, $value ) {
	return $value;
}
function __( $text ) {
	return $text;
}

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

function assert_true( $condition, $message ) {
	assert_same( true, (bool) $condition, $message );
}

$payload = array(
	'usd_sell' => array(
		'value'     => '11100',
		'change'    => -25,
		'timestamp' => 1568212950,
		'date'      => '1398-06-20 19:12:30',
	),
);

$parsed = WP_CarDealer_Navasan::extract_rate_from_payload( $payload, 'usd_sell' );
assert_same( 11100.0, $parsed['rate'], 'extracts usd_sell.value from latest payload' );
assert_same( '1398-06-20 19:12:30', $parsed['date'], 'extracts Navasan date' );
assert_same( 1568212950, $parsed['timestamp'], 'extracts timestamp' );

$flat = array(
	'value'     => '95000',
	'change'    => 10,
	'timestamp' => 1700000000,
	'date'      => '1402-01-01 12:00:00',
);
$parsed_flat = WP_CarDealer_Navasan::extract_rate_from_payload( $flat, 'usd_sell' );
assert_same( 95000.0, $parsed_flat['rate'], 'extracts value from a flat item payload' );

$empty = WP_CarDealer_Navasan::extract_rate_from_payload( array( 'foo' => 'bar' ), 'usd_sell' );
assert_same( 0.0, $empty['rate'], 'returns 0 for invalid payload' );

$null = WP_CarDealer_Navasan::extract_rate_from_payload( null, 'usd_sell' );
assert_same( 0.0, $null['rate'], 'returns 0 for non-array payload' );

assert_same( 277500000.0, WP_CarDealer_Navasan::convert_amount( 25000, 11100 ), 'converts 25000 USD at 11100 Toman' );
assert_same( 0.0, WP_CarDealer_Navasan::convert_amount( 'abc', 11100 ), 'non-numeric USD becomes 0' );
assert_same( 25000.0, WP_CarDealer_Navasan::convert_amount( 25000, 0 ), 'rate 0 leaves the USD amount unchanged' );
assert_same( 25000.0, WP_CarDealer_Navasan::convert_amount( 25000, -1 ), 'negative rate leaves the USD amount unchanged' );
assert_same( 277500001.0, WP_CarDealer_Navasan::convert_amount( 25000.00009, 11100 ), 'rounds converted Toman to nearest integer' );

$nested_other = array(
	'usd_buy' => array(
		'value' => '11050',
	),
);
$parsed_other = WP_CarDealer_Navasan::extract_rate_from_payload( $nested_other, 'usd_buy' );
assert_same( 11050.0, $parsed_other['rate'], 'extracts a non-default item key' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
