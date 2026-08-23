<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$query_vars = array(
	'post_type'     => 'listing',
	'post_status'   => 'publish',
	'author'        => $user_id,
	'fields'		=> 'ids',
	'posts_per_page' => -1
);
$listings = new WP_Query($query_vars);
$count_listings = $listings->post_count;

$favorite = WP_CarDealer_Favorite::get_listing_favorites();
$favorite = is_array($favorite) ? count($favorite) : 0;

?>

<div class="user-dashboard-wrapper">
	<h1 class="title"><?php esc_html_e('Dashboard', 'wp-cardealer'); ?></h1>
	<div class="statistics row">
		<div class="col-sm-6">
			<div class="posted-listings">
				<div class="listings-count"><?php echo WP_CarDealer_Mixes::format_number($count_listings); ?></div>
				<h4><?php esc_html_e('Posted Listings', 'wp-cardealer'); ?></h4>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="favorite">
				<div class="listings-count"><?php echo WP_CarDealer_Mixes::format_number($favorite); ?></div>
				<h4><?php esc_html_e('Favorites', 'wp-cardealer'); ?></h4>
			</div>
		</div>
	</div>
	<div class="recent-wrapper row">
		<div class="col-sm-8">
			<!-- recent review -->
			<?php
			$post_ids = array();

			if ( !empty($listings->posts) ) {
				$post_ids = $listings->posts;
			}
			$number = apply_filters('wp-cardealer-dashboard-number-reviews', 3);
			$args = array(
				'post_type' => array('listing'),
				'status' => 'approve',
				'number'  => $number,
				'meta_query' => array(
			        array(
			           'key' => '_rating_avg',
			           'value' => 0,
			           'compare' => '>',
			        )
			    )
			);
			$comments = null;
			if ( !empty($post_ids) ) {
				$comments = WP_CarDealer_Review::get_comments( $args, $post_ids );
			}
			?>

			<div class="user-reviews">
				<?php
				if ( $comments ) {
					
					echo '<ul class="list-reviews">';
						wp_list_comments(array(
							'per_page' => $number,
							'page' => 1,
							'reverse_top_level' => false,
							'callback' => array('WP_CarDealer_Review', 'user_reviews')
						), $comments);
					echo '</ul>';
				} else { ?>
					<div class="not-found"><?php esc_html_e('No reviews found.', 'wp-cardealer'); ?></div>
				<?php } ?>

			</div>
		</div>
		<div class="col-sm-4">
			<!-- recent message -->
		</div>
	</div>
</div>
