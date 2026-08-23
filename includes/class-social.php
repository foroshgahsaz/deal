<?php
/**
 * Social: Social
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_CarDealer_Social {
    /**
     * Initialize social
     *
     * @access public
     * @return void
     */
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'open_graph_meta' ), 1 );
    }

    /**
     * The Open Graph protocol meta
     * http://ogp.me/
     *
     * @access public
     * @return string
     */
    public static function open_graph_meta() {
        if ( is_singular() ) {
            echo '<meta property="og:title" content="' . get_the_title() . '" />';
            $thumbnail_id = get_post_thumbnail_id();
            if ( ! empty( $thumbnail_id ) ) {
                $image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
                if ( !empty($image[0]) ) {
                    echo '<meta property="og:image" content="' . esc_attr( $image[0] ) . '" />';
                }
            }
        }
    }
}

WP_CarDealer_Social::init();