<?php

/**
 * Class WP_CarDealer_CMB2_Field_Enable_Input
 */
class WP_CarDealer_CMB2_Field_Enable_Input {

	/**
	 * Initialize the plugin by hooking into CMB2
	 */
	public function __construct() {
		add_filter( 'cmb2_render_wp_cardealer_enable_input', array( $this, 'render_enable_input' ), 10, 5 );
		add_filter( 'cmb2_sanitize_wp_cardealer_enable_input', array( $this, 'sanitize_enable_input' ), 10, 4 );
	}

	/**
	 * Render field
	 */
	public function render_enable_input( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		echo '<div class="enable-input-wrapper">';

		$checkbox_args = array(
			'type'       => 'checkbox',
			'name'       => $field->args( '_name' ) . '[enable]',
			'id'       => $field->args( '_name' ) . '_enable',
			'value'      => 'on',
			'desc'       => sprintf(__('Enable %s', 'wp-cardealer'), $field->args( 'name' )),
		);
		
		if ( !empty( $field_escaped_value['enable'] ) ) {
			$checkbox_args['checked'] = 'checked';
		}
		echo $field_type_object->input($checkbox_args);

		$placeholder = !empty($field->args( 'attributes' )['placeholder']) ? $field->args( 'attributes' )['placeholder'] : '';
		echo $field_type_object->input( array(
			'type'       => 'text',
			'name'       => $field->args( '_name' ) . '[key]',
			'id'       => $field->args( '_name' ) . '_key',
			'value'      => isset( $field_escaped_value['key'] ) ? $field_escaped_value['key'] : '',
			'class'       => 'cmb2-text-small',
			'placeholder' => $placeholder,
		) );

		echo '</div>';
	}

	public function sanitize_enable_input( $override_value, $value, $object_id, $field_args ) {
		if ( isset( $field_args['split_values'] ) && $field_args['split_values'] ) {
			
			if ( ! empty( $value['enable'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_enable', $value['enable'] );
			} else {
				update_post_meta( $object_id, $field_args['id'] . '_enable', '' );
			}

			if ( ! empty( $value['key'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_key', $value['key'] );
			}
		}

		return $value;
	}

}
$cmb2_field_enable_input = new WP_CarDealer_CMB2_Field_Enable_Input();
