<?php
/**
 * Label
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_CarDealer_Taxonomy_Car_Label{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );

		add_filter( "manage_edit-listing_label_columns", array( __CLASS__, 'tax_columns' ) );
		add_filter( "manage_listing_label_custom_column", array( __CLASS__, 'tax_column' ), 10, 3 );

		add_action( "listing_label_add_form_fields", array( __CLASS__, 'add_fields_form' ) );
		add_action( "listing_label_edit_form_fields", array( __CLASS__, 'edit_fields_form' ), 10, 2 );

		add_action( 'create_term', array( __CLASS__, 'save' ), 10, 3  );
		add_action( 'edit_term', array( __CLASS__, 'save' ), 10, 3 );
	}

	/**
	 *
	 */
	public static function definition() {
		$singular = __( 'Label', 'wp-cardealer' );
		$plural   = __( 'Labels', 'wp-cardealer' );

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

		$rewrite_slug = get_option('wp_cardealer_listing_label_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'listing-label', 'Car label slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'listing_label', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_label_labels', $labels ),
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

	public static function add_fields_form($taxonomy) {
		global $apus_cityo_listing_type;
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Background Color', 'wp-cardealer' ); ?></label>
			<?php self::color_field('bg_color'); ?>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Text Color', 'wp-cardealer' ); ?></label>
			<?php self::color_field('text_color'); ?>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Order', 'wp-cardealer' ); ?></label>
			<?php self::input_field(); ?>
		</div>
		<?php
	}

	public static function edit_fields_form( $term, $taxonomy ) {
		$bg_color = get_term_meta( $term->term_id, 'bg_color', true );
		$text_color = get_term_meta( $term->term_id, 'text_color', true );
		$menu_order = get_term_meta( $term->term_id, 'menu_order', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Background Color', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::color_field('bg_color', $bg_color); ?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Text Color', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::color_field('text_color', $text_color); ?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Order', 'wp-cardealer' ); ?></label></th>
			<td>
				<?php self::input_field($menu_order); ?>
			</td>
		</tr>
		<?php
	}

	public static function color_field( $name, $val = '' ) {
		?>
		<input class="tax_color_input" name="<?php echo esc_attr($name); ?>" type="text" value="<?php echo esc_attr($val); ?>">
		<?php
	}

	public static function input_field( $val = '' ) {
		?>
		<input name="menu_order" type="number" value="<?php echo esc_attr($val); ?>">
		<?php
	}

	public static function save( $term_id, $tt_id, $taxonomy ) {
		if ( $taxonomy == 'listing_label' ) {
		    if ( isset( $_POST['bg_color'] ) ) {
		    	update_term_meta( $term_id, 'bg_color', $_POST['bg_color'] );
		    }

		    if ( isset( $_POST['text_color'] ) ) {
		    	update_term_meta( $term_id, 'text_color', $_POST['text_color'] );
		    }
		    
		    if ( isset( $_POST['menu_order'] ) ) {
		    	update_term_meta( $term_id, 'menu_order', $_POST['menu_order'] );
		    } else {
		    	update_term_meta( $term_id, 'menu_order', 0 );
		    }
	    }
	}

	public static function tax_columns( $columns ) {
		$new_columns = array();
		foreach ($columns as $key => $value) {
			if ( $key == 'name' ) {
				$new_columns['color'] = esc_html__( 'Color', 'wp-cardealer' );
			}
			if ( $key == 'posts' ) {
				$new_columns['menu_order'] = esc_html__( 'Order', 'wp-cardealer' );
			}
			$new_columns[$key] = $value;
		}
		return $new_columns;
	}

	public static function tax_column( $columns, $column, $id ) {
		if ( $column == 'color' ) {
			$term = get_term($id);
			$bg_color = get_term_meta( $id, 'bg_color', true );
			$text_color = get_term_meta( $id, 'text_color', true );
			$styles = array();
			if ( !empty($bg_color) ) {
				$styles[] = 'background-color: '.$bg_color;
			}
			if ( !empty($text_color) ) {
				$styles[] = 'color: '.$text_color;
			}
			if ( !empty($styles) ) {
				$styles[] = 'padding: 10px; display: inline-block;';
				?>
				<div style="<?php echo esc_attr(implode(';', $styles)); ?>"><?php echo $term->name; ?></div>
				<?php
			}
		} elseif ( $column == 'menu_order' ) {
			$menu_order = get_term_meta( $id, 'menu_order', true );
			echo intval($menu_order);
		}
		return $columns;
	}
}

WP_CarDealer_Taxonomy_Car_Label::init();