<?php
/**
 * Standalone tests for caching template resolution.
 *
 * Measurement on the affected site showed a filter widget rendered about
 * 115,000 times on one homepage. Each render resolved its template, and each
 * resolution stats the child theme, the parent theme and the plugin, which
 * added up to well over half a million filesystem calls.
 *
 * Run: php tests/test-template-loader-cache.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CARDEALER_PLUGIN_DIR', sys_get_temp_dir() . '/wpcd-loader-test/plugin/' );

$GLOBALS['wpcd_test_locate_template_calls'] = 0;
$GLOBALS['wpcd_test_theme_dir']             = sys_get_temp_dir() . '/wpcd-loader-test/theme';
$GLOBALS['wpcd_test_child_dir']             = sys_get_temp_dir() . '/wpcd-loader-test/child';

// A real plugin template so resolution succeeds without touching the theme.
@mkdir( WP_CARDEALER_PLUGIN_DIR . 'templates/widgets/filter-fields', 0777, true );
file_put_contents( WP_CARDEALER_PLUGIN_DIR . 'templates/widgets/filter-fields/price_range_slider.php', '<?php // test' );
@mkdir( $GLOBALS['wpcd_test_theme_dir'], 0777, true );
@mkdir( $GLOBALS['wpcd_test_child_dir'], 0777, true );

function add_filter() {}

function add_action() {}

function apply_filters( $tag, $value ) {
	return $value;
}

function locate_template( $names ) {
	$GLOBALS['wpcd_test_locate_template_calls']++;

	return '';
}

function get_stylesheet_directory() {
	return $GLOBALS['wpcd_test_child_dir'];
}

function get_template_directory() {
	return $GLOBALS['wpcd_test_theme_dir'];
}

require_once dirname( __DIR__ ) . '/includes/class-template-loader.php';

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

WP_CarDealer_Template_Loader::flush_located_cache();
$GLOBALS['wpcd_test_locate_template_calls'] = 0;

$first = WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/price_range_slider' );

assert_true( is_string( $first ) && '' !== $first, 'the template still resolves to a path' );
assert_same( 1, $GLOBALS['wpcd_test_locate_template_calls'], 'the first resolution searches the theme' );

// The pathological case from the measurement.
for ( $i = 0; $i < 115572; $i++ ) {
	$repeat = WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/price_range_slider' );
}

assert_same( 1, $GLOBALS['wpcd_test_locate_template_calls'], '115,572 further resolutions touch the filesystem zero more times' );
assert_same( $first, $repeat, 'the cached path is the same path' );

// Distinct templates must not collide.
file_put_contents( WP_CARDEALER_PLUGIN_DIR . 'templates/widgets/filter-fields/range_slider.php', '<?php // test' );
$other = WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/range_slider' );

assert_true( $other !== $first, 'a different template resolves to a different path' );
assert_same( 2, $GLOBALS['wpcd_test_locate_template_calls'], 'a template not seen before is resolved once' );

// A missing template must still raise, and must not be cached as found.
$threw = false;
try {
	WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/does-not-exist' );
} catch ( Exception $e ) {
	$threw = true;
}
assert_true( $threw, 'a missing template still throws' );

$threw_again = false;
try {
	WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/does-not-exist' );
} catch ( Exception $e ) {
	$threw_again = true;
}
assert_true( $threw_again, 'a missing template is not cached as if it were found' );

// Switching theme changes which file wins, so the cache must be dropped.
WP_CarDealer_Template_Loader::flush_located_cache();
$GLOBALS['wpcd_test_locate_template_calls'] = 0;
WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/price_range_slider' );

assert_same( 1, $GLOBALS['wpcd_test_locate_template_calls'], 'flushing the cache forces a fresh resolution' );

// Plugin-only resolution must ignore theme overrides.
@mkdir( $GLOBALS['wpcd_test_theme_dir'] . '/wp-cardealer/widgets/filter-fields', 0777, true );
file_put_contents(
	$GLOBALS['wpcd_test_theme_dir'] . '/wp-cardealer/widgets/filter-fields/price_range_slider.php',
	'<?php // theme override'
);
WP_CarDealer_Template_Loader::flush_located_cache();
$GLOBALS['wpcd_test_locate_template_calls'] = 0;

$theme_override = WP_CarDealer_Template_Loader::locate( 'widgets/filter-fields/price_range_slider' );
$plugin_only    = WP_CarDealer_Template_Loader::locate_plugin( 'widgets/filter-fields/price_range_slider' );

assert_true( $theme_override !== $plugin_only, 'the theme override wins in locate()' );
assert_same(
	WP_CARDEALER_PLUGIN_DIR . 'templates/widgets/filter-fields/price_range_slider.php',
	$plugin_only,
	'locate_plugin() always returns the plugin file'
);
assert_same( 1, $GLOBALS['wpcd_test_locate_template_calls'], 'locate_plugin() never searches the theme' );

// Cleanup.
array_map( 'unlink', glob( WP_CARDEALER_PLUGIN_DIR . 'templates/widgets/filter-fields/*.php' ) );
@unlink( $GLOBALS['wpcd_test_theme_dir'] . '/wp-cardealer/widgets/filter-fields/price_range_slider.php' );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
