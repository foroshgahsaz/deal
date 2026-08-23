<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !empty($listing_ids) && is_array($listing_ids) ) {
	$query_args = array(
		'post_type'         => 'listing',
		'posts_per_page'    => -1,
		'paged'    			=> 1,
		'post_status'       => 'publish',
		'post__in'       	=> $listing_ids,
		'fields'			=> 'ids'
	);

	$listings = new WP_Query($query_args);
	if ( $listings->have_posts() ) {
		?>
		<div class="wrapper-compare">
			<table class="compare-tables">
				<thead>
					<tr>
						<th>
							<?php esc_html_e('Basic Info', 'wp-cardealer'); ?>
						</th>
						<?php
						foreach ($listings->posts as $listing_id) {
							$obj_listing_meta = WP_CarDealer_Listing_Meta::get_instance($listing_id);
							$price = $obj_listing_meta->get_price_html();
							?>
							<th>
								<div class="thumb">
									<?php if ( has_post_thumbnail( $listing_id ) ) : ?>
										<?php echo get_the_post_thumbnail( $listing_id, 'thumbnail' ); ?>
						            <?php endif; ?>
						            <a href="javascript:void(0);" class="btn-remove-listing-compare" data-listing_id="<?php echo esc_attr($listing_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce( 'wp-cardealer-remove-listing-compare-nonce' )); ?>">
										<i class="flaticon-cross" aria-hidden="true"></i> remove
									</a>
								</div>

								<?php if ( $price ) { ?>
					                <div class="listing-price"><?php echo $price; ?></div>
					            <?php } ?>

								<h3 class="entry-title"><a href="<?php echo esc_url(get_permalink( $listing_id )); ?>"><?php echo get_the_title( $listing_id ) ?></a></h3>
							</th>
							<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
						$compare_fields = WP_CarDealer_Compare::compare_fields();
						$count = 0;
						foreach ($compare_fields as $key => $field) {
							if ( wp_cardealer_get_option('enable_compare_'.$field['id'], 'on') == 'on' ) {
								?>
								<tr class="<?php echo esc_attr($count%2 == 0 ? 'tr-0' : 'tr-1'); ?>">
									<td><?php echo trim($field['name']); ?></td>
									<?php foreach ($listings->posts as $listing_id) { ?>
										<td>
											<?php echo trim(WP_CarDealer_Compare::get_data($key, $listing_id, $field)); ?>
										</td>
									<?php } ?>
								</tr>
								<?php
								$count++;
							}
						}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}
} else {
?>
	<div class="not-found"><?php esc_html_e('No listings found.', 'wp-cardealer'); ?></div>
<?php
}