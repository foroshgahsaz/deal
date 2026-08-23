<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$meta_obj = WP_CarDealer_Listing_Meta::get_instance($post->ID);
?>
<div class="listing-detail-detail">
    <h3><?php esc_html_e('Details', 'wp-cardealer'); ?></h3>
    <ul class="list">
        <?php if ( $meta_obj->check_post_meta_exist('listing_id') ) { ?>
            <li>
                <div class="text"><?php esc_html_e('Listing ID : ', 'wp-cardealer'); ?></div>
                <div class="value"><?php echo trim($meta_obj->get_post_meta('listing_id')); ?></div>
            </li>
        <?php } ?>
        
        <?php if ( $meta_obj->check_post_meta_exist('price') ) { ?>
            <li>
                <div class="text"><?php esc_html_e('Price : ', 'wp-cardealer'); ?></div>
                <div class="value"><?php echo trim($meta_obj->get_price_html()); ?></div>
            </li>
        <?php } ?>

        <?php if ( $meta_obj->check_post_meta_exist('year') ) { ?>
            <li>
                <div class="text"><?php esc_html_e('Year built : ', 'wp-cardealer'); ?></div>
                <div class="value"><?php echo trim($meta_obj->get_post_meta('year')); ?></div>
            </li>
        <?php } ?>

        <?php do_action('wp-cardealer-single-listing-details', $post); ?>
    </ul>
</div>