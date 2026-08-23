<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$orderby_options = apply_filters( 'wp-cardealer-listings-orderby', array(
	'menu_order' => esc_html__('Default', 'wp-cardealer'),
	'newest' => esc_html__('Newest', 'wp-cardealer'),
	'oldest' => esc_html__('Oldest', 'wp-cardealer'),
	'price-lowest' => esc_html__('Lowest Price', 'wp-cardealer'),
	'price-highest' => esc_html__('Highest Price', 'wp-cardealer'),
	'random' => esc_html__('Random', 'wp-cardealer'),
));
$orderby = isset( $_GET['filter-orderby'] ) ? wp_unslash( $_GET['filter-orderby'] ) : 'menu_order';
if ( !WP_CarDealer_Mixes::is_ajax_request() ) {
	wp_enqueue_script('wpcd-select2');
	wp_enqueue_style('wpcd-select2');
}
?>
<div class="listings-ordering">
	<form class="listings-ordering" method="get" action="<?php echo WP_CarDealer_Mixes::get_listings_page_url(); ?>">
		<div class="label"><?php esc_html_e('Sort by:', 'wp-cardealer'); ?></div>
		<select name="filter-orderby" class="orderby" data-placeholder="<?php esc_attr_e('Sort by', 'wp-cardealer'); ?>">
			<?php foreach ( $orderby_options as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="hidden" name="paged" value="1" />
		<?php WP_CarDealer_Mixes::query_string_form_fields( null, array( 'filter-orderby', 'submit', 'paged' ) ); ?>
	</form>
</div>