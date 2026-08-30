<?php
/**
 * Plugin Name: WP CarDealer
 * Plugin URI: http://apusthemes.com/wp-cardealer/
 * Description: The latest plugins Car Dealer you want. Completely all features, easy customize and override layout, functions. Supported global payment, build market, single, list car...etc. All fields are defined dynamic, they will help you can build any kind of Car Dealer website.
 * Version: 1.2.10
 * Author: Habq
 * Author URI: http://apusthemes.com/
 * Requires at least: 3.8
 * Tested up to: 5.2
 *
 * Text Domain: wp-cardealer
 * Domain Path: /languages/
 *
 * @package wp-cardealer
 * @category Plugins
 * @author Habq
 */
if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

if ( !class_exists("WP_CarDealer") ) {
	
	final class WP_CarDealer {

		private static $instance;

		public static function getInstance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_CarDealer ) ) {
				self::$instance = new WP_CarDealer;
				self::$instance->setup_constants();
				self::$instance->load_textdomain();
				self::$instance->plugin_update();

				add_action( 'activated_plugin', array( self::$instance, 'plugin_order' ) );
				add_action( 'tgmpa_register', array( self::$instance, 'register_plugins' ) );
				add_action( 'widgets_init', array( self::$instance, 'register_widgets' ) );

				self::$instance->libraries();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 *
		 */
		public function setup_constants(){
			define( 'WP_CARDEALER_PLUGIN_VERSION', '1.2.10' );

			define( 'WP_CARDEALER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			define( 'WP_CARDEALER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

			define( 'WP_CARDEALER_LISTING_PREFIX', '_listing_' );
			define( 'WP_CARDEALER_USER_PREFIX', '_user_' );
			define( 'WP_CARDEALER_DEALER_PREFIX', '_dealer_' );
			
			define( 'WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX', '_saved_search_' );
		}

		public function includes() {
			global $wp_cardealer_options;
			// 3rd-party
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/3rd-party/class-wpml.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/3rd-party/class-polylang.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/3rd-party/class-elementor.php';
			
			// Admin Settings
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/admin/class-settings.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/admin/class-permalink-settings.php';

			$wp_cardealer_options = wp_cardealer_get_settings();
			
			// post type
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/post-types/class-post-type-listing.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/post-types/class-post-type-dealer.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/post-types/class-post-type-saved-search.php';

			// custom fields
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/custom-fields/class-fields-manager.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/custom-fields/class-custom-fields-html.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/custom-fields/class-custom-fields.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/custom-fields/class-custom-fields-display.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-listing.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-listing-meta.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-dealer.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-dealer-meta.php';

			$custom_all_fields = WP_CarDealer_Fields_Manager::get_custom_fields_data(WP_CARDEALER_LISTING_PREFIX);
			
			$fields = array();
			if ( $custom_all_fields ) {
				foreach ($custom_all_fields as $field) {
					if ( !empty($field['id']) ) {
						$fields[$field['id']] = $field;
					}
				}
			}
			// taxonomies
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'condition']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-condition.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'label']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-label.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'category']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-category.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'type']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-type.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'make']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-make.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'model']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-model.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'offer_type']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-offer-type.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'drive_type']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-drive-type.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'transmission']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-transmission.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'fuel_type']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-fuel-type.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'cylinder']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-cylinder.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'color']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-color.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'door']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-door.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'feature']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-feature.php';
			}
			if ( empty($fields) || isset($fields[WP_CARDEALER_LISTING_PREFIX.'location']) ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-listing-location.php';
			}
			

			//
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-scripts.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-template-loader.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-review.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-price.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-navasan.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-listing-location.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-listing-body-damage.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-query.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-shortcodes.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-abstract-form.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-submit-form.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-edit-form.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-user.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-user-notification.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-image.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-recaptcha.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-email.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-abstract-filter.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-listing-filter.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-dealer-filter.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-saved-search.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-ajax.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-social.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-cache-helper.php';

			// social login
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/socials/class-social-facebook.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/socials/class-social-google.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/socials/class-social-linkedin.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/socials/class-social-twitter.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-favorite.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-compare.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/class-mixes.php';

			

			if ( wp_cardealer_get_option('users_requires_approval') == 'phone_approve' ) {
				require_once WP_CARDEALER_PLUGIN_DIR . 'includes/sms/class-sms.php';
			}

			add_action('init', array( __CLASS__, 'register_post_statuses' ) );
		}

		public static function plugin_update() {
	        require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/plugin-update-checker/plugin-update-checker.php';
	        Puc_v4_Factory::buildUpdateChecker(
	            'https://www.apusthemes.com/themeplugins/wp-cardealer.json',
	            __FILE__,
	            'wp-cardealer'
	        );
	    }

		public static function register_post_statuses() {
			register_post_status(
				'expired',
				array(
					'label'                     => _x( 'Expired', 'post status', 'wp-cardealer' ),
					'public'                    => false,
					'protected'                 => true,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'wp-cardealer' ),
				)
			);
			register_post_status(
				'preview',
				array(
					'label'                     => _x( 'Preview', 'post status', 'wp-cardealer' ),
					'public'                    => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => false,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Preview <span class="count">(%s)</span>', 'Preview <span class="count">(%s)</span>', 'wp-cardealer' ),
				)
			);
			register_post_status(
				'pending_approve',
				array(
					'label'                     => _x( 'Pending Approval', 'post status', 'wp-cardealer' ),
					'public'                    => false,
					'protected'                 => true,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Pending Approve <span class="count">(%s)</span>', 'Pending Approve <span class="count">(%s)</span>', 'wp-cardealer' ),
				)
			);
		}
		public static function register_widgets() {
			// widgets
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/widgets/class-widget-listing-filter.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'includes/widgets/class-widget-listing-save-search-form.php';
		}
		/**
		 * Loads third party libraries
		 *
		 * @access public
		 * @return void
		 */
		public static function libraries() {
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_map/cmb-field-map.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_tags/cmb2-field-type-tags.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_file/cmb2-field-type-file.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_image_select/cmb2-field-type-image-select.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_ajax_search/cmb2-field-ajax-search.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_select2/cmb-field-select2.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_taxonomy_select2/cmb-field-taxonomy-select2.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_taxonomy_select2_parent/cmb-field-taxonomy-select2-parent.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_taxonomy_select2_search/cmb-field-taxonomy-select2-search.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_taxonomy_location/cmb-field-taxonomy-location.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb_field_body_damage/cmb-field-body-damage.php';

			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_rate_exchange/cmb2-field-type-rate_exchange.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_enable_input/cmb2-field-enable-input.php';
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2_field_hours/cmb2-field-hours.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/cmb2/cmb2-tabs/plugin.php';
			
			require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/class-tgm-plugin-activation.php';
		}

		/**
	     * Loads this plugin first
	     *
	     * @access public
	     * @return void
	     */
	    public static function plugin_order() {
		    $wp_path_to_this_file = preg_replace( '/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR.'/$2', __FILE__ );
		    $this_plugin = plugin_basename( trim( $wp_path_to_this_file ) );
		    $active_plugins = get_option( 'active_plugins' );
		    $this_plugin_key = array_search( $this_plugin, $active_plugins );
			if ( $this_plugin_key ) {
				array_splice( $active_plugins, $this_plugin_key, 1 );
				array_unshift( $active_plugins, $this_plugin );
			    update_option( 'active_plugins', $active_plugins );
		    }
	    }

		/**
		 * Install plugins
		 *
		 * @access public
		 * @return void
		 */
		public static function register_plugins() {
			$plugins = array(
	            array(
		            'name'      => 'CMB2',
		            'slug'      => 'cmb2',
		            'required'  => true,
	            )
			);

			tgmpa( $plugins );
		}

		public static function maybe_schedule_cron_listings() {
			if ( ! wp_next_scheduled( 'wp_cardealer_check_for_expired_listings' ) ) {
				wp_schedule_event( time(), 'hourly', 'wp_cardealer_check_for_expired_listings' );
			}
			if ( ! wp_next_scheduled( 'wp_cardealer_delete_old_previews' ) ) {
				wp_schedule_event( time(), 'daily', 'wp_cardealer_delete_old_previews' );
			}
			if ( ! wp_next_scheduled( 'wp_cardealer_email_daily_notices' ) ) {
				wp_schedule_event( time(), 'daily', 'wp_cardealer_email_daily_notices' );
			}
			if ( ! wp_next_scheduled( 'wp_cardealer_navasan_refresh_rate' ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'wp_cardealer_navasan_refresh_rate' );
			}
		}

		/**
		 * Unschedule cron listings. This is run on plugin deactivation.
		 */
		public static function unschedule_cron_listings() {
			wp_clear_scheduled_hook( 'wp_cardealer_check_for_expired_listings' );
			wp_clear_scheduled_hook( 'wp_cardealer_delete_old_previews' );
			wp_clear_scheduled_hook( 'wp_cardealer_email_daily_notices' );
			wp_clear_scheduled_hook( 'wp_cardealer_navasan_refresh_rate' );
		}

		/**
		 *
		 */
		public function load_textdomain() {
			// Set filter for WP_CarDealer's languages directory
			$lang_dir = WP_CARDEALER_PLUGIN_DIR . 'languages/';
			$lang_dir = apply_filters( 'wp_cardealer_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-cardealer' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'wp-cardealer', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/wp-cardealer/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/wp-cardealer folder
				load_textdomain( 'wp-cardealer', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/wp-cardealer/languages/ folder
				load_textdomain( 'wp-cardealer', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'wp-cardealer', false, $lang_dir );
			}
		}
	}
}

register_activation_hook( __FILE__, array( 'WP_CarDealer', 'maybe_schedule_cron_listings' ) );
register_deactivation_hook( __FILE__, array( 'WP_CarDealer', 'unschedule_cron_listings' ) );

function WP_CarDealer() {
	return WP_CarDealer::getInstance();
}

add_action( 'plugins_loaded', 'WP_CarDealer' );
