<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$amenities = get_categories( array(
    'taxonomy'      => 'listing_amenity',
    'hide_empty'    => false,
) );
?>

<?php if ( ! empty( $amenities ) ) : ?>
    <div class="listing-section listing-amenities">
        <h3 class="title"><?php echo esc_html__('Amenities', 'wp-cardealer'); ?></h3>
        <ul class="columns-gap list-check">
            <?php foreach ( $amenities as $amenity ) : ?>
                <?php $has_term = has_term( $amenity->term_id, 'listing_amenity' ); ?>

                <li <?php if ( $has_term ) : ?>class="yes"<?php else : ?>class="no"<?php endif; ?>><?php echo esc_html( $amenity->name ); ?></li>
                
            <?php endforeach; ?>
        </ul>

        <?php do_action('wp-cardealer-single-listing-amenities', $post); ?>
    </div><!-- /.listing-amenities -->
<?php endif; ?>