<?php
/**
 * Finds listings whose price distorts the price filter.
 *
 * The upper bound of the price filter comes from the highest listing price, so
 * one mistyped figure sets the bound for the whole site. This lists the highest
 * prices so the owner can find and correct such a row.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Price_Audit {

	/**
	 * Highest listing prices, newest schema first.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function get_highest_prices( $limit = 10 ) {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return array();
		}

		$limit = max( 1, min( 50, (int) $limit ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT posts.ID, posts.post_title, posts.post_status,
					CAST( meta.meta_value AS DECIMAL(30,2) ) AS price
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				WHERE posts.post_type = 'listing'
					AND posts.post_status NOT IN ( 'trash', 'auto-draft' )
					AND meta.meta_key = %s
					AND meta.meta_value <> ''
					AND meta.meta_value REGEXP '^[0-9]+(\\\\.[0-9]+)?$'
				ORDER BY price DESC
				LIMIT %d",
				WP_CARDEALER_LISTING_PREFIX . 'price',
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Annotate rows with whether each price exceeds the filter ceiling.
	 *
	 * Pure function so the flagging rules are unit testable.
	 *
	 * @param array $rows
	 * @param float $ceiling
	 * @return array
	 */
	public static function describe( $rows, $ceiling ) {
		$described = array();

		if ( ! is_array( $rows ) ) {
			return $described;
		}

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['ID'] ) ) {
				continue;
			}

			$price = isset( $row['price'] ) && is_numeric( $row['price'] ) ? (float) $row['price'] : 0.0;

			$described[] = array(
				'id'      => (int) $row['ID'],
				'title'   => isset( $row['post_title'] ) && '' !== $row['post_title'] ? $row['post_title'] : '(بدون عنوان)',
				'status'  => isset( $row['post_status'] ) ? $row['post_status'] : '',
				'price'   => $price,
				'flagged' => $ceiling > 0 && $price > $ceiling,
			);
		}

		return $described;
	}

	/**
	 * @return array
	 */
	public static function get_report( $limit = 10 ) {
		$ceiling = class_exists( 'WP_CarDealer_Price' ) ? WP_CarDealer_Price::get_filter_price_ceiling() : 0.0;

		return self::describe( self::get_highest_prices( $limit ), $ceiling );
	}
}
