<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_enqueue_script('wpcd-select2');
wp_enqueue_style('wpcd-select2');
?>
<div class="search-orderby-wrapper flex-middle-sm">
	<div class="search-listings-favorite-form widget-search">
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
	<div class="sort-listings-favorite-form sortby-form">
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
<?php
if ( !empty($listing_ids) && is_array($listing_ids) ) {
	if ( get_query_var( 'paged' ) ) {
	    $paged = get_query_var( 'paged' );
	} elseif ( get_query_var( 'page' ) ) {
	    $paged = get_query_var( 'page' );
	} else {
	    $paged = 1;
	}
	$query_args = array(
		'post_type'         => 'listing',
		'posts_per_page'    => get_option('posts_per_page'),
		'paged'    			=> $paged,
		'post_status'       => 'publish',
		'post__in'       	=> $listing_ids,
	);
	if ( isset($_GET['search']) ) {
		$query_vars['s'] = $_GET['search'];
	}
	if ( isset($_GET['orderby']) ) {
		switch ($_GET['orderby']) {
			case 'menu_order':
				$query_vars['orderby'] = array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
					'ID'         => 'DESC',
				);
				break;
			case 'newest':
				$query_vars['orderby'] = 'date';
				$query_vars['order'] = 'DESC';
				break;
			case 'oldest':
				$query_vars['orderby'] = 'date';
				$query_vars['order'] = 'ASC';
				break;
		}
	}

	$listings = new WP_Query($query_args);
	if ( $listings->have_posts() ) {
		while ( $listings->have_posts() ) : $listings->the_post();
			?>

			<?php echo WP_CarDealer_Template_Loader::get_template_part( 'listings-styles/inner-list-favorite' ); ?>

			<?php
		endwhile;

		wp_reset_postdata();

		WP_CarDealer_Mixes::custom_pagination( array(
			'max_num_pages' => $listings->max_num_pages,
			'prev_text'     => esc_html__( 'Previous page', 'wp-cardealer' ),
			'next_text'     => esc_html__( 'Next page', 'wp-cardealer' ),
			'wp_query' 		=> $listings
		));
	}
?>

<?php } else { ?>
	<div class="not-found"><?php esc_html_e('No listings found.', 'wp-cardealer'); ?></div>
<?php } ?>