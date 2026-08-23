<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="submission-form-wrapper">
	<?php
		do_action( 'wp_cardealer_listing_submit_done_content_after', sanitize_title( $listing->post_status ), $listing );

		switch ( $listing->post_status ) :
			case 'publish' :
				echo wp_kses_post(sprintf(__( 'Listing listed successfully. To view your listing <a href="%s">click here</a>.', 'wp-cardealer' ), get_permalink( $listing->ID ) ));
			break;
			case 'pending' :
				echo wp_kses_post(sprintf(esc_html__( 'Listing submitted successfully. Your listing will be visible once approved.', 'wp-cardealer' ), get_permalink( $listing->ID )));
			break;
			default :
				do_action( 'wp_cardealer_listing_submit_done_content_' . str_replace( '-', '_', sanitize_title( $listing->post_status ) ), $listing );
			break;
		endswitch;

		do_action( 'wp_cardealer_listing_submit_done_content_after', sanitize_title( $listing->post_status ), $listing );
	?>
</div>
