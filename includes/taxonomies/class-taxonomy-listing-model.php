<?php
/**
 * Models
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_CarDealer_Taxonomy_Car_Model{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );

		add_filter( "manage_edit-listing_model_columns", array( __CLASS__, 'tax_columns' ) );
		add_filter( "manage_listing_model_custom_column", array( __CLASS__, 'tax_column' ), 10, 3 );
		add_action( "listing_model_add_form_fields", array( __CLASS__, 'add_fields_form' ) );
		add_action( "listing_model_edit_form_fields", array( __CLASS__, 'edit_fields_form' ), 10, 2 );

		add_action( 'create_term', array( __CLASS__, 'save' ), 10, 3 );
		add_action( 'edit_term', array( __CLASS__, 'save' ), 10, 3 );
	}

	/**
	 *
	 */
	public static function definition() {
		$singular = __( 'Model', 'wp-cardealer' );
		$plural   = __( 'Models', 'wp-cardealer' );

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

		$rewrite_slug = get_option('wp_cardealer_listing_model_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'listing-model', 'Car model slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'listing_model', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_model_labels', $labels ),
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
				$i = 1;
				foreach ($type_slugs as $slug) {
					$term = get_term_by('slug', $slug, 'listing_make');
					if ( $term ) {
						$columns .= '<a href="' . esc_url( get_term_link($term->term_id) ) . '" >'.$term->name.'</a>' . ($i < count($type_slugs) ? ', ' : '');
					}
					$i++;
				}

			} else {
				$columns .= esc_html__('All', 'wp-cardealer');
			}
		}

		return $columns;
	}

	public static function add_fields_form($taxonomy) {
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Select Makes', 'wp-cardealer' ); ?></label>
			<?php self::categories_field(); ?>
		</div>
		<?php
	}

	public static function edit_fields_form( $term, $taxonomy ) {
		$type_value = get_term_meta( $term->term_id, '_category_parent', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Select Makes', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::categories_field($type_value); ?>
			</td>
		</tr>
		<?php
	}

	public static function categories_field( $val = '' ) {
		$args = array(
			'taxonomy' => 'listing_make',
			'posts_per_page' => -1,
			'hide_empty' => 0,
			'lang' => apply_filters( 'wp-cardealer-current-lang', null )
		);
		
		$terms_hash = 'wpcd_cats_' . md5( wp_json_encode( $args ) . WP_CarDealer_Cache_Helper::get_transient_version('wpcd_get_listing_make') );
		$terms = get_transient( $terms_hash );
		if ( empty( $terms ) ) {
			$terms = get_terms($args);
			set_transient( $terms_hash, $terms, DAY_IN_SECONDS * 7 );
		}

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			foreach ($terms as $term) {
				$checked = '';
				if ( !is_array($val) ) {
					if ( $term->slug == $val ) {
						$checked = 'checked="checked"';
					}
				} elseif ( in_array($term->slug, $val) ) {
					$checked = 'checked="checked"';
				}
			?>
				<label>
					<input name="_category_parent[]" type="checkbox" value="<?php echo esc_attr($term->slug); ?>" <?php echo trim($checked); ?>> <?php echo $term->name; ?>
				</label>
			<?php } ?>
		<?php } ?>
		<p><?php esc_html_e( 'Choose on what listing makes should this term be available. Set to empty for all.', 'wp-cardealer' ); ?></p>
		<?php
	}

	public static function save( $term_id, $tt_id, $taxonomy ) {
		if ( $taxonomy == 'listing_model' ) {
		    $old_value  = get_term_meta( $term_id, '_category_parent', true );
	    	$new_value = isset( $_POST['_category_parent'] ) ? $_POST['_category_parent'] : '';

			if ( $old_value && '' === $new_value ) {
		        delete_term_meta( $term_id, '_category_parent' );
			} elseif ( $old_value !== $new_value ) {
		        update_term_meta( $term_id, '_category_parent', $new_value );
		    }
	    }
	}

}

WP_CarDealer_Taxonomy_Car_Model::init();