<?php
/**
 * Post Type: Saved Search
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Post_Type_Saved_Search {
	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields' ) );

	  	add_filter( 'manage_edit-saved_search_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_saved_search_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );
	}

	public static function register_post_type() {
		$singular = __( 'Saved Search', 'wp-cardealer' );
		$plural   = __( 'Saved Searches', 'wp-cardealer' );

		$labels = array(
			'name'                  => $plural,
			'singular_name'         => $singular,
			'add_new'               => sprintf(__( 'Add New %s', 'wp-cardealer' ), $singular),
			'add_new_item'          => sprintf(__( 'Add New %s', 'wp-cardealer' ), $singular),
			'edit_item'             => sprintf(__( 'Edit %s', 'wp-cardealer' ), $singular),
			'new_item'              => sprintf(__( 'New %s', 'wp-cardealer' ), $singular),
			'all_items'             => sprintf(__( 'All %s', 'wp-cardealer' ), $plural),
			'view_item'             => sprintf(__( 'View %s', 'wp-cardealer' ), $singular),
			'search_items'          => sprintf(__( 'Search %s', 'wp-cardealer' ), $singular),
			'not_found'             => sprintf(__( 'No %s found', 'wp-cardealer' ), $singular),
			'not_found_in_trash'    => sprintf(__( 'No %s found in Trash', 'wp-cardealer' ), $singular),
			'parent_item_colon'     => '',
			'menu_name'             => $plural,
		);
		
		register_post_type( 'saved_search',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title' ),
				'public'            => true,
		        'has_archive'       => false,
		        'publicly_queryable' => false,
				'show_in_rest'		=> false,
				'show_in_menu'		=> 'edit.php?post_type=listing',
			)
		);
	}

	/**
	 * Defines custom fields
	 *
	 * @access public
	 * @param array $metaboxes
	 * @return array
	 */
	public static function fields( array $metaboxes ) {
		$email_frequency_default = WP_CarDealer_Saved_Search::get_email_frequency();
		$email_frequency = array();
		if ( $email_frequency_default && is_admin() ) {
			foreach ($email_frequency_default as $key => $value) {
				if ( !empty($value['label']) && !empty($value['days']) ) {
					$email_frequency[$key] = $value['label'];
				}
			}
		}
		$fields = array();
		if ( isset($_GET['post']) && $_GET['post'] && is_admin() ) {
			$post = get_post($_GET['post']);
			if ( $post && $post->post_type == 'saved_search' ) {
				$author_name = get_the_author_meta('display_name', $post->post_author);
				$author_email = get_the_author_meta('user_email', $post->post_author);
				$fields[] = array(
					'name' => sprintf( __('Author: %s (%s)', 'wp-cardealer'), $author_name, $author_email ),
					'type' => 'title',
					'id'   => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'author'
				);
			}
		}
		$fields[] = array(
			'name'              => __( 'Saved Search Query', 'wp-cardealer' ),
			'id'                => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'saved_search_query',
			'type'              => 'textarea',
		);
		$fields[] = array(
			'name'              => __( 'Email Frequency', 'wp-cardealer' ),
			'id'                => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'email_frequency',
			'type'              => 'select',
			'options'			=> $email_frequency
		);
		$metaboxes[ WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'general' ] = array(
			'id'                        => WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'general',
			'title'                     => __( 'General Options', 'wp-cardealer' ),
			'object_types'              => array( 'saved_search' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => $fields
		);
		return $metaboxes;
	}
	/**
	 * Custom admin columns for post type
	 *
	 * @access public
	 * @return array
	 */
	public static function custom_columns() {
		$fields = array(
			'cb' 				=> '<input type="checkbox" />',
			'title' 			=> esc_html__( 'Title', 'wp-cardealer' ),
			'email_frequency' 	=> esc_html__( 'Email Frequency', 'wp-cardealer' ),
			'date' 				=> esc_html__( 'Date', 'wp-cardealer' ),
			'author' 			=> esc_html__( 'Author', 'wp-cardealer' ),
		);
		return $fields;
	}

	/**
	 * Custom admin columns implementation
	 *
	 * @access public
	 * @param string $column
	 * @return array
	 */
	public static function custom_columns_manage( $column ) {
		switch ( $column ) {
			case 'email_frequency':
					$email_frequency = get_post_meta( get_the_ID(), WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'email_frequency', true );
					echo wp_kses_post($email_frequency);
				break;
		}
	}

}
WP_CarDealer_Post_Type_Saved_Search::init();