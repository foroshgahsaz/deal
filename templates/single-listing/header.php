<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$address = WP_CarDealer_Listing::get_post_meta( $post->ID, 'address', true );
$price = WP_CarDealer_Listing::get_price_html($post->ID);

?>
<div class="listing-detail-header">
    
    <div class="listing-information">
        <?php WP_CarDealer_Listing::get_listing_types_html($post->ID); ?>

        <?php the_title( '<h1 class="entry-title listing-title">', '</h1>' ); ?>

        <div class="listing-date-author">
            <?php echo sprintf(__('posted %s ago', 'wp-cardealer'), human_time_diff(get_the_time('U'), current_time('timestamp')) ); ?> 
            
        </div>
        <div class="listing-metas">
            <?php if ( $address ) { ?>
                <div class="listing-location"><?php echo wp_kses_post($address); ?></div>
            <?php } ?>
            <?php if ( $price ) { ?>
                <div class="listing-price"><?php echo wp_kses_post($price); ?></div>
            <?php } ?>
        </div>
    </div>

    <div class="listing-detail-buttons">
        <?php WP_CarDealer_Listing::display_favorite_btn($post->ID); ?>
    </div>
</div>