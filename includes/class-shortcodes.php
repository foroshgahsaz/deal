<?php
/**
 * Shortcodes
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Shortcodes {
	/**
	 * Initialize shortcodes
	 *
	 * @access public
	 * @return void
	 */
	public static function init() {
	    add_action( 'wp', array( __CLASS__, 'check_logout' ) );

	    // login | register
		add_shortcode( 'wp_cardealer_logout', array( __CLASS__, 'logout' ) );
	    add_shortcode( 'wp_cardealer_login', array( __CLASS__, 'login' ) );
	    add_shortcode( 'wp_cardealer_register', array( __CLASS__, 'register' ) );

	    // profile
	    add_shortcode( 'wp_cardealer_user_dashboard', array( __CLASS__, 'user_dashboard' ) );
	    add_shortcode( 'wp_cardealer_change_password', array( __CLASS__, 'change_password' ) );
	    add_shortcode( 'wp_cardealer_change_profile', array( __CLASS__, 'change_profile' ) );
    	add_shortcode( 'wp_cardealer_approve_user', array( __CLASS__, 'approve_user' ) );

    	// user
		add_shortcode( 'wp_cardealer_submission', array( __CLASS__, 'submission' ) );
	    add_shortcode( 'wp_cardealer_my_listings', array( __CLASS__, 'my_listings' ) );
	    add_shortcode( 'wp_cardealer_my_saved_search', array( __CLASS__, 'my_saved_search' ) );
	    add_shortcode( 'wp_cardealer_my_listing_favorite', array( __CLASS__, 'my_listing_favorites' ) );
	    add_shortcode( 'wp_cardealer_listing_compare', array( __CLASS__, 'my_listing_compare' ) );
	    add_shortcode( 'wp_cardealer_user_reviews', array( __CLASS__, 'user_reviews' ) );

	    // list
	    add_shortcode( 'wp_cardealer_listings', array( __CLASS__, 'listings' ) );
	    add_shortcode( 'wp_cardealer_dealers', array( __CLASS__, 'dealers' ) );

	    // currency
	    add_shortcode( 'wp_cardealer_currencies', array( __CLASS__, 'currencies' ) );
	}

	/**
	 * Logout checker
	 *
	 * @access public
	 * @param $wp
	 * @return void
	 */
	public static function check_logout( $wp ) {
		$post = get_post();
		
		if ( is_page() ) {
			if ( has_shortcode( $post->post_content, 'wp_cardealer_logout' ) ) {
				wp_redirect( html_entity_decode( wp_logout_url( home_url( '/' ) ) ) );
				exit();
			} elseif ( has_shortcode( $post->post_content, 'wp_cardealer_my_listings' ) ) {
				self::my_listings_hanlder();
			}
		}
	}

	/**
	 * Logout
	 *
	 * @access public
	 * @return void
	 */
	public static function logout( $atts ) {}

	/**
	 * Login
	 *
	 * @access public
	 * @return string
	 */
	public static function login( $atts ) {
		if ( is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/login', $atts );
	}

	/**
	 * Login
	 *
	 * @access public
	 * @return string
	 */
	public static function register( $atts ) {
		if ( is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/register', $atts );
	}

	/**
	 * Submission index
	 *
	 * @access public
	 * @return string|void
	 */
	public static function submission( $atts ) {
	    if ( !is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
	    $user = wp_get_current_user();
    	$user_roles = ( array ) $user->roles;
	    $allow_roles = wp_cardealer_get_option('submit_user_roles');
		
	    $allow = false;
	    if ( empty($allow_roles) ) {
	    	$allow = true;
	    } else {
	    	foreach( ( array )$user_roles as $role ) {
	    		if ( in_array($role, $allow_roles)) {
	    			$allow = true;
	    		}
	    	}
	    }
	    if ( !$allow ) {
	    	return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }

		$form = WP_CarDealer_Submit_Form::get_instance();

		return $form->output();
	}

	public static function edit_form( $atts ) {
	    if ( ! is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
	    
		$form = WP_CarDealer_Edit_Form::get_instance();

		return $form->output();
	}

	public static function my_listings_hanlder() {
		$action = !empty($_REQUEST['action']) ? sanitize_title( $_REQUEST['action'] ) : '';
		$listing_id = isset( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;

		if ( $action == 'relist' || $action == 'continue' ) {
			$submit_form_page_id = wp_cardealer_get_option('submit_listing_form_page_id');
			if ( $submit_form_page_id ) {
				$submit_page_url = get_permalink($submit_form_page_id);
				wp_safe_redirect( add_query_arg( array( 'listing_id' => absint( $listing_id ), 'action' => $action ), $submit_page_url ) );
				exit;
			}
			
		}
	}
	/**
	 * Submission index
	 *
	 * @access public
	 * @param $atts
	 * @return void
	 */
	public static function my_listings( $atts ) {
		if ( ! is_user_logged_in() ) {
			return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
		}
		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = sanitize_title( $_REQUEST['action'] );

			if ( $action == 'edit' ) {
				return self::edit_form($atts);
			}
		}
		return WP_CarDealer_Template_Loader::get_template_part( 'submission/my-listings' );
	}
	
	/**
	 * Agent dashboard
	 *
	 * @access public
	 * @param $atts
	 * @return string
	 */
	public static function user_dashboard( $atts ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/user-dashboard', array( 'user_id' => $user_id ) );
	    } else {
	    	return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
	}

	/**
	 * Change password
	 *
	 * @access public
	 * @param $atts
	 * @return string
	 */
	public static function change_password( $atts ) {
		if ( ! is_user_logged_in() ) {
			return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
		}

		return WP_CarDealer_Template_Loader::get_template_part( 'misc/password-form' );
	}

	/**
	 * Change profile
	 *
	 * @access public
	 * @param $atts
	 * @return void
	 */
	public static function change_profile( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }

	    $metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
	    $metaboxes_form = array();
	    $user_id = get_current_user_id();
	    if ( WP_CarDealer_User::is_dealer($user_id) ) {
	    	if ( ! isset( $metaboxes[ WP_CARDEALER_DEALER_PREFIX . 'fields_front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-cardealer' );
			}
			$metaboxes_form = $metaboxes[ WP_CARDEALER_DEALER_PREFIX . 'fields_front' ];
			$post_id = WP_CarDealer_User::get_dealer_by_user_id($user_id);
	    } else {
	    	if ( ! isset( $metaboxes[ WP_CARDEALER_USER_PREFIX . 'fields_front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-cardealer' );
			}
			$metaboxes_form = $metaboxes[ WP_CARDEALER_USER_PREFIX . 'fields_front' ];
		}
		


	    wp_enqueue_script('google-maps');
		wp_enqueue_script('wpcd-select2');
		wp_enqueue_style('wpcd-select2');

    	return WP_CarDealer_Template_Loader::get_template_part( 'misc/profile-form', array( 'metaboxes_form' => $metaboxes_form  ) );
	}

	public static function approve_user($atts) {
	    return WP_CarDealer_Template_Loader::get_template_part( 'misc/approve-user' );
	}
	
	public static function my_saved_search( $atts ) {
		if ( !is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }

	    $user_id = get_current_user_id();
	    if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_vars = array(
		    'post_type' => 'saved_search',
		    'posts_per_page'    => get_option('posts_per_page'),
		    'paged'    			=> $paged,
		    'post_status' => 'publish',
		    'fields' => 'ids',
		    'author' => $user_id,
		);
		if ( isset($_GET['search']) ) {
			$query_vars['s'] = $_GET['search'];
		}
		if ( isset($_GET['orderby']) ) {
			switch ($_GET['orderby']) {
				case 'menu_order':
					$query_vars['orderby'] = array(
						'menu_order' => 'ASC',
						'date'       => 'DESC',
						'ID'         => 'DESC',
					);
					break;
				case 'newest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'DESC';
					break;
				case 'oldest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'ASC';
					break;
			}
		}
		$alerts = WP_CarDealer_Query::get_posts($query_vars);

		return WP_CarDealer_Template_Loader::get_template_part( 'misc/my-saved-searches', array( 'alerts' => $alerts ) );
	}

	public static function my_listing_favorites( $atts ) {
	    $listing_ids = WP_CarDealer_Favorite::get_listing_favorites();

	    $listing_ids_list = array();
	    if ( !empty($listing_ids) ) {
			foreach ($listing_ids as $listing_id) {
				$ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
				if ( !is_array($ids) ) {
					$ids = array($ids);
				}

				$listing_ids_list = array_merge($listing_ids_list, $ids);
			}
		}
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/listing-favorites', array( 'listing_ids' => $listing_ids_list ) );
	}

	public static function my_listing_compare( $atts ) {
	    $listing_ids = WP_CarDealer_Compare::get_compare_items();

	    $listing_ids_list = array();
	    if ( !empty($listing_ids) ) {
			foreach ($listing_ids as $listing_id) {
				$ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
				if ( !is_array($ids) ) {
					$ids = array($ids);
				}

				$listing_ids_list = array_merge($listing_ids_list, $ids);
			}
		}
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/listing-compare', array( 'listing_ids' => $listing_ids_list ) );
	}

	public static function user_reviews( $atts ) {
		if ( !is_user_logged_in() ) {
		    return WP_CarDealer_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }

		return WP_CarDealer_Template_Loader::get_template_part( 'misc/user-reviews' );
	}

	public static function listings( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => wp_cardealer_get_option('number_listings_per_page', 10)
		));
		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_args = array(
			'post_type' => 'listing',
		    'post_status' => 'publish',
		    'posts_per_page' => $atts['limit'],
		    'paged' => $paged,
		);
		$params = true;
		if ( WP_CarDealer_Abstract_Filter::has_filter() ) {
			$params = $_GET;
		} elseif (WP_CarDealer_Abstract_Filter::has_filter($atts)) {
			$params = $atts;
		}

		$listings = WP_CarDealer_Query::get_posts($query_args, $params);
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/listings', array( 'listings' => $listings, 'atts' => $atts ) );
	}
	
	public static function dealers( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => wp_cardealer_get_option('number_dealers_per_page', 10)
		));
		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_args = array(
			'post_type' => 'dealer',
		    'post_status' => 'publish',
		    'posts_per_page' => $atts['limit'],
		    'paged' => $paged,
		);
		$params = true;
		if ( WP_CarDealer_Abstract_Filter::has_filter() ) {
			$params = $_GET;
		} elseif (WP_CarDealer_Abstract_Filter::has_filter($atts)) {
			$params = $atts;
		}

		$dealers = WP_CarDealer_Query::get_posts($query_args, $params);
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/dealers', array( 'dealers' => $dealers, 'atts' => $atts ) );
	}

	public static function currencies() {
		return WP_CarDealer_Template_Loader::get_template_part( 'misc/currencies' );
	}
}

WP_CarDealer_Shortcodes::init();
