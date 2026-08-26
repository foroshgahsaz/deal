<?php
/**
 * CMB2 body damage field (admin).
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_CMB2_Field_Body_Damage {

	const VERSION = '1.0.1';

	public function __construct() {
		add_filter( 'cmb2_render_wpcd_body_damage', array( $this, 'render' ), 10, 5 );
		add_filter( 'cmb2_sanitize_wpcd_body_damage', array( $this, 'sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_wpcd_body_damage', array( $this, 'escaped_value' ), 10, 3 );
		add_action( 'save_post_listing', array( $this, 'save_listing_meta' ), 20, 2 );
	}

	public function render( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->enqueue_assets();

		if ( ! class_exists( 'WP_CarDealer_Listing_Body_Damage' ) ) {
			return;
		}

		$map     = WP_CarDealer_Listing_Body_Damage::get_damage_map( $field_object_id );
		$parts   = WP_CarDealer_Listing_Body_Damage::get_parts();
		$options = WP_CarDealer_Listing_Body_Damage::get_status_options();
		$name    = $field_type_object->_name();

		echo '<div class="wpcd-body-damage-admin">';
		echo '<table class="wpcd-body-damage-admin-table"><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'قطعه', 'wp-cardealer' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'وضعیت', 'wp-cardealer' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $parts as $slug => $label ) {
			$current = isset( $map[ $slug ] ) ? $map[ $slug ] : '';
			echo '<tr>';
			echo '<th scope="row" class="wpcd-body-damage-admin-part">' . esc_html( $label ) . '</th>';
			echo '<td class="wpcd-body-damage-admin-status">';
			echo '<select name="' . esc_attr( $name . '[' . $slug . ']' ) . '" class="wpcd-body-damage-admin-select">';
			foreach ( $options as $value => $option_label ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $value ),
					selected( $current, $value, false ),
					esc_html( $option_label )
				);
			}
			echo '</select></td></tr>';
		}

		echo '</tbody></table>';
		echo '<p class="cmb2-metabox-description wpcd-body-damage-admin-help">' . esc_html__( 'فقط بدنه فلزی. برای هر قطعه یک وضعیت انتخاب کنید. در سایت دیاگرام و لیست قطعات علامت‌خورده نمایش داده می‌شود.', 'wp-cardealer' ) . '</p>';
		echo '</div>';
	}

	public function sanitize( $check, $meta_value, $object_id, $field_args ) {
		unset( $check, $object_id, $field_args );

		if ( ! class_exists( 'WP_CarDealer_Listing_Body_Damage' ) ) {
			return array();
		}

		return WP_CarDealer_Listing_Body_Damage::sanitize_damage_map(
			$this->normalize_meta_value( $meta_value )
		);
	}

	public function escaped_value( $check, $meta_value, $field_args ) {
		unset( $field_args );

		if ( ! class_exists( 'WP_CarDealer_Listing_Body_Damage' ) ) {
			return $check;
		}

		if ( is_array( $meta_value ) ) {
			return WP_CarDealer_Listing_Body_Damage::sanitize_damage_map( $meta_value );
		}

		return WP_CarDealer_Listing_Body_Damage::sanitize_damage_map(
			$this->normalize_meta_value( $meta_value )
		);
	}

	/**
	 * Fallback save when CMB2 tabs do not persist the custom field.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_listing_meta( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! class_exists( 'WP_CarDealer_Listing_Body_Damage' ) ) {
			return;
		}

		if ( ! isset( $_POST[ WP_CarDealer_Listing_Body_Damage::META_KEY ] ) ) {
			return;
		}

		$raw = wp_unslash( $_POST[ WP_CarDealer_Listing_Body_Damage::META_KEY ] );
		if ( ! is_array( $raw ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			WP_CarDealer_Listing_Body_Damage::META_KEY,
			WP_CarDealer_Listing_Body_Damage::sanitize_damage_map( $raw )
		);
	}

	/**
	 * @param mixed $meta_value
	 * @return array
	 */
	private function normalize_meta_value( $meta_value ) {
		if ( is_array( $meta_value ) ) {
			return $meta_value;
		}

		if ( is_string( $meta_value ) && $meta_value !== '' ) {
			$decoded = json_decode( $meta_value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array();
	}

	public function enqueue_assets() {
		$asset_path = apply_filters( 'wpcd_cmb2_field_body_damage_asset_path', plugins_url( '', __FILE__ ) );
		wp_enqueue_style( 'wpcd-body-damage-admin', $asset_path . '/css/admin.css', array(), self::VERSION );
	}
}

new WP_CarDealer_CMB2_Field_Body_Damage();
