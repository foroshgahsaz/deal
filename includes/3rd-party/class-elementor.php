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
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Listing_Price_Html() );
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Listing_Fees_Html() );
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Customs_Fee() );
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Shipping_Fee() );
		$dynamic_tags->register( new WP_CarDealer_Elementor_Tag_Body_Damage() );
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

class WP_CarDealer_Elementor_Tag_Listing_Price_Html extends WP_CarDealer_Elementor_Tag_Listing_Base {

	public function get_name() {
		return 'wp-cardealer-listing-price-html';
	}

	public function get_title() {
		return 'قیمت آگهی (با هزینه‌ها)';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$html = WP_CarDealer_Listing::get_price_html( $post_id );
		if ( ! $html ) {
			return;
		}

		echo wp_kses_post( $html );
	}
}

class WP_CarDealer_Elementor_Tag_Listing_Fees_Html extends WP_CarDealer_Elementor_Tag_Listing_Base {

	public function get_name() {
		return 'wp-cardealer-listing-fees-html';
	}

	public function get_title() {
		return 'هزینه گمرک و حمل‌ونقل';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$html = WP_CarDealer_Listing::get_fees_html( $post_id );
		if ( ! $html ) {
			return;
		}

		echo wp_kses_post( $html );
	}
}

abstract class WP_CarDealer_Elementor_Tag_Listing_Fee extends WP_CarDealer_Elementor_Tag_Listing_Base {

	abstract protected function get_fee_suffix();

	abstract public function get_title();

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {
		$this->add_control(
			'plain_text',
			array(
				'label'        => 'متن ساده (بدون HTML)',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);
	}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Price' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$suffix = $this->get_fee_suffix();
		$plain  = $this->get_settings( 'plain_text' ) === 'yes';

		if ( $plain ) {
			echo esc_html( WP_CarDealer_Price::get_listing_fee_plain( $post_id, $suffix, true ) );
			return;
		}

		echo wp_kses_post( WP_CarDealer_Price::get_listing_fee_formatted( $post_id, $suffix, true ) );
	}
}

class WP_CarDealer_Elementor_Tag_Customs_Fee extends WP_CarDealer_Elementor_Tag_Listing_Fee {

	public function get_name() {
		return 'wp-cardealer-listing-customs-fee';
	}

	public function get_title() {
		return 'هزینه گمرک (دلار → تومان)';
	}

	protected function get_fee_suffix() {
		return 'customs_fee';
	}
}

class WP_CarDealer_Elementor_Tag_Shipping_Fee extends WP_CarDealer_Elementor_Tag_Listing_Fee {

	public function get_name() {
		return 'wp-cardealer-listing-shipping-fee';
	}

	public function get_title() {
		return 'هزینه حمل‌ونقل (دلار → تومان)';
	}

	protected function get_fee_suffix() {
		return 'shipping_fee';
	}
}

class WP_CarDealer_Elementor_Tag_Body_Damage extends WP_CarDealer_Elementor_Tag_Listing_Base {

	public function get_name() {
		return 'wp-cardealer-listing-body-damage';
	}

	public function get_title() {
		return 'رنگ‌شدگی و تعویض بدنه';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {}

	public function render() {
		if ( ! class_exists( 'WP_CarDealer_Listing' ) ) {
			return;
		}

		$post_id = $this->get_listing_post_id();
		if ( ! $post_id ) {
			return;
		}

		$html = WP_CarDealer_Listing::get_body_damage_html( $post_id );
		if ( ! $html ) {
			return;
		}

		echo wp_kses_post( $html );
	}
}

WP_CarDealer_Elementor::init();
