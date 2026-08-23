<?php
/**
 * Locations
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_ListingDealer_Taxonomy_Listing_Location{

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
		$singular = __( 'Location', 'wp-cardealer' );
		$plural   = __( 'Locations', 'wp-cardealer' );

		$labels = array(
			'name'              => sprintf(__( 'Listing %s', 'wp-cardealer' ), $plural),
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

		$rewrite_slug = get_option('wp_cardealer_listing_location_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'listing-location', 'Listing location slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'listing_location', 'listing', array(
			'labels'            => apply_filters( 'wp_cardealer_taxomony_listing_location_labels', $labels ),
			'hierarchical'      => true,
			'rewrite'           => $rewrite,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'		=> true
		) );
	}

}

WP_ListingDealer_Taxonomy_Listing_Location::init();