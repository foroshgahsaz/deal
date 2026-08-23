<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_frequency_default = WP_CarDealer_Saved_Search::get_email_frequency();
if ( !WP_CarDealer_Abstract_Filter::has_filter() ) {
	return;
}
?>
<div class="saved-search-form-btn">
	<a href="#saved-search-form-btn-wrapper" class="btn-saved-search"><?php esc_html_e('Save Search', 'wp-cardealer'); ?></a>
</div>
<div id="saved-search-form-btn-wrapper" class="saved-search-form-wrapper mfp-hide" data-effect="fadeIn">
	<form method="get" action="" class="saved-search-form">
		<div class="form-group">
		    <label for="saved_search_title"><?php esc_html_e('Title', 'wp-cardealer'); ?></label>

		    <input type="text" name="name" class="form-control" id="saved_search_title" placeholder="<?php esc_html_e('Title', 'wp-cardealer'); ?>">
		</div><!-- /.form-group -->

		<div class="form-group">
		    <label for="saved_search_email_frequency"><?php esc_html_e('Email Frequency', 'wp-cardealer'); ?></label>
		    <div class="wrapper-select">
			    <select name="email_frequency" class="form-control" id="saved_search_email_frequency">
			        <?php if ( !empty($email_frequency_default) ) { ?>
			            <?php foreach ($email_frequency_default as $key => $value) {
			                if ( !empty($value['label']) && !empty($value['days']) ) {
			            ?>
			                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($value['label']); ?></option>

			                <?php } ?>
			            <?php } ?>
			        <?php } ?>
			    </select>
		    </div>
		</div><!-- /.form-group -->

		<?php
			do_action('wp-cardealer-add-saved-search-form');

			wp_nonce_field('wp-cardealer-add-saved-search-nonce', 'nonce');
		?>

		<div class="form-group">
			<button class="button"><?php esc_html_e('Save', 'wp-cardealer'); ?></button>
		</div><!-- /.form-group -->

	</form>
</div>