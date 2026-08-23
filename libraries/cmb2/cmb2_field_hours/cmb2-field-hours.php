<?php

/**
 * Class WP_CarDealer_CMB2_Field_Hours
 */
class WP_CarDealer_CMB2_Field_Hours {

	/**
	 * Current version number
	 */
	const VERSION = '1.0.0';

	/**
	 * Initialize the plugin by hooking into CMB2
	 */
	public function __construct() {
		add_filter( 'cmb2_render_wpcd_hours', array( $this, 'render_hours' ), 10, 5 );
		add_filter( 'cmb2_sanitize_wpcd_hours', array( $this, 'sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_wpcd_hours', array( $this, 'escaped_value' ), 10, 3 );
	}

	/**
	 * Render select box field
	 */
	public function render_hours( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		
		global $wp_locale;
		$days = $this->get_days_of_week();
		?>
		<div class="hours-field group-fields">
			
			<div class="hours-field-timezone">
				<?php
				$timezones = timezone_identifiers_list();
				$default_timezone = date_default_timezone_get();

				$wp_timezone = get_option('timezone_string');
				$listing_timezone = isset($field_escaped_value) && isset($field_escaped_value['timezone']) && in_array( $field_escaped_value['timezone'], $timezones ) ? $field_escaped_value['timezone'] : false;

				$current_timezone = ( $listing_timezone ? $listing_timezone : ( $wp_timezone ? $wp_timezone : $default_timezone ) );
				?>
				<label><?php esc_html_e( 'Timezone', 'wp-cardealer' ) ?></label>
				<select name="<?php echo esc_attr($field_type_object->_name()); ?>[timezone]" placeholder="<?php esc_attr_e( 'Timezone', 'wp-cardealer' ) ?>">
					<?php echo $this->wp_timezone_choice($current_timezone); ?>
				</select>
			</div>
			<div class="hours-field-wrapper">
				
				<div class="list-hours">

					<?php $i = 0; foreach ( $days as $key => $day ) {
						$type = !empty($field_escaped_value['day'][esc_attr($day)]['type']) ? $field_escaped_value['day'][esc_attr($day)]['type'] : '';
					?>
						<div class="list">
						    
						    <div class="enter-hours-content">
						    	<div class="enter-hours-content-wrapper">
						    		<?php
						    		$form = !empty($field_escaped_value['day'][esc_attr($day)]['from']) && is_array($field_escaped_value['day'][esc_attr($day)]['from']) ? $field_escaped_value['day'][esc_attr($day)]['from'] : array();
						    		$to = !empty($field_escaped_value['day'][esc_attr($day)]['to']) && is_array($field_escaped_value['day'][esc_attr($day)]['to']) ? $field_escaped_value['day'][esc_attr($day)]['to'] : array();
						    		if ( !empty($form) ) {
						    			
					    				?>
					    				<div class="enter-hours-content-item ">
											<div class="group-field-item-content ">

						    					<div class="row d-xl-flex align-items-center">
						    						<div class="left-inner col-xl-1 col-4">
														<a data-toggle="tab" href="#hours-tab-<?php echo esc_attr($day); ?>"><?php echo trim($wp_locale->get_weekday( $day )); ?></a>
													</div>
									    			<div class="col-xl-6 col-8 enter-hours-wrapper">
									    				<?php
									    				foreach ($form as $key => $form_val) {
									    					$to_val = !empty($to[$key]) ? $to[$key] : '';
									    				?>
										    				<div class="enter-hours-item-inner">
										    					<div class="row">
												    				<div class="col-lg-6 col-6">
												    					<div class="wrapper-select">
													    				<select class="select-job-hour-from" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][from][]" placeholder="<?php esc_attr_e( 'From', 'wp-cardealer' ) ?>">
																			<option value=""><?php esc_html_e( 'From', 'wp-cardealer' ) ?></option>
																			<?php foreach (range(0, 86399, 900) as $time) {
																				$value = gmdate( 'H:i', $time);
																			?>
																				<option value="<?php echo esc_attr( $value ) ?>" <?php echo trim($value == $form_val ? 'selected="selected"' : ''); ?>><?php echo esc_html( gmdate( get_option( 'time_format' ), $time ) ) ?></option>
																			<?php }
																				$value = gmdate( 'H:i', 86399);
																			?>
																			<option value="<?php echo esc_attr( $value ) ?>" <?php echo trim($value == $form_val ? 'selected="selected"' : ''); ?>><?php echo esc_html( gmdate( get_option( 'time_format' ), 86399 ) ) ?></option>
																		</select>
													    				</div>
													    			</div>
												    				<div class="col-lg-6 col-6">
												    					<div class="wrapper-select">
													    				<select class="select-job-hour-to" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][to][]" placeholder="<?php esc_attr_e( 'To', 'wp-cardealer' ) ?>">
																			<option value=""><?php esc_html_e( 'To', 'wp-cardealer' ) ?></option>
																			<?php foreach (range(0, 86399, 900) as $time) {
																				$value = gmdate( 'H:i', $time);
																			?>
																				<option value="<?php echo esc_attr( $value ) ?>" <?php echo trim($value == $to_val ? 'selected="selected"' : ''); ?>><?php echo esc_html( gmdate( get_option( 'time_format' ), $time ) ) ?></option>
																			<?php }
																				$value = gmdate( 'H:i', 86399);
																			?>
																			<option value="<?php echo esc_attr( $value ) ?>" <?php echo trim($value == $to_val ? 'selected="selected"' : ''); ?>><?php echo esc_html( gmdate( get_option( 'time_format' ), 86399 ) ) ?></option>
																		</select>
												    					</div>
												    				</div>
												    			</div>
										    				</div>
										    			<?php } ?>
									    			</div>
								    			
									    			<div class="hours-last col-xl-5 col-12">
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="enter_hours" <?php echo trim(empty($type) || $type == 'enter_hours' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Enter Hours', 'wp-cardealer'); ?></label>
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="open_all_day" <?php echo trim($type == 'open_all_day' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Open All Days', 'wp-cardealer'); ?></label>
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="closed_all_day" <?php echo trim($type == 'closed_all_day' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Closed All Days', 'wp-cardealer'); ?></label>
												    </div>
											    </div>
							    			</div>
							    		</div>
					    				<?php
						    		} else {
						    		?>
							    		<div class="enter-hours-content-item">
											<div class="group-field-item-content">
								    			<div class="row d-xl-flex align-items-center">
								    				<div class="left-inner col-xl-1 col-4">
														<a data-toggle="tab" href="#hours-tab-<?php echo esc_attr($day); ?>"><?php echo trim($wp_locale->get_weekday( $day )); ?></a>
													</div>
									    			<div class="col-xl-6 col-8 enter-hours-wrapper">
									    				<div class="enter-hours-item-inner">
									    					<div class="row">
											    				<div class="col-lg-6 col-6 ">
											    					<div class="wrapper-select">
												    				<select class="select-job-hour-from" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][from][]" placeholder="<?php esc_attr_e( 'From', 'wp-cardealer' ) ?>">
																		<option value=""><?php esc_html_e( 'From', 'wp-cardealer' ) ?></option>
																		<?php foreach (range(0, 86399, 900) as $time) {
																			$value = gmdate( 'H:i', $time);
																		?>
																			<option value="<?php echo esc_attr( $value ) ?>"><?php echo esc_html( gmdate( get_option( 'time_format' ), $time ) ) ?></option>
																		<?php }
																			$value = gmdate( 'H:i', 86399);
																		?>
																		<option value="<?php echo esc_attr( $value ) ?>"><?php echo esc_html( gmdate( get_option( 'time_format' ), '86399' ) ) ?></option>
																	</select>
																	</div>
																</div>
											    				<div class="col-lg-6 col-6">
											    					<div class="wrapper-select">
												    				<select class="select-job-hour-to" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][to][]" placeholder="<?php esc_attr_e( 'To', 'wp-cardealer' ) ?>">
																		<option value=""><?php esc_html_e( 'To', 'wp-cardealer' ) ?></option>
																		<?php foreach (range(0, 86399, 900) as $time) {
																			$value = gmdate( 'H:i', $time);

																		?>
																			<option value="<?php echo esc_attr( $value ) ?>"><?php echo esc_html( gmdate( get_option( 'time_format' ), $time ) ) ?></option>
																		<?php }

																			$value = gmdate( 'H:i', 86399);
																		?>
																		<option value="<?php echo esc_attr( $value ) ?>"><?php echo esc_html( gmdate( get_option( 'time_format' ), '86399' ) ) ?></option>
																	</select>
																	</div>
																</div>
															</div>
														</div>
									    			</div>
									    			<div class="hours-last col-xl-5 col-12">
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="enter_hours" <?php echo trim(empty($type) || $type == 'enter_hours' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Enter Hours', 'wp-cardealer'); ?></label>
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="open_all_day" <?php echo trim($type == 'open_all_day' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Open All Days', 'wp-cardealer'); ?></label>
												    	<label><input type="radio" name="<?php echo esc_attr($field_type_object->_name()); ?>[day][<?php echo esc_attr($day); ?>][type]" value="closed_all_day" <?php echo trim($type == 'closed_all_day' ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Closed All Days', 'wp-cardealer'); ?></label>
												    </div>
								    			</div>
							    			</div>
							    		</div>
							    	<?php } ?>
						    	</div>
						    	<div class="row">
						    		<div class="left-inner col-xl-1 d-none d-xl-block">
						    		</div>
						    		<div class="right-inner col-xl-11 col-12">
						    			<div class="bottom-action-hour">
									    	<a class="add-new-hour btn-action button text-success" href="javascript:void(0);"><?php esc_html_e( 'Add New', 'wp-cardealer' ); ?></a>
											<a class="remove-hour btn-action button text-danger" href="javascript:void(0);"><?php esc_html_e( 'Remove', 'wp-cardealer' ); ?></a>
										</div>
						    		</div>
								</div>
						    </div>
					  	</div>
					<?php $i++; } ?>
				</div>
			</div>
		</div>

		<?php

		if ( !empty($a['desc']) ) {
			echo $a['desc'];
		}
	}

	public function wp_timezone_choice( $selected_zone ) {
		static $mo_loaded = false, $locale_loaded = null;

		$continents = array( 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific' );

		$zonen = array();
		foreach ( timezone_identifiers_list() as $zone ) {
			$zone = explode( '/', $zone );
			if ( ! in_array( $zone[0], $continents, true ) ) {
				continue;
			}

			// This determines what gets set and translated - we don't translate Etc/* strings here, they are done later.
			$exists    = array(
				0 => ( isset( $zone[0] ) && $zone[0] ),
				1 => ( isset( $zone[1] ) && $zone[1] ),
				2 => ( isset( $zone[2] ) && $zone[2] ),
			);
			$exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
			$exists[4] = ( $exists[1] && $exists[3] );
			$exists[5] = ( $exists[2] && $exists[3] );

			// phpcs:disable WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			$zonen[] = array(
				'continent'   => ( $exists[0] ? $zone[0] : '' ),
				'city'        => ( $exists[1] ? $zone[1] : '' ),
				'subcity'     => ( $exists[2] ? $zone[2] : '' ),
				't_continent' => ( $exists[3] ? translate( str_replace( '_', ' ', $zone[0] ), 'continents-cities' ) : '' ),
				't_city'      => ( $exists[4] ? translate( str_replace( '_', ' ', $zone[1] ), 'continents-cities' ) : '' ),
				't_subcity'   => ( $exists[5] ? translate( str_replace( '_', ' ', $zone[2] ), 'continents-cities' ) : '' ),
			);
			// phpcs:enable
		}
		usort( $zonen, '_wp_timezone_choice_usort_callback' );

		$structure = array();

		if ( empty( $selected_zone ) ) {
			$structure[] = '<option selected="selected" value="">' . __( 'Select a city', 'wp-cardealer' ) . '</option>';
		}

		foreach ( $zonen as $key => $zone ) {
			// Build value in an array to join later.
			$value = array( $zone['continent'] );

			if ( empty( $zone['city'] ) ) {
				// It's at the continent level (generally won't happen).
				$display = $zone['t_continent'];
			} else {
				// It's inside a continent group.

				// Continent optgroup.
				if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
					$label       = $zone['t_continent'];
					$structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
				}

				// Add the city to the value.
				$value[] = $zone['city'];

				$display = $zone['t_city'];
				if ( ! empty( $zone['subcity'] ) ) {
					// Add the subcity to the value.
					$value[]  = $zone['subcity'];
					$display .= ' - ' . $zone['t_subcity'];
				}
			}

			// Build the value.
			$value    = implode( '/', $value );
			$selected = '';
			if ( $value === $selected_zone ) {
				$selected = 'selected="selected" ';
			}
			$structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';

			// Close continent optgroup.
			if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
				$structure[] = '</optgroup>';
			}
		}

		// Do UTC.
		$structure[] = '<optgroup label="' . esc_attr__( 'UTC', 'wp-cardealer' ) . '">';
		$selected    = '';
		if ( 'UTC' === $selected_zone ) {
			$selected = 'selected="selected" ';
		}
		$structure[] = '<option ' . $selected . 'value="' . esc_attr( 'UTC' ) . '">' . __( 'UTC', 'wp-cardealer' ) . '</option>';
		$structure[] = '</optgroup>';

		return implode( "\n", $structure );
	}

	public function get_days_of_week() {
		$days = array(0, 1, 2, 3, 4, 5, 6);

		$start_day = get_option( 'start_of_week' );

		$first = array_splice( $days, $start_day, count( $days ) - $start_day );

		$second = array_splice( $days, 0, $start_day );

		$days = array_merge( $first, $second );

		return $days;
	}

	/**
	 * Handle sanitization for repeatable fields
	 */
	public function sanitize( $check, $meta_value, $object_id, $field_args ) {
		return $meta_value;
	}


	/**
	 * Handle escaping for repeatable fields
	 */
	public function escaped_value( $check, $meta_value, $field_args ) {
		return $meta_value;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function setup_admin_scripts() {
		$asset_path = apply_filters( 'wpcd_cmb2_field_hours_asset_path', plugins_url( '', __FILE__  ) );

		wp_enqueue_script( 'wpcd-hours-script', $asset_path . '/js/script.js', array( 'cmb2-scripts', 'wpcd-select2', 'jquery-ui-sortable' ), self::VERSION );
		if ( is_admin() ) {
			wp_enqueue_style( 'wpcd-hours-style', $asset_path . '/css/style.css', array( 'wpcd-select2' ), self::VERSION );
		}
	}

}
$wpcd_cmb2_field_select2 = new WP_CarDealer_CMB2_Field_Hours();
