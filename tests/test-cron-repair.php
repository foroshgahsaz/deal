<?php
/**
 * Standalone tests for the cron option repair.
 *
 * Run: php tests/test-cron-repair.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}

function apply_filters( $tag, $value ) {
	return $value;
}

require_once dirname( __DIR__ ) . '/includes/class-cron-repair.php';

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

$navasan = 'wp_cardealer_navasan_refresh_rate';

// A realistic bloated cron array: our hook stacked across many timestamps,
// mixed with events owned by WordPress core and other plugins.
$cron = array();
for ( $i = 0; $i < 500; $i++ ) {
	$cron[ 1787000000 + $i ] = array(
		$navasan => array(
			'40cd750bba9870f18aada2478b24840a' => array(
				'schedule' => false,
				'args'     => array(),
			),
		),
	);
}
$cron[1787900000] = array(
	'wp_version_check'                     => array( 'abc' => array( 'schedule' => 'twicedaily', 'args' => array() ) ),
	$navasan                               => array( 'def' => array( 'schedule' => 'hourly', 'args' => array() ) ),
	'wp_cardealer_check_for_expired_listings' => array( 'ghi' => array( 'schedule' => 'hourly', 'args' => array() ) ),
);
$cron['version'] = 2;

$result = WP_CarDealer_Cron_Repair::prune_cron_array( $cron, array( $navasan ) );

assert_same( 501, $result['removed_events'], 'removes every stacked entry for the managed hook' );
assert_same( 500, $result['removed_timestamps'], 'drops timestamps that become empty' );
assert_same( 2, $result['remaining_events'], 'keeps events owned by core and other features' );

assert_same( true, isset( $result['cron'][1787900000]['wp_version_check'] ), 'core events survive the prune' );
assert_same(
	true,
	isset( $result['cron'][1787900000]['wp_cardealer_check_for_expired_listings'] ),
	'other plugin events survive the prune'
);
assert_same( false, isset( $result['cron'][1787900000][ $navasan ] ), 'the managed hook is gone from a shared timestamp' );
assert_same( false, isset( $result['cron'][1787000000] ), 'a timestamp holding only the managed hook is removed' );
assert_same( 2, $result['cron']['version'], 'the cron array version marker is preserved' );

// Nothing to do is a valid outcome and must not corrupt the array.
$clean = array(
	1787900000 => array( 'wp_version_check' => array( 'abc' => array( 'schedule' => 'twicedaily', 'args' => array() ) ) ),
	'version'  => 2,
);
$result = WP_CarDealer_Cron_Repair::prune_cron_array( $clean, array( $navasan ) );

assert_same( 0, $result['removed_events'], 'a healthy cron array loses nothing' );
assert_same( $clean, $result['cron'], 'a healthy cron array is returned untouched' );

// Defensive handling of malformed input.
$result = WP_CarDealer_Cron_Repair::prune_cron_array( 'not an array', array( $navasan ) );
assert_same( array(), $result['cron'], 'a malformed cron option yields an empty array' );
assert_same( 0, $result['removed_events'], 'a malformed cron option removes nothing' );

$result = WP_CarDealer_Cron_Repair::prune_cron_array( array( 1787900000 => 'corrupt', 'version' => 2 ), array( $navasan ) );
assert_same( 'corrupt', $result['cron'][1787900000], 'unexpected entries are left alone rather than dropped' );

// Only declared hooks are ever pruned.
$result = WP_CarDealer_Cron_Repair::prune_cron_array( $cron, array() );
assert_same( 0, $result['removed_events'], 'an empty hook list prunes nothing' );
assert_same( 503, $result['remaining_events'], 'every event is accounted for when nothing is pruned' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
