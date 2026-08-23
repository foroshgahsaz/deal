<?php
/**
 * Category Radio/Check Walker
 *
 * @package    wp-job-board-pro
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Category_Radio_Check_Walker extends Walker {

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

	public function start_lvl( &$output, $depth=0 ,$args = array() ) {
        $indent = str_repeat( "\t", $depth );
        $output    .= "\n$indent<ul class=\"terms-list circle-check level-".($depth + 1)."\">\n";

    }

    public function end_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }
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


		$cat_name = $object->name;

		$value = isset( $args['value'] ) && 'id' === $args['value'] ? $object->term_id : $object->slug;


		$checked = '';
		if ( isset( $args['selected'] ) && (
				$value == $args['selected'] // phpcs:ignore WordPress.PHP.StrictComparisons
				|| ( is_array( $args['selected'] ) && in_array( $value, $args['selected'] ) ) // phpcs:ignore WordPress.PHP.StrictInArray
			)
		) {
			$checked = ' checked="checked"';
		}
		$count = '';
		if ( ! empty( $args['show_count'] ) ) {
			$count = '&nbsp;(' . intval( $object->count ) . ')';
		}

		$output .= '<li class="list-item level-' . intval( $depth ) . '">';
        	$output .= '<div class="list-item-inner">';
        	if ( $args['input_type'] == 'checkbox' ) {
	        	$output .= '<input id="'.esc_attr($args['name'].'-'.$object->slug.'-'.$args['rand_id']).'" type="checkbox" name="'.esc_attr($args['name']).'[]" value="'.esc_attr($value).'" '.$checked.'>';
	        } else {
	        	$output .= '<input id="'.esc_attr($args['name'].'-'.$object->slug.'-'.$args['rand_id']).'" type="radio" name="'.esc_attr($args['name']).'" value="'.esc_attr($value).'" '.$checked.'>';
	        }
        	$output .= '<label for="'.esc_attr($args['name'].'-'.$object->slug.'-'.$args['rand_id']).'">'. esc_html( $cat_name.$count ).'</label>';

        	$output .= '</div>';

	}

	public function end_el( &$output, $cat, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

}
