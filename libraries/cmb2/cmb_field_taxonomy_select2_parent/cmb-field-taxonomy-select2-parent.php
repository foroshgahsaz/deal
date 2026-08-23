<?php
/*
Plugin Name: CMB2 Field Type: Select2
Plugin URI: https://github.com/mustardBees/cmb-field-select2
GitHub Plugin URI: https://github.com/mustardBees/cmb-field-select2
Description: Select2 field type for CMB2.
Version: 3.0.3
Author: Phil Wylie
Author URI: https://www.philwylie.co.uk/
License: GPLv2+
*/

/**
 * Class PW_CMB2_Field_Taxonomy_Select2_Parent
 */
class PW_CMB2_Field_Taxonomy_Select2_Parent {

	/**
	 * Current version number
	 */
	const VERSION = '3.0.3';

	/**
	 * Initialize the plugin by hooking into CMB2
	 */
	public function __construct() {
		add_filter( 'cmb2_render_pw_taxonomy_select_parent', array( $this, 'render_pw_taxonomy_select' ), 10, 5 );
		add_filter( 'cmb2_render_pw_taxonomy_multiselect_parent', array( $this, 'render_pw_taxonomy_multiselect' ), 10, 5 );
		add_filter( 'cmb2_sanitize_pw_taxonomy_multiselect_parent', array( $this, 'pw_taxonomy_multiselect_sanitize' ), 10, 4 );
		add_filter( 'cmb2_sanitize_pw_taxonomy_select_parent', array( $this, 'pw_taxonomy_select_sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_pw_taxonomy_multiselect_parent', array( $this, 'pw_taxonomy_multiselect_escaped_value' ), 10, 3 );

		add_filter( 'cmb2_render_pw_taxonomy_checkbox_parent', array( $this, 'render_pw_taxonomy_checkbox' ), 10, 5 );
		add_filter( 'cmb2_sanitize_pw_taxonomy_checkbox_parent', array( $this, 'pw_taxonomy_checkbox_sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_pw_taxonomy_checkbox_parent', array( $this, 'pw_taxonomy_checkbox_escaped_value' ), 10, 3 );

		add_filter( 'cmb2_repeat_table_row_types', array( $this, 'pw_taxonomy_multiselect_table_row_class' ), 10, 1 );
	}

	/**
	 * Render select box field
	 */
	public function render_pw_taxonomy_select( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}
		$attrs = $field->args( 'attributes' );
		echo $field_type_object->select( array(
			'class'            => 'pw_taxonomy_select2_parent pw_taxonomy_select_parent',
			'desc'             => $field_type_object->_desc( true ),
			'options'          => $this->get_pw_taxonomy_options( $field_escaped_value, $field_type_object ),
			'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
		) );
	}

	/**
	 * Render multi-value select input field
	 */
	public function render_pw_taxonomy_multiselect( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}


		$a = $field_type_object->parse_args( 'pw_taxonomy_multiselect_parent', array(
			'multiple'         => 'multiple',
			'style'            => 'width: 99%',
			'class'            => 'pw_taxonomy_select2_parent pw_taxonomy_multiselect_parent',
			'name'             => $field_type_object->_name() . '[]',
			'id'               => $field_type_object->_id(),
			'desc'             => $field_type_object->_desc( true ),
			'options'          => $this->get_pw_taxonomy_options( $field_escaped_value, $field_type_object ),
			'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
		) );

		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		echo sprintf( '<select%s>%s</select>%s', $attrs, $a['options'], $a['desc'] );
	}

	/**
	 * Render multi-value select input field
	 */
	public function render_pw_taxonomy_checkbox( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		
		$parent_options = (array) $this->get_terms($field_type_object->field->args( 'taxonomy' ));
		$field_escaped_value = $this->options_terms($field_type_object->field);

		// If we have selected items, we need to preserve their order
		if ( ! empty( $field_escaped_value ) ) {
			if ( !is_array($field_escaped_value) ) {
				$field_escaped_value = array($field_escaped_value);
			}
		}
// echo "<pre>".print_r($parent_options,1); die;
		$other_items = '';
		foreach ( $parent_options as $parent_term_slug => $options ) {

			if ( !empty($options['parent_term']) ) {

				$other_items .= '<div class="term-parent-wrapper">';
				$other_items .= '<h3>'.$options['parent_term']->name.'</h3>';
			} else {
				$other_items .= '<div class="term-parent-wrapper">';
				$other_items .= '<h3>'.esc_html__('General', 'wp-cardealer').'</h3>';
			}
			
			if ( !empty($options['terms']) ) {
				$other_items .= '<ul class="term-parent-inner">';

				foreach ( $options['terms'] as $term ) {
					// Split options into those which are selected and the rest
					if ( in_array( $term->term_id, (array) $field_escaped_value ) ) {
						$other_items .= '<li><label><input type="checkbox" name="'.$field_type_object->_name().'[]" value="'.$term->term_id.'" checked="checked">'.$term->name.'</label></li>';
					} else {
						$other_items .= '<li><label><input type="checkbox" name="'.$field_type_object->_name().'[]" value="'.$term->term_id.'">'.$term->name.'</label></li>';
					}
				}
				
				$other_items .= '</ul>';
			}

			$other_items .= '</div>';
		}

		echo $other_items.$field_type_object->_desc( true );
	}

	/**
	 * Return list of options for pw_taxonomy_multiselect
	 *
	 * Return the list of options, with selected options at the top preserving their order. This also handles the
	 * removal of selected options which no longer exist in the options array.
	 */
	public function get_pw_taxonomy_options( $field_escaped_value, $field_type_object ) {
		$parent_options = (array) $this->get_terms($field_type_object->field->args( 'taxonomy' ));
		
		$field_escaped_value = $this->options_terms($field_type_object->field);

		// If we have selected items, we need to preserve their order
		if ( ! empty( $field_escaped_value ) ) {
			if ( !is_array($field_escaped_value) ) {
				$field_escaped_value = array($field_escaped_value);
			}
			// $options = $this->sort_array_by_array( $options, $field_escaped_value );
		}

		$selected_items = '<option></option>';
		$other_items = '';
		foreach ( $parent_options as $parent_term_slug => $options ) {

			if ( !empty($options['parent_term']) ) {
				$other_items .= '<optgroup label="'.$options['parent_term']->name.'">';
			} else {
				$other_items .= '<optgroup label="'.esc_attr__('General', 'wp-cardealer').'">';
			}
			foreach ( $options['terms'] as $term ) {

				// Clone args & modify for just this item
				$option = array(
					'value' => $term->term_id,
					'label' => $term->name,
				);

				// Split options into those which are selected and the rest
				if ( in_array( $term->term_id, (array) $field_escaped_value ) ) {
					$option['checked'] = true;
					$other_items .= $field_type_object->select_option( $option );
				} else {
					$other_items .= $field_type_object->select_option( $option );
				}
			}

			$other_items .= '</optgroup>';
		}

		return $selected_items . $other_items;
	}

	public function options_terms($field) {
		if ( empty($field->data_args()['id']) ) {
			return array();
		}
		$object_id = $field->data_args()['id'];
		$terms = get_the_terms( $object_id, $field->args( 'taxonomy' ) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			foreach ( $terms as $index => $term ) {
				$terms[ $index ] = $term->term_id;
			}
		}

		return $terms;
	}
	
	/**
	 * Sort an array by the keys of another array
	 *
	 * @author Eran Galperin
	 * @link http://link.from.pw/1Waji4l
	 */
	public function sort_array_by_array( array $array, array $orderArray ) {
		$ordered = array();

		foreach ( $orderArray as $key ) {
			if ( array_key_exists( $key, $array ) ) {
				$ordered[ $key ] = $array[ $key ];
				unset( $array[ $key ] );
			}
		}

		return $ordered + $array;
	}

	/**
	 * Handle sanitization for repeatable fields
	 */
	public function pw_taxonomy_multiselect_sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( empty($meta_value) || !is_array( $meta_value ) ) {
			wp_set_object_terms( $object_id, '', $field_args['taxonomy'], false );
			return $check;
		}
		if ( $field_args['repeatable'] ) {
			foreach ( $meta_value as $key => $val ) {
				$meta_value[$key] = array_map( 'absint', $val );
				wp_set_object_terms( $object_id, array_map( 'absint', $val ), $field_args['taxonomy'], false );
			}
		} else {
			$meta_value = array_map( 'absint', $meta_value );
			wp_set_object_terms( $object_id, $meta_value, $field_args['taxonomy'], false );
		}

		return $meta_value;
	}

	/**
	 * Handle sanitization for repeatable fields
	 */
	public function pw_taxonomy_checkbox_sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( empty($meta_value) || !is_array( $meta_value ) ) {
			wp_set_object_terms( $object_id, '', $field_args['taxonomy'], false );
			return $check;
		}
		if ( $field_args['repeatable'] ) {
			foreach ( $meta_value as $key => $val ) {
				$meta_value[$key] = array_map( 'absint', $val );
				wp_set_object_terms( $object_id, array_map( 'absint', $val ), $field_args['taxonomy'], false );
			}
		} else {
			$meta_value = array_map( 'absint', $meta_value );
			wp_set_object_terms( $object_id, $meta_value, $field_args['taxonomy'], false );
		}

		return $meta_value;
	}

	/**
	 * Handle sanitization for repeatable fields
	 */
	public function pw_taxonomy_select_sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( empty( $meta_value ) ) {
			wp_set_object_terms( $object_id, '', $field_args['taxonomy'], false );
			return $check;
		}

		if ( is_array($meta_value) ) {
			$meta_value = array_map( 'absint', $meta_value );
		} else {
			$meta_value = intval($meta_value);
		}
		
		wp_set_object_terms( $object_id, $meta_value, $field_args['taxonomy'], false );

		return $meta_value;
	}

	/**
	 * Handle escaping for repeatable fields
	 */
	public function pw_taxonomy_multiselect_escaped_value( $check, $meta_value, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}

		foreach ( $meta_value as $key => $val ) {
			$meta_value[$key] = array_map( 'esc_attr', $val );
		}

		return $meta_value;
	}

	/**
	 * Handle escaping for repeatable fields
	 */
	public function pw_taxonomy_checkbox_escaped_value( $check, $meta_value, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}

		foreach ( $meta_value as $key => $val ) {
			$meta_value[$key] = array_map( 'esc_attr', $val );
		}

		return $meta_value;
	}

	/**
	 * Add 'table-layout' class to multi-value select field
	 */
	public function pw_taxonomy_multiselect_table_row_class( $check ) {
		$check[] = 'pw_taxonomy_multiselect_parent';

		return $check;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function setup_admin_scripts() {
		$asset_path = apply_filters( 'pw_cmb2_field_select2_asset_path', plugins_url( '', __FILE__  ) );

		wp_enqueue_script( 'pw-taxonomy-select2-parent-init', $asset_path . '/js/script.js', array( 'cmb2-scripts', 'wpcd-select2', 'jquery-ui-sortable' ), self::VERSION );
		wp_enqueue_style( 'pw-taxonomy-select2-parent-tweaks', $asset_path . '/css/style.css', array( 'wpcd-select2' ), self::VERSION );
	}

    public function get_terms($taxonomy, $query_args = array()) {
        
        $defaults = array(
	        'taxonomy' => $taxonomy,
	        'hide_empty' => false,
	        'orderby' => 'menu_order',
            'order' => 'ASC',
            'hierarchical' => 1,
            'lang' => apply_filters( 'wp-cardealer-current-lang', null )
	    );
	    $args = wp_parse_args( $query_args, $defaults );

	    $terms_hash = 'wpcd_cats_' . md5( wp_json_encode( $args ) . WP_CarDealer_Cache_Helper::get_transient_version('wpcd_get_' . $taxonomy) );
		$terms = get_transient( $terms_hash );

		if ( empty( $terms ) ) {
		    $terms = get_terms( $taxonomy, $args );
		    set_transient( $terms_hash, $terms, DAY_IN_SECONDS * 7 );
	    }
		
		$return = array();
	    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){

	        // foreach ( $terms as $key => $term ) {
	        // 	$parent = get_term_meta( $term->term_id, '_category_parent', true );
		       //  if ( !empty($parent) ) {
		       //      if ( !is_array($parent) ) {
		       //          $return[$parent][] = $term;
		       //      } else {
		       //          foreach ($parent as $parent_id) {
		       //              $return[$parent_id][] = $term;
		       //          }
		       //      }
		       //  } else {
		       //      $return[0][] = $term;
		       //  }
	        // }

		    foreach ($terms as $term) {
		        $parent = get_term_meta( $term->term_id, '_category_parent', true );
		        if ( !empty($parent) ) {
		            if ( !is_array($parent) ) {
		                $return[$parent]['terms'][] = $term;

		                $menu_order = 0;
		                $term = get_term_by('slug', $parent, 'listing_feature_category');
		                if ( $term ) {
		                    $menu_order = get_term_meta( $term->term_id, 'menu_order', true );
		                    $return[$parent]['priority'] = $menu_order;
		                    $return[$parent]['parent_term'] = $term;
		                } else {
		                	$return[$parent]['priority'] = 0;
		                }
		            } else {
		                foreach ($parent as $parent_id) {
		                    $return[$parent_id]['terms'][] = $term;
		                    $menu_order = 0;
		                    $term = get_term_by('slug', $parent_id, 'listing_feature_category');
		                    if ( $term ) {
		                        $menu_order = get_term_meta( $term->term_id, 'menu_order', true );
		                        $return[$parent_id]['priority'] = $menu_order;
		                        $return[$parent]['parent_term'] = $term;
		                    } else {
			                	$return[$parent]['priority'] = 0;
			                }
		                }
		            }
		        } else {
		            $return[0]['terms'][] = $term;
		            $return[$parent]['priority'] = 0;
		        }
		    }
		    
		    if ( $return ) {
		        uasort( $return, array( 'WP_CarDealer_Mixes', 'sort_array_by_priority' ) );
		    }
	    }

        return $return;
    }

    public function get_term_childs( $terms, $id_parent, $level, &$dropdown ) {
        foreach ( $terms as $key => $term ) {
            if ( $term->parent == $id_parent ) {
                $dropdown[$term->term_id] = str_repeat( "- ", $level ) . $term->name;
                unset($terms[$key]);
                $this->get_term_childs( $terms, $term->term_id, $level + 1, $dropdown );
            }
        }
    }
}
$pw_cmb2_field_select2 = new PW_CMB2_Field_Taxonomy_Select2_Parent();
