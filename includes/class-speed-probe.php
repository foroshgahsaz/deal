<?php
/**
 * One click frontend speed measurement for site owners.
 *
 * Requests the site's own homepage with profiling turned on, reads the report
 * the profiler leaves in the markup, and presents it in wp-admin. This exists
 * so diagnosing a slow page never requires reading page source by hand.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Speed_Probe {

	const NONCE_ACTION = 'wp_cardealer_speed_probe';
	const TIMEOUT      = 120;

	public static function init() {
		add_action( 'wp_ajax_wp_cardealer_speed_probe', array( __CLASS__, 'ajax_probe' ) );
	}

	/**
	 * Extract the profiler report from a rendered page.
	 *
	 * Pure function so the parsing rules are unit testable.
	 *
	 * @param string $html
	 * @return string Empty string when the page carries no report.
	 */
	public static function extract_report( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return '';
		}

		if ( ! preg_match( '/<!--\s*wpcd-profile\s(.*?)-->/s', $html, $matches ) ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', $matches[1] ) );
	}

	/**
	 * Turn a report into labelled metrics for display.
	 *
	 * @param string $report
	 * @return array<string, string>
	 */
	public static function parse_metrics( $report ) {
		$metrics = array();

		if ( ! is_string( $report ) || '' === trim( $report ) ) {
			return $metrics;
		}

		foreach ( explode( '|', $report ) as $section ) {
			$section = trim( $section );
			if ( '' === $section ) {
				continue;
			}

			if ( preg_match_all( '/([a-z_]+)=([^\s]+)/i', $section, $pairs, PREG_SET_ORDER ) ) {
				$label = preg_split( '/\s+/', $section );
				$label = ( isset( $label[0] ) && strpos( $label[0], '=' ) === false ) ? $label[0] : '';

				foreach ( $pairs as $pair ) {
					$key             = '' === $label ? $pair[1] : $label . '.' . $pair[1];
					$metrics[ $key ] = $pair[2];
				}
			}
		}

		return $metrics;
	}

	/**
	 * Plain language verdict so the owner does not have to interpret numbers.
	 *
	 * @param array $metrics
	 * @return string
	 */
	public static function summarise( $metrics ) {
		if ( empty( $metrics ) ) {
			return 'گزارشی دریافت نشد. ممکن است افزونهٔ کش، صفحهٔ ذخیره‌شده را برگردانده باشد.';
		}

		$total       = isset( $metrics['total'] ) ? (float) str_replace( array( 'ms', ',' ), '', $metrics['total'] ) : 0;
		$cache_reads = isset( $metrics['cache_reads'] ) ? (int) str_replace( ',', '', $metrics['cache_reads'] ) : 0;
		$price_calls = isset( $metrics['format_price.calls'] ) ? (int) str_replace( ',', '', $metrics['format_price.calls'] ) : 0;

		if ( $total < 3000 ) {
			return sprintf( 'صفحهٔ اول در %s میلی‌ثانیه ساخته شد. سرعت سالم است.', number_format_i18n( $total ) );
		}

		if ( $cache_reads > 500000 && $price_calls < 5000 ) {
			return sprintf(
				'صفحه %s میلی‌ثانیه طول کشید و %s بار از حافظهٔ موقت خواند، ولی قیمت‌ها فقط %s بار ساخته شدند. پس کندی از ماژول قیمت نیست و از قالب یا صفحه‌ساز می‌آید.',
				number_format_i18n( $total ),
				number_format_i18n( $cache_reads ),
				number_format_i18n( $price_calls )
			);
		}

		if ( $price_calls >= 5000 ) {
			return sprintf(
				'صفحه %s میلی‌ثانیه طول کشید و قیمت‌ها %s بار ساخته شدند. این تعداد بسیار زیاد است و از تعداد ویجت‌های صفحهٔ اول می‌آید.',
				number_format_i18n( $total ),
				number_format_i18n( $price_calls )
			);
		}

		return sprintf( 'صفحه %s میلی‌ثانیه طول کشید. جزئیات زیر را بفرستید تا بررسی شود.', number_format_i18n( $total ) );
	}

	/**
	 * @return void
	 */
	public static function ajax_probe() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'اجازهٔ این کار را ندارید.' ) );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$url = add_query_arg(
			array(
				'wpcd_profile' => 'hooks',
				'wpcd_nocache' => time(),
			),
			home_url( '/' )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => self::TIMEOUT,
				'sslverify' => false,
				'headers'   => array( 'Cache-Control' => 'no-cache' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'اندازه‌گیری ناموفق بود: ' . $response->get_error_message() ) );
		}

		$report  = self::extract_report( wp_remote_retrieve_body( $response ) );
		$metrics = self::parse_metrics( $report );

		wp_send_json_success(
			array(
				'message' => self::summarise( $metrics ),
				'report'  => $report,
			)
		);
	}
}

WP_CarDealer_Speed_Probe::init();
