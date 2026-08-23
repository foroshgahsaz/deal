<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;
?>

<?php do_action( 'wp_cardealer_before_listing_detail', $post->ID ); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<!-- heading -->
	<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/header' ); ?>

	<!-- Main content -->
	<div class="row">
		<div class="col-sm-9">

			<?php do_action( 'wp_cardealer_before_listing_content', $post->ID ); ?>

			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/description' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/energy' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/detail' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/amenities' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/floor-plans' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/sublistings' ); ?>
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/releated' ); ?>

			
			<?php if ( comments_open() || get_comments_number() ) : ?>
				<!-- Review -->
				<?php comments_template(); ?>
			<?php endif; ?>

			<?php do_action( 'wp_cardealer_after_listing_content', $post->ID ); ?>
		</div>
		<div class="col-sm-3">
			<?php do_action( 'wp_cardealer_before_listing_sidebar', $post->ID ); ?>
			<!-- listing detail agent -->
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/agent-detail' ); ?>
			<!-- listing detail -->
			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'single-listing/map-location' ); ?>

			<?php do_action( 'wp_cardealer_after_listing_sidebar', $post->ID ); ?>
		</div>
	</div>

</article><!-- #post-## -->

<?php do_action( 'wp_cardealer_after_listing_detail', $post->ID ); ?>