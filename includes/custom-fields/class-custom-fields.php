<?php
/**
 * Custom Fields
 *
 * @package    wp-cardealer
 * @author     Habq
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_CarDealer_Custom_Fields {
	
	public static function init() {
		// Listing submit admin
		add_filter( 'wp-cardealer-listing-fields-admin', array( __CLASS__, 'admin_custom_fields' ), 10 );

		// Listing submit frontend
		add_filter( 'wp-cardealer-listing-fields-front', array( __CLASS__, 'front_custom_fields' ), 100, 2 );

		// filter fields
		add_filter( 'wp-cardealer-default-listing-filter-fields', array( __CLASS__, 'filter_custom_fields' ), 100 );
		
		// compare fields
		add_filter( 'wp-cardealer-default-listing-compare-fields', array( __CLASS__, 'compare_custom_fields' ), 100 );


		// Dealer submit admin
		add_filter( 'wp-cardealer-dealer-fields-admin', array( __CLASS__, 'dealer_admin_custom_fields' ), 10 );

		// Dealer submit frontend
		add_filter( 'wp-cardealer-dealer-fields-front', array( __CLASS__, 'dealer_front_custom_fields' ), 100, 2 );

		// Dealer fields
		add_filter( 'wp-cardealer-default-dealer-filter-fields', array( __CLASS__, 'dealer_filter_custom_fields' ), 100 );


		// User submit admin
		add_filter( 'wp-cardealer-get-user-profile-fields-admin', array( __CLASS__, 'user_admin_custom_fields' ), 10, 2 );

		// User submit frontend
		add_filter( 'wp-cardealer-get-user-profile-fields', array( __CLASS__, 'user_front_custom_fields' ), 100, 2 );
	}

	public static function filter_custom_fields($old_fields) {
		$fields = self::get_search_custom_fields($old_fields, true);
		$fields['center-location'] = array(
			'name' => __( 'Location', 'wp-cardealer' ),
			'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input_location'),
			'placeholder' => __( 'All Location', 'wp-cardealer' ),
			'show_distance' => true,
			'toggle' => true,
			'for_post_type' => 'listing',
		);
		$fields['distance'] = array(
			'name' => __( 'Search Distance', 'wp-cardealer' ),
			'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input_distance'),
			'placeholder' => __( 'Distance', 'wp-cardealer' ),
			'toggle' => true,
			'for_post_type' => 'listing',
		);
		return apply_filters( 'wp-cardealer-types-add_custom_fields', $fields, $old_fields);
	}

	public static function compare_custom_fields($old_fields) {
		$fields = self::get_compare_custom_fields($old_fields, true);
		return apply_filters( 'wp-cardealer-types-add_custom_fields', $fields, $old_fields);
	}

	public static function admin_custom_fields() {
		$prefix = WP_CARDEALER_LISTING_PREFIX;
		$init_fields = self::get_custom_fields(array(), true, 0, $prefix);
		$init_fields = apply_filters( 'wp-cardealer-types-admin_custom_fields', $init_fields);
		
		$fields = array();
		$key_tab = 'tab-heading-start'.rand(100,1000);
		$tab_data = array(
			'id' => $key_tab,
			'icon' => 'dashicons-admin-home',
			'title'  => esc_html__( 'General', 'wp-cardealer' ),
			'fields' => array(),
		);
		$i = 0;
		foreach ($init_fields as $key => $field) {
			if ( $i == 0 && (empty($field['type']) || $field['type'] !== 'title') ) {
				$fields[$key_tab] = $tab_data;
			} elseif ( !empty($field['type']) && $field['type'] == 'title' ) {
				$key_tab = $field['id'];
				$fields[$key_tab] = array(
					'id' => $key_tab,
					'icon' => !empty($field['icon']) ? $field['icon'] : '',
					'title'  => !empty($field['name']) ? $field['name'] : '',
					'fields' => array(),
				);
			}

			$fields[$key_tab]['fields'][] = $field;
			$i++;
		}
		
		// author fields
		$post_author_id = '';
		if ( !empty($_GET['post']) ) {
			$post_author_id = get_post_field( 'post_author', $_GET['post'] );
		}
		$author_key = 'tab-heading-author'.rand(100,1000);
		$fields[$author_key] = array(
			'id' => $author_key,
			'icon' => 'dashicons-admin-users',
			'title'  => esc_html__( 'Author', 'wp-cardealer' ),
			'fields' => array(
				array(
					'name'          => __( 'Author', 'wp-cardealer' ),
					'id'            => WP_CARDEALER_LISTING_PREFIX . 'posted_by',
					'type'          => 'user_ajax_search',
					'default'		=> $post_author_id
				)
			),
		);

		$box_options = array(
			'id'           => 'listing_metabox',
			'title'        => esc_html__( 'Listing Metabox', 'wp-cardealer' ),
			'object_types' => array( 'listing' ),
			'show_names'   => true,
		);
		
		// Setup meta box
		$cmb = new_cmb2_box( $box_options );

		// Set tabs
		$cmb->add_field( [
			'id'   => '__tabs',
			'type' => 'tabs',
			'tabs' => array(
				'config' => $box_options,
				'layout' => 'vertical', // Default : horizontal
				'tabs'   => apply_filters('wp-cardealer-admin-custom-fields', $fields),
			),
		] );

		return true;
	}

	public static function front_custom_fields($old_fields, $post_id) {
		$prefix = WP_CARDEALER_LISTING_PREFIX;
		$fields = self::get_custom_fields($old_fields, false, $post_id, $prefix);
		
		return apply_filters( 'wp-cardealer-types-submit_form_fields', $fields, $old_fields, $post_id);
	}

	// Dealer
	public static function dealer_filter_custom_fields($old_fields) {
		$fields = self::get_search_custom_fields($old_fields, true, WP_CARDEALER_DEALER_PREFIX);
		$fields['center-location'] = array(
			'name' => __( 'Location', 'wp-cardealer' ),
			'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input_location'),
			'placeholder' => __( 'All Location', 'wp-cardealer' ),
			'show_distance' => true,
			'toggle' => true,
			'for_post_type' => 'dealer',
		);
		$fields['distance'] = array(
			'name' => __( 'Search Distance', 'wp-cardealer' ),
			'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input_distance'),
			'placeholder' => __( 'Distance', 'wp-cardealer' ),
			'toggle' => true,
			'for_post_type' => 'dealer',
		);
		return apply_filters( 'wp-cardealer-types-dealer-add_custom_fields', $fields, $old_fields);
	}

	public static function dealer_admin_custom_fields() {
		$prefix = WP_CARDEALER_DEALER_PREFIX;
		$init_fields = self::get_custom_fields(array(), true, 0, $prefix);
		$init_fields = apply_filters( 'wp-cardealer-types-dealer-admin_custom_fields', $init_fields);
		
		$fields = array();
		$key_tab = 'tab-heading-start'.rand(100,1000);
		$tab_data = array(
			'id' => $key_tab,
			'icon' => 'dashicons-admin-home',
			'title'  => esc_html__( 'General', 'wp-cardealer' ),
			'fields' => array(),
		);
		$i = 0;
		foreach ($init_fields as $key => $field) {
			if ( $i == 0 && (empty($field['type']) || $field['type'] !== 'title') ) {
				$fields[$key_tab] = $tab_data;
			} elseif ( !empty($field['type']) && $field['type'] == 'title' ) {
				$key_tab = $field['id'];
				$fields[$key_tab] = array(
					'id' => $key_tab,
					'icon' => !empty($field['icon']) ? $field['icon'] : '',
					'title'  => !empty($field['name']) ? $field['name'] : '',
					'fields' => array(),
				);
			}

			$fields[$key_tab]['fields'][] = $field;
			$i++;
		}
		
		// author fields
		$post_author_id = '';
		if ( !empty($_GET['post']) ) {
			$post_author_id = get_post_field( 'post_author', $_GET['post'] );
		}
		$author_key = 'tab-heading-author'.rand(100,1000);
		$fields[$author_key] = array(
			'id' => $author_key,
			'icon' => 'dashicons-admin-users',
			'title'  => esc_html__( 'Author', 'wp-cardealer' ),
			'fields' => array(
				array(
					'name'          => __( 'Author', 'wp-cardealer' ),
					'id'            => WP_CARDEALER_DEALER_PREFIX . 'posted_by',
					'type'          => 'user_ajax_search',
					'default'		=> $post_author_id
				)
			),
		);

		$box_options = array(
			'id'           => 'dealer_metabox',
			'title'        => esc_html__( 'Dealer Metabox', 'wp-cardealer' ),
			'object_types' => array( 'dealer' ),
			'show_names'   => true,
		);
		
		// Setup meta box
		$cmb = new_cmb2_box( $box_options );

		// Set tabs
		$cmb->add_field( [
			'id'   => '__tabs',
			'type' => 'tabs',
			'tabs' => array(
				'config' => $box_options,
				'layout' => 'vertical', // Default : horizontal
				'tabs'   => apply_filters('wp-cardealer-admin-dealer-custom-fields', $fields),
			),
		] );

		return true;
	}

	public static function dealer_front_custom_fields($old_fields, $post_id) {
		$prefix = WP_CARDEALER_DEALER_PREFIX;
		$fields = self::get_custom_fields($old_fields, false, $post_id, $prefix);
		
		return apply_filters( 'wp-cardealer-types-dealer-submit_form_fields', $fields, $old_fields, $post_id);
	}

	// user
	public static function user_admin_custom_fields($old_fields, $user_id) {
		$prefix = WP_CARDEALER_USER_PREFIX;
		$fields = self::get_custom_fields($old_fields, true, $user_id, $prefix);
		
		return apply_filters( 'wp-cardealer-user-admin-types-submit_form_fields', $fields, $old_fields, $user_id, $prefix);
	}

	public static function user_front_custom_fields($old_fields, $user_id) {
		$prefix = WP_CARDEALER_USER_PREFIX;
		$fields = self::get_custom_fields($old_fields, false, $user_id, $prefix);
		
		return apply_filters( 'wp-cardealer-user-types-submit_form_fields', $fields, $old_fields, $user_id, $prefix);
	}

	public static function get_all_custom_fields($old_fields, $admin_field = true, $prefix = WP_CARDEALER_LISTING_PREFIX) {
		
		$fields = array();

		$custom_all_fields = WP_CarDealer_Fields_Manager::get_custom_fields_data($prefix);
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_CarDealer_Fields_Manager::get_all_field_type_keys();
			if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
		        $available_types = WP_CarDealer_Fields_Manager::get_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_all_types_fields_required();
		    } elseif ( $prefix == WP_CARDEALER_DEALER_PREFIX ) {
		    	$available_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_required();
		    }
			$i = 1;

			foreach ($custom_all_fields as $key => $custom_field) {
				
				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) ) {
					$type = '';
					$required_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $available_types);

					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
						$fieldtype_type = 'required';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
						$fieldtype_type = 'available';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$fieldtype_type = 'custom';
						$field_data = $custom_field;
						if ( in_array($fieldtype, array('heading', 'file', 'url', 'email')) ) {
							continue;
						}
					}

					
						$id = str_replace($prefix, '', $field_data['id']);
						$fields[$id] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, $fieldtype_type);
					
				}
				$i++;
			}

			// echo "<pre>".print_r($fields,1); die;
		} else {
			$fields = $old_fields;
		}

		return $fields;
	}

	public static function get_search_custom_fields($old_fields, $admin_field = true, $prefix = WP_CARDEALER_LISTING_PREFIX) {
		
		$fields = array();

		$custom_all_fields = WP_CarDealer_Fields_Manager::get_custom_fields_data($prefix);
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_CarDealer_Fields_Manager::get_all_field_type_keys();
			if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
		        $available_types = WP_CarDealer_Fields_Manager::get_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_all_types_fields_required();
		    } elseif ( $prefix == WP_CARDEALER_DEALER_PREFIX ) {
		    	$available_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_required();
		    }
			$i = 1;

			foreach ($custom_all_fields as $key => $custom_field) {
				
				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) ) {
					$type = '';
					$required_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $available_types);

					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
						$fieldtype_type = 'required';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
						$fieldtype_type = 'available';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$fieldtype_type = 'custom';
						$field_data = $custom_field;
						if ( in_array($fieldtype, array('heading', 'file', 'url', 'email')) ) {
							continue;
						}
					}

					if ( !in_array($fieldkey, array( $prefix.'heading', $prefix.'featured_image', $prefix.'gallery', $prefix.'description', $prefix.'expiry_date', $prefix.'price_prefix', $prefix.'price_suffix', $prefix.'price_custom', $prefix.'customs_fee', $prefix.'shipping_fee', $prefix.'lot_dimensions', $prefix.'video', $prefix.'map_location', $prefix.'featured_image', $prefix.'gallery', $prefix.'attachments', $prefix.'address', $prefix.'file' )) ) {

						$id = str_replace($prefix, '', $field_data['id']);
						$fields[$id] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, $fieldtype_type);
						if ( empty($fields[$id]['field_call_back']) ) {
							if ( !empty($field_data['field_call_back']) ) {
								$fields[$id]['field_call_back'] = $field_data['field_call_back'];
							} else {
								unset($fields[$id]);
							}
						}
					}
				}
				$i++;
			}

			// echo "<pre>".print_r($fields,1); die;
		} else {
			$fields = $old_fields;
		}

		return $fields;
	}

	public static function get_compare_custom_fields($old_fields, $admin_field = true) {
		$prefix = WP_CARDEALER_LISTING_PREFIX;
		$fields = array();
		
		$custom_all_fields = WP_CarDealer_Fields_Manager::get_custom_fields_data($prefix);
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_CarDealer_Fields_Manager::get_all_field_type_keys();
	        $available_types = WP_CarDealer_Fields_Manager::get_all_types_fields_available();
	        $required_types = WP_CarDealer_Fields_Manager::get_all_types_fields_required();
			$i = 1;

			foreach ($custom_all_fields as $key => $custom_field) {
				
				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) ) {
					$type = '';
					$required_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $available_types);
					$custom_field_type = '';
					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
						if ( empty($field_data['show_compare']) ) {
							continue;
						}
						$custom_field_type = 'required';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
						if ( empty($field_data['show_compare']) ) {
							continue;
						}
						$custom_field_type = 'available';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$field_data = $custom_field;
						if ( in_array($fieldtype, array('heading', 'file', 'url', 'email')) ) {
							continue;
						}
						$custom_field_type = 'custom_field';
					}

					$id = str_replace(WP_CARDEALER_LISTING_PREFIX, '', $field_data['id']);
					$field = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field);
					$field['custom_field_type'] = $custom_field_type;
					$fields[$id] = $field;
				}
				$i++;
			}
		} else {
			$fields = $old_fields;
		}
		//echo "<pre>".print_r($fields,1); die;
		return $fields;
	}

	public static function get_custom_fields($old_fields, $admin_field = true, $post_id = 0, $prefix = WP_CARDEALER_LISTING_PREFIX) {
		
		$fields = array();

		$package_id = 0;
		if ( !$admin_field ) {
			$package_id = self::get_package_id($post_id);
		}
		
		$custom_all_fields = WP_CarDealer_Fields_Manager::get_custom_fields_data($prefix);
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_CarDealer_Fields_Manager::get_all_field_type_keys();
			if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
		        $available_types = WP_CarDealer_Fields_Manager::get_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_all_types_fields_required();
		    } elseif ( $prefix == WP_CARDEALER_DEALER_PREFIX ) {
		        $available_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_dealer_all_types_fields_required();
		    } else {
		    	$available_types = WP_CarDealer_Fields_Manager::get_user_all_types_fields_available();
		        $required_types = WP_CarDealer_Fields_Manager::get_user_all_types_fields_required();
		    }
			$i = 1;
			foreach ($custom_all_fields as $key => $custom_field) {
				$check_package_field = true;
				if ( $prefix == WP_CARDEALER_LISTING_PREFIX && !$admin_field ) {
					$check_package_field = self::check_package_field($custom_field, $package_id);
				}

				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) && $check_package_field ) {
					$type = '';
					$required_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_CarDealer_Fields_Manager::get_field_id($fieldkey, $available_types);
					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$field_data = $custom_field;
					}
					
					if ( !$admin_field && (!empty($field_data['show_in_submit_form']) || $fieldtype == 'heading') && $fieldkey !== $prefix.'featured' ) {
						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i, false, '', $prefix);
					} elseif( $admin_field && (!empty($field_data['show_in_admin_edit']) || $fieldtype == 'heading') && !in_array($fieldkey, array( $prefix.'title', $prefix.'description', $prefix.'featured_image', WP_CARDEALER_USER_PREFIX.'email', WP_CARDEALER_USER_PREFIX.'first_name', WP_CARDEALER_USER_PREFIX.'last_name', WP_CARDEALER_USER_PREFIX.'description', WP_CARDEALER_USER_PREFIX.'url' ))) {

						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, '', $prefix);
					}
				}
				$i++;
			}
		} else {
			$fields = $old_fields;
		}
		return $fields;
	}

	public static function get_package_id($post_id) {
		
		$package_id = apply_filters('wp-cardealer-get-listing-package-id', 0, $post_id);
		
		return apply_filters( 'wp-cardealer-types-get_package_id', $package_id);
	}

	public static function check_package_field($field, $package_id) {
		$return = false;
		if ( empty($package_id) ) {
			$return = true;
		}
		if ( empty($field['show_in_package']) ) {
			$return = true;
		}
		if ( !empty($field['show_in_package']) ) {
			$package_display = !empty($field['package_display']) ? $field['package_display'] : array();
			if ( !empty($package_display) && is_array($package_display) && in_array($package_id, $package_display) ) {
				$return = true;
			}
		}
		
		return apply_filters( 'wp-cardealer-types-check_package_field', $return, $field, $package_id);
	}

	public static function render_field($field_data, $fieldkey, $fieldtype, $priority, $admin_field = false, $fieldtype_type = '', $prefix = WP_CARDEALER_LISTING_PREFIX) {
		$name = stripslashes(isset($field_data['name']) ? $field_data['name'] : '');
		$id = isset($field_data['id']) ? $field_data['id'] : '';
        $placeholder = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $format = isset($field_data['format']) ? $field_data['format'] : '';
        $required = isset($field_data['required']) ? $field_data['required'] : '';
        $default = isset($field_data['default']) ? $field_data['default'] : '';

		$field = array(
			'name' => $name,
			'id' => $id,
			'type' => $fieldtype,
			'priority' => $priority,
			'description' => $description,
			'default' => $default,
			'attributes' => array()
		);
		if ( !empty($field_data['attributes']) ) {
			$field['attributes'] = $field_data['attributes'];
		}
		if ( $placeholder ) {
			$field['attributes']['placeholder'] = $placeholder;
			$field['placeholder'] = $placeholder;
		}
		if ( $required ) {
			$field['attributes']['required'] = 'required';
			$field['label_cb'] = array( 'WP_CarDealer_Mixes', 'required_add_label' );
		}
		if ( $fieldtype_type == 'custom' ) {
			$field['filter-name-prefix'] = 'filter-cfield';
		}
		switch ($fieldtype) {
			case 'wysiwyg':
			case 'textarea':
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'text':
				$field['type'] = 'text';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'number':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'number';
				$field['attributes']['min'] = 0;
				$field['attributes']['pattern'] = '\d*';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'url':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'url';
				$field['attributes']['pattern'] = 'https?://.+';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'email':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'email';
				$field['attributes']['pattern'] = '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2, 4}$';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'date':
				$field['type'] = 'text_date';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_date_field_input');
					$field['filter-name-prefix'] = 'filter-cfielddate';
				}
				$field['date_format'] = 'Y-m-d';
				break;
			case 'checkbox':
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_checkbox');
				}
				break;
			case 'radio':
			case 'select':
				$doptions = !empty($field_data['options']) ? $field_data['options'] : array();
				$options = array();
				if ( !empty($placeholder) ) {
					$options = array('' => $placeholder);
				}
				if ( is_array($doptions) ) {
					$options = $doptions;
				} elseif ( !empty($doptions) ) {
					$doptions = explode("\n", str_replace("\r", "", stripslashes($doptions)));
					foreach ($doptions as $val) {
						$options[$val] = $val;
					}
				}
				$field['options'] = $options;
				if ( $fieldtype == 'select' ) {
					$field['type'] = 'pw_select';
				}
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_select');
				}
				break;
			case 'multiselect':
				$doptions = !empty($field_data['options']) ? $field_data['options'] : array();
				$options = array();
				if ( !empty($placeholder) ) {
					$options = array('' => $placeholder);
				}
				if ( is_array($doptions) ) {
					$options = $doptions;
				} elseif ( !empty($doptions) ) {
					$doptions = explode("\n", str_replace("\r", "", stripslashes($doptions)));
					foreach ($doptions as $val) {
						$options[$val] = $val;
					}
				}
				$field['options'] = $options;
				$field['type'] = 'pw_multiselect';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_CarDealer_Abstract_Filter', 'filter_field_select');
				}
				break;
			case 'file':
				$allow_types = !empty($field_data['allow_types']) ? $field_data['allow_types'] : array();
				$multiples = !empty($field_data['multiple_files']) ? $field_data['multiple_files'] : false;
				$ajax = !empty($field_data['ajax']) ? $field_data['ajax'] : false;

				
				$field['ajax'] = $ajax ? true : (is_user_logged_in() ? true : false);
				if ( $multiples ) {
					$field['file_limit'] = !empty($field_data['file_limit']) ? $field_data['file_limit'] : 10;
				}
				if ( !$admin_field ) {
					$field['type'] = 'wp_cardealer_file';
					$field['file_multiple'] = $multiples ? true : false;

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$all_mime_types = get_allowed_mime_types();
						$mime_types = array();
						foreach ($allow_types as $mime_type) {
							$tmime = explode('|', $mime_type);
							$mime_types = array_merge($mime_types, $tmime);

							if ( isset($all_mime_types[$mime_type]) ) {
								$allowed_mime_types[] = $all_mime_types[$mime_type];
							}
						}

						$field['mime_types'] = $mime_types;
						$field['allow_mime_types'] = $allowed_mime_types;
					}
				} else {
					if ( !$multiples ) {
						$field['type'] = 'file';
						$field['preview_size'] = 'thumbnail';
					} else {
						$field['type'] = 'file_list';
					}

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$mime_types = get_allowed_mime_types();
						foreach ($allow_types as $mime_type) {
							if ( isset($mime_types[$mime_type]) ) {
								$allowed_mime_types[$mime_type] = $mime_types[$mime_type];
							}
						}
						$field['allow_mime_types'] = $allowed_mime_types;
						$field['query_args']['type'] = $allowed_mime_types;
					}
				}
				break;
			case 'wp_cardealer_file':
				$allow_types = !empty($field_data['allow_types']) ? $field_data['allow_types'] : array();

				$multiples = !empty($field_data['multiple_files']) ? $field_data['multiple_files'] : false;
				$ajax = !empty($field_data['ajax']) ? $field_data['ajax'] : false;
				
				$field['ajax'] = $ajax ? true : false;
				if ( $multiples ) {
					$field['file_limit'] = !empty($field_data['file_limit']) ? $field_data['file_limit'] : 10;
				}
				if ( !$admin_field ) {
					$field['file_multiple'] = $multiples ? true : false;
					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$all_mime_types = get_allowed_mime_types();
						$mime_types = array();
						foreach ($allow_types as $mime_type) {
							$tmime = explode('|', $mime_type);
							$mime_types = array_merge($mime_types, $tmime);

							if ( isset($all_mime_types[$mime_type]) ) {
								$allowed_mime_types[] = $all_mime_types[$mime_type];
							}
						}
						$field['mime_types'] = $mime_types;
						$field['allow_mime_types'] = $allowed_mime_types;
					}

				} else {
					if ( !$multiples ) {
						$field['type'] = 'file';
						$field['preview_size'] = 'thumbnail';
					} else {
						$field['type'] = 'file_list';
					}

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$mime_types = get_allowed_mime_types();
						foreach ($allow_types as $mime_type) {
							if ( isset($mime_types[$mime_type]) ) {
								$allowed_mime_types[$mime_type] = $mime_types[$mime_type];
							}
						}
						$field['allow_mime_types'] = $allowed_mime_types;
						$field['query_args']['type'] = $allowed_mime_types;
					}
				}
				break;
			case 'heading':
				$field['type'] = 'title';
				$field['icon'] = !empty($field_data['icon']) ? $field_data['icon'] : '';
				$field['number_columns'] = !empty($field_data['number_columns']) ? $field_data['number_columns'] : '';
			case 'pw_map':

				$field['split_values'] = isset($field_data['split_values']) ? $field_data['split_values'] : false;
			case 'repeater':
			case 'group':
				$subfields = array();
				if ( !empty($field_data['fields']) ) {
					foreach ($field_data['fields'] as $subf) {
						$subfield = $subf;
						if ( !empty($subfield['type']) && $subfield['type'] == 'wp_cardealer_file' ) {
							if ( $admin_field ) {
								$subfield['type'] = 'file';
								$subfield['preview_size'] = 'thumbnail';
							}
							$subfields[] = $subfield;
						} else {
							$subfields[] = $subfield;
						}
					}
				}
				$field['fields'] = $subfields;
				if ( !empty($field_data['options']) ) {
					$field['options'] = $field_data['options'];
				}
				break;
		}
    	
    	switch ($fieldkey) {
			case $prefix.'parent_listing':
				$post__not_in = 0;
				if ( $admin_field ) {
					if ( !empty($_GET['post']) ) {
						$author = get_post_field( 'post_author', $_GET['post'] );
						$post__not_in = $_GET['post'];
					} else {
						$author = get_current_user_id();
						$post__not_in = 0;
					}
				} else {
					$author = get_current_user_id();
					$post__not_in = !empty( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;
				}

				$args = array(
					'fields' => 'ids',
					'author' => $author,
					'post__not_in' => array($post__not_in)
				);
				$posts = WP_CarDealer_Query::get_posts($args);

				if ( !empty($posts->posts) ) {
					$options = array();
					
					foreach ($posts->posts as $post_id) {
						$options[$post_id] = get_post_field('post_title', $post_id);
					}
					$field['options'] = $options;
				}
				$field['attributes']['data-allowclear'] = true;
			break;
			case $prefix.'description':
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'wysiwyg';
				if ( !empty($field_data['options']) ) {
					$field['options'] = $field_data['options'];
				}
			break;
			case $prefix.'location':
				$field['taxonomy'] = ! empty( $field_data['taxonomy'] ) ? $field_data['taxonomy'] : 'listing_location';
				$field['type'] = 'wpcd_taxonomy_location';
				if ( empty( $field['attributes'] ) || ! is_array( $field['attributes'] ) ) {
					$field['attributes'] = array();
				}
				$field['attributes']['placeholder'] = ! empty( $field['placeholder'] ) ? $field['placeholder'] : 'شهر را انتخاب کنید';
				break;
			case $prefix.'type':
			case $prefix.'make':
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';
				$field['taxonomy'] = !empty($field_data['taxonomy']) ? $field_data['taxonomy'] : '';
				$field['attributes']['data-condition'] = 'listing_category';
				break;
			break;
			case $prefix.'model':
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';
				$field['taxonomy'] = !empty($field_data['taxonomy']) ? $field_data['taxonomy'] : '';
				$field['attributes']['data-condition'] = 'listing_make';
				break;
			break;
			case $prefix.'condition':
			case $prefix.'label':
			case $prefix.'category':
			case $prefix.'color':
			
			case $prefix.'cylinder':
			case $prefix.'door':
			case $prefix.'drive_type':
			case $prefix.'feature':
			case $prefix.'fuel_type':
			case $prefix.'offer_type':
			case $prefix.'safety_type':
			case $prefix.'transmission':
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';
				$field['taxonomy'] = !empty($field_data['taxonomy']) ? $field_data['taxonomy'] : '';
				break;
			case $prefix.'expiry_date':
				$field['date_format'] = !empty($field_data['date_format']) ? $field_data['date_format'] : 'Y-m-d';
				break;
		}

		if ( !$admin_field && $prefix == WP_CARDEALER_USER_PREFIX && is_user_logged_in() ) {

			$userdata = wp_get_current_user();
			switch ($fieldkey) {
				case $prefix.'first_name':
					$field['default'] = get_user_meta( $userdata->ID, 'first_name', true );
					break;
				case $prefix.'last_name':
					$field['default'] = get_user_meta( $userdata->ID, 'last_name', true );
					break;
				case $prefix.'description':
					$field['default'] = get_user_meta( $userdata->ID, 'description', true );
					break;
				case $prefix.'email':
					$field['default'] = $userdata->user_email;
					break;
				case $prefix.'url':
					$field['default'] = $userdata->user_url;
					break;
			}
		}

		if ( class_exists( 'WP_CarDealer_Price' ) && WP_CarDealer_Price::is_listing_fee_field( $id ) ) {
			$field['type'] = 'text';
			$field['sanitization_cb'] = array( 'WP_CarDealer_Price', 'sanitize_toman_fee' );
			if ( empty( $field['attributes'] ) || ! is_array( $field['attributes'] ) ) {
				$field['attributes'] = array();
			}
			$field['attributes']['type'] = 'number';
			$field['attributes']['min'] = 0;
			$field['attributes']['step'] = '1';
			$field['attributes']['inputmode'] = 'numeric';
		}

		return apply_filters( 'wp-cardealer-types-render_field', $field, $field_data, $fieldkey, $fieldtype, $priority);
	}

}
WP_CarDealer_Custom_Fields::init();