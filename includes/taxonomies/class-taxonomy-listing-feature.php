<?php
/**
 * Features
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_CarDealer_Taxonomy_Car_Feature{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );

		add_filter( "manage_edit-listing_feature_columns", array( __CLASS__, 'tax_columns' ) );
		add_filter( "manage_listing_feature_custom_column", array( __CLASS__, 'tax_column' ), 10, 3 );
		add_action( "listing_feature_add_form_fields", array( __CLASS__, 'add_fields_form' ) );
		add_action( "listing_feature_edit_form_fields", array( __CLASS__, 'edit_fields_form' ), 10, 2 );

		add_action( 'create_term', array( __CLASS__, 'save' ), 10, 3 );
		add_action( 'edit_term', array( __CLASS__, 'save' ), 10, 3 );


		add_filter( "manage_edit-listing_feature_category_columns", array( __CLASS__, 'feature_category_tax_columns' ) );
		add_filter( "manage_listing_feature_category_custom_column", array( __CLASS__, 'feature_category_tax_column' ), 10, 3 );

		add_action( "listing_feature_category_add_form_fields", array( __CLASS__, 'feature_category_add_fields_form' ) );
		add_action( "listing_feature_category_edit_form_fields", array( __CLASS__, 'feature_category_edit_fields_form' ), 10, 2 );
	}

	/**
	 *
	 */
	public static function definition() {
		// Feature Category
		$singular = __( 'Features Category', 'wp-cardealer' );
		$plural   = __( 'Features Categories', 'wp-cardealer' );

		$labels = array(
			'name'              => sprintf(__( 'Car %s', 'wp-cardealer' ), $plural),
			'singular_name'     => $singular,
			'search_items'      => sprintf(__( 'Search %s', 'wp-cardealer' ), $plural),
			'all_items'         => sprintf(__( 'All %s', 'wp-cardealer' ), $plural),
			'parent_item'       => sprintf(__( 'Parent %s', 'wp-cardealer' ), $singular),
			'parent_item_colon' => sprintf(__( 'Parent %s:', 'wp-cardealer' ), $singular),
			'edit_item'         => __( 'Edit', 'wp-cardealer' ),
			'update_item'       => __( 'Update', 'wp-cardealer' ),
			'add_new_item'      => __( 'Add New', 'wp-cardealer' ),
			'new_item_name'     => sprintf(__( 'New %s', 'wp-cardealer' ), $singular),
			'menu_name'         => $plural,
		);

		register_taxonomy( 'listing_feature_category', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_feature_category_labels', $labels ),
			'hierarchical'      => false,
			'parent_item'       => null,
        	'parent_item_colon' => null,
			'rewrite'           => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'		=> true,
			'show_in_quick_edit'=> false,
			'meta_box_cb'       => false,
		) );

		//
		$singular = __( 'Feature', 'wp-cardealer' );
		$plural   = __( 'Features', 'wp-cardealer' );

		$labels = array(
			'name'              => sprintf(__( 'Car %s', 'wp-cardealer' ), $plural),
			'singular_name'     => $singular,
			'search_items'      => sprintf(__( 'Search %s', 'wp-cardealer' ), $plural),
			'all_items'         => sprintf(__( 'All %s', 'wp-cardealer' ), $plural),
			'parent_item'       => sprintf(__( 'Parent %s', 'wp-cardealer' ), $singular),
			'parent_item_colon' => sprintf(__( 'Parent %s:', 'wp-cardealer' ), $singular),
			'edit_item'         => __( 'Edit', 'wp-cardealer' ),
			'update_item'       => __( 'Update', 'wp-cardealer' ),
			'add_new_item'      => __( 'Add New', 'wp-cardealer' ),
			'new_item_name'     => sprintf(__( 'New %s', 'wp-cardealer' ), $singular),
			'menu_name'         => $plural,
		);

		$rewrite_slug = get_option('wp_cardealer_listing_feature_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'listing-feature', 'Car feature slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'listing_feature', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_feature_labels', $labels ),
			'hierarchical'      => false,
			'parent_item'       => null,
        	'parent_item_colon' => null,
			'rewrite'           => $rewrite,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'		=> true,
			'show_in_quick_edit'=> false,
			'meta_box_cb'       => false,
		) );

	}

	public static function tax_columns( $columns ) {
		$new_columns = array();
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ( $key == 'name' ) {
				$new_columns['category'] = esc_html__( 'Category', 'wp-cardealer' );
			}
		}
		return $new_columns;
	}

	public static function tax_column( $columns, $column, $id ) {
		if ( $column == 'category' ) {
			$type_slugs = get_term_meta( $id, '_category_parent', true );
			if ( $type_slugs ) {
				
				$term = get_term_by('slug', $type_slugs, 'listing_feature_category');
				if ( $term ) {
					$columns .= $term->name;
				}

			} else {
				$columns .= '--';
			}
		}

		return $columns;
	}

	public static function add_fields_form($taxonomy) {
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Select Category', 'wp-cardealer' ); ?></label>
			<?php self::categories_field(); ?>
		</div>
		<?php
	}

	public static function edit_fields_form( $term, $taxonomy ) {
		$type_value = get_term_meta( $term->term_id, '_category_parent', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Select Category', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::categories_field($type_value); ?>
			</td>
		</tr>
		<?php
	}

	public static function categories_field( $val = '' ) {
		$args = array(
			'taxonomy' => 'listing_feature_category',
			'posts_per_page' => -1,
			'hide_empty' => 0,
		);
		
		$terms_hash = 'wpcd_cats_' . md5( wp_json_encode( $args ) . WP_CarDealer_Cache_Helper::get_transient_version('wpcd_get_listing_feature_category') );
		$terms = get_transient( $terms_hash );
		if ( empty( $terms ) ) {
			$terms = get_terms($args);
			set_transient( $terms_hash, $terms, DAY_IN_SECONDS * 7 );
		}

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			?>
			<select name="_category_parent">
				<option><?php esc_html_e('Choose a feature category', 'wp-cardealer'); ?></option>
				<?php
				foreach ($terms as $term) {
					$selected = '';
					if ( $term->slug == $val ) {
						$selected = 'selected="selected"';
					}
				?>
					<option value="<?php echo esc_attr($term->slug); ?>" <?php echo trim($selected); ?>><?php echo $term->name; ?></option>
				<?php } ?>
			</select>
		<?php } ?>
		<p><?php esc_html_e( 'Choose on what listing categories should this term be available. Set to empty for all.', 'wp-cardealer' ); ?></p>
		<?php
	}

	public static function save( $term_id, $tt_id, $taxonomy ) {
		if ( $taxonomy == 'listing_feature' ) {
		    $old_value  = get_term_meta( $term_id, '_category_parent', true );
	    	$new_value = isset( $_POST['_category_parent'] ) ? $_POST['_category_parent'] : '';

			if ( $old_value && '' === $new_value ) {
		        delete_term_meta( $term_id, '_category_parent' );
			} elseif ( $old_value !== $new_value ) {
		        update_term_meta( $term_id, '_category_parent', $new_value );
		    }
	    } elseif ( $taxonomy == 'listing_feature_category' ) {
	    	if ( isset( $_POST['menu_order'] ) ) {
		    	update_term_meta( $term_id, 'menu_order', $_POST['menu_order'] );
		    } else {
		    	update_term_meta( $term_id, 'menu_order', 0 );
		    }
	    }
	}


	public static function feature_category_add_fields_form($taxonomy) {
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Order', 'wp-cardealer' ); ?></label>
			<?php self::input_field(); ?>
		</div>
		<?php
	}

	public static function feature_category_edit_fields_form( $term, $taxonomy ) {
		$menu_order = get_term_meta( $term->term_id, 'menu_order', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Order', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::input_field($menu_order); ?>
			</td>
		</tr>
		<?php
	}

	public static function input_field( $val = '' ) {
		?>
		<input name="menu_order" type="number" value="<?php echo esc_attr($val); ?>">
		<?php
	}

	public static function feature_category_tax_columns( $columns ) {
		$new_columns = array();
		foreach ($columns as $key => $value) {
			if ( $key == 'posts' ) {
				$new_columns['menu_order'] = esc_html__( 'Order', 'wp-cardealer' );
			}
			$new_columns[$key] = $value;
		}
		return $new_columns;
	}

	public static function feature_category_tax_column( $columns, $column, $id ) {
		if ( $column == 'menu_order' ) {
			$menu_order = get_term_meta( $id, 'menu_order', true );
			echo intval($menu_order);
		}
		return $columns;
	}
}

WP_CarDealer_Taxonomy_Car_Feature::init();