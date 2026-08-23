<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="listings-pagination-wrapper">
	<?php
		WP_CarDealer_Mixes::custom_pagination( array(
			'max_num_pages' => $listings->max_num_pages,
			'prev_text'     => esc_html__( 'Previous page', 'wp-cardealer' ),
			'next_text'     => esc_html__( 'Next page', 'wp-cardealer' ),
			'wp_query' 		=> $listings
		));
	?>
</div>
