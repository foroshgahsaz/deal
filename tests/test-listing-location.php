<?php
/**
 * Standalone tests for listing location helpers.
 *
 * Run: php tests/test-listing-location.php
 */

define( 'ABSPATH', __DIR__ . '/' );

class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public $parent;

	public function __construct( $term_id, $name, $slug = '', $parent = 0 ) {
		$this->term_id = $term_id;
		$this->name    = $name;
		$this->slug    = $slug ? $slug : $name;
		$this->parent  = $parent;
	}
}

$GLOBALS['wp_cardealer_location_terms'] = array();
$GLOBALS['wp_cardealer_post_terms']     = array();
$GLOBALS['wp_cardealer_ancestors']      = array();

function get_ancestors( $term_id, $taxonomy ) {
	unset( $taxonomy );
	$term_id = (int) $term_id;
	return isset( $GLOBALS['wp_cardealer_ancestors'][ $term_id ] )
		? $GLOBALS['wp_cardealer_ancestors'][ $term_id ]
		: array();
}

function get_term( $term_id, $taxonomy ) {
	unset( $taxonomy );
	$term_id = (int) $term_id;
	return isset( $GLOBALS['wp_cardealer_location_terms'][ $term_id ] )
		? $GLOBALS['wp_cardealer_location_terms'][ $term_id ]
		: null;
}

function wp_get_post_terms( $post_id, $taxonomy, $args = array() ) {
	unset( $taxonomy, $args );
	return isset( $GLOBALS['wp_cardealer_post_terms'][ $post_id ] )
		? $GLOBALS['wp_cardealer_post_terms'][ $post_id ]
		: array();
}

function get_term_link( $term ) {
	return 'https://example.test/listing-location/' . $term->slug . '/';
}

function esc_url( $url ) {
	return $url;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function absint( $value ) {
	return abs( (int) $value );
}

function is_wp_error( $thing ) {
	return false;
}

require_once dirname( __DIR__ ) . '/includes/class-listing-location.php';

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
	if ( strpos( (string) $haystack, (string) $needle ) !== false ) {
		$passed++;
		echo "PASS  {$message}\n";
		return;
	}
	$failed++;
	echo "FAIL  {$message}\n";
}

$GLOBALS['wp_cardealer_location_terms'] = array(
	1 => new WP_Term( 1, 'تهران', 'tehran', 0 ),
	2 => new WP_Term( 2, 'ورامین', 'varamin', 1 ),
);
$GLOBALS['wp_cardealer_ancestors'] = array(
	2 => array( 1 ),
);
$GLOBALS['wp_cardealer_post_terms'][99] = array(
	$GLOBALS['wp_cardealer_location_terms'][2],
);

assert_same( 'ورامین', WP_CarDealer_Listing_Location::get_leaf_name( 99 ), 'returns deepest assigned term name' );
assert_same( 'تهران › ورامین', WP_CarDealer_Listing_Location::get_path_text( 99 ), 'builds full location path' );
assert_contains( 'tehran', WP_CarDealer_Listing_Location::get_path_html( 99 ), 'path html links include parent slug' );
assert_same( '', WP_CarDealer_Listing_Location::get_leaf_name( 100 ), 'empty listing returns blank leaf' );

$chain = WP_CarDealer_Listing_Location::get_term_chain( 99 );
assert_same( 2, count( $chain ), 'term chain includes parent and child' );
assert_same( 'تهران', $chain[0]->name, 'chain starts at parent city' );
assert_same( 'ورامین', $chain[1]->name, 'chain ends at child city' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
