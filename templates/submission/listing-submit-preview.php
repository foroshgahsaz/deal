<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;
?>
<div class="listing-submission-preview-form-wrapper">
	<?php if ( sizeof($form_obj->errors) ) : ?>
		<ul class="messages">
			<?php foreach ( $form_obj->errors as $message ) { ?>
				<li class="message_line danger">
					<?php echo wp_kses_post( $message ); ?>
				</li>
			<?php
			}
			?>
		</ul>
	<?php endif; ?>
	<form action="<?php echo esc_url($form_obj->get_form_action());?>" class="cmb-form" method="post" enctype="multipart/form-data" encoding="multipart/form-data">
		<input type="hidden" name="<?php echo esc_attr($form_obj->get_form_name()); ?>" value="<?php echo esc_attr($form_obj->get_form_name()); ?>">
		<input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
		<input type="hidden" name="submit_step" value="<?php echo esc_attr($step); ?>">
		<input type="hidden" name="object_id" value="<?php echo esc_attr($listing_id); ?>">
		<?php wp_nonce_field('wp-cardealer-listing-submit-preview-nonce', 'security-listing-submit-preview'); ?>

		<button class="button btn" name="continue-submit-listing"><?php esc_html_e('Submit Listing', 'wp-cardealer'); ?></button>
		<button class="button btn" name="continue-edit-listing"><?php esc_html_e('Edit Listing', 'wp-cardealer'); ?></button>

		
	</form>

	<?php echo WP_CarDealer_Template_Loader::get_template_part( 'content-single-listing' ); ?>
</div>
