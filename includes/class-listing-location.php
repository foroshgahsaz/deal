<?php
/**
 * Listing location taxonomy helpers.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Listing_Location {

	const TAXONOMY = 'listing_location';

	/**
	 * @param int $post_id
	 * @return WP_Term|null
	 */
	public static function get_deepest_term( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		$terms = wp_get_post_terms( $post_id, self::TAXONOMY, array( 'orderby' => 'term_id' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		$deepest = null;
		$depth   = -1;

		foreach ( $terms as $term ) {
			$term_depth = count( get_ancestors( $term->term_id, self::TAXONOMY ) );
			if ( $term_depth > $depth ) {
				$depth   = $term_depth;
				$deepest = $term;
			}
		}

		return $deepest;
	}

	/**
	 * Ordered ancestor chain including the deepest assigned term.
	 *
	 * @param int $post_id
	 * @return WP_Term[]
	 */
	public static function get_term_chain( $post_id ) {
		$deepest = self::get_deepest_term( $post_id );
		if ( ! $deepest ) {
			return array();
		}

		$ancestor_ids = array_reverse( get_ancestors( $deepest->term_id, self::TAXONOMY ) );
		$chain        = array();

		foreach ( $ancestor_ids as $ancestor_id ) {
			$term = get_term( $ancestor_id, self::TAXONOMY );
			if ( $term && ! is_wp_error( $term ) ) {
				$chain[] = $term;
			}
		}

		$chain[] = $deepest;

		return $chain;
	}

	/**
	 * @param int $post_id
	 * @return string
	 */
	public static function get_leaf_name( $post_id ) {
		$term = self::get_deepest_term( $post_id );

		return $term ? $term->name : '';
	}

	/**
	 * @param int    $post_id
	 * @param string $separator
	 * @return string
	 */
	public static function get_path_text( $post_id, $separator = ' › ' ) {
		$chain = self::get_term_chain( $post_id );
		if ( empty( $chain ) ) {
			return '';
		}

		$names = array();
		foreach ( $chain as $term ) {
			$names[] = $term->name;
		}

		return implode( $separator, $names );
	}

	/**
	 * @param int    $post_id
	 * @param string $separator
	 * @return string
	 */
	/**
	 * @param WP_Term $term
	 * @return string
	 */
	public static function get_term_archive_url( $term ) {
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		$link = get_term_link( $term, self::TAXONOMY );
		if ( is_wp_error( $link ) ) {
			return '';
		}

		return $link;
	}

	/**
	 * Top-level city in the assigned chain (parent of the leaf when present).
	 *
	 * @param int $post_id
	 * @return WP_Term|null
	 */
	public static function get_city_term( $post_id ) {
		$chain = self::get_term_chain( $post_id );

		return ! empty( $chain ) ? $chain[0] : null;
	}

	/**
	 * Term used for the leaf label link: city when a parent exists, otherwise the leaf.
	 *
	 * @param int $post_id
	 * @return WP_Term|null
	 */
	public static function get_leaf_link_term( $post_id ) {
		$leaf = self::get_deepest_term( $post_id );
		if ( ! $leaf ) {
			return null;
		}

		if ( $leaf->parent ) {
			$city = get_term( (int) $leaf->parent, self::TAXONOMY );
			if ( $city && ! is_wp_error( $city ) ) {
				return $city;
			}
		}

		return $leaf;
	}

	/**
	 * @param int    $post_id
	 * @param string $class
	 * @return string
	 */
	public static function get_leaf_link_html( $post_id, $class = 'listing-location-link' ) {
		$leaf = self::get_deepest_term( $post_id );
		if ( ! $leaf ) {
			return '';
		}

		$link_term = self::get_leaf_link_term( $post_id );
		$url       = $link_term ? self::get_term_archive_url( $link_term ) : '';

		if ( ! $url ) {
			return esc_html( $leaf->name );
		}

		return '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $leaf->name ) . '</a>';
	}

	public static function get_path_html( $post_id, $separator = ' › ', $class = 'listing-location-link' ) {
		$chain = self::get_term_chain( $post_id );
		if ( empty( $chain ) ) {
			return '';
		}

		$parts = array();
		foreach ( $chain as $term ) {
			$url = self::get_term_archive_url( $term );
			if ( $url ) {
				$parts[] = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $term->name ) . '</a>';
			} else {
				$parts[] = esc_html( $term->name );
			}
		}

		return implode( '<span class="listing-location-separator">' . esc_html( $separator ) . '</span>', $parts );
	}

	/**
	 * Seed demo cities once.
	 */
	public static function maybe_seed_terms() {
		if ( get_option( 'wp_cardealer_listing_location_seeded' ) ) {
			return;
		}

		if ( ! taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}

		$cities = array(
			array( 'tehran', 'تهران', 'varamin', 'ورامین' ),
			array( 'isfahan', 'اصفهان', 'khomeinishahr', 'خمینی‌شهر' ),
			array( 'mashhad', 'مشهد', 'torghabeh', 'طرقبه' ),
			array( 'shiraz', 'شیراز', 'sadra', 'صدرا' ),
			array( 'tabriz', 'تبریز', 'osku', 'اسکو' ),
			array( 'karaj', 'کرج', 'mohammadshahr', 'محمدشهر' ),
			array( 'ahvaz', 'اهواز', 'moammadiyeh', 'معتمدیه' ),
			array( 'qom', 'قم', 'qanavat', 'قنوات' ),
			array( 'kermanshah', 'کرمانشاه', 'islamabad-gharb', 'اسلام‌آباد غرب' ),
			array( 'urmia', 'ارومیه', 'silvana', 'سیلوه' ),
			array( 'rasht', 'رشت', 'khomam', 'خمام' ),
			array( 'zahedan', 'زاهدان', 'nosratabad', 'نصرت‌آباد' ),
			array( 'hamedan', 'همدان', 'bahar', 'بهار' ),
			array( 'kerman', 'کرمان', 'mahan', 'ماهان' ),
			array( 'yazd', 'یزد', 'hamidia', 'حمیدیا' ),
			array( 'arak', 'اراک', 'farmahin', 'فرمهین' ),
			array( 'bandar-abbas', 'بندرعباس', 'tavaleh', 'طبل' ),
			array( 'gorgan', 'گرگان', 'gomishan', 'گمیشان' ),
			array( 'sari', 'ساری', 'qaemshahr', 'قائم‌شهر' ),
			array( 'sanandaj', 'سنندج', 'shuyesheh', 'شویشه' ),
		);

		foreach ( $cities as $city ) {
			$parent = wp_insert_term(
				$city[1],
				self::TAXONOMY,
				array( 'slug' => $city[0] )
			);

			if ( is_wp_error( $parent ) ) {
				if ( $parent->get_error_code() === 'term_exists' ) {
					$existing = get_term_by( 'slug', $city[0], self::TAXONOMY );
					$parent_id = $existing ? (int) $existing->term_id : 0;
				} else {
					continue;
				}
			} else {
				$parent_id = (int) $parent['term_id'];
			}

			if ( ! $parent_id ) {
				continue;
			}

			$child = wp_insert_term(
				$city[3],
				self::TAXONOMY,
				array(
					'slug'   => $city[2],
					'parent' => $parent_id,
				)
			);

			if ( is_wp_error( $child ) && $child->get_error_code() !== 'term_exists' ) {
				continue;
			}
		}

		update_option( 'wp_cardealer_listing_location_seeded', 1 );
	}
}
