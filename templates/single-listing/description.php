<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;


?>
<div class="description inner">
    <h3 class="title"><?php esc_html_e('Overview', 'wp-cardealer'); ?></h3>
    <div class="description-inner">
        <?php the_content(); ?>

        <?php do_action('wp-cardealer-single-listing-description', $post); ?>
    </div>
</div>