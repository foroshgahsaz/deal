<?php
/**
 * Standalone tests for flagging listings that distort the price filter.
 *
 * Run: php tests/test-price-audit.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

require_once dirname( __DIR__ ) . '/includes/class-price-audit.php';

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

$ceiling = 100000000.0;

// The row actually found on the affected site, alongside believable ones.
$rows = array(
	array( 'ID' => '412', 'post_title' => 'Toyota Land Cruiser', 'post_status' => 'publish', 'price' => '2700000000' ),
	array( 'ID' => '77',  'post_title' => 'Porsche 911',         'post_status' => 'publish', 'price' => '185000' ),
	array( 'ID' => '90',  'post_title' => '',                    'post_status' => 'draft',   'price' => '42000' ),
);

$described = WP_CarDealer_Price_Audit::describe( $rows, $ceiling );

assert_same( 3, count( $described ), 'every row is described' );
assert_same( 412, $described[0]['id'], 'the identifier is returned as an integer' );
assert_same( 2700000000.0, $described[0]['price'], 'the price is returned as a number' );
assert_same( true, $described[0]['flagged'], 'a price above the ceiling is flagged' );
assert_same( false, $described[1]['flagged'], 'a believable price is not flagged' );
assert_same( 'publish', $described[1]['status'], 'the status is preserved so a draft can be told apart' );
assert_same( '(بدون عنوان)', $described[2]['title'], 'a listing with no title is still identifiable' );

// A price exactly at the ceiling is acceptable, matching the clamp.
$edge = WP_CarDealer_Price_Audit::describe(
	array( array( 'ID' => 1, 'post_title' => 'Edge', 'post_status' => 'publish', 'price' => $ceiling ) ),
	$ceiling
);
assert_same( false, $edge[0]['flagged'], 'a price exactly at the ceiling is not flagged' );

// With no ceiling nothing is flagged.
$unbounded = WP_CarDealer_Price_Audit::describe( $rows, 0 );
assert_same( false, $unbounded[0]['flagged'], 'nothing is flagged when there is no ceiling' );

// Malformed input must not produce warnings or bogus entries.
assert_same( array(), WP_CarDealer_Price_Audit::describe( 'not an array', $ceiling ), 'a malformed result set yields nothing' );
assert_same( array(), WP_CarDealer_Price_Audit::describe( array( 'nonsense' ), $ceiling ), 'rows that are not rows are skipped' );
assert_same( array(), WP_CarDealer_Price_Audit::describe( array( array( 'post_title' => 'no id' ) ), $ceiling ), 'a row with no identifier is skipped' );

$missing_price = WP_CarDealer_Price_Audit::describe(
	array( array( 'ID' => 5, 'post_title' => 'No price', 'post_status' => 'publish' ) ),
	$ceiling
);
assert_same( 0.0, $missing_price[0]['price'], 'a missing price reads as zero' );
assert_same( false, $missing_price[0]['flagged'], 'a missing price is not flagged' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
