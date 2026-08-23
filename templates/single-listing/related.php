<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$relate_count = apply_filters('wp_cardealer_number_listing_releated', 2);

$tax_query = array();
$terms = WP_CarDealer_Listing::get_listing_taxs( $post->ID, 'listing_type' );
if ($terms) {
    $termids = array();
    foreach($terms as $term) {
        $termids[] = $term->term_id;
    }
    $tax_query[] = array(
        'taxonomy' => 'listing_type',
        'field' => 'id',
        'terms' => $termids,
        'operator' => 'IN'
    );
}

$terms = WP_CarDealer_Listing::get_listing_taxs( $post->ID, 'listing_status' );
if ($terms) {
    $termids = array();
    foreach($terms as $term) {
        $termids[] = $term->term_id;
    }
    $tax_query[] = array(
        'taxonomy' => 'listing_status',
        'field' => 'id',
        'terms' => $termids,
        'operator' => 'IN'
    );
}

if ( empty($tax_query) ) {
    return;
}
$args = array(
    'post_type' => 'listing',
    'posts_per_page' => $relate_count,
    'post__not_in' => array( get_the_ID() ),
    'tax_query' => array_merge(array( 'relation' => 'AND' ), $tax_query)
);
$relates = new WP_Query( $args );
if( $relates->have_posts() ):
?>
    <div class="widget releated-listings">
        <h4 class="widget-title">
            <span><?php esc_html_e( 'Related Listings', 'wp-cardealer' ); ?></span>
        </h4>
        <div class="widget-content">
            <?php
                while ( $relates->have_posts() ) : $relates->the_post();
                    echo WP_CarDealer_Template_Loader::get_template_part( 'listings-styles/inner-list' );
                endwhile;
            ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
<?php endif; ?>