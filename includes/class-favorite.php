<?php
/**
 * Favorite
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Favorite {
	
	public static function init() {
		// Ajax endpoints.
		// add_listing_favorite
		add_action( 'wpcd_ajax_wp_cardealer_ajax_add_listing_favorite',  array(__CLASS__,'process_add_listing_favorite') );

		// remove listing favorite
		add_action( 'wpcd_ajax_wp_cardealer_ajax_remove_listing_favorite',  array(__CLASS__,'process_remove_listing_favorite') );
	}

	public static function process_add_listing_favorite() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-add-listing-favorite-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$listing_id = !empty($_POST['listing_id']) ? $_POST['listing_id'] : '';
		$post = get_post($listing_id);

		if ( !$post || empty($post->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Listing did not exists.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-cardealer-process-add-listing-favorite', $_POST);


		$favorite = array();
        if ( isset($_COOKIE['cardealer_favorite']) ) {
            $favorite = explode( ',', $_COOKIE['cardealer_favorite'] );
            if ( !self::check_added_favorite($listing_id) ) {
                $favorite[] = $listing_id;

                $notify_args = array(
					'user_id' => $post->post_author,
		            'type' => 'new_favorite',
		            'listing_id' => $listing_id,
				);
				WP_CarDealer_User_Notification::add_notification($notify_args);
            }
        } else {
            $favorite = array( $listing_id );

            $notify_args = array(
				'user_id' => $post->post_author,
	            'type' => 'new_favorite',
	            'listing_id' => $listing_id,
			);
			WP_CarDealer_User_Notification::add_notification($notify_args);
        }
		setcookie( 'cardealer_favorite', implode(',', $favorite), time()+3600*24*10, '/' );
        $_COOKIE['cardealer_favorite'] = implode(',', $favorite);

        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-cardealer-remove-listing-favorite-nonce' ), 'msg' => esc_html__('Add favorite successfully.', 'wp-cardealer') );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_remove_listing_favorite() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-remove-listing-favorite-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$listing_id = !empty($_POST['listing_id']) ? $_POST['listing_id'] : '';

		if ( empty($listing_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Listing did not exists.', 'wp-cardealer') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-cardealer-process-remove-listing-favorite', $_POST);

		$newfavorite = array();
        if ( isset($_COOKIE['cardealer_favorite']) ) {
            $favorite = explode( ',', $_COOKIE['cardealer_favorite'] );
            
            $listing_ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
            if ( !is_array($listing_ids) ) {
                $listing_ids = array($listing_ids);
            }

            foreach ($favorite as $key => $value) {
                
                $has_listing = false;
                foreach ( $listing_ids as $tlisting_id ) {
                    if ( $tlisting_id == $value ) {
                        $has_listing = true;
                    }
                }

                if ( !$has_listing ) {
                    $newfavorite[] = $value;
                }
            }
        }

        setcookie( 'cardealer_favorite', implode(',', $newfavorite) , time()+3600*24*10, '/' );
        $_COOKIE['cardealer_favorite'] = implode(',', $newfavorite);

        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-cardealer-add-listing-favorite-nonce' ), 'msg' => esc_html__('Remove listing from favorite successfully.', 'wp-cardealer') );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function check_added_favorite($listing_id) {
		if ( empty($listing_id) ) {
			return false;
		}

		if ( isset($_COOKIE['cardealer_favorite']) && !empty($_COOKIE['cardealer_favorite']) ) {
            $favorites = explode( ',', $_COOKIE['cardealer_favorite'] );
            $listing_ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
            if ( !is_array($listing_ids) ) {
                $listing_ids = array($listing_ids);
            }
            foreach ( $listing_ids as $tlisting_id ) {
                if ( in_array($tlisting_id, $favorites) ) {
    	            return true;
    	        }
            }
        }
    	return false;
	}

	public static function get_listing_favorites() {
		if ( isset($_COOKIE['cardealer_favorite']) && !empty($_COOKIE['cardealer_favorite']) ) {
            return explode( ',', $_COOKIE['cardealer_favorite'] );
        }
        return array();
    }

	public static function display_favorite_btn($listing_id, $args = array()) {
		$args = wp_parse_args( $args, array(
			'show_icon' => true,
			'show_text' => false,
			'echo' => true,
			'tooltip' => true,
			'added_classes' => 'btn-added-listing-favorite',
			'added_text' => esc_html__('Remove Favorite', 'wp-cardealer'),
			'added_tooltip_title' => esc_html__('Remove Favorite', 'wp-cardealer'),
			'added_icon_class' => 'flaticon-heart',
			'add_classes' => 'btn-add-listing-favorite',
			'add_text' => esc_html__('Add Favorite', 'wp-cardealer'),
			'add_icon_class' => 'flaticon-heart',
			'add_tooltip_title' => esc_html__('Add Favorite', 'wp-cardealer'),
		));

		if ( self::check_added_favorite($listing_id) ) {
			$classes = $args['added_classes'];
			$nonce = wp_create_nonce( 'wp-cardealer-remove-listing-favorite-nonce' );
			$text = $args['added_text'];
			$icon_class = $args['added_icon_class'];
			$tooltip_title = $args['added_tooltip_title'];
		} else {
			$classes = $args['add_classes'];
			$nonce = wp_create_nonce( 'wp-cardealer-add-listing-favorite-nonce' );
			$text = $args['add_text'];
			$icon_class = $args['add_icon_class'];
			$tooltip_title = $args['add_tooltip_title'];
		}
		ob_start();
		?>
		<a href="javascript:void(0)" class="action-favorite <?php echo esc_attr($classes); ?>" data-listing_id="<?php echo esc_attr($listing_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"
			<?php if ($args['tooltip']) { ?>
                data-toggle="tooltip"
                title="<?php echo esc_attr($tooltip_title); ?>"
            <?php } ?>>
			<?php if ( $args['show_icon'] ) { ?>
				<i class="<?php echo esc_attr($icon_class); ?>"></i>
			<?php } ?>
			<?php if ( $args['show_text'] ) { ?>
				<span><?php echo esc_html($text); ?></span>
			<?php } ?>
		</a>
		<?php
		$output = ob_get_clean();
	    if ( $args['echo'] ) {
	    	echo trim($output);
	    } else {
	    	return $output;
	    }
	}
}
WP_CarDealer_Favorite::init();