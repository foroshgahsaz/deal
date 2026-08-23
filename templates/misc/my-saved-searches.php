<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_enqueue_script('wpcd-select2');
wp_enqueue_style('wpcd-select2');
?>
<div class="box-agent widget">
	<h3 class="widget-title"><?php echo esc_html__('Listing Saved Searches','wp-cardealer') ?></h3>
	
	<div class="search-orderby-wrapper flex-middle-sm">
		<div class="search-listings-saved-search-form widget-search">
			<form action="" method="get">
				<div class="input-group">
					<input type="text" placeholder="<?php echo esc_html__( 'Search ...', 'wp-cardealer' ); ?>" class="form-control" name="search" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
					<span class="input-group-btn">
						<button class="search-submit btn btn-sm btn-search" name="submit">
							<i class="flaticon-magnifying-glass"></i>
						</button>
					</span>
				</div>
				<input type="hidden" name="paged" value="1" />
			</form>
		</div>
		<div class="sort-listings-saved-search-form sortby-form">
			<?php
				$orderby_options = apply_filters( 'wp_cardealer_my_listings_orderby', array(
					'menu_order'	=> esc_html__( 'Default', 'wp-cardealer' ),
					'newest' 		=> esc_html__( 'Newest', 'wp-cardealer' ),
					'oldest'     	=> esc_html__( 'Oldest', 'wp-cardealer' ),
				) );

				$orderby = isset( $_GET['orderby'] ) ? wp_unslash( $_GET['orderby'] ) : 'newest'; 
			?>

			<div class="orderby-wrapper flex-middle">
				<span class="text-sort">
					<?php echo esc_html__('Sort by: ','wp-cardealer'); ?>
				</span>
				<form class="my-listings-ordering" method="get">
					<select name="orderby" class="orderby">
						<?php foreach ( $orderby_options as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="hidden" name="paged" value="1" />
					<?php WP_CarDealer_Mixes::query_string_form_fields( null, array( 'orderby', 'submit', 'paged' ) ); ?>
				</form>
			</div>
		</div>
	</div>
	<?php if ( !empty($alerts) && !empty($alerts->posts) ) {
		$email_frequency_default = WP_CarDealer_Saved_Search::get_email_frequency(); ?>
		<div class="table-responsive">
			<table class="listing-table">
				<thead>
					<tr>
						<th class="listing-title"><?php esc_html_e('Title', 'wp-cardealer'); ?></th>
						<th class="alert-query"><?php esc_html_e('Saved Search Query', 'wp-cardealer'); ?></th>
						<th class="listing-number"><?php esc_html_e('Number Listings', 'wp-cardealer'); ?></th>
						<th class="listing-times"><?php esc_html_e('Times', 'wp-cardealer'); ?></th>
						<th class="listing-actions"><?php esc_html_e('Actions', 'wp-cardealer'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($alerts->posts as $saved_search_id) {
					
					$email_frequency = get_post_meta($saved_search_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'email_frequency', true);
					if ( !empty($email_frequency_default[$email_frequency]['label']) ) {
						$email_frequency = $email_frequency_default[$email_frequency]['label'];
					}

					$saved_search_query = get_post_meta($saved_search_id, WP_CARDEALER_LISTING_SAVED_SEARCH_PREFIX . 'saved_search_query', true);
					$params = null;
					if ( !empty($saved_search_query) ) {
						$params = json_decode($saved_search_query, true);
					}

					$query_args = array(
						'post_type' => 'listing',
					    'post_status' => 'publish',
					    'post_per_page' => 1,
					    'fields' => 'ids'
					);
					$listings = WP_CarDealer_Query::get_posts($query_args, $params);
					$count_listings = $listings->found_posts;

					$listings_saved_search_url = WP_CarDealer_Mixes::get_listings_page_url();
					if ( !empty($params) ) {
						foreach ($params as $key => $value) {
							if ( is_array($value) ) {
								$listings_saved_search_url = remove_query_arg( $key.'[]', $listings_saved_search_url );
								foreach ($value as $val) {
									$listings_saved_search_url = add_query_arg( $key.'[]', $val, $listings_saved_search_url );
								}
							} else {
								$listings_saved_search_url = add_query_arg( $key, $value, remove_query_arg( $key, $listings_saved_search_url ) );
							}
						}
					}
					?>

					<?php do_action( 'wp_cardealer_before_saved_search_content', $saved_search_id ); ?>
					<tr <?php post_class('saved-search-wrapper'); ?>>
						<td>
							<div class="listing-table-info-content-title">
					        	<a href="<?php echo esc_url($listings_saved_search_url); ?>" rel="bookmark"><?php echo get_the_title($saved_search_id); ?></a>
					        </div>
						</td>
						<td>
							<div class="alert-query">
					        	<?php
					        	$params = WP_CarDealer_Abstract_Filter::get_filters($params);
					        	if ( $params ) {
					        		?>
					        		<ul class="list">
					        			<?php
						        			foreach ($params as $key => $value) {
						        				WP_CarDealer_Listing_Filter::display_filter_value_simple($key, $value, $params);
						        			}
					        			?>
					        		</ul>
					        	<?php } ?>
					        </div>
						</td>
						<td>
							<div class="listing-found">
					            <?php echo sprintf(esc_html__('Listings found %d', 'wp-cardealer'), intval($count_listings) ); ?>
					        </div>
						</td>
						<td>
							<div class="listing-metas">
					            <?php echo wp_kses_post($email_frequency); ?>
					        </div>
						</td>
						<td>
							<a href="javascript:void(0)" class="btn-remove-saved-search btn-action-icon deleted btn-action-sm" data-saved_search_id="<?php echo esc_attr($saved_search_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce( 'wp-cardealer-remove-saved-search-nonce' )); ?>"><i class="flaticon-rubbish-bin"></i></a>
						</td>
					</tr>
					
					<?php do_action( 'wp_cardealer_after_saved_search_content', $saved_search_id );
				}

				?>
				</tbody>
			</table>
		</div>
		<?php WP_CarDealer_Mixes::custom_pagination( array(
			'wp_query' => $alerts,
			'max_num_pages' => $alerts->max_num_pages,
			'prev_text'     => esc_html__( 'Previous page', 'wp-cardealer' ),
			'next_text'     => esc_html__( 'Next page', 'wp-cardealer' ),
		));
	?>

	<?php } else { ?>
		<div class="not-found"><?php esc_html_e('No listing alert found.', 'wp-cardealer'); ?></div>
	<?php } ?>
</div>