<?php
/**
 * Post Type: Dealer
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Post_Type_Dealer {

	public static $prefix = WP_CARDEALER_DEALER_PREFIX;

	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );

	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields_front' ) );

	  	add_filter( 'cmb2_admin_init', array( __CLASS__, 'metaboxes' ) );

	  	add_filter( 'manage_edit-dealer_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_dealer_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );

		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
	}

	public static function register_post_type() {
		$singular = __( 'Dealer', 'wp-cardealer' );
		$plural   = __( 'Dealers', 'wp-cardealer' );

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
			'not_found'             => sprintf(__( 'No %s found', 'wp-cardealer' ), $plural),
			'not_found_in_trash'    => sprintf(__( 'No %s found in Trash', 'wp-cardealer' ), $plural),
			'parent_item_colon'     => '',
			'menu_name'             => $plural,
		);
		$has_archive = 'dealers';
		$dealer_archive = get_option('wp_cardealer_dealer_archive_slug');
		if ( $dealer_archive ) {
			$has_archive = $dealer_archive;
		}
		$rewrite_slug = get_option('wp_cardealer_dealer_base_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'dealer', 'Dealer slug - resave permalinks after changing this', 'wp-cardealer' );
		}
		$rewrite = array(
			'slug'       => $rewrite_slug,
			'with_front' => false
		);
		register_post_type( 'dealer',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title', 'editor', 'thumbnail', 'comments' ),
				'public'            => true,
				'has_archive'       => $has_archive,
				'rewrite'           => $rewrite,
				'menu_position'     => 51,
				'categories'        => array(),
				'menu_icon'         => 'dashicons-admin-post',
				'show_in_rest'		=> true,
				'capabilities' => array(
				    'create_posts' => false,
				),
				'map_meta_cap' => true,
			)
		);
	}

	public static function save_post($post_id, $post) {
		if ( $post->post_type === 'dealer' ) {
			$arg = array( 'ID' => $post_id );
			if ( !empty($_POST[self::$prefix . 'featured']) ) {
				$arg['menu_order'] = -1;
			} else {
				$arg['menu_order'] = 0;
			}
			
			remove_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );
			wp_update_post( $arg );
			add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

			delete_transient( 'wp_cardealer_filter_counts' );
			
			clean_post_cache( $post_id );
		}
	}

	public static function process_denied_to_publish($post) {
		if ( $post->post_type === 'dealer' ) {
			$user_id = WP_CarDealer_User::get_user_by_dealer_id($post->ID);
			remove_action('denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
			do_action( 'wp_cardealer_new_user_approve_approve_user', $user_id );
			add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		}
	}

	public static function process_pending_to_publish($post) {
		if ( $post->post_type === 'dealer' ) {
			$user_id = WP_CarDealer_User::get_user_by_dealer_id($post->ID);
			remove_action('pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
			do_action( 'wp_cardealer_new_user_approve_approve_user', $user_id );
			add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
		}
	}
	
	public static function metaboxes() {
		global $pagenow;
		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
			do_action('wp-cardealer-dealer-fields-admin');
		}
	}

	public static function fields_front( array $metaboxes ) {
		if ( ! is_admin() ) {
			$post_id = 0;
			$user_id = get_current_user_id();
			if ( WP_CarDealer_User::is_dealer($user_id) ) {
				$post_id = WP_CarDealer_User::get_dealer_by_user_id($user_id);
				if ( !empty($post_id) ) {
					$post = get_post( $post_id );
					$featured_image = get_post_thumbnail_id( $post_id );
				}
			}
			
			$init_fields = apply_filters( 'wp-cardealer-dealer-fields-front', array(), $post_id );
			
			// uasort( $init_fields, array( 'WP_CarDealer_Mixes', 'sort_array_by_priority') );

			$fields = array();
			$i = 1;
			$heading_count = 0;
			$index = 0;
			foreach ($init_fields as $field) {
				$rfield = $field;
				if ( $i == 1 ) {
					if ( $field['type'] !== 'title' ) {
						$fields[] = array(
							'name' => esc_html__('General', 'wp-cardealer'),
							'type' => 'title',
							'id'   => self::$prefix.'heading_general_title',
							'priority' => 0,
							'before_row' => '<div id="heading-'.self::$prefix.'heading_general_title" class="before-group-row before-group-row-'.$heading_count.' active"><div class="before-group-row-inner">',
						);
						$heading_count = 1;
						$index = 0;
					}
				}
				
				if ( $rfield['id'] == self::$prefix . 'title' ) {
					$rfield['default'] = !empty( $post ) ? $post->post_title : '';
				} elseif ( $rfield['id'] == self::$prefix . 'description' ) {
					$rfield['default'] = !empty( $post ) ? $post->post_content : '';
				} elseif ( $rfield['id'] == self::$prefix . 'featured_image' ) {
					$rfield['default'] = !empty( $featured_image ) ? $featured_image : '';
				} elseif ( $rfield['id'] == self::$prefix . 'tag' ) {
					$rfield['default'] = $tags_default;
				}
				if ( $rfield['type'] == 'title' ) {
					$before_row = '';
					if ( $i > 1 ) {
						$before_row .= '</div></div>';
					}
					$classes = '';
					if ( !empty($rfield['number_columns']) ) {
						$classes = 'columns-'.$rfield['number_columns'];
					}
					$before_row .= '<div id="heading-'.$rfield['id'].'" class="before-group-row before-group-row-'.$heading_count.' '.($heading_count == 0 ? 'active' : '').' '.$classes.'"><div class="before-group-row-inner">';

					$rfield['before_row'] = $before_row;

					$heading_count++;
					$index++;
				}

				if ( $i == count($init_fields) ) {
					if ( $rfield['type'] == 'group' ){
						$rfield['after_group'] = '</div></div>';
					} else {
						$rfield['after_row'] = '</div></div>';
					}
				}

				$fields[] = $rfield;

				$i++;
			}

			$fields[] = array(
				'id'                => self::$prefix . 'post_type',
				'type'              => 'hidden',
				'default'           => 'dealer',
				'priority'          => 100,
			);

			$fields = apply_filters( 'wp-cardealer-dealer-fields', $fields, $post_id );
			
			$metaboxes[ self::$prefix . 'fields_front' ] = array(
				'id'                        => self::$prefix . 'fields_front',
				'title'                     => __( 'General Options', 'wp-cardealer' ),
				'object_types'              => array( 'dealer' ),
				'context'                   => 'normal',
				'priority'                  => 'high',
				'show_names'                => true,
				'fields'                    => $fields
			);
		}
		return $metaboxes;
	}
	/**
	 * Custom admin columns for post type
	 *
	 * @access public
	 * @return array
	 */
	public static function custom_columns($columns) {
		if ( isset($columns['comments']) ) {
			unset($columns['comments']);
		}
		if ( isset($columns['date']) ) {
			unset($columns['date']);
		}
		$fields = array_merge($columns, array(
			'title' 			=> __( 'Title', 'wp-cardealer' ),
			'thumbnail' 		=> __( 'Thumbnail', 'wp-cardealer' ),
			'attached-user' 	=> __( 'Attached User', 'wp-cardealer' ),
			// 'category' 			=> __( 'Category', 'wp-cardealer' ),
			// 'location' 			=> __( 'Location', 'wp-cardealer' ),
			'featured' 			=> __( 'Featured', 'wp-cardealer' ),
			'date' 				=> __( 'Date', 'wp-cardealer' ),
		));
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
			case 'thumbnail':
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'thumbnail', array(
						'class' => 'attachment-thumbnail attachment-thumbnail-small ',
					) );
				} else {
					echo '-';
				}
				break;
			case 'category':
				$terms = get_the_terms( get_the_ID(), 'dealer_category' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$category = array_shift( $terms );
					echo sprintf( '<a href="?post_type=dealer&dealer_category=%s">%s</a>', $category->slug, $category->name );
				} else {
					echo '-';
				}
				break;
			case 'location':
				$terms = get_the_terms( get_the_ID(), 'dealer_location' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$location = array_shift( $terms );
					echo sprintf( '<a href="?post_type=dealer&dealer_location=%s">%s</a>', $location->slug, $location->name );
				} else {
					echo '-';
				}
				break;
			case 'featured':
				$featured = get_post_meta( get_the_ID(), self::$prefix . 'featured', true );

				if ( ! empty( $featured ) ) {
					echo '<div class="dashicons dashicons-star-filled"></div>';
				} else {
					echo '<div class="dashicons dashicons-star-empty"></div>';
				}
				break;
			case 'attached-user':
				$user_id = get_post_meta( get_the_ID(), self::$prefix . 'user_id', true );
				$display_name = get_post_meta( get_the_ID(), self::$prefix . 'display_name', true );
				$email = get_post_meta( get_the_ID(), self::$prefix . 'email', true );
				if ( $user_id && ($user = get_user_by( 'ID', $user_id)) ) {
					$html = '<div><strong>'.$display_name.'</strong></div>';
					$html .= $email;
				} else {
					$html = '-';
				}
				if ( !empty($html) ) {
					echo wp_kses_post($html);
				}

				break;
		}
	}


}
WP_CarDealer_Post_Type_Dealer::init();