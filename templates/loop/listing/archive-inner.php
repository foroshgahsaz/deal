<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>
<div class="listings-listing-wrapper">
	<?php
	/**
	 * wp_cardealer_before_listing_archive
	 */
	do_action( 'wp_cardealer_before_listing_archive', $listings );
	?>

	<?php if ( $listings->have_posts() ) : ?>
		<?php
		/**
		 * wp_cardealer_before_loop_listing
		 */
		do_action( 'wp_cardealer_before_loop_listing', $listings );
		?>

		<div class="listings-wrapper">
			<?php while ( $listings->have_posts() ) : $listings->the_post(); ?>
				<?php echo WP_CarDealer_Template_Loader::get_template_part( 'listings-styles/inner-list' ); ?>
			<?php endwhile; ?>
		</div>

		<?php
		/**
		 * wp_cardealer_after_loop_listing
		 */
		do_action( 'wp_cardealer_after_loop_listing', $listings );

		WP_CarDealer_Mixes::custom_pagination( array(
			'max_num_pages' => $listings->max_num_pages,
			'prev_text'     => esc_html__( 'Previous page', 'wp-cardealer' ),
			'next_text'     => esc_html__( 'Next page', 'wp-cardealer' ),
			'wp_query' 		=> $listings
		));
		?>

	<?php else : ?>
		<div class="not-found"><?php esc_html_e('No listing found.', 'wp-cardealer'); ?></div>
	<?php endif; ?>

	<?php
	/**
	 * wp_cardealer_before_listing_archive
	 */
	do_action( 'wp_cardealer_before_listing_archive', $listings );
	?>
</div>