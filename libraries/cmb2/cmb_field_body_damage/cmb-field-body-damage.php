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

	const VERSION = '1.0.0';

	public function __construct() {
		add_filter( 'cmb2_render_wpcd_body_damage', array( $this, 'render' ), 10, 5 );
		add_filter( 'cmb2_sanitize_wpcd_body_damage', array( $this, 'sanitize' ), 10, 4 );
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
			return '';
		}
		return WP_CarDealer_Listing_Body_Damage::encode_damage_map( $meta_value );
	}

	public function enqueue_assets() {
		$asset_path = apply_filters( 'wpcd_cmb2_field_body_damage_asset_path', plugins_url( '', __FILE__ ) );
		wp_enqueue_style( 'wpcd-body-damage-admin', $asset_path . '/css/admin.css', array(), self::VERSION );
	}
}

new WP_CarDealer_CMB2_Field_Body_Damage();
