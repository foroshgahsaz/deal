<?php
/**
 * Elementor Pro dynamic tags for listing location.
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
}

class WP_CarDealer_Elementor_Tag_Location_Leaf extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'wp-cardealer-listing-location-leaf';
	}

	public function get_title() {
		return 'موقعیت آگهی (نام)';
	}

	public function get_group() {
		return 'wp-cardealer';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing_Location' ) ) {
			return;
		}

		echo esc_html( WP_CarDealer_Listing_Location::get_leaf_name( get_the_ID() ) );
	}
}

class WP_CarDealer_Elementor_Tag_Location_Path extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'wp-cardealer-listing-location-path';
	}

	public function get_title() {
		return 'موقعیت آگهی (مسیر کامل)';
	}

	public function get_group() {
		return 'wp-cardealer';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
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

		$separator = $this->get_settings( 'separator' );
		if ( $separator === null || $separator === '' ) {
			$separator = ' › ';
		}

		echo esc_html( WP_CarDealer_Listing_Location::get_path_text( get_the_ID(), $separator ) );
	}
}

WP_CarDealer_Elementor::init();
