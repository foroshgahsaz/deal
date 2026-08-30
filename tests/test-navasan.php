<?php
/**
 * Standalone tests for BrsApi USD→Toman helpers.
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

$payload = array(
	'gold'     => array(),
	'currency' => array(
		array(
			'date'           => '1405/06/04',
			'time'           => '18:14',
			'time_unix'      => 1787755476,
			'symbol'         => 'USDT_IRT',
			'name'           => 'دلار تتر',
			'price'          => 198726,
			'change_value'   => -358,
			'change_percent' => -0.18,
			'unit'           => 'تومان',
		),
		array(
			'date'           => '1405/06/04',
			'time'           => '18:14',
			'time_unix'      => 1787755476,
			'symbol'         => 'USD',
			'name_en'        => 'US Dollar',
			'name'           => 'دلار',
			'price'          => 200500,
			'change_value'   => 0,
			'change_percent' => 0,
			'unit'           => 'تومان',
		),
	),
);

$parsed = WP_CarDealer_Navasan::extract_rate_from_payload( $payload, 'USD' );
assert_same( 200500.0, $parsed['rate'], 'extracts USD.price from BrsApi currency list' );
assert_same( 'USD', $parsed['item'], 'keeps USD symbol' );
assert_same( 'دلار', $parsed['name'], 'extracts Persian name' );
assert_same( '1405/06/04 18:14', $parsed['date'], 'joins BrsApi date and time' );
assert_same( 1787755476, $parsed['timestamp'], 'extracts time_unix' );

$tether = WP_CarDealer_Navasan::extract_rate_from_payload( $payload, 'USDT_IRT' );
assert_same( 198726.0, $tether['rate'], 'extracts USDT_IRT from the same payload' );

$list = WP_CarDealer_Navasan::extract_rate_from_payload( $payload['currency'], 'USD' );
assert_same( 200500.0, $list['rate'], 'accepts a flat list of currency rows' );

$empty = WP_CarDealer_Navasan::extract_rate_from_payload( array( 'foo' => 'bar' ), 'USD' );
assert_same( 0.0, $empty['rate'], 'returns 0 for invalid payload' );

$null = WP_CarDealer_Navasan::extract_rate_from_payload( null, 'USD' );
assert_same( 0.0, $null['rate'], 'returns 0 for non-array payload' );

assert_same( 200500.0, WP_CarDealer_Navasan::price_to_toman( 200500, 'تومان' ), 'keeps Toman prices as-is' );
assert_same( 85900.0, WP_CarDealer_Navasan::price_to_toman( 859000, 'ریال' ), 'converts Rial prices to Toman' );

assert_same( 5012500000.0, WP_CarDealer_Navasan::convert_amount( 25000, 200500 ), 'converts 25000 USD at 200500 Toman' );
assert_same( 0.0, WP_CarDealer_Navasan::convert_amount( 'abc', 200500 ), 'non-numeric USD becomes 0' );
assert_same( 25000.0, WP_CarDealer_Navasan::convert_amount( 25000, 0 ), 'rate 0 leaves the USD amount unchanged' );

assert_same( 200500.0, WP_CarDealer_Navasan::resolve_cached_rate( 200500, 0 ), 'prefers transient rate' );
assert_same( 198726.0, WP_CarDealer_Navasan::resolve_cached_rate( false, 198726 ), 'falls back to last stored rate' );
assert_same( 0.0, WP_CarDealer_Navasan::resolve_cached_rate( false, 0 ), 'returns 0 when nothing is cached' );

function wp_doing_cron() {
	return ! empty( $GLOBALS['wp_cardealer_test_doing_cron'] );
}

function wp_doing_ajax() {
	return ! empty( $GLOBALS['wp_cardealer_test_doing_ajax'] );
}

function sanitize_text_field( $text ) {
	return is_string( $text ) ? trim( $text ) : '';
}

$GLOBALS['wp_cardealer_test_doing_cron'] = false;
$GLOBALS['wp_cardealer_test_doing_ajax'] = false;
assert_same( false, WP_CarDealer_Navasan::should_fetch_rate_synchronously(), 'frontend requests do not sync-fetch' );

$GLOBALS['wp_cardealer_test_doing_cron'] = true;
assert_same( true, WP_CarDealer_Navasan::should_fetch_rate_synchronously(), 'cron may sync-fetch' );
$GLOBALS['wp_cardealer_test_doing_cron'] = false;

$GLOBALS['wp_cardealer_test_doing_ajax'] = true;
$_REQUEST['action'] = 'wp_cardealer_navasan_test_token';
assert_same( true, WP_CarDealer_Navasan::should_fetch_rate_synchronously(), 'settings test ajax may sync-fetch' );
$GLOBALS['wp_cardealer_test_doing_ajax'] = false;
unset( $_REQUEST['action'] );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
