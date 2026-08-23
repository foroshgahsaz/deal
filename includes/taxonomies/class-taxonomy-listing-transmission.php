<?php
/**
 * Transmissions
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_CarDealer_Taxonomy_Car_Transmission{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );
	}

	/**
	 *
	 */
	public static function definition() {
		$singular = __( 'Transmission', 'wp-cardealer' );
		$plural   = __( 'Transmissions', 'wp-cardealer' );

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

		$rewrite_slug = get_option('wp_cardealer_listing_transmission_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'listing-transmission', 'Car transmission slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'listing_transmission', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_transmission_labels', $labels ),
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

}

WP_CarDealer_Taxonomy_Car_Transmission::init();