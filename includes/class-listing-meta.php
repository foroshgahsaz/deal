<?php
/**
 * Listing Meta
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Listing_Meta {

	private static $_instance = null;
	private $metas = null;
	private $post_id = null;

	public static function get_instance($post_id) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self($post_id);
		} else {
			self::$_instance->post_id = $post_id;
		}
		return self::$_instance;
	}

	public function __construct($post_id) {
		$this->post_id = $post_id;
		$this->metas = $this->get_post_metas();
	}

	public function get_post_metas() {
		$return = array();
		$fields = WP_CarDealer_Custom_Fields::get_all_custom_fields(array(), false, WP_CARDEALER_LISTING_PREFIX);
		if ( !empty($fields) ) {
			foreach ($fields as $field) {
				if ( !empty($field['id']) ) {
					$return[$field['id']] = $field;
				}
			}
		}
		return apply_filters('wp-cardealer-get-listing-metas', $return);
	}

	public function get_metas() {
		return $this->metas;
	}

	public function check_post_meta_exist($key) {
		if ( isset($this->metas[WP_CARDEALER_LISTING_PREFIX.$key]) ) {
			return true;
		}
		return false;
	}

	public function check_custom_post_meta_exist($key) {
		if ( isset($this->metas[$key]) ) {
			return true;
		}
		return false;
	}
	
	public function get_post_meta($key) {
		return get_post_meta($this->post_id, WP_CARDEALER_LISTING_PREFIX.$key, true);
	}

	public function get_custom_post_meta($key) {
		return get_post_meta($this->post_id, $key, true);
	}

	public function get_post_meta_title($key) {
		if ( !empty($this->metas[WP_CARDEALER_LISTING_PREFIX.$key]) && isset($this->metas[WP_CARDEALER_LISTING_PREFIX.$key]['name'])) {
			return $this->metas[WP_CARDEALER_LISTING_PREFIX.$key]['name'];
		}
		return '';
	}

	public function get_custom_post_meta_title($key) {
		if ( !empty($this->metas[$key]) && isset($this->metas[$key]['name'])) {
			return $this->metas[$key]['name'];
		}
		return '';
	}
	
	public function get_custom_meta_field($key) {
		if ( !empty($this->metas[$key]) ) {
			return $this->metas[$key];
		}
		return '';
	}
	
	public function get_price_html() {
		if ( ! WP_CarDealer_Profiler::is_enabled() ) {
			return $this->render_price_html();
		}

		WP_CarDealer_Profiler::start( 'get_price_html' );
		$rendered = $this->render_price_html();
		WP_CarDealer_Profiler::stop( 'get_price_html' );

		return $rendered;
	}

	/**
	 * @return bool|string
	 */
	private function render_price_html() {
		$price_html = '';
		$price_custom = $this->get_post_meta( 'price_custom' );

		if ( $price_custom ) {
			$price_html = $price_custom;
		} else {
			$price = class_exists( 'WP_CarDealer_Navasan' )
				? WP_CarDealer_Navasan::get_stored_usd_price( $this->post_id )
				: $this->get_post_meta( 'price' );

			if ( ! empty( $price ) && is_numeric( $price ) ) {
				$formatted = WP_CarDealer_Price::format_price( $price );
				if ( $formatted ) {
					$price_html = $formatted;
					$price_prefix = $this->get_post_meta( 'price_prefix' );
					$price_suffix = $this->get_post_meta( 'price_suffix' );
					if ( $price_prefix ) {
						$price_html = '<span class="prefix-text additional-text">' . $price_prefix . '</span>' . $price_html;
					}
					if ( $price_suffix ) {
						$price_html = $price_html . '<span class="suffix-text additional-text">' . $price_suffix . '</span>';
					}
				}
			}
		}

		if ( class_exists( 'WP_CarDealer_Price' ) ) {
			$fees_html = WP_CarDealer_Price::get_listing_fees_html( $this->post_id );
			if ( $fees_html ) {
				$price_html .= $fees_html;
			}
		}

		if ( $price_html === '' ) {
			return false;
		}

		$price_html = '<div class="listing-price-stack">' . $price_html . '</div>';

		return apply_filters( 'wp-cardealer-get-price-html', $price_html, $this->post_id, $this );
	}
}
