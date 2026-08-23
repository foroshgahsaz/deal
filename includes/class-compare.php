<?php
/**
 * Compare
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Compare {
	
	public static function init() {
        // Ajax endpoints.
        // add_listing_compare
        add_action( 'wpcd_ajax_wp_cardealer_ajax_add_listing_compare',  array(__CLASS__,'process_add_listing_compare') );

        // remove listing compare
        add_action( 'wpcd_ajax_wp_cardealer_ajax_remove_listing_compare',  array(__CLASS__,'process_remove_listing_compare') );

        // remove all listing compare
        add_action( 'wpcd_ajax_wp_cardealer_ajax_remove_all_listing_compare',  array(__CLASS__,'process_remove_all_listing_compare') );

        // compatible handlers.
		// add_listing_compare
		add_action( 'wp_ajax_wp_cardealer_ajax_add_listing_compare',  array(__CLASS__,'process_add_listing_compare') );
		add_action( 'wp_ajax_nopriv_wp_cardealer_ajax_add_listing_compare',  array(__CLASS__,'process_add_listing_compare') );

		// remove listing compare
		add_action( 'wp_ajax_wp_cardealer_ajax_remove_listing_compare',  array(__CLASS__,'process_remove_listing_compare') );
		add_action( 'wp_ajax_nopriv_wp_cardealer_ajax_remove_listing_compare',  array(__CLASS__,'process_remove_listing_compare') );

        // remove all listing compare
        add_action( 'wp_ajax_wp_cardealer_ajax_remove_all_listing_compare',  array(__CLASS__,'process_remove_all_listing_compare') );
        add_action( 'wp_ajax_nopriv_wp_cardealer_ajax_remove_all_listing_compare',  array(__CLASS__,'process_remove_all_listing_compare') );
	}

	public static function compare_fields() {
		return apply_filters( 'wp-cardealer-default-listing-compare-fields', array() );
	}

	public static function process_add_listing_compare() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-add-listing-compare-nonce' ) ) {
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

		do_action('wp-cardealer-process-add-listing-compare', $_POST);


		$compare = array();
        if ( isset($_COOKIE['cardealer_compare']) ) {
            $compare = explode( ',', $_COOKIE['cardealer_compare'] );
            if ( !self::check_added_compare($listing_id, $compare) ) {
                $compare[] = $listing_id;
            }
        } else {
            $compare = array( $listing_id );
        }
		setcookie( 'cardealer_compare', implode(',', $compare), time()+3600*24*10, '/' );
        $_COOKIE['cardealer_compare'] = implode(',', $compare);


        $return = apply_filters( 'wp-cardealer-process-add-listing-compare-return', array(
            'status' => true,
            'nonce' => wp_create_nonce( 'wp-cardealer-remove-listing-compare-nonce' ),
            'msg' => esc_html__('Add compare successfully.', 'wp-cardealer'),
        ));
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_remove_listing_compare() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-remove-listing-compare-nonce' ) ) {
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

		do_action('wp-cardealer-process-remove-listing-compare', $_POST);


		$newcomapre = array();
        if ( isset($_COOKIE['cardealer_compare']) ) {
            $compare = explode( ',', $_COOKIE['cardealer_compare'] );
            $listing_ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
            if ( !is_array($listing_ids) ) {
                $listing_ids = array($listing_ids);
            }
            
            foreach ($compare as $key => $value) {
                
                $has_listing = false;
                foreach ( $listing_ids as $tlisting_id ) {
                    if ( $tlisting_id == $value ) {
                        $has_listing = true;
                    }
                }

                if ( !$has_listing ) {
                    $newcomapre[] = $value;
                }
            }
        }
        setcookie( 'cardealer_compare', implode(',', $newcomapre) , time()+3600*24*10, '/' );
        $_COOKIE['cardealer_compare'] = implode(',', $newcomapre);


        $return = apply_filters( 'wp-cardealer-process-remove-listing-compare-return', array(
            'status' => true,
        	'nonce' => wp_create_nonce( 'wp-cardealer-add-listing-compare-nonce' ),
        	'msg' => esc_html__('Remove listing from compare successfully.', 'wp-cardealer'),
        	'count' => count($newcomapre)
        ));
	   	echo wp_json_encode($return);
	   	exit;
	}

    public static function process_remove_all_listing_compare() {
        $return = array();
        if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-cardealer-remove-listing-compare-nonce' ) ) {
            $return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-cardealer') );
            echo wp_json_encode($return);
            exit;
        }


        do_action('wp-cardealer-process-remove-listing-compare', $_POST);

        if ( isset($_COOKIE['cardealer_compare']) ) {
            setcookie( 'cardealer_compare', '' , -100, '/' );
        }

        $return = apply_filters( 'wp-cardealer-process-remove-all-listing-compare-return', array(
            'status' => true,
            'nonce' => wp_create_nonce( 'wp-cardealer-add-listing-compare-nonce' ),
            'msg' => esc_html__('Remove all listings from compare successfully.', 'wp-cardealer'),
            'count' => 0
        ));
        echo wp_json_encode($return);
        exit;
    }

	public static function get_compare_items() {
        if ( isset($_COOKIE['cardealer_compare']) && !empty($_COOKIE['cardealer_compare']) ) {
            return explode( ',', $_COOKIE['cardealer_compare'] );
        }
        return array();
    }

	public static function check_added_compare($listing_id) {
		if ( empty($listing_id) ) {
			return false;
		}
        
		if ( empty($compares) && isset($_COOKIE['cardealer_compare']) && !empty($_COOKIE['cardealer_compare']) ) {
            $compares = explode( ',', $_COOKIE['cardealer_compare'] );

            $listing_ids = apply_filters( 'wp-cardealer-translations-post-ids', $listing_id );
            if ( !is_array($listing_ids) ) {
                $listing_ids = array($listing_ids);
            }
            foreach ( $listing_ids as $tlisting_id ) {
                if ( in_array($tlisting_id, $compares) ) {
                    return true;
                }
            }
        }
        return false;
	}

	public static function display_compare_btn($listing_id, $args = array()) {
        $args = wp_parse_args( $args, array(
            'show_icon' => true,
            'show_text' => false,
            'echo' => true,
            'tooltip' => true,
            'added_classes' => 'btn-added-listing-compare',
            'added_text' => esc_html__('Remove Compare', 'wp-cardealer'),
            'added_tooltip_title' => esc_html__('Remove Compare', 'wp-cardealer'),
            'added_icon_class' => 'flaticon-repeat',
            'add_classes' => 'btn-add-listing-compare',
            'add_text' => esc_html__('Add Compare', 'wp-cardealer'),
            'add_icon_class' => 'flaticon-repeat',
            'add_tooltip_title' => esc_html__('Add Compare', 'wp-cardealer'),
        ));

		if ( self::check_added_compare($listing_id) ) {
			$classes = $args['added_classes'];
			$nonce = wp_create_nonce( 'wp-cardealer-remove-listing-compare-nonce' );
			$text = $args['added_text'];
            $icon_class = $args['added_icon_class'];
			$tooltip_title = $args['added_tooltip_title'];
		} else {
			$classes = $args['add_classes'];
			$nonce = wp_create_nonce( 'wp-cardealer-add-listing-compare-nonce' );
			$text = $args['add_text'];
			$icon_class = $args['add_icon_class'];
            $tooltip_title = $args['add_tooltip_title'];
		}
		ob_start();
		?>
		<a href="javascript:void(0)" class="action-compare <?php echo esc_attr($classes); ?>" data-listing_id="<?php echo esc_attr($listing_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"
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

	public static function get_data($key, $post_id, $field) {
		$obj_listing_meta = WP_CarDealer_Listing_Meta::get_instance($post_id);
		
        switch ($key) {
            case 'title':
                $value = '<h3><a href="'.esc_url(get_permalink($post_id)).'">'.get_the_title($post_id).'</a></h3>';
                break;
            case 'description':
                $value = get_post_field('post_content', $post_id);
                break;
            case 'engine_size':
            case 'mileage':
            case 'vin':
            case 'year':
                $value = $obj_listing_meta->get_post_meta( $key );
                break;
            // taxonomy
            case 'feature':
                $features = get_the_terms($post_id, 'listing_feature');
                $value = '';

                if ( ! empty( $features ) && ! is_wp_error( $features ) ){
                    $features_cats = [];
                    foreach ($features as $feature) {
                        $parent = get_term_meta( $feature->term_id, '_category_parent', true );
                        if ( !empty($parent) ) {
                            if ( !is_array($parent) ) {
                                $features_cats[$parent]['features'][] = $feature;

                                $menu_order = 0;
                                $term = get_term_by('slug', $parent, 'listing_feature_category');
                                if ( $term ) {
                                    $menu_order = get_term_meta( $term->term_id, 'menu_order', true );
                                    $features_cats[$parent]['priority'] = $menu_order;
                                    $features_cats[$parent]['parent_term'] = $term;
                                }
                            } else {
                                foreach ($parent as $parent_id) {
                                    $features_cats[$parent_id]['features'][] = $feature;
                                    $menu_order = 0;
                                    $term = get_term_by('slug', $parent_id, 'listing_feature_category');
                                    if ( $term ) {
                                        $menu_order = get_term_meta( $term->term_id, 'menu_order', true );
                                        $features_cats[$parent_id]['priority'] = $menu_order;
                                        $features_cats[$parent]['parent_term'] = $term;
                                    }
                                }
                            }
                        } else {
                            $features_cats[0]['features'][] = $feature;
                            $features_cats[$parent]['priority'] = 0;
                        }
                    }

                    if ( $features_cats ) {
                        uasort( $features_cats, array( 'WP_CarDealer_Mixes', 'sort_array_by_priority' ) );
                    }
                    
                    if ( $features_cats ) {
                        uasort( $features_cats, array( 'WP_CarDealer_Mixes', 'sort_array_by_priority' ) );
                    }
                    if ( !empty($features_cats) ) {
                        foreach ($features_cats as $parent_slug => $features) {
                            $value .= '<div class="feature-item">';
                            if ( !empty($features['parent_term']) ) {
                                $value .= '<h3>'.trim($features['parent_term']->name).'</h3>';
                            } else {
                                $value .= '<h3>'.esc_html__('General', 'wp-cardealer').'</h3>';
                            }
                            $value .= '<ul class="list-features">';
                            foreach ($features['features'] as $term) {
                                $value .= '<li><a href="'.esc_url(get_term_link($term)).'">'.$term->name.'</a></li>';
                            }
                            $value .= '</ul>';
                            $value .= '</div>';
                        }
                    }
                }
                $value = '<div class="features-wrapper">'.$value.'</div>';
                break;
            case 'category':
            case 'condition':
            case 'type':
            case 'color':
            case 'cylinder':
            case 'door':
            case 'drive_type':
            case 'fuel_type':
            case 'make':
            case 'model':
            case 'offer_type':
            case 'transmission':
            case 'label':
                $terms = get_the_terms($post_id, 'listing_'.$key);
                $value = '';
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ($terms as $term) {
                        $value .= ', ' . '<a href="'.esc_url(get_term_link($term)).'">'.$term->name.'</a>';
                    }
                }
                $value = ltrim($value, ', ');
                break;
            case 'price':
                $value = $obj_listing_meta->get_price_html();
                break;
            // yes/no
            case 'featured':
                $value = $obj_listing_meta->get_post_meta( $key );
                break;
            default:
                if ( !empty($field['custom_field_type']) && $field['custom_field_type'] == 'custom_field' ) {
                    $value = $obj_listing_meta->get_custom_post_meta( $key );
                } else {
                    $value = $obj_listing_meta->get_post_meta( $key );
                }
                break;
        }
        return apply_filters('wp-cardealer-compare-field-value', $value, $key, $post_id);
    }
}

WP_CarDealer_Compare::init();