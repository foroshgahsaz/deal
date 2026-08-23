<?php
/**
 * Widget: Listing Filter
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Widget_Listing_Filter extends WP_Widget {
	/**
	 * Initialize widget
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		parent::__construct(
			'listing_filter_widget',
			__( 'Listing Filter', 'wp-cardealer' ),
			array(
				'description' => __( 'Filter for filtering listings.', 'wp-cardealer' ),
			)
		);
	}

	/**
	 * Frontend
	 *
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		include WP_CarDealer_Template_Loader::locate( 'widgets/listing-filter' );
	}

	/**
	 * Update
	 *
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	/**
	 * Backend
	 *
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : '';
		$sort = ! empty( $instance['sort'] ) ? $instance['sort'] : '';

		$_id = rand(100, 100000);
		?>
		<div id="filter-listing-<?php echo esc_attr($_id); ?>">
			<!-- TITLE -->
			<p>
			    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
			        <?php echo __( 'Title', 'wp-cardealer' ); ?>
			    </label>

			    <input  class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>

			<!-- BUTTON TEXT -->
			<p>
			    <label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>">
			        <?php echo __( 'Button text', 'wp-cardealer' ); ?>
			    </label>

			    <input  class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" type="text" value="<?php echo esc_attr( $button_text ); ?>">
			</p>

			<h3><?php _e('Filter Fields', 'wp-cardealer'); ?></h3>
			<ul class="wp-cardealer-filter-fields wp-cardealer-filter-listing-fields">
				<?php

				$fields = $adv_fields = $all_fields = WP_CarDealer_Listing_Filter::get_fields();
				
				if ( ! empty( $sort ) ) {
					$filtered_keys = array_filter( explode( ',', $sort ) );
					$fields = array_replace( array_flip( $filtered_keys ), $all_fields );
				}
				
				?>
				<input type="hidden" value="<?php echo esc_attr( $sort ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sort' ) ); ?>" value="<?php echo esc_attr( $sort ); ?>">

				<?php foreach ( $fields as $key => $value ) :
					if ( !empty($all_fields[$key]) ) {
				?>
					<li data-field-id="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $instance[ 'hide_' . $key ] ) ) : ?>class="invisible"<?php endif; ?>>
						<p>
							<label for="<?php echo esc_attr( $this->get_field_id( 'hide_' . $key ) ); ?>">
								<?php echo esc_attr( $value['name'] ); ?>
							</label>

							<span class="visibility">
								<input 	type="checkbox" class="checkbox field-visibility" <?php echo ! empty( $instance[ 'hide_'. $key ] ) ? 'checked="checked"' : ''; ?> name="<?php echo esc_attr( $this->get_field_name( 'hide_' . $key ) ); ?>">

								<i class="dashicons dashicons-visibility"></i>
							</span>
						</p>
					</li>
				<?php } ?>
				<?php endforeach ?>
			</ul>

			<p>
				<h3><label><?php echo esc_html__( 'Show Advance Filter Fields', 'wp-cardealer' ); ?></label></h3>
				<label>
					<input type="checkbox" class="checkbox field-visibility show_adv_fields"
							<?php echo trim(! empty( $instance['show_adv_fields'] ) ? 'checked="checked"' : ''); ?>
				            name="<?php echo esc_attr( $this->get_field_name( 'show_adv_fields' ) ); ?>">
			        <?php echo esc_html__( 'Show Advance Filter Fields', 'wp-cardealer' ); ?>
			    </label>
			</p>
			<hr>
			<div class="wp-cardealer-advance-filter-fields-wrapper">
				<h3><?php echo esc_html__('Advance Filter Fields', 'wp-cardealer'); ?></h3>
				<ul class="wp-cardealer-filter-fields wp-cardealer-advance-filter-fields">
					<?php if ( ! empty( $instance['sort_adv'] ) ) : ?>
						<?php
						$filtered_keys = array_filter( explode( ',', $instance['sort_adv'] ) );
						$adv_fields = array_replace( array_flip( $filtered_keys ), $all_fields );
						?>
					<?php endif; ?>

					<input type="hidden" value="<?php echo esc_attr( $sort_adv ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'sort_adv' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sort_adv' ) ); ?>" value="<?php echo esc_attr( $sort_adv ); ?>">

					<?php foreach ( $adv_fields as $key => $value ) :
						if ( !empty($all_fields[$key]) ) {
					?>
						<li data-field-id="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $instance[ 'hide_adv_' . $key ] ) ) : ?>class="invisible"<?php endif; ?>>
							<p>
								<label for="<?php echo esc_attr( $this->get_field_id( 'hide_adv_' . $key ) ); ?>">
									<?php echo esc_attr( $value['name'] ); ?>
								</label>

								<span class="visibility">
						            <input 	type="checkbox" class="checkbox field-visibility" <?php echo ! empty( $instance[ 'hide_adv_'. $key ] ) ? 'checked="checked"' : ''; ?> name="<?php echo esc_attr( $this->get_field_name( 'hide_adv_' . $key ) ); ?>">

									<i class="dashicons dashicons-visibility"></i>
								</span>
								
							</p>
						</li>
					<?php } ?>
					<?php endforeach ?>
				</ul>
			</div>
		</div>

		<script type="text/javascript">
			
			jQuery(document).ready(function($) {
				var self = $("body #filter-listing-<?php echo esc_attr($_id); ?>");

				$('.wp-cardealer-filter-listing-fields', self).each(function() {
					var el = $(this);

					el.sortable({
						update: function(event, ui) {
							var data = el.sortable('toArray', {
								attribute: 'data-field-id'
							});

							$('#<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>').attr('value', data);
						}
					});

					$(this).find('input[type=checkbox]').on('change', function() {
						if ($(this).is(':checked')) {
							$(this).closest('li').addClass('invisible');
						} else {
							$(this).closest('li').removeClass('invisible');
						}
					});
				});

				$('.wp-cardealer-advance-filter-fields', self).each(function() {
					var el = $(this);

					el.sortable({
						update: function(event, ui) {
							var data = el.sortable('toArray', {
								attribute: 'data-field-id'
							});

							$('#<?php echo esc_attr( $this->get_field_id( 'sort_adv' ) ); ?>').attr('value', data);
						}
					});

					$(this).find('input[type=checkbox]').on('change', function() {
						if ($(this).is(':checked')) {
							$(this).closest('li').addClass('invisible');
						} else {
							$(this).closest('li').removeClass('invisible');
						}
					});
				});
				$('.show_adv_fields', self).on('change', function() {
					if ($(this).is(':checked')) {
						$('.wp-cardealer-advance-filter-fields-wrapper', self).show();
					} else {
						$('.wp-cardealer-advance-filter-fields-wrapper', self).hide();
					}
				});
				if ( $('.show_adv_fields', self).is(':checked') ) {
					$('.wp-cardealer-advance-filter-fields-wrapper', self).show();
				} else {
					$('.wp-cardealer-advance-filter-fields-wrapper', self).hide();
				}
			});
		</script>

		<?php
	}
}
register_widget('WP_CarDealer_Widget_Listing_Filter');