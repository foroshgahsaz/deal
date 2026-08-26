<?php
/**
 * Listing location taxonomy (موقعیت).
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Taxonomy_Listing_Location {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );
		add_action( 'init', array( __CLASS__, 'maybe_seed_terms' ), 20 );
		add_action( 'pre_get_posts', array( __CLASS__, 'location_archive_include_children' ), 25 );
		add_filter( 'wp_cardealer_cmb2_field_taxonomy_location_number', array( __CLASS__, 'dropdown_levels' ) );
		add_filter( 'wp_cardealer_cmb2_field_taxonomy_location_field_name_1', array( __CLASS__, 'level_one_label' ) );
		add_filter( 'wp_cardealer_cmb2_field_taxonomy_location_field_name_2', array( __CLASS__, 'level_two_label' ) );
	}

	public static function dropdown_levels() {
		return 2;
	}

	public static function level_one_label() {
		return 'شهر';
	}

	public static function level_two_label() {
		return 'زیرمجموعه';
	}

	public static function definition() {
		$labels = array(
			'name'              => 'موقعیت‌ها',
			'singular_name'     => 'موقعیت',
			'search_items'      => 'جستجوی موقعیت',
			'all_items'         => 'همه موقعیت‌ها',
			'parent_item'       => 'موقعیت والد',
			'parent_item_colon' => 'موقعیت والد:',
			'edit_item'         => 'ویرایش موقعیت',
			'update_item'       => 'به‌روزرسانی موقعیت',
			'add_new_item'      => 'افزودن موقعیت جدید',
			'new_item_name'     => 'نام موقعیت جدید',
			'menu_name'         => 'موقعیت',
		);

		$rewrite_slug = get_option( 'wp_cardealer_listing_location_slug' );
		if ( empty( $rewrite_slug ) ) {
			$rewrite_slug = 'listing-location';
		}

		register_taxonomy(
			'listing_location',
			'listing',
			array(
				'labels'             => apply_filters( 'wp_cardealer_taxomony_listing_location_labels', $labels ),
				'hierarchical'       => true,
				'rewrite'            => array(
					'slug'         => $rewrite_slug,
					'with_front'   => false,
					'hierarchical' => true,
				),
				'public'             => true,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'show_in_quick_edit' => false,
				'meta_box_cb'        => false,
			)
		);
	}

	public static function maybe_seed_terms() {
		if ( class_exists( 'WP_CarDealer_Listing_Location' ) ) {
			WP_CarDealer_Listing_Location::maybe_seed_terms();
		}
	}

	/**
	 * City archives include sub-locations so one city link lists every area ad.
	 *
	 * @param WP_Query $query
	 */
	public static function location_archive_include_children( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'listing_location' ) ) {
			return;
		}

		$term = $query->get_queried_object();
		if ( ! ( $term instanceof WP_Term ) ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		$found = false;
		foreach ( $tax_query as $key => $clause ) {
			if ( $key === 'relation' || ! is_array( $clause ) ) {
				continue;
			}
			if ( isset( $clause['taxonomy'] ) && $clause['taxonomy'] === 'listing_location' ) {
				$tax_query[ $key ]['include_children'] = true;
				$found = true;
			}
		}

		if ( ! $found ) {
			$tax_query[] = array(
				'taxonomy'         => 'listing_location',
				'field'            => 'term_id',
				'terms'            => array( (int) $term->term_id ),
				'include_children' => true,
			);
		}

		$query->set( 'tax_query', $tax_query );
	}
}

WP_CarDealer_Taxonomy_Listing_Location::init();
