<?php
/**
 * Listing body damage (paint / replacement) map.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Listing_Body_Damage {

	const META_KEY = '_listing_body_damage';
	const DIAGRAM_QUERY_VAR = 'wpcd_listing_body_diagram';

	const STATUS_PAINTED  = 'painted';
	const STATUS_REPLACED   = 'replaced';

	const COLOR_PAINTED  = '#2563eb';
	const COLOR_REPLACED = '#8B5E3C';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_output_diagram_svg' ) );
	}

	/**
	 * @return array slug => Persian label
	 */
	public static function get_parts() {
		return array(
			'front_bumper'       => 'سپر جلو',
			'hood'               => 'کاپوت',
			'roof'               => 'سقف',
			'trunk'              => 'درب صندوق',
			'rear_bumper'        => 'سپر عقب',
			'left_front_fender'  => 'گلگیر جلو چپ',
			'right_front_fender' => 'گلگیر جلو راست',
			'left_front_door'    => 'درب جلو چپ',
			'right_front_door'   => 'درب جلو راست',
			'left_quarter_panel' => 'گلگیر عقب چپ',
			'right_quarter_panel'=> 'گلگیر عقب راست',
		);
	}

	/**
	 * @return array
	 */
	public static function get_status_options() {
		return array(
			''                     => 'سالم / بدون رنگ و تعویض',
			self::STATUS_PAINTED   => 'رنگ‌شدگی',
			self::STATUS_REPLACED  => 'تعویض',
		);
	}

	/**
	 * @return array
	 */
	public static function get_status_labels() {
		return array(
			self::STATUS_PAINTED  => 'رنگ‌شده',
			self::STATUS_REPLACED => 'تعویض‌شده',
		);
	}

	/**
	 * @param int $post_id
	 * @return array part_slug => status
	 */
	public static function get_damage_map( $post_id ) {
		$post_id = absint( $post_id );
		$parts   = self::get_parts();
		$empty   = array_fill_keys( array_keys( $parts ), '' );

		if ( ! $post_id ) {
			return $empty;
		}

		$stored = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_string( $stored ) && $stored !== '' ) {
			$decoded = json_decode( $stored, true );
			if ( is_array( $decoded ) ) {
				$stored = $decoded;
			}
		}

		if ( ! is_array( $stored ) ) {
			return $empty;
		}

		foreach ( $parts as $slug => $label ) {
			if ( isset( $stored[ $slug ] ) ) {
				$empty[ $slug ] = self::sanitize_status( $stored[ $slug ] );
			}
		}

		return $empty;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public static function sanitize_status( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( $value === self::STATUS_PAINTED || $value === self::STATUS_REPLACED ) {
			return $value;
		}
		return '';
	}

	/**
	 * @param mixed $value
	 * @return array
	 */
	public static function sanitize_damage_map( $value ) {
		$parts  = self::get_parts();
		$result = array_fill_keys( array_keys( $parts ), '' );

		if ( ! is_array( $value ) ) {
			return $result;
		}

		foreach ( $parts as $slug => $label ) {
			if ( isset( $value[ $slug ] ) ) {
				$result[ $slug ] = self::sanitize_status( $value[ $slug ] );
			}
		}

		return $result;
	}

	/**
	 * @param array $map
	 * @return string JSON
	 */
	public static function encode_damage_map( $map ) {
		return wp_json_encode( self::sanitize_damage_map( $map ) );
	}

	/**
	 * @param array $map
	 * @return array
	 */
	public static function get_marked_parts( $map ) {
		$marked = array();
		$labels = self::get_parts();
		$status = self::get_status_labels();

		foreach ( self::sanitize_damage_map( $map ) as $slug => $value ) {
			if ( $value === '' ) {
				continue;
			}
			$marked[] = array(
				'slug'         => $slug,
				'label'        => isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug,
				'status'       => $value,
				'status_label' => isset( $status[ $value ] ) ? $status[ $value ] : $value,
			);
		}

		return $marked;
	}

	/**
	 * @param int $post_id
	 * @return bool
	 */
	public static function has_damage( $post_id ) {
		foreach ( self::get_damage_map( $post_id ) as $status ) {
			if ( $status !== '' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $map
	 * @return string
	 */
	public static function get_diagram_svg( $map ) {
		$path = WP_CARDEALER_PLUGIN_DIR . 'assets/svg/car-body-diagram.svg';
		if ( ! file_exists( $path ) ) {
			return '';
		}

		$svg = file_get_contents( $path );
		if ( $svg === false || $svg === '' ) {
			return '';
		}

		$map = self::sanitize_damage_map( $map );
		foreach ( $map as $part => $status ) {
			if ( $status === '' ) {
				continue;
			}
			$class = $status === self::STATUS_PAINTED ? 'is-painted' : 'is-replaced';
			$quoted = preg_quote( $part, '/' );
			$patterns = array(
				'/(<(?:path|circle)\b[^>]*\bclass=")([^"]*)("[^>]*\bdata-part="' . $quoted . '")/',
				'/(<(?:path|circle)\b[^>]*\bdata-part="' . $quoted . '"[^>]*\bclass=")([^"]*)(")/',
			);
			foreach ( $patterns as $pattern ) {
				$svg = preg_replace( $pattern, '$1$2 ' . $class . '$3', $svg );
			}
		}

		return trim( $svg );
	}

	/**
	 * @param int $post_id
	 * @return string
	 */
	public static function get_diagram_url( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return '';
		}

		return add_query_arg( self::DIAGRAM_QUERY_VAR, $post_id, home_url( '/' ) );
	}

	/**
	 * Serve the listing body diagram SVG when requested directly.
	 */
	public static function maybe_output_diagram_svg() {
		if ( empty( $_GET[ self::DIAGRAM_QUERY_VAR ] ) ) {
			return;
		}

		$post_id = absint( wp_unslash( $_GET[ self::DIAGRAM_QUERY_VAR ] ) );
		if ( ! $post_id || get_post_type( $post_id ) !== 'listing' ) {
			status_header( 404 );
			exit;
		}

		$svg = self::get_diagram_svg( self::get_damage_map( $post_id ) );
		if ( $svg === '' ) {
			status_header( 404 );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: image/svg+xml; charset=utf-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG asset output
		echo $svg;
		exit;
	}

	/**
	 * Render the diagram as an HTML img (Elementor-safe) with Persian view labels.
	 *
	 * @param array $map
	 * @param int   $post_id
	 * @return string
	 */
	public static function get_diagram_markup( $map, $post_id = 0 ) {
		$post_id = absint( $post_id );
		$svg     = self::get_diagram_svg( $map );
		if ( $svg === '' || ! $post_id ) {
			return '';
		}

		$src = self::get_diagram_url( $post_id );
		if ( $src === '' ) {
			return '';
		}

		ob_start();
		?>
		<div class="listing-body-damage-diagram-inner" dir="ltr">
			<div class="listing-body-damage-view-labels" aria-hidden="true">
				<span class="listing-body-damage-view-label"><?php esc_html_e( 'سمت چپ', 'wp-cardealer' ); ?></span>
				<span class="listing-body-damage-view-label"><?php esc_html_e( 'نمای بالا', 'wp-cardealer' ); ?></span>
				<span class="listing-body-damage-view-label"><?php esc_html_e( 'سمت راست', 'wp-cardealer' ); ?></span>
			</div>
			<img
				class="listing-body-damage-svg-img"
				src="<?php echo esc_url( $src ); ?>"
				alt="<?php echo esc_attr__( 'نقشه وضعیت بدنه', 'wp-cardealer' ); ?>"
				width="570"
				height="620"
				decoding="async"
				loading="lazy"
			/>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param int $post_id
	 * @return string
	 */
	public static function get_html( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return '';
		}

		$map     = self::get_damage_map( $post_id );
		$marked  = self::get_marked_parts( $map );
		$diagram = self::get_diagram_markup( $map, $post_id );

		if ( $diagram === '' ) {
			return '';
		}

		ob_start();
		?>
		<div class="listing-body-damage">
			<div class="listing-body-damage-diagram"><?php echo $diagram; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped img + labels ?></div>
			<div class="listing-body-damage-legend">
				<span class="listing-body-damage-legend-item listing-body-damage-legend-item--painted"><?php esc_html_e( 'رنگ‌شده', 'wp-cardealer' ); ?></span>
				<span class="listing-body-damage-legend-item listing-body-damage-legend-item--replaced"><?php esc_html_e( 'تعویض‌شده', 'wp-cardealer' ); ?></span>
			</div>
			<?php if ( empty( $marked ) ) : ?>
				<p class="listing-body-damage-empty"><?php esc_html_e( 'بدون رنگ‌شدگی و تعویض — بدنه سالم', 'wp-cardealer' ); ?></p>
			<?php else : ?>
				<div class="listing-body-damage-list">
					<div class="listing-body-damage-list-title">
						<?php
						printf(
							/* translators: %d: number of marked parts */
							esc_html__( 'قطعه‌های علامت‌خورده (%d)', 'wp-cardealer' ),
							count( $marked )
						);
						?>
					</div>
					<ul class="listing-body-damage-tags">
						<?php foreach ( $marked as $item ) : ?>
							<li class="listing-body-damage-tag listing-body-damage-tag--<?php echo esc_attr( $item['status'] ); ?>">
								<span class="listing-body-damage-tag-dot" aria-hidden="true"></span>
								<span class="listing-body-damage-tag-text">
									<?php echo esc_html( $item['label'] . ' · ' . $item['status_label'] ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

WP_CarDealer_Listing_Body_Damage::init();
