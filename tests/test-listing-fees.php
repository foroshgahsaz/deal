<?php
/**
 * Standalone tests for USD customs/shipping fee helpers.
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
	'currency'                => 'USD',
	'currency_position'       => 'before',
	'money_decimals'          => 0,
	'enable_multi_currencies' => 'no',
	'custom_symbol'           => '$',
	'enable_usd_to_toman'     => 'yes',
	'navasan_api_key'         => 'test-key',
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
assert_same( '500', WP_CarDealer_Price::sanitize_usd_amount( '۵۰۰' ), 'converts Persian digits' );
assert_same( '900000', WP_CarDealer_Price::sanitize_usd_amount( '900,000' ), 'strips thousand separators' );
assert_same( '', WP_CarDealer_Price::sanitize_usd_amount( '' ), 'empty stays empty' );
assert_same( '', WP_CarDealer_Price::sanitize_usd_amount( '-10' ), 'rejects negative amounts' );
assert_same( true, WP_CarDealer_Price::is_listing_fee_field( '_listing_customs_fee' ), 'recognizes customs fee key' );
assert_same( true, WP_CarDealer_Price::is_listing_fee_field( '_listing_shipping_fee' ), 'recognizes shipping fee key' );
assert_same( false, WP_CarDealer_Price::is_listing_fee_field( '_listing_price' ), 'does not treat USD price as a fee' );

$GLOBALS['wp_cardealer_test_meta'][14] = array(
	'_listing_customs_fee' => '500',
);
assert_same( '500', WP_CarDealer_Price::get_listing_fee_value( 14, 'customs_fee' ), 'reads customs meta value' );
assert_contains( '107000000', WP_CarDealer_Price::get_listing_fee_formatted( 14, 'customs_fee' ), 'converts customs fee USD to Toman' );
assert_contains( '107000000', WP_CarDealer_Price::get_listing_fee_plain( 14, 'customs_fee' ), 'converts customs fee plain text to Toman' );
assert_contains( '0', WP_CarDealer_Price::get_listing_fee_plain( 14, 'shipping_fee', true ), 'empty shipping renders as zero in plain text' );

$GLOBALS['wp_cardealer_test_meta'][12] = array(
	'_listing_customs_fee'  => '500',
	'_listing_shipping_fee' => '900',
);
$fees_html = WP_CarDealer_Price::get_listing_fees_html( 12 );
assert_contains( 'هزینه گمرک', $fees_html, 'renders customs label' );
assert_contains( 'هزینه حمل‌ونقل', $fees_html, 'renders shipping label' );
assert_contains( '107000000', $fees_html, 'renders converted customs amount' );
assert_contains( '192600000', $fees_html, 'renders converted shipping amount' );
assert_contains( 'listing-price-extras', $fees_html, 'wraps extras for stacking under the main price' );

$GLOBALS['wp_cardealer_test_meta'][13] = array(
	'_listing_customs_fee'  => '',
	'_listing_shipping_fee' => '900',
);
$partial = WP_CarDealer_Price::get_listing_fees_html( 13 );
assert_contains( 'هزینه حمل‌ونقل', $partial, 'shows shipping when customs is empty' );
assert_contains( 'هزینه گمرک', $partial, 'keeps the empty customs row' );
assert_contains( 'listing-price-extra--customs_fee', $partial, 'renders the empty customs row wrapper' );
assert_contains( '>0</span>', $partial, 'empty customs amount renders as 0' );

assert_same( '', WP_CarDealer_Price::get_listing_fees_html( 0 ), 'no HTML without a listing id' );

$empty = WP_CarDealer_Fields_Manager::inject_listing_fee_fields( array(), WP_CARDEALER_LISTING_PREFIX );
assert_same( array(), $empty, 'does not invent fields when Fields Manager data is empty' );

$dealer = array( array( 'type' => '_dealer_name' ) );
assert_same( $dealer, WP_CarDealer_Fields_Manager::inject_listing_fee_fields( $dealer, '_dealer_' ), 'leaves dealer fields alone' );

$saved = array(
	array( 'type' => '_listing_year', 'name' => 'Year' ),
	array( 'type' => '_listing_price', 'name' => 'Price' ),
	array( 'type' => '_listing_price_prefix', 'name' => 'Prefix' ),
);
$injected = WP_CarDealer_Fields_Manager::inject_listing_fee_fields( $saved, WP_CARDEALER_LISTING_PREFIX );
assert_same( '_listing_year', $injected[0]['type'], 'keeps fields before price' );
assert_same( '_listing_price', $injected[1]['type'], 'keeps the price field' );
assert_same( '_listing_customs_fee', $injected[2]['type'], 'inserts customs fee after price' );
assert_same( '_listing_shipping_fee', $injected[3]['type'], 'inserts shipping fee after customs' );
assert_same( '_listing_price_prefix', $injected[4]['type'], 'keeps fields that followed price' );

$again = WP_CarDealer_Fields_Manager::inject_listing_fee_fields( $injected, WP_CARDEALER_LISTING_PREFIX );
assert_same( 5, count( $again ), 'does not duplicate fee fields on a second pass' );

$no_price = array( array( 'type' => '_listing_year' ) );
$appended = WP_CarDealer_Fields_Manager::inject_listing_fee_fields( $no_price, WP_CARDEALER_LISTING_PREFIX );
assert_same( '_listing_customs_fee', $appended[1]['type'], 'appends fees when price is missing from saved data' );
assert_same( '_listing_shipping_fee', $appended[2]['type'], 'appends shipping after customs when price is missing' );

$simple = WP_CarDealer_Fields_Manager::add_listing_fee_simple_types( array( '_listing_price' ) );
assert_true( in_array( '_listing_customs_fee', $simple, true ), 'registers customs as a simple Fields Manager type' );
assert_true( in_array( '_listing_shipping_fee', $simple, true ), 'registers shipping as a simple Fields Manager type' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
