<?php
/**
 * Standalone tests for the one click speed measurement.
 *
 * Run: php tests/test-speed-probe.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}

function number_format_i18n( $number ) {
	return number_format( (float) $number );
}

require_once dirname( __DIR__ ) . '/includes/class-speed-probe.php';

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
	if ( is_string( $haystack ) && strpos( $haystack, $needle ) !== false ) {
		$passed++;
		echo "PASS  {$message}\n";
		return;
	}
	$failed++;
	echo "FAIL  {$message}\n";
	echo '      missing: ' . $needle . "\n";
	echo '      in:      ' . var_export( $haystack, true ) . "\n";
}

$page = '<html><body><p>سلام</p>' . "\n"
	. '<!-- wpcd-profile total=10926ms peak_mem=321.9MB cache_reads=2,687,179 queries=315 |' . "\n"
	. 'format_price calls=48 time=11.4ms cache_reads=2 reentrant=0 |' . "\n"
	. 'busiest_hooks the_content=1,204 wp_head=88 -->' . "\n"
	. '</body></html>';

$report = WP_CarDealer_Speed_Probe::extract_report( $page );

assert_contains( 'total=10926ms', $report, 'pulls the report out of the page markup' );
assert_contains( 'busiest_hooks the_content=1,204', $report, 'keeps the hook ranking' );
assert_same( false, strpos( $report, "\n" ), 'collapses the report onto a single line' );

$metrics = WP_CarDealer_Speed_Probe::parse_metrics( $report );

assert_same( '10926ms', $metrics['total'], 'reads the total page time' );
assert_same( '2,687,179', $metrics['cache_reads'], 'reads the object cache total' );
assert_same( '48', $metrics['format_price.calls'], 'namespaces metrics under their segment' );
assert_same( '2', $metrics['format_price.cache_reads'], 'keeps segment and request cache reads apart' );

// A page with no report must be reported as such, not silently accepted.
assert_same( '', WP_CarDealer_Speed_Probe::extract_report( '<html><body>hi</body></html>' ), 'a page with no report yields nothing' );
assert_same( '', WP_CarDealer_Speed_Probe::extract_report( '' ), 'an empty response yields nothing' );
assert_same( '', WP_CarDealer_Speed_Probe::extract_report( null ), 'a non string response yields nothing' );
assert_same( array(), WP_CarDealer_Speed_Probe::parse_metrics( '' ), 'an empty report has no metrics' );

assert_contains(
	'گزارشی دریافت نشد',
	WP_CarDealer_Speed_Probe::summarise( array() ),
	'a missing report is explained rather than shown as a pass'
);

// The verdict has to distinguish the two cases that need different fixes.
assert_contains(
	'از قالب یا صفحه‌ساز می‌آید',
	WP_CarDealer_Speed_Probe::summarise(
		array( 'total' => '10926ms', 'cache_reads' => '2,687,179', 'format_price.calls' => '48' )
	),
	'heavy page with light price rendering points away from the price module'
);

assert_contains(
	'تعداد ویجت‌های صفحهٔ اول',
	WP_CarDealer_Speed_Probe::summarise(
		array( 'total' => '10926ms', 'cache_reads' => '900,000', 'format_price.calls' => '90,000' )
	),
	'heavy page with heavy price rendering points at the page layout'
);

assert_contains(
	'سرعت سالم است',
	WP_CarDealer_Speed_Probe::summarise(
		array( 'total' => '910ms', 'cache_reads' => '16,959', 'format_price.calls' => '48' )
	),
	'a fast page is reported as healthy'
);

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
