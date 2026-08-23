<?php
/**
 * Category Select Walker
 *
 * @package    wp-job-board-pro
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Category_Select_Walker extends Walker {

	/**
	 * Tree type that the class handles.
	 *
	 * @var string
	 */
	public $tree_type = 'category';

	/**
	 * Database fields to use.
	 *
	 * @var array
	 */
	public $db_fields = [
		'parent' => 'parent',
		'id'     => 'term_id',
		'slug'   => 'slug',
	];

	/**
	 * Start the list walker.
	 *
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $object Category data object.
	 * @param int    $depth Depth of category in reference to parents.
	 * @param array  $args
	 * @param int    $current_object_id
	 */
	public function start_el( &$output, $object, $depth = 0, $args = [], $current_object_id = 0 ) {

		if ( ! empty( $args['hierarchical'] ) ) {
			$pad = str_repeat( '&nbsp;', $depth * 3 );
		} else {
			$pad = '';
		}

		$class_attr = '';
		if ( !empty($args['condition']) ) {

			$type_slugs = get_term_meta( $object->term_id, '_category_parent', true );
			
			if ( $type_slugs ) {
				foreach ($type_slugs as $slug) {
					$class_attr .= ' condition-'.$slug;
				}

			} else {
				$class_attr .= 'condition--all';
			}
		}

		$cat_name = $object->name;

		$value = isset( $args['value'] ) && 'id' === $args['value'] ? $object->term_id : $object->slug;

		$output .= "\t<option class=\"level-" . intval( $depth ) . ' '. $class_attr . '" value="' . esc_attr( $value ) . '"';

		if (
			isset( $args['selected'] ) && (
				$value == $args['selected'] // phpcs:ignore WordPress.PHP.StrictComparisons
				|| ( is_array( $args['selected'] ) && in_array( $value, $args['selected'] ) ) // phpcs:ignore WordPress.PHP.StrictInArray
			)
		) {
			$output .= ' selected="selected"';
		}

		$output .= '>';

		$output .= $pad . esc_html( $cat_name );

		if ( ! empty( $args['show_count'] ) ) {
			$output .= '&nbsp;(' . intval( $object->count ) . ')';
		}

		$output .= "</option>\n";
	}
}
