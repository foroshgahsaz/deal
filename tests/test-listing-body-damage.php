<?php
/**
 * Standalone tests for listing body damage helpers.
 *
 * Run: php tests/test-listing-body-damage.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CARDEALER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

function get_post_meta( $post_id, $key, $single = true ) {
	if ( ! isset( $GLOBALS['wp_cardealer_test_meta'][ $post_id ][ $key ] ) ) {
		return '';
	}
	return $GLOBALS['wp_cardealer_test_meta'][ $post_id ][ $key ];
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
	return esc_html( $text );
}

function esc_html_e( $text ) {
	echo esc_html( $text );
}

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function add_action() {}
function add_filter() {}

require_once dirname( __DIR__ ) . '/includes/class-listing-body-damage.php';
require_once dirname( __DIR__ ) . '/includes/custom-fields/class-fields-manager.php';

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
}

assert_same( 11, count( WP_CarDealer_Listing_Body_Damage::get_parts() ), 'defines eleven metal body parts' );
assert_same( 'painted', WP_CarDealer_Listing_Body_Damage::sanitize_status( 'painted' ), 'keeps painted status' );
assert_same( 'replaced', WP_CarDealer_Listing_Body_Damage::sanitize_status( 'replaced' ), 'keeps replaced status' );
assert_same( '', WP_CarDealer_Listing_Body_Damage::sanitize_status( 'broken' ), 'rejects unknown status' );

$GLOBALS['wp_cardealer_test_meta'][20] = array(
	'_listing_body_damage' => wp_json_encode(
		array(
			'right_front_fender' => 'replaced',
			'trunk'              => 'painted',
		)
	),
);

$map = WP_CarDealer_Listing_Body_Damage::get_damage_map( 20 );
assert_same( 'replaced', $map['right_front_fender'], 'reads replaced part from meta' );
assert_same( 'painted', $map['trunk'], 'reads painted part from meta' );
assert_same( '', $map['hood'], 'empty parts stay healthy' );

$marked = WP_CarDealer_Listing_Body_Damage::get_marked_parts( $map );
assert_same( 2, count( $marked ), 'returns two marked parts' );
assert_true( WP_CarDealer_Listing_Body_Damage::has_damage( 20 ), 'detects damage on listing' );

$GLOBALS['wp_cardealer_test_meta'][21] = array(
	'_listing_body_damage' => wp_json_encode( array() ),
);
assert_same( false, WP_CarDealer_Listing_Body_Damage::has_damage( 21 ), 'detects healthy body' );

$html = WP_CarDealer_Listing_Body_Damage::get_html( 20 );
assert_contains( 'listing-body-damage-diagram', $html, 'renders diagram wrapper' );
assert_contains( 'is-replaced', $html, 'applies replaced class in svg' );
assert_contains( 'is-painted', $html, 'applies painted class in svg' );
assert_contains( 'گلگیر جلو راست', $html, 'lists replaced part in persian' );
assert_contains( 'درب صندوق', $html, 'lists painted part in persian' );

$healthy_html = WP_CarDealer_Listing_Body_Damage::get_html( 21 );
assert_contains( 'بدون رنگ‌شدگی و تعویض — بدنه سالم', $healthy_html, 'shows healthy body message' );

$injected = WP_CarDealer_Fields_Manager::inject_listing_body_damage_fields(
	array(
		array( 'type' => '_listing_location', 'id' => '_listing_location' ),
	),
	WP_CARDEALER_LISTING_PREFIX
);
assert_same( 'heading', $injected[1]['type'], 'inserts body damage heading after location' );
assert_same( '_listing_heading_body_damage', $injected[1]['id'], 'heading uses body damage tab id' );
assert_same( '_listing_body_damage', $injected[2]['type'], 'inserts body damage field after heading' );
assert_same( 'no', $injected[2]['show_in_submit_form'], 'body damage is admin only' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
