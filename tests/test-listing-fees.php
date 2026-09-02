<?php
/**
 * Standalone tests for USD customs/shipping fees and total cost.
 *
 * Run: php tests/test-listing-fees.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );
define( 'MINUTE_IN_SECONDS', 60 );

$GLOBALS['wpcd_test_options'] = array();
$GLOBALS['wp_cardealer_test_meta'] = array();

function add_action() {}
function add_filter() {}
function apply_filters( $tag, $value ) {
	return $value;
}
function __( $text ) {
	return $text;
}
function absint( $value ) {
	return abs( (int) $value );
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text ) {
	return $text;
}
function is_admin() {
	return false;
}
function wp_doing_ajax() {
	return false;
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
function get_post_meta( $post_id, $key, $single = true ) {
	if ( ! isset( $GLOBALS['wp_cardealer_test_meta'][ $post_id ][ $key ] ) ) {
		return '';
	}
	return $GLOBALS['wp_cardealer_test_meta'][ $post_id ][ $key ];
}

require_once dirname( __DIR__ ) . '/includes/class-profiler.php';
require_once dirname( __DIR__ ) . '/includes/class-mixes.php';
require_once dirname( __DIR__ ) . '/includes/class-price.php';
require_once dirname( __DIR__ ) . '/includes/class-navasan.php';
require_once dirname( __DIR__ ) . '/includes/custom-fields/class-fields-manager.php';

$GLOBALS['wpcd_test_options'] = array(
	'currency'                 => 'USD',
	'currency_position'        => 'before',
	'money_decimals'           => 0,
	'enable_multi_currencies'  => 'no',
	'custom_symbol'            => '$',
	'enable_usd_to_toman'      => 'yes',
	'navasan_api_key'          => 'test-key',
	'navasan_frontend_convert' => 'server',
);

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
	assert_same( true, (bool) $actual, $message );
}

function assert_contains( $needle, $haystack, $message ) {
	global $failed, $passed;
	if ( strpos( (string) $haystack, (string) $needle ) !== false ) {
		$passed++;
		echo "PASS  {$message}\n";
		return;
	}
	$failed++;
	echo "FAIL  {$message}\n";
	echo '      missing:  ' . var_export( $needle, true ) . "\n";
	echo '      haystack: ' . var_export( $haystack, true ) . "\n";
}

assert_same( '500', WP_CarDealer_Price::sanitize_usd_amount( '500' ), 'keeps a latin USD amount' );
assert_same( true, WP_CarDealer_Price::is_listing_fee_field( '_listing_customs_fee' ), 'recognizes customs fee key' );

$GLOBALS['wp_cardealer_test_meta'][12] = array(
	'_listing_price'        => '25000',
	'_listing_customs_fee'  => '500',
	'_listing_shipping_fee' => '900',
);
assert_same( 500.0, WP_CarDealer_Price::get_listing_fee_usd( 12, 'customs_fee' ), 'reads customs USD amount' );
assert_same( 26400.0, WP_CarDealer_Price::get_listing_total_usd( 12 ), 'sums price customs and shipping in USD' );

$total_html = WP_CarDealer_Price::get_listing_total_cost_html( 12 );
assert_contains( 'listing-total-cost', $total_html, 'wraps total cost row' );
assert_contains( 'هزینه کل', $total_html, 'renders total cost label' );
assert_contains( '5649600000', $total_html, 'converts total USD sum to Toman' );

$fees_html = WP_CarDealer_Price::get_listing_fees_html( 12 );
assert_contains( 'هزینه گمرک', $fees_html, 'renders customs label' );
assert_contains( 'هزینه گمرک :', $fees_html, 'renders customs label with colon' );
assert_contains( 'هزینه حمل‌ونقل :', $fees_html, 'renders shipping label with colon' );

$labeled = WP_CarDealer_Price::get_listing_fee_labeled_html( 12, 'customs_fee' );
assert_contains( 'listing-price-extra-label', $labeled, 'wraps the customs label' );
assert_contains( 'هزینه گمرک :', $labeled, 'prefixes customs amount with its label' );
assert_contains( 'هزینه گمرک :', WP_CarDealer_Price::get_listing_fee_plain( 12, 'customs_fee', true, true ), 'plain customs text includes the label' );
assert_contains( '107000000', $fees_html, 'converts customs fee to Toman' );
assert_contains( 'listing-price-extras', $fees_html, 'wraps fee rows' );

$total_only = WP_CarDealer_Price::get_listing_total_cost_html( 12 );
assert_contains( 'listing-total-cost', $total_only, 'renders total cost block on its own' );

$GLOBALS['wpcd_test_options']['enable_shorten_long_number'] = 'yes';
$GLOBALS['wpcd_test_options']['shorten_million'] = array( 'enable' => 'on', 'key' => ' میلیون' );
$GLOBALS['wpcd_test_options']['shorten_billion'] = array( 'enable' => 'on', 'key' => ' میلیارد' );
WP_CarDealer_Price::flush_runtime_cache();

assert_contains( '856', WP_CarDealer_Price::number_shorten( 856000000 ), 'whole millions stay whole when shortened' );
assert_contains( '1.22', WP_CarDealer_Price::number_shorten( 1219800000 ), 'fractional billions keep two decimals' );

assert_same( '', WP_CarDealer_Price::get_listing_total_cost_html( 0 ), 'no total without a listing id' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
