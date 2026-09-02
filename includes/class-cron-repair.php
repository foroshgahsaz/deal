<?php
/**
 * Repairs the WordPress cron option for hooks owned by this plugin.
 *
 * Earlier releases of the USD to Toman module scheduled its refresh event from
 * frontend requests. On busy sites that can leave a large number of stale
 * entries behind in the `cron` option, which WordPress loads and unserializes
 * on every single request. This runs once in wp-admin and cleans them out.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Cron_Repair {

	const REPAIR_VERSION  = 1;
	const OPTION_VERSION  = 'wp_cardealer_cron_repair_version';
	const OPTION_REPORT   = 'wp_cardealer_cron_repair_report';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_repair' ) );
	}

	/**
	 * Hooks this plugin is allowed to prune. Other plugins are never touched.
	 *
	 * @return array
	 */
	public static function get_managed_hooks() {
		return apply_filters(
			'wp_cardealer_cron_managed_hooks',
			array( 'wp_cardealer_navasan_refresh_rate' )
		);
	}

	/**
	 * Remove every scheduled entry belonging to the given hooks.
	 *
	 * Pure function: no WordPress calls, so the behaviour is unit testable.
	 *
	 * @param array $cron  Cron array as returned by _get_cron_array().
	 * @param array $hooks Hook names to remove.
	 * @return array{cron: array, removed_events: int, removed_timestamps: int, remaining_events: int}
	 */
	public static function prune_cron_array( $cron, $hooks ) {
		$pruned             = array();
		$removed_events     = 0;
		$removed_timestamps = 0;
		$remaining_events   = 0;

		if ( ! is_array( $cron ) ) {
			return array(
				'cron'               => array(),
				'removed_events'     => 0,
				'removed_timestamps' => 0,
				'remaining_events'   => 0,
			);
		}

		foreach ( $cron as $timestamp => $hook_list ) {
			if ( ! is_array( $hook_list ) ) {
				$pruned[ $timestamp ] = $hook_list;
				continue;
			}

			$kept = array();

			foreach ( $hook_list as $hook => $events ) {
				$event_count = is_array( $events ) ? count( $events ) : 1;

				if ( in_array( $hook, $hooks, true ) ) {
					$removed_events += $event_count;
					continue;
				}

				$kept[ $hook ]     = $events;
				$remaining_events += $event_count;
			}

			if ( empty( $kept ) ) {
				$removed_timestamps++;
				continue;
			}

			$pruned[ $timestamp ] = $kept;
		}

		return array(
			'cron'               => $pruned,
			'removed_events'     => $removed_events,
			'removed_timestamps' => $removed_timestamps,
			'remaining_events'   => $remaining_events,
		);
	}

	/**
	 * Stored byte size of the cron option, which is what WordPress unserializes
	 * on every request.
	 *
	 * @return int
	 */
	public static function get_option_bytes() {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return 0;
		}

		$bytes = $wpdb->get_var( "SELECT LENGTH(option_value) FROM {$wpdb->options} WHERE option_name = 'cron' LIMIT 1" );

		return is_numeric( $bytes ) ? (int) $bytes : 0;
	}

	/**
	 * @return void
	 */
	public static function maybe_repair() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( (int) get_option( self::OPTION_VERSION, 0 ) >= self::REPAIR_VERSION ) {
			return;
		}

		self::repair();
	}

	/**
	 * @return array
	 */
	public static function repair() {
		$before = self::get_option_bytes();
		$result = self::prune_cron_array( _get_cron_array(), self::get_managed_hooks() );

		if ( $result['removed_events'] > 0 ) {
			_set_cron_array( $result['cron'] );
		}

		$report = array(
			'ran_at'             => current_time( 'mysql' ),
			'bytes_before'       => $before,
			'bytes_after'        => self::get_option_bytes(),
			'removed_events'     => $result['removed_events'],
			'removed_timestamps' => $result['removed_timestamps'],
			'remaining_events'   => $result['remaining_events'],
		);

		update_option( self::OPTION_REPORT, $report, false );
		update_option( self::OPTION_VERSION, self::REPAIR_VERSION, true );

		return $report;
	}

	/**
	 * @return array
	 */
	public static function get_last_report() {
		$report = get_option( self::OPTION_REPORT, array() );

		return is_array( $report ) ? $report : array();
	}
}

WP_CarDealer_Cron_Repair::init();
