<?php
/**
 * Elementor Pro dynamic tags for WP CarDealer listings.
 *
 * @package wp-cardealer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_CarDealer_Elementor {

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'bootstrap' ), 20 );
	}

	public static function bootstrap() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_dynamic_tags' ) );
	}

	public static function register_dynamic_tags( $dynamic_tags ) {
		if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
			return;
		}

		$dynamic_tags->register_group(
			'wp-cardealer',
			array(
				'title' => 'WP CarDealer',
			)
		);

		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Location_Leaf() );
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Location_Path() );
	}

	/**
	 * @return int
	 */
	public static function get_listing_post_id() {
		$post_id = get_the_ID();
		if ( $post_id && get_post_type( $post_id ) === 'listing' ) {
			return (int) $post_id;
		}

		$queried = get_queried_object();
		if ( $queried instanceof WP_Post && $queried->post_type === 'listing' ) {
			return (int) $queried->ID;
		}

		return 0;
	}
}

abstract class WP_CarDealer_Elementor_Tag_Listing_Base extends \Elementor\Core\DynamicTags\Tag {

	public function get_group() {
		return 'wp-cardealer';
	}

	protected function get_listing_post_id() {
		return WP_CarDealer_Elementor::get_listing_post_id();
	}
}

class WP_CarDealer_Elementor_Tag_Location_Leaf extends WP_CarDealer_Elementor_Tag_Listing_Base {

	public function get_name() {
		return 'wp-cardealer-listing-location-leaf';
	}

	public function get_title() {
		return 'موقعیت آگهی (نام)';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	public function get_content_type() {
		return 'html';
	}

	protected function register_controls() {}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing_Location' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$html = WP_CarDealer_Listing_Location::get_leaf_link_html( $post_id );
		if ( ! $html ) {
			return;
		}

		echo wp_kses_post( $html );
	}
}

class WP_CarDealer_Elementor_Tag_Location_Path extends WP_CarDealer_Elementor_Tag_Listing_Base {

	public function get_name() {
		return 'wp-cardealer-listing-location-path';
	}

	public function get_title() {
		return 'موقعیت آگهی (مسیر کامل)';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	public function get_content_type() {
		return 'html';
	}

	protected function register_controls() {
		$this->add_control(
			'separator',
			array(
				'label'   => 'جداکننده',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => ' › ',
			)
		);
	}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing_Location' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$separator = $this->get_settings( 'separator' );
		if ( $separator === null || $separator === '' ) {
			$separator = ' › ';
		}

		$html = WP_CarDealer_Listing_Location::get_path_html( $post_id, $separator );
		if ( ! $html ) {
			return;
		}

		echo wp_kses_post( $html );
	}
}

WP_CarDealer_Elementor::init();
