<?php
/**
 * Standalone tests for reusing rendered price markup within a request.
 *
 * Real measurement on the reported site showed format_price() running 231,145
 * times on a single homepage, roughly the square of the listing count. The
 * amount is the only input that changes, so identical amounts must be rendered
 * once and reused.
 *
 * Run: php tests/test-price-memoization.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

// Profiling is on so the reuse counter is observable.
$_GET['wpcd_profile'] = '1';

$GLOBALS['wpcd_test_settings'] = null;
$GLOBALS['wpcd_test_options']  = array(
	'currency'                => 'USD',
	'currency_position'       => 'before',
	'money_decimals'          => 0,
	'enable_multi_currencies' => 'no',
	'custom_symbol'           => '$',
	'enable_usd_to_toman'     => 'no',
);

function add_action() {}

function add_filter() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function __( $text ) {
	return $text;
}

function esc_html( $text ) {
	return $text;
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function is_admin() {
	return false;
}

function wp_doing_ajax() {
	return false;
}

function absint( $value ) {
	return abs( (int) $value );
}

function get_option( $name, $default = false ) {
	return $default;
}

function get_transient( $name ) {
	return false;
}

function wp_cardealer_get_option( $key = '', $default = false ) {
	return isset( $GLOBALS['wpcd_test_options'][ $key ] ) && '' !== $GLOBALS['wpcd_test_options'][ $key ]
		? $GLOBALS['wpcd_test_options'][ $key ]
		: $default;
}

require_once dirname( __DIR__ ) . '/includes/class-profiler.php';
require_once dirname( __DIR__ ) . '/includes/class-mixes.php';
require_once dirname( __DIR__ ) . '/includes/class-price.php';
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

function assert_true( $actual, $message ) {
	assert_same( true, $actual, $message );
}

assert_true( WP_CarDealer_Profiler::is_enabled(), 'profiling is active so reuse is observable' );

// The pathological case: the same amount asked for over and over.
WP_CarDealer_Price::flush_runtime_cache();

$first = WP_CarDealer_Price::format_price( 12000 );
for ( $i = 0; $i < 999; $i++ ) {
	$repeat = WP_CarDealer_Price::format_price( 12000 );
}

assert_same( 1000, WP_CarDealer_Profiler::get_calls( 'format_price' ), 'every request for the amount is still counted' );
assert_same( 999, WP_CarDealer_Profiler::get_calls( 'format_price_cache_hit' ), 'the amount is rendered once and reused after that' );
assert_same( $first, $repeat, 'the reused markup is identical to the first render' );

// Distinct amounts must not collide.
WP_CarDealer_Price::flush_runtime_cache();

$twelve  = WP_CarDealer_Price::format_price( 12000 );
$sixteen = WP_CarDealer_Price::format_price( 16000 );

assert_true( $twelve !== $sixteen, 'different amounts render differently' );
assert_same( '<span class="suffix">$</span><span class="price-text">16000</span>', $sixteen, 'a distinct amount renders correctly' );

// The formatting flags are part of the identity, not just the amount.
WP_CarDealer_Price::flush_runtime_cache();

assert_same( false, WP_CarDealer_Price::format_price( 0 ), 'an empty amount is hidden by default' );
assert_same( '<span class="suffix">$</span><span class="price-text">0</span>', WP_CarDealer_Price::format_price( 0, true ), 'the same amount with show_null set renders zero' );
assert_same( false, WP_CarDealer_Price::format_price( 0 ), 'the hidden variant is still hidden after the visible one was cached' );

// Reuse must not survive a settings change.
WP_CarDealer_Price::flush_runtime_cache();
WP_CarDealer_Price::format_price( 12000 );

$GLOBALS['wpcd_test_options']['custom_symbol'] = 'EU';
WP_CarDealer_Price::flush_runtime_cache();

assert_same(
	'<span class="suffix">EU</span><span class="price-text">12000</span>',
	WP_CarDealer_Price::format_price( 12000 ),
	'changing settings invalidates previously rendered markup'
);

$GLOBALS['wpcd_test_options']['custom_symbol'] = '$';

// Unbounded growth would trade a speed problem for a memory problem.
WP_CarDealer_Price::flush_runtime_cache();

for ( $i = 0; $i < WP_CarDealer_Price::PRICE_CACHE_LIMIT + 200; $i++ ) {
	WP_CarDealer_Price::format_price( 1000 + $i );
}

$hits_before = WP_CarDealer_Profiler::get_calls( 'format_price_cache_hit' );
WP_CarDealer_Price::format_price( 1000 );
assert_same( $hits_before + 1, WP_CarDealer_Profiler::get_calls( 'format_price_cache_hit' ), 'amounts cached before the limit are still reused' );

WP_CarDealer_Price::format_price( 1000 + WP_CarDealer_Price::PRICE_CACHE_LIMIT + 100 );
assert_same( $hits_before + 1, WP_CarDealer_Profiler::get_calls( 'format_price_cache_hit' ), 'the cache stops growing once the limit is reached' );

// Non scalar input must not be keyed or cached.
assert_same( false, WP_CarDealer_Price::format_price( array( 12000 ) ), 'an array amount is rejected rather than cached' );

// Caller sampling has to name the file that drove the calls.
$callers = WP_CarDealer_Profiler::get_top_callers( 'format_price' );
assert_true( ! empty( $callers ), 'the profiler records where the calls came from' );
assert_true(
	strpos( implode( ' ', array_keys( $callers ) ), 'test-price-memoization.php' ) !== false,
	'the sampled caller points at the file that made the calls'
);

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
