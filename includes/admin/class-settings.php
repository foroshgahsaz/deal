<?php
/**
 * Settings
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Settings {

	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = 'wp_cardealer_settings';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	protected $option_metabox = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 1.0
	 */
	public function __construct() {
	
		add_action( 'admin_menu', array( $this, 'admin_menu' ) , 10 );

		add_action( 'admin_init', array( $this, 'init' ) );

		//Custom CMB2 Settings Fields
		add_action( 'cmb2_render_wp_cardealer_title', 'wp_cardealer_title_callback', 10, 5 );
		add_action( 'cmb2_render_wp_cardealer_hidden', 'wp_cardealer_hidden_callback', 10, 5 );

		add_action( "cmb2_save_options-page_fields", array( $this, 'settings_notices' ), 10, 3 );


		add_action( 'cmb2_render_api_keys', 'wp_cardealer_api_keys_callback', 10, 5 );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-wp_cardealer_listings_page_listing-settings", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	public function admin_menu() {
		//Settings
	 	$wp_cardealer_settings_page = add_submenu_page( 'edit.php?post_type=listing', __( 'Settings', 'wp-cardealer' ), __( 'Settings', 'wp-cardealer' ), 'manage_options', 'listing-settings',
	 		array( $this, 'admin_page_display' ) );
	}

	/**
	 * Register our setting to WP
	 * @since  1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Retrieve settings tabs
	 *
	 * @since 1.0
	 * @return array $tabs
	 */
	public function wp_cardealer_get_settings_tabs() {
		$tabs             	  = array();
		$tabs['general']  	  = __( 'General', 'wp-cardealer' );
		$tabs['listing_submission']   = __( 'Listing Submission', 'wp-cardealer' );
		$tabs['pages']   = __( 'Pages', 'wp-cardealer' );
		$tabs['user_register_settings']   = __( 'User Register Settings', 'wp-cardealer' );
		$tabs['compare_settings'] = __( 'Compare Settings', 'wp-cardealer' );
		$tabs['review_settings'] = __( 'Review Settings', 'wp-cardealer' );
	 	$tabs['api_settings'] = __( 'Social API', 'wp-cardealer' );
	 	$tabs['recaptcha_api_settings'] = __( 'ReCaptcha API', 'wp-cardealer' );
	 	$tabs['email_notification'] = __( 'Email Notification', 'wp-cardealer' );

		return apply_filters( 'wp_cardealer_settings_tabs', $tabs );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  1.0
	 */
	public function admin_page_display() {

		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->wp_cardealer_get_settings_tabs() ) ? $_GET['tab'] : 'general';
		
		?>

		<div class="wrap wp_cardealer_settings_page cmb2_options_page <?php echo $this->key; ?>">
			<h2></h2>
			<div class="nav-tab-wrapper">
				<?php
				foreach ( $this->wp_cardealer_get_settings_tabs() as $tab_id => $tab_name ) {

					$tab_url = esc_url( add_query_arg( array(
						'settings-updated' => false,
						'tab'              => $tab_id
					) ) );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

					echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );

					echo '</a>';
				}
				?>
			</div>
			
			<?php cmb2_metabox_form( $this->wp_cardealer_settings( $active_tab ), $this->key ); ?>

		</div><!-- .wrap -->

		<?php
	}

	/**
	 * Define General Settings Metabox and field configurations.
	 *
	 * Filters are provided for each settings section to allow add-ons and other plugins to add their own settings
	 *
	 * @param $active_tab active tab settings; null returns full array
	 *
	 * @return array
	 */
	public function wp_cardealer_settings( $active_tab ) {

		$pages = wp_cardealer_cmb2_get_page_options( array(
			'post_type'   => 'page',
			'numberposts' => - 1
		) );

		$countries = array( '' => __('All Countries', 'wp-cardealer') );
		$countries = array_merge( $countries, WP_CarDealer_Mixes::get_all_countries() );
		
		// currency
		$currencies = WP_CarDealer_Price::get_currencies();
		$currencies_opts = [];
		foreach ($currencies as $k => $wc_currency) {
			$currencies_opts[$k] = $k.' - '.$wc_currency.'('.WP_CarDealer_Price::currency_symbol($k).')';
		}
		$wp_cardealer_settings = array();
		// General
		$wp_cardealer_settings['general'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'General Settings', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_general', array(
					array(
						'name' => __( 'General Settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_1',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Number listings per page', 'wp-cardealer' ),
						'desc'    => __( 'Number of listings to display per page.', 'wp-cardealer' ),
						'id'      => 'number_listings_per_page',
						'type'    => 'text',
						'default' => '10',
					),
					array(
						'name'    => __( 'Number deaers per page', 'wp-cardealer' ),
						'desc'    => __( 'Number of dealers to display per page.', 'wp-cardealer' ),
						'id'      => 'number_dealers_per_page',
						'type'    => 'text',
						'default' => '10',
					),
					array(
						'name' => __( 'Currency Settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_2',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Enable Multiple Currencies', 'wp-cardealer' ),
						'id'      => 'enable_multi_currencies',
						'type'    => 'select',
						'options' => array(
							'yes' 	=> __( 'Yes', 'wp-cardealer' ),
							'no'   => __( 'No', 'wp-cardealer' ),
						),
						'default' => 'no',
					),
					array(
						'name'            => __( 'Exchangerate API Key', 'wp-cardealer' ),
						'id'              => 'exchangerate_api_key',
						'type'            => 'text',
						'desc' => sprintf(__( 'Acquire an API key from the <a href="%s" target="_blank">Exchange Rate API</a>', 'wp-cardealer' ), 'https://www.exchangerate-api.com/docs/php-currency-api'),
					),
					array(
						'name'    => __( 'Currency', 'wp-cardealer' ),
						'desc'    => __( 'Choose a currency.', 'wp-cardealer' ),
						'id'      => 'currency',
						'type'    => 'pw_select',
						'options' => $currencies_opts,
						'default' => 'USD',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'            => __( 'Custom symbol', 'wp-cardealer' ),
						'id'              => 'custom_symbol',
						'type'            => 'text_small',
						'attributes'        => array(
		                    'placeholder' => __( 'eg: CAD $', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'    => __( 'Currency Position', 'wp-cardealer' ),
						'desc'    => 'Choose the position of the currency sign.',
						'id'      => 'currency_position',
						'type'    => 'pw_select',
						'options' => array(
							'before' => __( 'Before - $10', 'wp-cardealer' ),
							'after'  => __( 'After - 10$', 'wp-cardealer' ),
							'before_space' => __( 'Before with space - $ 10', 'wp-cardealer' ),
							'after_space'  => __( 'After with space - 10 $', 'wp-cardealer' ),
						),
						'default' => 'before',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'Number of decimals', 'wp-cardealer' ),
						'desc'    => __( 'This sets the number of decimal points shown in displayed prices.', 'wp-cardealer' ),
						'id'      => 'money_decimals',
						'type'    => 'text_small',
						'attributes' 	    => array(
							'type' 				=> 'number',
							'min'				=> 0,
							'pattern' 			=> '\d*',
						)
					),
					array(
						'name'            => __( 'Decimal Separator', 'wp-cardealer' ),
						'desc'            => __( 'The symbol (usually , or .) to separate decimal points', 'wp-cardealer' ),
						'id'              => 'money_dec_point',
						'type'            => 'text_small',
						// 'default' 		=> '.',
						'attributes'        => array(
		                    'placeholder' => '.',
		                ),
					),
					array(
						'name'    => __( 'Thousands Separator', 'wp-cardealer' ),
						'desc'    => __( 'If you need space, enter &nbsp;', 'wp-cardealer' ),
						'id'      => 'money_thousands_separator',
						'type'    => 'text_small',
						'attributes'        => array(
		                    'placeholder' => ',',
		                ),
					),
					/////
					array(
						'name'              => __( 'More Currencies', 'wp-cardealer' ),
						'id'                => 'multi_currencies',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Currency', 'wp-cardealer' ),
							'add_button'        => __( 'Add Another Currency', 'wp-cardealer' ),
							'remove_button'     => __( 'Remove Currency', 'wp-cardealer' ),
							'sortable'          => true,
							'closed'         => true,
							'remove_confirm' => __( 'Do you want to remove this currency', 'wp-cardealer' ),
						),
						'fields'			=> array(
							array(
								'name'    => __( 'Currency', 'wp-cardealer' ),
								'desc'    => __( 'Choose a currency.', 'wp-cardealer' ),
								'id'      => 'currency',
								'type'    => 'pw_select',
								'options' => $currencies_opts,
								'attributes'        => array(
				                    'data-allowclear' => 'false',
				                    'data-width'		=> '25em'
				                ),
				                'classes' => 'multi-currency-select'
							),
							array(
								'name'    => __( 'Currency Position', 'wp-cardealer' ),
								'desc'    => 'Choose the position of the currency sign.',
								'id'      => 'currency_position',
								'type'    => 'pw_select',
								'options' => array(
									'before' => __( 'Before - $10', 'wp-cardealer' ),
									'after'  => __( 'After - 10$', 'wp-cardealer' ),
									'before_space' => __( 'Before with space - $ 10', 'wp-cardealer' ),
									'after_space'  => __( 'After with space - 10 $', 'wp-cardealer' ),
								),
								'default' => 'before',
								'attributes'        => array(
				                    'data-allowclear' => 'false',
				                    'data-width'		=> '25em'
				                ),
							),
							array(
								'name'    => __( 'Number of decimals', 'wp-cardealer' ),
								'desc'    => __( 'This sets the number of decimal points shown in displayed prices.', 'wp-cardealer' ),
								'id'      => 'money_decimals',
								'type'    => 'text_small',
								'attributes' 	    => array(
									'type' 				=> 'number',
									'min'				=> 0,
									'pattern' 			=> '\d*',
								)
							),
							array(
								'name'            => __( 'Rate + Exchange Fee', 'wp-cardealer' ),
								'id'              => 'rate_exchange_fee',
								'type'            => 'wp_cardealer_rate_exchange',
								'default' => 1
							),
							array(
								'name'            => __( 'Custom symbol', 'wp-cardealer' ),
								'id'              => 'custom_symbol',
								'type'            => 'text_small',
								'attributes'        => array(
				                    'placeholder' => __( 'eg: CAD $', 'wp-cardealer' ),
				                ),
							),
						),
					),
					///////
					array(
						'name' => __( 'Shorten Long Number', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_shorten_long_number',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Enable Shorten Long Number', 'wp-cardealer' ),
						'id'      => 'enable_shorten_long_number',
						'type'    => 'select',
						'options' => array(
							'yes' 	=> __( 'Yes', 'wp-cardealer' ),
							'no'   => __( 'No', 'wp-cardealer' ),
						),
						'default' => 'no',
					),
					array(
						'name'    => __( 'Number precision', 'wp-cardealer' ),
						'desc'    => __( 'This sets the number of precision shown in displayed number.', 'wp-cardealer' ),
						'id'      => 'shorten_precision',
						'type'    => 'text_small',
						'attributes' 	    => array(
							'type' 				=> 'number',
							'min'				=> 0,
							'pattern' 			=> '\d*',
						),
						'default' => 3
					),
					array(
						'name'            => __( 'Shorten Thousand', 'wp-cardealer' ),
						'id'              => 'shorten_thousand',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "K"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: K', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'            => __( 'Shorten Million', 'wp-cardealer' ),
						'id'              => 'shorten_million',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "M"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: M', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'            => __( 'Shorten Billion', 'wp-cardealer' ),
						'id'              => 'shorten_billion',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "B"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: B', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'            => __( 'Shorten Trillion', 'wp-cardealer' ),
						'id'              => 'shorten_trillion',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "T"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: T', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'            => __( 'Shorten Quadrillion', 'wp-cardealer' ),
						'id'              => 'shorten_quadrillion',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "Qa"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: Qa', 'wp-cardealer' ),
		                ),
					),
					array(
						'name'            => __( 'Shorten Quintillion', 'wp-cardealer' ),
						'id'              => 'shorten_quintillion',
						'type'            => 'wp_cardealer_enable_input',
						'desc' => __( 'Enter space for translate to all languages "Qi"', 'wp-cardealer' ),
						'attributes'        => array(
		                    'placeholder' => __( 'eg: Qi', 'wp-cardealer' ),
		                ),
					),

					////
					array(
						'name' => __( 'Measurement', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_measurement',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Distance Unit', 'wp-cardealer' ),
						'id'      => 'measurement_distance_unit',
						'type'    => 'text_small',
						'default' => 'ft',
					),
					array(
						'name'    => __( 'Search Distance unit', 'wp-cardealer' ),
						'id'      => 'search_distance_unit',
						'type'    => 'pw_select',
						'options' => array(
							'km' => __('Kilometers', 'wp-cardealer'),
							'miles' => __('Miles', 'wp-cardealer'),
						),
						'default' => 'miles',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),

					array(
						'name' => __( 'Map API Settings', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_4',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Map Service', 'wp-cardealer' ),
						'id'      => 'map_service',
						'type'    => 'pw_select',
						'options' => array(
							'mapbox' => __('Mapbox', 'wp-cardealer'),
							'google-map' => __('Google Maps', 'wp-cardealer'),
							'here' => __('Here Maps', 'wp-cardealer'),
							'openstreetmap' => __('OpenStreetMap', 'wp-cardealer'),
						),
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'Google Map API', 'wp-cardealer' ),
						'desc'    => __( 'Google requires an API key to retrieve location information for listing listings. Acquire an API key from the <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key">Google Maps API developer site.</a>', 'wp-cardealer' ),
						'id'      => 'google_map_api_keys',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Google Map Type', 'wp-cardealer' ),
						'id'      => 'googlemap_type',
						'type'    => 'pw_select',
						'options' => array(
							'roadmap' => __('ROADMAP', 'wp-cardealer'),
							'satellite' => __('SATELLITE', 'wp-cardealer'),
							'hybrid' => __('HYBRID', 'wp-cardealer'),
							'terrain' => __('TERRAIN', 'wp-cardealer'),
						),
						'default' => 'roadmap',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'Google Maps Style', 'wp-cardealer' ),
						'desc' 	  => wp_kses(__('<a href="//snazzymaps.com/">Get custom style</a> and paste it below. If there is nothing added, we will fallback to the Google Maps service.', 'wp-cardealer'), array('a' => array('href' => array()))),
						'id'      => 'google_map_style',
						'type'    => 'textarea',
						'default' => '',
					),
					array(
						'name'    => __( 'Mapbox Token', 'wp-cardealer' ),
						'desc' => wp_kses(__('<a href="//www.mapbox.com/help/create-api-access-token/">Get a FREE token</a> and paste it below. If there is nothing added, we will fallback to the Google Maps service.', 'wp-cardealer'), array('a' => array('href' => array()))),
						'id'      => 'mapbox_token',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Mapbox Style', 'wp-cardealer' ),
						'id'      => 'mapbox_style',
						'type'    => 'wp_cardealer_image_select',
						'default' => 'streets-v11',
						'options' => array(
		                    'streets-v11' => array(
		                        'alt' => esc_html__('streets', 'wp-cardealer'),
		                        'img' => WP_CARDEALER_PLUGIN_URL . '/assets/images/streets.png'
		                    ),
		                    'light-v10' => array(
		                        'alt' => esc_html__('light', 'wp-cardealer'),
		                        'img' => WP_CARDEALER_PLUGIN_URL . '/assets/images/light.png'
		                    ),
		                    'dark-v10' => array(
		                        'alt' => esc_html__('dark', 'wp-cardealer'),
		                        'img' => WP_CARDEALER_PLUGIN_URL . '/assets/images/dark.png'
		                    ),
		                    'outdoors-v11' => array(
		                        'alt' => esc_html__('outdoors', 'wp-cardealer'),
		                        'img' => WP_CARDEALER_PLUGIN_URL . '/assets/images/outdoors.png'
		                    ),
		                    'satellite-v9' => array(
		                        'alt' => esc_html__('satellite', 'wp-cardealer'),
		                        'img' => WP_CARDEALER_PLUGIN_URL . '/assets/images/satellite.png'
		                    ),
		                ),
					),
					array(
						'name'    => __( 'Here Maps API Key', 'wp-cardealer' ),
						'desc' => wp_kses(__('<a href="https://developer.here.com/tutorials/getting-here-credentials/">Get a API key</a> and paste it below. If there is nothing added, we will fallback to the Google Maps service.', 'wp-cardealer'), array('a' => array('href' => array()))),
						'id'      => 'here_map_api_key',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Here Maps Style', 'wp-cardealer' ),
						'id'      => 'here_map_style',
						'type'    => 'select',
						'options' => array(
							'normal.day' => esc_html__('Normal Day', 'wp-cardealer'),
							'normal.day.grey' => esc_html__('Normal Day Grey', 'wp-cardealer'),
							'normal.day.transit' => esc_html__('Normal Day Transit', 'wp-cardealer'),
							'normal.night' => esc_html__('Normal Night', 'wp-cardealer'),
							'reduced.day' => esc_html__('Reduced Day', 'wp-cardealer'),
							'reduced.night' => esc_html__('Reduced Night', 'wp-cardealer'),
							'pedestrian.day' => esc_html__('Pedestrian Day', 'wp-cardealer'),
						)
					),
					array(
						'name'    => __( 'Geocoder Country', 'wp-cardealer' ),
						'id'      => 'geocoder_country',
						'type'    => 'pw_select',
						'options' => $countries,
						'attributes'        => array(
		                    'data-allowclear' => 'true',
		                    'data-width'		=> '25em',
		                    'data-placeholder'	=> __( 'All Countries', 'wp-cardealer' )
		                ),
					),
					array(
						'name' => __( 'Default maps location', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_general_settings_default_maps_location',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Latitude', 'wp-cardealer' ),
						'desc'    => __( 'Enter your latitude', 'wp-cardealer' ),
						'id'      => 'default_maps_location_latitude',
						'type'    => 'text_small',
						'default' => '43.6568',
					),
					array(
						'name'    => __( 'Longitude', 'wp-cardealer' ),
						'desc'    => __( 'Enter your longitude', 'wp-cardealer' ),
						'id'      => 'default_maps_location_longitude',
						'type'    => 'text_small',
						'default' => '-79.4512',
					),
					

				), $pages
			)		 
		);

		// Listing Submission
		$roles  = get_editable_roles();
		$capabilities_and_roles = [];
		foreach ( $roles as $key => $role ) {
			$capabilities_and_roles[ $key ] = $role['name'];
		}
		$wp_cardealer_settings['listing_submission'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Listing Submission', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_listing_submission', array(
					array(
						'name' => __( 'Listing Submission', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_listing_submission_settings_1',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Submit Listing Capability', 'wp-cardealer' ),
						'desc'    => __( 'Enter which roles allow visitors to submit listing.', 'wp-cardealer' ),
						'id'      => 'submit_user_roles',
						'type'    => 'pw_multiselect',
						'options' => $capabilities_and_roles,
						'attributes'        => array(
		                    'data-placeholder' => __( 'Everyone', 'wp-cardealer' ),
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Submit Listing Form Page', 'wp-cardealer' ),
						'desc'    => __( 'This is page to display form for submit listing. The <code>[wp_cardealer_submission]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'submit_listing_form_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Moderate New Listings', 'wp-cardealer' ),
						'desc'    => __( 'Require admin approval of all new listing submissions', 'wp-cardealer' ),
						'id'      => 'submission_requires_approval',
						'type'    => 'pw_select',
						'options' => array(
							'on' 	=> __( 'Enable', 'wp-cardealer' ),
							'off'   => __( 'Disable', 'wp-cardealer' ),
						),
						'default' => 'on',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'Allow Published Edits', 'wp-cardealer' ),
						'desc'    => __( 'Choose whether published listing listings can be edited and if edits require admin approval. When moderation is required, the original listing listings will be unpublished while edits await admin approval.', 'wp-cardealer' ),
						'id'      => 'user_edit_published_submission',
						'type'    => 'pw_select',
						'options' => array(
							'no' 	=> __( 'Users cannot edit', 'wp-cardealer' ),
							'yes'   => __( 'Users can edit without admin approval', 'wp-cardealer' ),
							'yes_moderated'   => __( 'Users can edit, but edits require admin approval', 'wp-cardealer' ),
						),
						'default' => 'yes',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'            => __( 'Listing Duration', 'wp-cardealer' ),
						'desc'            => __( 'Listings will display for the set number of days, then expire. Leave this field blank if you don\'t want listings to have an expiration date.', 'wp-cardealer' ),
						'id'              => 'submission_duration',
						'type'            => 'text_small',
						'default'         => 30,
					),
				), $pages
			)
		);

		// Listing Submission
		$wp_cardealer_settings['pages'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Pages', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_pages', array(
					array(
						'name'    => __( 'Listings Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the listings listing page. The <code>[wp_cardealer_listings]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'listings_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					
					array(
						'name'    => __( 'Dealers Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the dealers listing page. The <code>[wp_cardealer_dealers]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'dealers_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),

					array(
						'name'    => __( 'Login Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the listing listings page. The <code>[wp_cardealer_login]</code> <code>[wp_cardealer_register]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'login_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Register Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the listing listings page. The <code>[wp_cardealer_login]</code> <code>[wp_cardealer_register]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'register_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'After Login Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the page after User login/register.', 'wp-cardealer' ),
						'id'      => 'after_login_page_id_user',
						'type'    => 'pw_select',
						'options' => $pages,
						'page-type' => 'page',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'Approve User Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the job listings page. The <code>[wp_cardealer_approve_user]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'approve_user_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'page-type' => 'page',
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
					),
					array(
						'name'    => __( 'User Dashboard Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the user dashboard. The <code>[wp_cardealer_user_dashboard]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'user_dashboard_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Edit Profile Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the user edit profile. The <code>[wp_cardealer_change_profile]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'edit_profile_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Change Password Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the user change password. The <code>[wp_cardealer_change_password]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'change_password_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'My Listings Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the my listing page. The <code>[wp_cardealer_my_listings]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'my_listings_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Favorite Listings Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the my favorite listing page. The <code>[wp_cardealer_my_listing_favorite]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'favorite_listings_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					
					array(
						'name'    => __( 'Compare Listings Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the location of the listing compare page. The <code>[wp_cardealer_listing_compare]</code> shortcode should be on this page.', 'wp-cardealer' ),
						'id'      => 'compare_listings_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
					array(
						'name'    => __( 'Terms and Conditions Page', 'wp-cardealer' ),
						'desc'    => __( 'This lets the plugin know the Terms and Conditions page.', 'wp-cardealer' ),
						'id'      => 'terms_conditions_page_id',
						'type'    => 'pw_select',
						'options' => $pages,
						'attributes'        => array(
		                    'data-allowclear' => 'false',
		                    'data-width'		=> '25em'
		                ),
		                'page-type' => 'page'
					),
				), $pages
			)
		);
		$cc_list = include WP_CARDEALER_PLUGIN_DIR.'includes/sms/countries-phone.php';
		$phone_css = [];
		foreach ($cc_list as $country_code => $country_phone_code) {
			$phone_css[$country_phone_code] = $country_code . $country_phone_code;
		}
		// user register settings
		$wp_cardealer_settings['user_register_settings'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'User Register Settings', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_user_register_fields_settings', array(
				array(
					'name' => __( 'User Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_user',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Moderate New User', 'wp-cardealer' ),
					'desc'    => __( 'Require admin approval of all new users', 'wp-cardealer' ),
					'id'      => 'users_requires_approval',
					'type'    => 'pw_select',
					'options' => array(
						'auto' 	=> __( 'Auto Approve', 'wp-cardealer' ),
						'email_approve' => __( 'Email Approve', 'wp-cardealer' ),
						'phone_approve' => __( 'Phone Approve', 'wp-cardealer' ),
						'admin_approve' => __( 'Administrator Approve', 'wp-cardealer' ),
					),
					'default' => 'auto',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'name' => __( 'Phone Approve Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_phone_approve',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Phone Operator', 'wp-cardealer' ),
					'id'      => 'phone_approve_operator',
					'type'    => 'pw_select',
					'options' => array(
						'twilio' 	=> __( 'twilio', 'wp-cardealer' ),
						'aws' => __( 'Amazon', 'wp-cardealer' ),
					),
					'default' => 'twilio',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'name' => __( 'Amazon SNS Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_amazon_settings',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Access key', 'wp-cardealer' ),
					'id'      => 'phone_approve_aws_access_key',
					'type'    => 'text',
				),
				array(
					'name'    => __( 'Secret access key', 'wp-cardealer' ),
					'id'      => 'phone_approve_aws_secret_access_key',
					'type'    => 'text',
				),
				array(
					'name' => __( 'Twilio Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_twilio_settings',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Account SID', 'wp-cardealer' ),
					'id'      => 'phone_approve_twilio_account_sid',
					'type'    => 'text',
				),
				array(
					'name'    => __( 'Auth Token', 'wp-cardealer' ),
					'id'      => 'phone_approve_twilio_auth_token',
					'type'    => 'text',
				),
				array(
					'name'    => __( 'Sender\'s Number', 'wp-cardealer' ),
					'id'      => 'phone_approve_twilio_sender_number',
					'type'    => 'text',
				),
				array(
					'name' => __( 'Register Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_phone_register_settings',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				
				array(
					'name'    => __( 'Default Country Code', 'wp-cardealer' ),
					'desc'    => __( 'Geolocation = User location.', 'wp-cardealer' ),
					'id'      => 'phone_approve_default_country_code',
					'type'    => 'pw_select',
					'options' => array(
						'geolocation' 	=> __( 'Geolocation', 'wp-cardealer' ),
						'custom'   => __( 'Custom', 'wp-cardealer' ),
					),
					'default' => 'geolocation',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'name'    => __( 'Default Country Code (Custom)', 'wp-cardealer' ),
					'id'      => 'phone_approve_default_country_code_custom',
					'type'    => 'pw_select',
					'options' => $phone_css,
					'default' => '',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'name'    => __( 'SMS Text', 'wp-cardealer' ),
					'desc'    => __( 'Shortcodes: [otp]', 'wp-cardealer' ),
					'id'      => 'phone_approve_sms_text',
					'type'    => 'textarea',
					'default' => '[otp] is your One Time Verification(OTP) to confirm your phone no at xootix.',
				),
				array(
					'name' => __( 'OTP Settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_general_settings_otp_settings',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'OTP Digits', 'wp-cardealer' ),
					'id'      => 'phone_approve_otp_digits',
					'type'    => 'text',
					'default' => '4',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
				array(
					'name'    => __( 'Incorrect OTP Limit', 'wp-cardealer' ),
					'id'      => 'phone_approve_incorrect_otp_limit',
					'type'    => 'text',
					'default' => '10',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
				array(
					'name'    => __( 'OTP Expiry', 'wp-cardealer' ),
					'desc'    => __( 'In Seconds', 'wp-cardealer' ),
					'id'      => 'phone_approve_otp_expiry',
					'type'    => 'text',
					'default' => '120',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
				array(
					'name'    => __( 'Resend OTP Limit', 'wp-cardealer' ),
					'id'      => 'phone_approve_resend_otp_limit',
					'type'    => 'text',
					'default' => '8',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
				array(
					'name'    => __( 'Ban Time', 'wp-cardealer' ),
					'desc'    => __( 'Time in seconds', 'wp-cardealer' ),
					'id'      => 'phone_approve_ban_time',
					'type'    => 'text',
					'default' => '600',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
				array(
					'name'    => __( 'Resend OTP Wait Time', 'wp-cardealer' ),
					'desc'    => __( 'Waiting time to resend a new OTP (In seconds)', 'wp-cardealer' ),
					'id'      => 'phone_approve_resend_otp_wait_time',
					'type'    => 'text',
					'default' => '30',
					'attributes'        => array(
	                    'autocomplete'		=> 'off',
	                    'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
	                ),
				),
			), $pages )
		);
		
		// Compare Settings
		$compare_fields = apply_filters( 'wp-cardealer-default-listing-compare-fields', array() );
		$setting_compare_fields = array();
		if ( !empty($compare_fields) ) {
			foreach ($compare_fields as $field) {
				$setting_compare_fields[] = array(
					'name'    => sprintf(__( 'Enable %s', 'wp-cardealer' ), $field['name']),
					'id'      => sprintf('enable_compare_%s', $field['id']),
					'type'    => 'pw_select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-cardealer' ),
						'off'   => __( 'Disable', 'wp-cardealer' ),
					),
					'default' => 'on',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				);
			}
		}
		$wp_cardealer_settings['compare_settings'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Compare Fields Settings', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_compare_fields_settings', $setting_compare_fields, $pages, $compare_fields )
		);

		// Review Settings
		$wp_cardealer_settings['review_settings'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Review Settings', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_review_settings', array(
				// review listing
				array(
					'name' => __( 'Listing review settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_listing_review_settings_title',
					'before_row' => '<hr>',
					'after_row'  => '<hr>',
				),
				array(
					'name'    => __( 'Enable Listing Review', 'wp-cardealer' ),
					'id'      => 'enable_listing_review',
					'type'    => 'pw_select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-cardealer' ),
						'off'   => __( 'Disable', 'wp-cardealer' ),
					),
					'default' => 'on',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'id'          => 'listing_review_category',
					'type'        => 'group',
					'name' => __( 'Listing Review Category', 'wp-cardealer' ),
					'repeatable'  => true,
					'options'     => array(
						'group_title'       => __( 'Category {#}', 'wp-cardealer' ), // since version 1.1.4, {#} gets replaced by row number
						'add_button'        => __( 'Add Another Category', 'wp-cardealer' ),
						'remove_button'     => __( 'Remove Category', 'wp-cardealer' ),
						'sortable'          => true,
					),
					'fields'	=> array(
						array(
							'name'            => __( 'Category Key', 'wp-cardealer' ),
							'desc'            => __( 'Enter category key.', 'wp-cardealer' ),
							'id'              => 'key',
							'type'            => 'text',
							'attributes' 	    => array(
								'data-general-review-key' => 'listing'
							),
						),
						array(
							'name'            => __( 'Category Name', 'wp-cardealer' ),
							'desc'            => __( 'Enter category name.', 'wp-cardealer' ),
							'id'              => 'name',
							'type'            => 'text',
						),
					)
				),
				// Dealer
				array(
					'name' => __( 'Dealer review settings', 'wp-cardealer' ),
					'type' => 'wp_cardealer_title',
					'id'   => 'wp_cardealer_title_dealer_review_settings_title',
					'before_row' => '<hr>',
					'after_row'  => '<hr>',
				),
				array(
					'name'    => __( 'Enable Dealer Review', 'wp-cardealer' ),
					'id'      => 'enable_dealer_review',
					'type'    => 'pw_select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-cardealer' ),
						'off'   => __( 'Disable', 'wp-cardealer' ),
					),
					'default' => 'on',
					'attributes'        => array(
	                    'data-allowclear' => 'false',
	                    'data-width'		=> '25em'
	                ),
				),
				array(
					'id'          => 'dealer_review_category',
					'type'        => 'group',
					'name' => __( 'Dealer Review Category', 'wp-cardealer' ),
					'repeatable'  => true,
					'options'     => array(
						'group_title'       => __( 'Category {#}', 'wp-cardealer' ), // since version 1.1.4, {#} gets replaced by row number
						'add_button'        => __( 'Add Another Category', 'wp-cardealer' ),
						'remove_button'     => __( 'Remove Category', 'wp-cardealer' ),
						'sortable'          => true,
					),
					'fields'	=> array(
						array(
							'name'            => __( 'Category Key', 'wp-cardealer' ),
							'desc'            => __( 'Enter category key.', 'wp-cardealer' ),
							'id'              => 'key',
							'type'            => 'text',
							'attributes' 	    => array(
								'data-general-review-key' => 'dealer'
							),
						),
						array(
							'name'            => __( 'Category Name', 'wp-cardealer' ),
							'desc'            => __( 'Enter category name.', 'wp-cardealer' ),
							'id'              => 'name',
							'type'            => 'text',
						),
					)
				),
			) )		 
		);

		// ReCaptcha
		$wp_cardealer_settings['api_settings'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Social API', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_api_settings', array(
					// Facebook
					array(
						'name' => __( 'Facebook API settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_api_settings_facebook_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-cardealer'), admin_url('admin-ajax.php?action=wp_cardealer_facebook_login')),
					),
					array(
						'name'            => __( 'App ID', 'wp-cardealer' ),
						'desc'            => __( 'Please enter App ID of your Facebook account.', 'wp-cardealer' ),
						'id'              => 'facebook_api_app_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'App Secret', 'wp-cardealer' ),
						'desc'            => __( 'Please enter App Secret of your Facebook account.', 'wp-cardealer' ),
						'id'              => 'facebook_api_app_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Facebook Login', 'wp-cardealer' ),
						'id'      => 'enable_facebook_login',
						'type'    => 'checkbox',
					),

					// Linkedin
					array(
						'name' => __( 'Linkedin API settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_api_settings_linkedin_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-cardealer'), home_url('/')),
					),
					array(
						'name'    => __( 'Linkedin Login Type', 'wp-cardealer' ),
						'id'      => 'linkedin_login_type',
						'type'    => 'radio',
						'options' => array(
							'' => 'OAuth',
							'openid' => 'OpenID',
						),
						'default' => '',
					),
					array(
						'name'            => __( 'Client ID', 'wp-cardealer' ),
						'desc'            => __( 'Please enter Client ID of your linkedin app.', 'wp-cardealer' ),
						'id'              => 'linkedin_api_client_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client Secret', 'wp-cardealer' ),
						'desc'            => __( 'Please enter Client Secret of your linkedin app.', 'wp-cardealer' ),
						'id'              => 'linkedin_api_client_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Linkedin Login', 'wp-cardealer' ),
						'id'      => 'enable_linkedin_login',
						'type'    => 'checkbox',
					),

					// Twitter
					array(
						'name' => __( 'Twitter API settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_api_settings_twitter_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-cardealer'), home_url('/')),
					),
					array(
						'name'            => __( 'Consumer Key', 'wp-cardealer' ),
						'desc'            => __( 'Set Consumer Key for twitter.', 'wp-cardealer' ),
						'id'              => 'twitter_api_consumer_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Consumer Secret', 'wp-cardealer' ),
						'desc'            => __( 'Set Consumer Secret for twitter.', 'wp-cardealer' ),
						'id'              => 'twitter_api_consumer_secret',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Access Token', 'wp-cardealer' ),
						'desc'            => __( 'Set Access Token for twitter.', 'wp-cardealer' ),
						'id'              => 'twitter_api_access_token',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Token Secret', 'wp-cardealer' ),
						'desc'            => __( 'Set Token Secret for twitter.', 'wp-cardealer' ),
						'id'              => 'twitter_api_token_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Twitter Login', 'wp-cardealer' ),
						'id'      => 'enable_twitter_login',
						'type'    => 'checkbox',
					),

					// Google API
					array(
						'name' => __( 'Google API settings Settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_api_settings_google_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-cardealer'), home_url('/')),
					),
					array(
						'name'            => __( 'API Key', 'wp-cardealer' ),
						'desc'            => __( 'Please enter API key of your Google account.', 'wp-cardealer' ),
						'id'              => 'google_api_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client ID', 'wp-cardealer' ),
						'desc'            => __( 'Please enter Client ID of your Google account.', 'wp-cardealer' ),
						'id'              => 'google_api_client_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client Secret', 'wp-cardealer' ),
						'desc'            => __( 'Please enter Client secret of your Google account.', 'wp-cardealer' ),
						'id'              => 'google_api_client_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Google Login', 'wp-cardealer' ),
						'id'      => 'enable_google_login',
						'type'    => 'checkbox',
					),
				)
			)		 
		);
		
		// ReCaaptcha
		$wp_cardealer_settings['recaptcha_api_settings'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'reCAPTCHA API', 'wp-cardealer' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_cardealer_settings_recaptcha_api_settings', array(
					
					// Google Recaptcha
					array(
						'name' => __( 'Google reCAPTCHA API (V2) Settings', 'wp-cardealer' ),
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_api_settings_google_recaptcha',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => __('The plugin use ReCaptcha v2', 'wp-cardealer'),
					),
					array(
						'name'            => __( 'Site Key', 'wp-cardealer' ),
						'desc'            => __( 'You can retrieve your site key from <a href="https://www.google.com/recaptcha/admin#list">Google\'s reCAPTCHA admin dashboard.</a>', 'wp-cardealer' ),
						'id'              => 'recaptcha_site_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Secret Key', 'wp-cardealer' ),
						'desc'            => __( 'You can retrieve your secret key from <a href="https://www.google.com/recaptcha/admin#list">Google\'s reCAPTCHA admin dashboard.</a>', 'wp-cardealer' ),
						'id'              => 'recaptcha_secret_key',
						'type'            => 'text',
					),
				)
			)		 
		);

		// Email notification
		$wp_cardealer_settings['email_notification'] = array(
			'id'         => 'options_page',
			'wp_cardealer_title' => __( 'Email Notification', 'wp-cardealer' ),
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->key )
			),
			'fields'     => apply_filters( 'wp_cardealer_settings_email_notification', array(
					
					array(
						'name' => __( 'Admin Notice of New Listing', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_admin_notice_add_new_listing',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Admin Notice of New Listing', 'wp-cardealer' ),
						'id'      => 'admin_notice_add_new_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send a notice to the site administrator when a new listing is submitted on the frontend.', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_add_new_listing', 'subject') ),
						'id'      => 'admin_notice_add_new_listing_subject',
						'type'    => 'text',
						'default' => 'New Listing Found',
					),
					array(
						'name'    => __( 'Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_add_new_listing', 'content') ),
						'id'      => 'admin_notice_add_new_listing_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('admin_notice_add_new_listing'),
					),

					array(
						'name' => __( 'Admin Notice of Updated Listing', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_admin_notice_updated_listing',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Admin Notice of Updated Listing', 'wp-cardealer' ),
						'id'      => 'admin_notice_updated_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send a notice to the site administrator when a listing is updated on the frontend.', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_updated_listing', 'subject') ),
						'id'      => 'admin_notice_updated_listing_subject',
						'type'    => 'text',
						'default' => 'A Listing Updated',
					),
					array(
						'name'    => __( 'Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_updated_listing', 'content') ),
						'id'      => 'admin_notice_updated_listing_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('admin_notice_updated_listing'),
					),

					
					array(
						'name' => __( 'Admin Notice of Expiring Listing', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_admin_notice_expiring_listing',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Admin Notice of Expiring Listing', 'wp-cardealer' ),
						'id'      => 'admin_notice_expiring_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send notices to the site administrator before a listing listing expires.', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Notice Period', 'wp-cardealer' ),
						'desc'    => __( 'days', 'wp-cardealer' ),
						'id'      => 'admin_notice_expiring_listing_days',
						'type'    => 'text_small',
						'default' => '1',
					),
					array(
						'name'    => __( 'Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_expiring_listing', 'subject') ),
						'id'      => 'admin_notice_expiring_listing_subject',
						'type'    => 'text',
						'default' => 'Listing Listing Expiring: {{listing_title}}',
					),
					array(
						'name'    => __( 'Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('admin_notice_expiring_listing', 'content') ),
						'id'      => 'admin_notice_expiring_listing_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('admin_notice_expiring_listing'),
					),

					
					array(
						'name' => __( 'User Notice of Expiring Listing', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_notice_expiring_listing',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'User Notice of Expiring Listing', 'wp-cardealer' ),
						'id'      => 'user_notice_expiring_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send notices to user before a listing listing expires.', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Notice Period', 'wp-cardealer' ),
						'desc'    => __( 'days', 'wp-cardealer' ),
						'id'      => 'user_notice_expiring_listing_days',
						'type'    => 'text_small',
						'default' => '1',
					),
					array(
						'name'    => __( 'Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_notice_expiring_listing', 'subject') ),
						'id'      => 'user_notice_expiring_listing_subject',
						'type'    => 'text',
						'default' => 'Listing Listing Expiring: {{listing_title}}',
					),
					array(
						'name'    => __( 'Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_notice_expiring_listing', 'content') ),
						'id'      => 'user_notice_expiring_listing_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_notice_expiring_listing'),
					),


					array(
						'name' => __( 'Listing Saved Search', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_saved_search',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Listing Saved Search Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('saved_search_notice', 'subject') ),
						'id'      => 'saved_search_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Listing Saved Search: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('saved_search_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Listing Saved Search Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('saved_search_notice', 'content') ),
						'id'      => 'saved_search_notice_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('saved_search_notice'),
					),

					// contact form Listing
					array(
						'name' => __( 'Listing Contact Form', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_contact_form_listing',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Listing Contact Form Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_contact_form_notice', 'subject') ),
						'id'      => 'listing_contact_form_notice_subject',
						'type'    => 'text',
						'default' => __( 'Contact Form', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Listing Contact Form Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_contact_form_notice', 'content') ),
						'id'      => 'listing_contact_form_notice_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('listing_contact_form_notice'),
					),

					// contact form
					array(
						'name' => __( 'Contact Form', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_contact_form',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Contact Form Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('contact_form_notice', 'subject') ),
						'id'      => 'contact_form_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Contact Form: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('contact_form_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Contact Form Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('contact_form_notice', 'content') ),
						'id'      => 'contact_form_notice_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('contact_form_notice'),
					),
					
					// Make an Offer
					array(
						'name' => __( 'Make an Offer', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_make_an_offer',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Make an Offer Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_make_an_offer_notice', 'subject') ),
						'id'      => 'listing_make_an_offer_notice_subject',
						'type'    => 'text',
						'default' => __( 'Make an Offer', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Make an Offer Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_make_an_offer_notice', 'content') ),
						'id'      => 'listing_make_an_offer_notice_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('listing_make_an_offer_notice'),
					),

					// Schedule Test Drive
					array(
						'name' => __( 'Schedule Test Drive', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_schedule_test_drive',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Make an Offer Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_schedule_test_drive_notice', 'subject') ),
						'id'      => 'listing_schedule_test_drive_notice_subject',
						'type'    => 'text',
						'default' => __( 'Make an Offer', 'wp-cardealer' ),
					),
					array(
						'name'    => __( 'Make an Offer Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('listing_schedule_test_drive_notice', 'content') ),
						'id'      => 'listing_schedule_test_drive_notice_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('listing_schedule_test_drive_notice'),
					),

					

					// Approve new user register
					array(
						'name' => __( 'New user register (auto approve)', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_register_auto_approve',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'New user register Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_auto_approve', 'subject') ),
						'id'      => 'user_register_auto_approve_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'New user register: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_auto_approve', 'subject') ),
					),
					array(
						'name'    => __( 'New user register Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_auto_approve', 'content') ),
						'id'      => 'user_register_auto_approve_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_register_auto_approve'),
					),
					// Approve new user register
					array(
						'name' => __( 'Approve new user register', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_register_need_approve',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Approve new user register Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_need_approve', 'subject') ),
						'id'      => 'user_register_need_approve_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_need_approve', 'subject') ),
					),
					array(
						'name'    => __( 'Approve new user register Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_need_approve', 'content') ),
						'id'      => 'user_register_need_approve_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_register_need_approve'),
					),
					// Approved user register
					array(
						'name' => __( 'Approved user', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_register_approved',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Approved user Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_approved', 'subject') ),
						'id'      => 'user_register_approved_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_approved', 'subject') ),
					),
					array(
						'name'    => __( 'Approved user Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_approved', 'content') ),
						'id'      => 'user_register_approved_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_register_approved'),
					),
					// Denied user register
					array(
						'name' => __( 'Denied user', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_register_denied',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Denied user Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_denied', 'subject') ),
						'id'      => 'user_register_denied_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_denied', 'subject') ),
					),
					array(
						'name'    => __( 'Denied user Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_register_denied', 'content') ),
						'id'      => 'user_register_denied_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_register_denied'),
					),

					// Reset Password
					array(
						'name' => __( 'Reset Password', 'wp-cardealer' ),
						'desc' => '',
						'type' => 'wp_cardealer_title',
						'id'   => 'wp_cardealer_title_user_reset_password',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Reset Password Subject', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_reset_password', 'subject') ),
						'id'      => 'user_reset_password_subject',
						'type'    => 'text',
						'default' => 'Your new password',
					),
					array(
						'name'    => __( 'Reset Password Content', 'wp-cardealer' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-cardealer' ), WP_CarDealer_Email::display_email_vars('user_reset_password', 'content') ),
						'id'      => 'user_reset_password_content',
						'type'    => 'wysiwyg',
						'default' => WP_CarDealer_Email::get_email_default_content('user_reset_password'),
					),
				)
			)		 
		);
		
		//Return all settings array if necessary
		if ( $active_tab === null   ) {  
			return apply_filters( 'wp_cardealer_registered_settings', $wp_cardealer_settings );
		}

		// Add other tabs and settings fields as needed
		return apply_filters( 'wp_cardealer_registered_'.$active_tab.'_settings', isset($wp_cardealer_settings[ $active_tab ])?$wp_cardealer_settings[ $active_tab ]:array() );
	}

	/**
	 * Show Settings Notices
	 *
	 * @param $object_id
	 * @param $updated
	 * @param $cmb
	 */
	public function settings_notices( $object_id, $updated, $cmb ) {

		//Sanity check
		if ( $object_id !== $this->key ) {
			return;
		}

		if ( did_action( 'cmb2_save_options-page_fields' ) === 1 ) {
			settings_errors( 'wp_cardealer-notices' );
		}

		add_settings_error( 'wp_cardealer-notices', 'global-settings-updated', __( 'Settings updated.', 'wp-cardealer' ), 'updated' );
	}


	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  1.0
	 *
	 * @param  string $field Field to retrieve
	 *
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'fields', 'wp_cardealer_title', 'options_page' ), true ) ) {
			return $this->{$field};
		}
		if ( 'option_metabox' === $field ) {
			return $this->option_metabox();
		}

		throw new Exception( 'Invalid listing: ' . $field );
	}


}

// Get it started
$WP_CarDealer_Settings = new WP_CarDealer_Settings();

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 *
 * @param  string $key Options array key
 *
 * @return mixed        Option value
 */
function wp_cardealer_get_option( $key = '', $default = false ) {
	global $wp_cardealer_options;

	$wp_cardealer_options = wp_cardealer_get_settings();
	
	$value = ! empty( $wp_cardealer_options[ $key ] ) ? $wp_cardealer_options[ $key ] : $default;
	$value = apply_filters( 'wp_cardealer_get_option', $value, $key, $default );

	return apply_filters( 'wp_cardealer_get_option_' . $key, $value, $key, $default );
}



/**
 * Get Settings
 *
 * Retrieves all WP_CarDealer plugin settings
 *
 * @since 1.0
 * @return array WP_CarDealer settings
 */
function wp_cardealer_get_settings() {
	return apply_filters( 'wp_cardealer_get_settings', get_option( 'wp_cardealer_settings' ) );
}


/**
 * WP_CarDealer Title
 *
 * Renders custom section titles output; Really only an <hr> because CMB2's output is a bit funky
 *
 * @since 1.0
 *
 * @param       $field_object , $escaped_value, $object_id, $object_type, $field_type_object
 *
 * @return void
 */
function wp_cardealer_title_callback( $field_object, $escaped_value, $object_id, $object_type, $field_type_object ) {
	$id                = $field_type_object->field->args['id'];
	$title             = $field_type_object->field->args['name'];
	$field_description = $field_type_object->field->args['desc'];
	if ( $field_description ) {
		echo '<div class="desc">'.$field_description.'</div>';
	}
}

function wp_cardealer_hidden_callback( $field_object, $escaped_value, $object_id, $object_type, $field_type_object ) {
	$id                = $field_type_object->field->args['id'];
	$title             = $field_type_object->field->args['name'];
	$field_description = $field_type_object->field->args['desc'];
	echo '<input type="hidden" name="'.$id.'" value="'.$escaped_value.'">';
	if ( $field_type_object->field->args['human_value'] ) {
		echo '<strong>'.$field_type_object->field->args['human_value'].'</strong>';
	}
	if ( $field_description ) {
		echo '<div class="desc">'.$field_description.'</div>';
	}
}

function wp_cardealer_cmb2_get_page_options( $query_args, $force = false ) {
	$post_options = array( '' => '' ); // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'listing-settings' != $_GET['page'] ) && ! $force ) {
		return $post_options;
	}

	$args = wp_parse_args( $query_args, array(
		'post_type'   => 'page',
		'numberposts' => 10,
	) );

	$posts = get_posts( $args );

	if ( $posts ) {
		foreach ( $posts as $post ) {
			$post_options[ $post->ID ] = $post->post_title;
		}
	}

	return $post_options;
}

add_filter( 'cmb2_get_metabox_form_format', 'wp_cardealer_modify_cmb2_form_output', 10, 3 );
function wp_cardealer_modify_cmb2_form_output( $form_format, $object_id, $cmb ) {
	//only modify the wp_cardealer settings form
	if ( 'wp_cardealer_settings' == $object_id && 'options_page' == $cmb->cmb_id ) {

		return '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s<div class="wp_cardealer-submit-wrap"><input type="submit" name="submit-cmb" value="' . __( 'Save Settings', 'wp-cardealer' ) . '" class="button-primary"></div></form>';
	}

	return $form_format;

}
