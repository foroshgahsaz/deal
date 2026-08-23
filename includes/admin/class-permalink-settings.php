<?php
/**
 * Permalink Settings
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Permalink_Settings {
	
	public static function init() {
		add_action('admin_init', array( __CLASS__, 'setup_fields') );
		add_action('admin_init', array( __CLASS__, 'settings_save') );
	}

	public static function setup_fields() {
		$meta_obj = WP_CarDealer_Listing_Meta::get_instance(0);

		add_settings_field(
			'wp_cardealer_listing_base_slug',
			__( 'Listing base', 'wp-cardealer' ),
			array( __CLASS__, 'listing_base_slug_input' ),
			'permalink',
			'optional'
		);
		if ( $meta_obj->check_post_meta_exist('type') ) {
			add_settings_field(
				'wp_cardealer_listing_type_slug',
				__( 'Listing type base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_type_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('category') ) {
			add_settings_field(
				'wp_cardealer_listing_category_slug',
				__( 'Listing category base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_category_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('color') ) {
			add_settings_field(
				'wp_cardealer_listing_color_slug',
				__( 'Listing color base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_color_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('condition') ) {
			add_settings_field(
				'wp_cardealer_listing_condition_slug',
				__( 'Listing condition base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_condition_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('label') ) {
			add_settings_field(
				'wp_cardealer_listing_label_slug',
				__( 'Listing label base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_label_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('cylinder') ) {
			add_settings_field(
				'wp_cardealer_listing_cylinder_slug',
				__( 'Listing cylinder base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_cylinder_slug_input' ),
				'permalink',
				'optional'
			);
		}

		// new
		if ( $meta_obj->check_post_meta_exist('door') ) {
			add_settings_field(
				'wp_cardealer_listing_door_slug',
				__( 'Listing door base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_door_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('drive_type') ) {
			add_settings_field(
				'wp_cardealer_listing_drive_type_slug',
				__( 'Listing drive_type base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_drive_type_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('feature') ) {
			add_settings_field(
				'wp_cardealer_listing_feature_slug',
				__( 'Listing feature base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_feature_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('fuel_type') ) {
			add_settings_field(
				'wp_cardealer_listing_fuel_type_slug',
				__( 'Listing fuel type base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_fuel_type_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('make') ) {
			add_settings_field(
				'wp_cardealer_listing_make_slug',
				__( 'Listing make base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_make_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('model') ) {
			add_settings_field(
				'wp_cardealer_listing_model_slug',
				__( 'Listing model base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_model_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('offer_type') ) {
			add_settings_field(
				'wp_cardealer_listing_offer_type_slug',
				__( 'Listing offer type base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_offer_type_slug_input' ),
				'permalink',
				'optional'
			);
		}
		if ( $meta_obj->check_post_meta_exist('transmission') ) {
			add_settings_field(
				'wp_cardealer_listing_transmission_slug',
				__( 'Listing transmission base', 'wp-cardealer' ),
				array( __CLASS__, 'listing_transmission_slug_input' ),
				'permalink',
				'optional'
			);
		}
		//
		add_settings_field(
			'wp_cardealer_listing_archive_slug',
			__( 'Listing archive page', 'wp-cardealer' ),
			array( __CLASS__, 'listing_archive_slug_input' ),
			'permalink',
			'optional'
		);

		// Dealer
		add_settings_field(
			'wp_cardealer_dealer_base_slug',
			__( 'Listing base', 'wp-cardealer' ),
			array( __CLASS__, 'dealer_base_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'wp_cardealer_dealer_archive_slug',
			__( 'Listing archive page', 'wp-cardealer' ),
			array( __CLASS__, 'dealer_archive_slug_input' ),
			'permalink',
			'optional'
		);
	}

	public static function listing_base_slug_input() {
		$value = get_option('wp_cardealer_listing_base_slug');
		?>
		<input name="wp_cardealer_listing_base_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_category_slug_input() {
		$value = get_option('wp_cardealer_listing_category_slug');
		?>
		<input name="wp_cardealer_listing_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-category', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_color_slug_input() {
		$value = get_option('wp_cardealer_listing_color_slug');
		?>
		<input name="wp_cardealer_listing_color_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-color', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_condition_slug_input() {
		$value = get_option('wp_cardealer_listing_condition_slug');
		?>
		<input name="wp_cardealer_listing_condition_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-condition', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_label_slug_input() {
		$value = get_option('wp_cardealer_listing_label_slug');
		?>
		<input name="wp_cardealer_listing_label_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-label', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_cylinder_slug_input() {
		$value = get_option('wp_cardealer_listing_cylinder_slug');
		?>
		<input name="wp_cardealer_listing_cylinder_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-cylinder', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_type_slug_input() {
		$value = get_option('wp_cardealer_listing_type_slug');
		?>
		<input name="wp_cardealer_listing_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-type', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_location_slug_input() {
		$value = get_option('wp_cardealer_listing_location_slug');
		?>
		<input name="wp_cardealer_listing_location_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-location', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_door_slug_input() {
		$value = get_option('wp_cardealer_listing_door_slug');
		?>
		<input name="wp_cardealer_listing_door_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-door', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_drive_type_slug_input() {
		$value = get_option('wp_cardealer_listing_drive_type_slug');
		?>
		<input name="wp_cardealer_listing_drive_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-drive-type', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_feature_slug_input() {
		$value = get_option('wp_cardealer_listing_feature_slug');
		?>
		<input name="wp_cardealer_listing_feature_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-feature', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_fuel_type_slug_input() {
		$value = get_option('wp_cardealer_listing_fuel_type_slug');
		?>
		<input name="wp_cardealer_listing_fuel_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-fuel-type', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_make_slug_input() {
		$value = get_option('wp_cardealer_listing_make_slug');
		?>
		<input name="wp_cardealer_listing_make_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-make', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_model_slug_input() {
		$value = get_option('wp_cardealer_listing_model_slug');
		?>
		<input name="wp_cardealer_listing_model_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-model', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_offer_type_slug_input() {
		$value = get_option('wp_cardealer_listing_offer_type_slug');
		?>
		<input name="wp_cardealer_listing_offer_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-offer-type', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_safety_type_slug_input() {
		$value = get_option('wp_cardealer_listing_safety_type_slug');
		?>
		<input name="wp_cardealer_listing_safety_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-safety-type', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_transmission_slug_input() {
		$value = get_option('wp_cardealer_listing_transmission_slug');
		?>
		<input name="wp_cardealer_listing_transmission_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listing-transmission', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function listing_archive_slug_input() {
		$value = get_option('wp_cardealer_listing_archive_slug');
		?>
		<input name="wp_cardealer_listing_archive_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'listings', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function dealer_base_slug_input() {
		$value = get_option('wp_cardealer_dealer_base_slug');
		?>
		<input name="wp_cardealer_dealer_base_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'dealer', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function dealer_archive_slug_input() {
		$value = get_option('wp_cardealer_dealer_archive_slug');
		?>
		<input name="wp_cardealer_dealer_archive_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'dealers', 'wp-cardealer' ); ?>" />
		<?php
	}

	public static function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		if ( isset( $_POST['permalink_structure'] ) ) {
			if ( function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( get_locale() );
			}
			if ( isset($_POST['wp_cardealer_listing_base_slug']) ) {
				update_option( 'wp_cardealer_listing_base_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_base_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_category_slug']) ) {
				update_option( 'wp_cardealer_listing_category_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_category_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_color_slug']) ) {
				update_option( 'wp_cardealer_listing_color_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_color_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_condition_slug']) ) {
				update_option( 'wp_cardealer_listing_condition_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_condition_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_label_slug']) ) {
				update_option( 'wp_cardealer_listing_label_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_label_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_cylinder_slug']) ) {
				update_option( 'wp_cardealer_listing_cylinder_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_cylinder_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_type_slug']) ) {
				update_option( 'wp_cardealer_listing_type_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_type_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_location_slug']) ) {
				update_option( 'wp_cardealer_listing_location_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_location_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_category_slug']) ) {
				update_option( 'wp_cardealer_listing_category_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_category_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_color_slug']) ) {
				update_option( 'wp_cardealer_listing_color_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_color_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_condition_slug']) ) {
				update_option( 'wp_cardealer_listing_condition_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_condition_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_label_slug']) ) {
				update_option( 'wp_cardealer_listing_label_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_label_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_cylinder_slug']) ) {
				update_option( 'wp_cardealer_listing_cylinder_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_cylinder_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_door_slug']) ) {
				update_option( 'wp_cardealer_listing_door_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_door_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_drive_type_slug']) ) {
				update_option( 'wp_cardealer_listing_drive_type_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_drive_type_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_feature_slug']) ) {
				update_option( 'wp_cardealer_listing_feature_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_feature_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_fuel_type_slug']) ) {
				update_option( 'wp_cardealer_listing_fuel_type_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_fuel_type_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_make_slug']) ) {
				update_option( 'wp_cardealer_listing_make_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_make_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_model_slug']) ) {
				update_option( 'wp_cardealer_listing_model_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_model_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_offer_type_slug']) ) {
				update_option( 'wp_cardealer_listing_offer_type_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_offer_type_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_safety_type_slug']) ) {
				update_option( 'wp_cardealer_listing_safety_type_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_safety_type_slug']) );
			}
			if ( isset($_POST['wp_cardealer_listing_transmission_slug']) ) {
				update_option( 'wp_cardealer_listing_transmission_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_transmission_slug']) );
			}

			if ( isset($_POST['wp_cardealer_listing_archive_slug']) ) {
				update_option( 'wp_cardealer_listing_archive_slug', sanitize_title_with_dashes($_POST['wp_cardealer_listing_archive_slug']) );
			}

			if ( isset($_POST['wp_cardealer_dealer_base_slug']) ) {
				update_option( 'wp_cardealer_dealer_base_slug', sanitize_title_with_dashes($_POST['wp_cardealer_dealer_base_slug']) );
			}

			if ( isset($_POST['wp_cardealer_dealer_archive_slug']) ) {
				update_option( 'wp_cardealer_dealer_archive_slug', sanitize_title_with_dashes($_POST['wp_cardealer_dealer_archive_slug']) );
			}

		}
	}
}

WP_CarDealer_Permalink_Settings::init();
