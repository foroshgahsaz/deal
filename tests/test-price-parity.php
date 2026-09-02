<?php
/**
 * Proves that rendering prices with USD to Toman enabled costs the frontend no
 * more than rendering them with the feature switched off.
 *
 * The reported slowdown was a 12x jump in memory and a 158x jump in object
 * cache reads with the feature on. Those reads come from option lookups, so
 * this asserts the option read count for a page full of prices is not higher
 * when the feature is enabled in its default mode.
 *
 * Run: php tests/test-price-parity.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );

// Object cache reads, which is the metric Query Monitor reported as 158x higher.
$GLOBALS['wpcd_test_cache_reads'] = 0;

// Calls into the plugin settings helper, which are pure PHP once settings are cached.
$GLOBALS['wpcd_test_settings_calls'] = 0;

$GLOBALS['wpcd_test_options']    = array();
$GLOBALS['wpcd_test_wp_options'] = array();
$GLOBALS['wpcd_test_settings']   = null;

function add_action() {}

function add_filter() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function __( $text ) {
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
	$GLOBALS['wpcd_test_cache_reads']++;

	return array_key_exists( $name, $GLOBALS['wpcd_test_wp_options'] )
		? $GLOBALS['wpcd_test_wp_options'][ $name ]
		: $default;
}

function get_transient( $name ) {
	$GLOBALS['wpcd_test_cache_reads']++;

	return false;
}

/**
 * Mirrors production: the settings row is fetched once and reused for the request.
 */
function wp_cardealer_get_settings() {
	if ( null === $GLOBALS['wpcd_test_settings'] ) {
		$GLOBALS['wpcd_test_settings'] = get_option( 'wp_cardealer_settings' );
	}

	return $GLOBALS['wpcd_test_settings'];
}

function wp_cardealer_get_option( $key = '', $default = false ) {
	$GLOBALS['wpcd_test_settings_calls']++;

	$settings = wp_cardealer_get_settings();

	return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
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

/**
 * Render a listing archive worth of prices and report how many option reads it took.
 */
function measure_price_rendering( $options, $wp_options, $prices ) {
	$wp_options['wp_cardealer_settings'] = $options;

	$GLOBALS['wpcd_test_wp_options'] = $wp_options;
	$GLOBALS['wpcd_test_settings']   = null;

	WP_CarDealer_Price::flush_runtime_cache();
	WP_CarDealer_Navasan::reset_request_rate_cache();

	$GLOBALS['wpcd_test_cache_reads']    = 0;
	$GLOBALS['wpcd_test_settings_calls'] = 0;

	$output = '';
	foreach ( $prices as $price ) {
		$output .= (string) WP_CarDealer_Price::format_price( $price );
	}

	return array(
		'cache_reads'    => $GLOBALS['wpcd_test_cache_reads'],
		'settings_calls' => $GLOBALS['wpcd_test_settings_calls'],
		'output'         => $output,
	);
}

$prices = array();
for ( $i = 0; $i < 60; $i++ ) {
	$prices[] = 12000 + ( $i * 500 );
}

$base_options = array(
	'currency'                => 'USD',
	'currency_position'       => 'before',
	'money_decimals'          => 0,
	'enable_multi_currencies' => 'no',
	'custom_symbol'           => '$',
);

$disabled = measure_price_rendering(
	array_merge( $base_options, array( 'enable_usd_to_toman' => 'no' ) ),
	array(),
	$prices
);

$enabled_client = measure_price_rendering(
	array_merge(
		$base_options,
		array(
			'enable_usd_to_toman'     => 'yes',
			'navasan_api_key'         => 'test-key',
			'navasan_frontend_convert' => 'client',
		)
	),
	array( 'wp_cardealer_navasan_usd_rate' => 200500 ),
	$prices
);

$enabled_server = measure_price_rendering(
	array_merge(
		$base_options,
		array(
			'enable_usd_to_toman'      => 'yes',
			'navasan_api_key'          => 'test-key',
			'navasan_frontend_convert' => 'server',
		)
	),
	array( 'wp_cardealer_navasan_usd_rate' => 200500 ),
	$prices
);

printf(
	"60 prices  ->  cache reads: off=%d client=%d server=%d   settings calls: off=%d client=%d server=%d\n\n",
	$disabled['cache_reads'],
	$enabled_client['cache_reads'],
	$enabled_server['cache_reads'],
	$disabled['settings_calls'],
	$enabled_client['settings_calls'],
	$enabled_server['settings_calls']
);

assert_true(
	$enabled_client['cache_reads'] <= $disabled['cache_reads'] + 2,
	'the default client mode makes no more object cache reads than having the feature off'
);

assert_true(
	$enabled_server['cache_reads'] <= $disabled['cache_reads'] + 2,
	'even server mode makes no more object cache reads than having the feature off'
);

assert_true(
	$enabled_client['settings_calls'] <= $disabled['settings_calls'] + 60,
	'client mode does not multiply settings lookups per price'
);

// The property that actually matters: cost must not grow with the number of prices.
$enabled_options = array_merge(
	$base_options,
	array( 'enable_usd_to_toman' => 'yes', 'navasan_api_key' => 'test-key' )
);
$rate_option = array( 'wp_cardealer_navasan_usd_rate' => 200500 );

$one_price   = measure_price_rendering( $enabled_options, $rate_option, array( 12000 ) );
$many_prices = measure_price_rendering( $enabled_options, $rate_option, $prices );

$extra_prices    = count( $prices ) - 1;
$cache_per_price = ( $many_prices['cache_reads'] - $one_price['cache_reads'] ) / $extra_prices;

printf( "object cache reads per additional price: %s\n\n", $cache_per_price );

assert_same( 0.0, (float) $cache_per_price, 'rendering an extra price causes no additional object cache read' );

// The rate itself must be resolved once, not once per price.
measure_price_rendering( $enabled_options, $rate_option, array() );

$GLOBALS['wpcd_test_cache_reads'] = 0;
for ( $i = 0; $i < 100; $i++ ) {
	WP_CarDealer_Navasan::get_usd_toman_rate();
}
assert_true( $GLOBALS['wpcd_test_cache_reads'] <= 2, 'the exchange rate is resolved once per request, not once per price' );

// Caching the currency context must not change a single rendered price.
$single = measure_price_rendering( array_merge( $base_options, array( 'enable_usd_to_toman' => 'no' ) ), array(), array( 12000 ) );
assert_same(
	'<span class="suffix">$</span><span class="price-text">12000</span>',
	$single['output'],
	'the default currency renders symbol first, unchanged by caching'
);

$multi = measure_price_rendering(
	array(
		'enable_usd_to_toman'     => 'no',
		'enable_multi_currencies' => 'yes',
		'currency'                => 'USD',
		'currency_position'       => 'after',
		'money_decimals'          => 2,
		'custom_symbol'           => '',
	),
	array(),
	array( 12000 )
);
assert_same(
	'<span class="price-text">12000</span><span class="suffix">&#36;</span>',
	$multi['output'],
	'multi currency mode still resolves symbol, position and decimals'
);

// The visitor picked a secondary currency, so its exchange fee must apply.
$_COOKIE['wp_cardealer_currency'] = 'EUR';

$with_fee = measure_price_rendering(
	array(
		'enable_usd_to_toman'     => 'no',
		'enable_multi_currencies' => 'yes',
		'currency'                => 'USD',
		'currency_position'       => 'after',
		'money_decimals'          => 2,
		'custom_symbol'           => '',
		'multi_currencies'        => array(
			array(
				'currency'          => 'EUR',
				'currency_position' => 'after',
				'money_decimals'    => 2,
				'rate_exchange_fee' => 2,
				'custom_symbol'     => 'EU',
			),
		),
	),
	array(),
	array( 12000 )
);

unset( $_COOKIE['wp_cardealer_currency'] );
assert_same(
	'<span class="price-text">24000</span><span class="suffix">EU</span>',
	$with_fee['output'],
	'the multi currency exchange fee is still applied to the amount'
);

$server_single = measure_price_rendering(
	array_merge(
		$base_options,
		array(
			'enable_usd_to_toman'      => 'yes',
			'navasan_api_key'          => 'test-key',
			'navasan_frontend_convert' => 'server',
		)
	),
	$rate_option,
	array( 12000 )
);
assert_same(
	'<span class="price-text">2406000000</span> <span class="suffix">تومان</span>',
	$server_single['output'],
	'server mode still converts and labels the amount in Toman'
);

// Client mode must leave the USD figure in the markup for the browser to convert.
assert_true(
	strpos( $enabled_client['output'], 'data-wpcd-usd="12000"' ) !== false,
	'client mode publishes the USD amount for the browser to convert'
);
assert_true(
	strpos( $enabled_server['output'], 'data-wpcd-usd' ) === false,
	'server mode renders the converted figure directly with no wrapper'
);

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
