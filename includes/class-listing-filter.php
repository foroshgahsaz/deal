<?php
/**
 * Listing Filter
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Listing_Filter extends WP_CarDealer_Abstract_Filter {
	
	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'archive' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'taxonomy' ) );

		add_filter( 'wp-cardealer-listing-filter-query', array( __CLASS__, 'filter_query_listing' ), 10, 2 );
  		add_filter( 'wp-cardealer-listing-query-args', array( __CLASS__, 'filter_query_args_listing' ), 10, 2 );
	}

	public static function get_fields() {
		return apply_filters( 'wp-cardealer-default-listing-filter-fields', array(
			'center-location' => array(
				'name' => __( 'Location', 'wp-cardealer' ),
				'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input_location'),
				'placeholder' => __( 'All Location', 'wp-cardealer' ),
				'show_distance' => true,
				'toggle' => true,
				'for_post_type' => 'listing',
			),
		));
	}
	
	public static function archive($query) {
		$suppress_filters = ! empty( $query->query_vars['suppress_filters'] ) ? $query->query_vars['suppress_filters'] : '';

		if ( ! is_post_type_archive( 'listing' ) || ! $query->is_main_query() || is_admin() || $query->query_vars['post_type'] != 'listing' || $suppress_filters ) {
			return;
		}

		$limit = wp_cardealer_get_option('number_listings_per_page', 10);
		$query_vars = &$query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;
		
		return self::filter_query( $query );
	}

	public static function taxonomy($query) {
		$is_correct_taxonomy = false;
		if ( is_tax( 'listing_type' ) || is_tax( 'listing_category' ) || is_tax( 'listing_color' ) || is_tax( 'listing_cylinder' ) || is_tax( 'listing_door' ) || is_tax( 'listing_drive_type' ) || is_tax( 'listing_fuel_type' ) || is_tax( 'listing_location' ) || is_tax( 'listing_make' ) || is_tax( 'listing_model' ) || is_tax( 'listing_offer_type' ) || is_tax( 'listing_feature' ) || is_tax( 'listing_transmission' ) || apply_filters( 'wp-cardealer-listing-query-taxonomy', false ) ) {
			$is_correct_taxonomy = true;
		}

		if ( ! $is_correct_taxonomy  || ! $query->is_main_query() || is_admin() ) {
			return;
		}

		$limit = wp_cardealer_get_option('number_listings_per_page', 10);
		$query_vars = $query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;

		return self::filter_query( $query );
	}


	public static function filter_query( $query = null, $params = array() ) {
		global $wpdb, $wp_query;

		if ( empty( $query ) ) {
			$query = $wp_query;
		}

		if ( empty( $params ) ) {
			$params = $_GET;
		}
		
		// Filter params
		$params = apply_filters( 'wp_cardealer_listing_filter_params', $params );

		// Initialize variables
		$query_vars = $query->query_vars;
		$query_vars = self::get_query_var_filter($query_vars, $params);
		$query->query_vars = $query_vars;

		// Meta query
		$meta_query = self::get_meta_filter($params);
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Tax query
		$tax_query = self::get_tax_filter($params);
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
		
		return apply_filters('wp-cardealer-listing-filter-query', $query, $params);
	}

	public static function get_query_var_filter($query_vars, $params) {
		$ids = null;
		$query_vars = self::orderby($query_vars, $params);

		// Listing title
		if ( ! empty( $params['filter-title'] ) ) {
			global $wp_cardealer_listing_keyword;
			$wp_cardealer_listing_keyword = sanitize_text_field( wp_unslash($params['filter-title']) );
			$query_vars['s'] = sanitize_text_field( wp_unslash($params['filter-title']) );
			add_filter( 'posts_search', array( __CLASS__, 'get_listings_keyword_search' ) );
		}

		$distance_ids = self::filter_by_distance($params);
		if ( !empty($distance_ids) ) {
			$ids = self::build_post_ids( $ids, $distance_ids );
		}
    	
    	
		if ( ! empty( $params['filter-author'] ) ) {
			$query_vars['author'] = sanitize_text_field( wp_unslash($params['filter-author']) );
		}

		// Post IDs
		if ( is_array( $ids ) && count( $ids ) > 0 ) {
			$query_vars['post__in'] = $ids;
		}
		
		return $query_vars;
	}

	public static function get_meta_filter($params) {
		$meta_query = array();
		// price
		if ( isset($params['filter-price-from']) && intval($params['filter-price-from']) >= 0 && isset($params['filter-price-to']) && intval($params['filter-price-to']) > 0) {
			$price_from = WP_CarDealer_Price::convert_current_currency_to_default($params['filter-price-from']);
			$price_to = WP_CarDealer_Price::convert_current_currency_to_default($params['filter-price-to']);
			
			if ( $price_from == 0 ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'price',
			           	'value' => array( intval($price_from), intval($price_to) ),
			           	'compare'   => 'BETWEEN',
						'type'      => 'NUMERIC',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'price',
			           	'value' => '',
			           	'compare'   => '==',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'price',
			           	'compare'   => 'NOT EXISTS',
					),
		       	);
			} else {
				$meta_query[] = array(
		           	'key' => WP_CARDEALER_LISTING_PREFIX . 'price',
		           	'value' => array( intval($price_from), intval($price_to) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);
			}
		}

		if ( ! empty( $params['filter-featured'] ) ) {
			$meta_query[] = array(
				'key'       => WP_CARDEALER_LISTING_PREFIX . 'featured',
				'value'     => 'on',
				'compare'   => '==',
			);
		}

		// Rooms
	    if ( ! empty( $params['filter-vin'] ) ) {
	    	$value = sanitize_text_field( wp_unslash($params['filter-vin']) );
	    	
		    $meta_query[] = array(
			    'key'       => WP_CARDEALER_LISTING_PREFIX . 'vin',
			    'value'     => '"' . $value . '"',
			    'compare'   => 'LIKE',
		    );
	    }

		// Year built
		if ( isset($params['filter-year-from']) && intval($params['filter-year-from']) >= 0 && isset($params['filter-year-to']) && intval($params['filter-year-to']) > 0) {
			if ( $params['filter-year-from'] == 0 ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'year',
			           	'value' => array( intval($params['filter-year-from']), intval($params['filter-year-to']) ),
			           	'compare'   => 'BETWEEN',
						'type'      => 'NUMERIC',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'year',
			           	'value' => '',
			           	'compare'   => '==',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'year',
			           	'compare'   => 'NOT EXISTS',
					),
		       	);
			} else {
				$meta_query[] = array(
		           	'key' => WP_CARDEALER_LISTING_PREFIX . 'year',
		           	'value' => array( intval($params['filter-year-from']), intval($params['filter-year-to']) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);
			}
		}

		if ( isset($params['filter-mileage-from']) && intval($params['filter-mileage-from']) >= 0 && isset($params['filter-mileage-to']) && intval($params['filter-mileage-to']) > 0) {
			if ( $params['filter-mileage-from'] == 0 ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'mileage',
			           	'value' => array( intval($params['filter-mileage-from']), intval($params['filter-mileage-to']) ),
			           	'compare'   => 'BETWEEN',
						'type'      => 'NUMERIC',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'mileage',
			           	'value' => '',
			           	'compare'   => '==',
					),
					array(
			           	'key' => WP_CARDEALER_LISTING_PREFIX . 'mileage',
			           	'compare'   => 'NOT EXISTS',
					),
		       	);
			} else {
				$meta_query[] = array(
		           	'key' => WP_CARDEALER_LISTING_PREFIX . 'mileage',
		           	'value' => array( intval($params['filter-mileage-from']), intval($params['filter-mileage-to']) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);
			}
		}
		

		return $meta_query;
	}

	public static function get_tax_filter($params) {
		$tax_query = array();
		
		if ( ! empty( $params['filter-location'] ) ) {
			if ( is_array($params['filter-location']) ) {
				$field = is_numeric( $params['filter-location'][0] ) ? 'term_id' : 'slug';
				$values = array_filter( array_map( 'sanitize_title', wp_unslash( $params['filter-location'] ) ) );
				
				if ( !empty($values) ) {
					$location_tax_query = array('relation' => 'AND');
					foreach ($values as $key => $value) {
						$location_tax_query[] = array(
							'taxonomy'  => 'listing_location',
							'field'     => $field,
							'terms'     => $value,
							'compare'   => '==',
						);
					}
					$tax_query[] = $location_tax_query;
				}
			} else {
				$field = is_numeric( $params['filter-location'] ) ? 'term_id' : 'slug';
				$tax_query[] = array(
					'taxonomy'  => 'listing_location',
					'field'     => $field,
					'terms'     => sanitize_text_field( wp_unslash($params['filter-location']) ),
					'compare'   => '==',
				);
			}
		}

		$tax_query = self::generate_tax_query($params, $tax_query, 'category');
		
		$tax_query = self::generate_tax_query($params, $tax_query, 'color');
		$tax_query = self::generate_tax_query($params, $tax_query, 'condition');
		$tax_query = self::generate_tax_query($params, $tax_query, 'cylinder');
		$tax_query = self::generate_tax_query($params, $tax_query, 'door');
		$tax_query = self::generate_tax_query($params, $tax_query, 'drive_type');
		$tax_query = self::generate_tax_query($params, $tax_query, 'feature');
		$tax_query = self::generate_tax_query($params, $tax_query, 'fuel_type');
		$tax_query = self::generate_tax_query($params, $tax_query, 'make');
		$tax_query = self::generate_tax_query($params, $tax_query, 'model');
		$tax_query = self::generate_tax_query($params, $tax_query, 'offer_type');
		$tax_query = self::generate_tax_query($params, $tax_query, 'safety_type');
		$tax_query = self::generate_tax_query($params, $tax_query, 'transmission');
		
		$tax_query = self::generate_tax_query($params, $tax_query, 'label');

		$tax_query = self::generate_tax_query($params, $tax_query, 'type');
		
		return $tax_query;
	}

	public static function generate_tax_query($params, $tax_query, $tax_key) {
		if ( ! empty( $params['filter-'.$tax_key] ) ) {
			if ( is_array($params['filter-'.$tax_key]) ) {
				//$field = is_numeric( $params['filter-'.$tax_key][0] ) ? 'term_id' : 'slug';
				$field = 'slug';
				$values = $params['filter-'.$tax_key];
				if ( !empty($values) ) {
					$tax_query[] = array(
						'taxonomy'  => 'listing_'.$tax_key,
						'field'     => $field,
						'terms'     => array_values($values),
						'compare'   => 'IN',
					);
				}
			} else {
				//$field = is_numeric( $params['filter-'.$tax_key] ) ? 'term_id' : 'slug';
				$field = 'slug';
				$tax_query[] = array(
					'taxonomy'  => 'listing_'.$tax_key,
					'field'     => $field,
					'terms'     => $params['filter-'.$tax_key],
					'compare'   => '==',
				);
			}
		}

		return $tax_query;
	}

	public static function get_listings_keyword_search( $search ) {
		global $wpdb, $wp_cardealer_listing_keyword;

		// Searchable Meta Keys: set to empty to search all meta keys.
		$searchable_meta_keys = array(
			WP_CARDEALER_LISTING_PREFIX.'address',
			WP_CARDEALER_LISTING_PREFIX.'vin',
		);

		$searchable_meta_keys = apply_filters( 'wp_cardealer_searchable_meta_keys', $searchable_meta_keys );

		// Set Search DB Conditions.
		$conditions = array();

		// Search Post Meta.
		if ( apply_filters( 'wp_cardealer_search_post_meta', true ) ) {

			// Only selected meta keys.
			if ( $searchable_meta_keys ) {
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '" . implode( "','", array_map( 'esc_sql', $searchable_meta_keys ) ) . "' ) AND meta_value LIKE '%" . esc_sql( $wp_cardealer_listing_keyword ) . "%' )";
			} else {
				// No meta keys defined, search all post meta value.
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $wp_cardealer_listing_keyword ) . "%' )";
			}
		}

		// Search taxonomy.
		$conditions[] = "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%" . esc_sql( $wp_cardealer_listing_keyword ) . "%' )";
		
		$conditions = apply_filters( 'wp_cardealer_search_conditions', $conditions, $wp_cardealer_listing_keyword );
		if ( empty( $conditions ) ) {
			return $search;
		}

		$conditions_str = implode( ' OR ', $conditions );

		if ( ! empty( $search ) ) {
			$search = preg_replace( '/^ AND /', '', $search );
			$search = " AND ( {$search} OR ( {$conditions_str} ) )";
		} else {
			$search = " AND ( {$conditions_str} )";
		}
		remove_filter( 'posts_search', array( __CLASS__, 'get_listings_keyword_search' ) );
		return $search;
	}

	public static function filter_query_listing($query, $params) {
    	$query_vars = $query->query_vars;

		$meta_query = self::filter_meta($query_vars, $params);

		$query->set('meta_query', $meta_query);
		return $query;
    }

    public static function filter_query_args_listing($query_vars, $params) {
    	$meta_query = self::filter_meta($query_vars, $params);

		$query_vars['meta_query'] = $meta_query;
		return $query_vars;
    }

    public static function filter_meta($query_args, $params) {
    	if ( isset($query_args['meta_query']) ) {
			$meta_query = $query_args['meta_query'];
		} else {
			$meta_query = array();
		}
		if ( empty($params) || !is_array($params) ) {
			return $meta_query;
		}
		$filter_fields = WP_CarDealer_Custom_Fields::filter_custom_fields(array());

		$cfielddate = [];
    	foreach ( $params as $key => $value ) {
    		if ( !empty($value) && strrpos( $key, 'filter-cfielddate-', -strlen( $key ) ) !== false ) {
    			$cfielddate[$key] = $value;
    		}
			if ( !empty($value) && strrpos( $key, 'filter-cfield-', -strlen( $key ) ) !== false ) {
				$custom_key = str_replace( 'filter-cfield-', '', $key );

		        if ( !empty($filter_fields[$custom_key]) ) {
		            $fielddata = $filter_fields[$custom_key];

		            $field_type = $fielddata['type'];
		            $meta_key = $custom_key;

		            switch ($field_type) {
		            	
		            	case 'text':
		            	case 'textarea':
		            	case 'wysiwyg':
		            	case 'number':
		            	case 'url':
		            	case 'email':
		            		$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => $value,
								'compare'   => 'LIKE',
							);
		            		break;
	            		case 'radio':
	            		case 'select':
	            		case 'pw_select':
		            		$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => $value,
								'compare'   => '=',
							);
		            		break;
	            		case 'checkbox':
	            			$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => 'on',
								'compare'   => '=',
							);
							break;
	            		case 'pw_multiselect':
	            		case 'multiselect':
	            		case 'multicheck':
	            			if ( is_array($value) ) {
	            				$multi_meta = array( 'relation' => 'OR' );
	            				foreach ($value as $val) {
	            					$multi_meta[] = array(
	            						'key'       => $meta_key,
										'value'     => '"'.$val.'"',
										'compare'   => 'LIKE',
	            					);
	            				}
	            				$meta_query[] = $multi_meta;
	            			} else {
	            				$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => '"'.$value.'"',
									'compare'   => 'LIKE',
								);
	            			}
	            			break;
		            }
		        }
			}
		}
		if ( !empty($cfielddate) ) {
			
			foreach ( $cfielddate as $key => $values ) {
				if ( !empty($values) && is_array($values) && count($values) == 2 ) {
					$custom_key = str_replace( 'filter-cfielddate-', '', $key );

			        if ( !empty($filter_fields[$custom_key]) ) {
			            $fielddata = $filter_fields[$custom_key];

			            $field_type = $fielddata['type'];
			            $meta_key = $custom_key;

			            
						if ( !empty($values['from']) && !empty($values['to']) ) {
							$meta_query[] = array(
					           	'key' => $meta_key,
					           	'value' => array($values['from'], $values['to']),
					           	'compare'   => 'BETWEEN',
								'type' 		=> 'DATE',
							);
						} elseif ( !empty($values['from']) && empty($values['to']) ) {
							$meta_query[] = array(
					           	'key' => $meta_key,
					           	'value' => $values['from'],
					           	'compare'   => '>',
								'type' 		=> 'DATE',
					       	);
						} elseif (empty($values['from']) && !empty($values['to']) ) {
							$meta_query[] = array(
					           	'key' => $meta_key,
					           	'value' => $values['to'],
					           	'compare'   => '<',
								'type' 		=> 'DATE',
					       	);
						}

			        }
				}
			}
		}
		
		return $meta_query;
    }

	public static function display_filter_value($key, $value, $filters) {
		$url = urldecode(WP_CarDealer_Mixes::get_full_current_url());
		if ( is_array($value) ) {
			$value = array_filter( array_map( 'sanitize_title', wp_unslash( $value ) ) );
		} else {
			$value = sanitize_text_field( wp_unslash($value) );
		}
		switch ($key) {
			case 'filter-condition':
				self::render_filter_tax($key, $value, 'listing_condition', $url);
				break;
			case 'filter-category':
				self::render_filter_tax($key, $value, 'listing_category', $url);
				break;
			case 'filter-location':
				self::render_filter_tax($key, $value, 'listing_location', $url);
				break;
			case 'filter-type':
				self::render_filter_tax($key, $value, 'listing_type', $url);
				break;
			case 'filter-feature':
				self::render_filter_tax($key, $value, 'listing_feature', $url);
				break;
			case 'filter-color':
				self::render_filter_tax($key, $value, 'listing_color', $url);
				break;
			case 'filter-cylinder':
				self::render_filter_tax($key, $value, 'listing_cylinder', $url);
				break;
			case 'filter-door':
				self::render_filter_tax($key, $value, 'listing_door', $url);
				break;
			case 'filter-drive_type':
				self::render_filter_tax($key, $value, 'listing_drive_type', $url);
				break;
			case 'filter-fuel_type':
				self::render_filter_tax($key, $value, 'listing_fuel_type', $url);
				break;
			case 'filter-make':
				self::render_filter_tax($key, $value, 'listing_make', $url);
				break;
			case 'filter-model':
				self::render_filter_tax($key, $value, 'listing_model', $url);
				break;
			case 'filter-offer_type':
				self::render_filter_tax($key, $value, 'listing_offer_type', $url);
				break;
			case 'filter-transmission':
				self::render_filter_tax($key, $value, 'listing_transmission', $url);
				break;
			case 'filter-price':
				if ( isset($value[0]) && isset($value[1]) ) {
					$from = WP_CarDealer_Price::format_price($value[0], true);
					$to = WP_CarDealer_Price::format_price($value[1], true);
					
					$rm_url = self::remove_url_var($key . '-from=' . $value[0], $url);
					$rm_url = self::remove_url_var($key . '-to=' . $value[1], $rm_url);
					self::render_filter_result_item( $from.' - '.$to, $rm_url );
				}
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_cardealer_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					self::render_filter_result_item( $title, $rm_url );
				}
				break;
			case 'filter-featured':
				$title = esc_html__('Featured', 'wp-cardealer');
				$rm_url = self::remove_url_var($key . $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-author':
				$user_info = get_userdata($value);
				if ( is_object($user_info) ) {
					$title = $user_info->display_name;
				} else {
					$title = $value;
				}
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-cardealer-listings-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-cardealer'),
					'newest' => esc_html__('Newest', 'wp-cardealer'),
					'oldest' => esc_html__('Oldest', 'wp-cardealer'),
					'random' => esc_html__('Random', 'wp-cardealer'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			default:
				if ( is_array($value) ) {
					foreach ($value as $val) {
						$rm_url = self::remove_url_var( $key . '[]=' . $val, $url);
						self::render_filter_result_item( $val, $rm_url);
					}
				} else {
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					self::render_filter_result_item( $value, $rm_url);
				}
				
				break;
		}
	}


	public static function display_filter_value_simple($key, $value, $filters) {
		if ( is_array($value) ) {
			$value = array_filter( array_map( 'sanitize_title', wp_unslash( $value ) ) );
		} else {
			$value = sanitize_text_field( wp_unslash($value) );
		}
		switch ($key) {
			case 'filter-condition':
				self::render_filter_tax_simple($key, $value, 'listing_condition', esc_html__('Condition', 'wp-cardealer'));
				break;
			case 'filter-category':
				self::render_filter_tax_simple($key, $value, 'listing_category', esc_html__('Category', 'wp-cardealer'));
				break;
			case 'filter-location':
				self::render_filter_tax_simple($key, $value, 'listing_location', esc_html__('Location', 'wp-cardealer'));
				break;
			case 'filter-type':
				self::render_filter_tax_simple($key, $value, 'listing_type', esc_html__('Type', 'wp-cardealer'));
				break;
			case 'filter-feature':
				self::render_filter_tax_simple($key, $value, 'listing_feature', esc_html__('Features', 'wp-cardealer'));
				break;
			case 'filter-color':
				self::render_filter_tax_simple($key, $value, 'listing_color', esc_html__('Color', 'wp-cardealer'));
				break;
			case 'filter-cylinder':
				self::render_filter_tax_simple($key, $value, 'listing_cylinder', esc_html__('Cylinder', 'wp-cardealer'));
				break;
			case 'filter-door':
				self::render_filter_tax_simple($key, $value, 'listing_door', esc_html__('Door', 'wp-cardealer'));
				break;
			case 'filter-drive_type':
				self::render_filter_tax_simple($key, $value, 'listing_drive_type', esc_html__('Drive Type', 'wp-cardealer'));
				break;
			case 'filter-fuel_type':
				self::render_filter_tax_simple($key, $value, 'listing_fuel_type', esc_html__('Fuel Type', 'wp-cardealer'));
				break;
			case 'filter-make':
				self::render_filter_tax_simple($key, $value, 'listing_make', esc_html__('Make', 'wp-cardealer'));
				break;
			case 'filter-model':
				self::render_filter_tax_simple($key, $value, 'listing_model', esc_html__('Model', 'wp-cardealer'));
				break;
			case 'filter-offer_type':
				self::render_filter_tax_simple($key, $value, 'listing_offer_type', esc_html__('Offer Type', 'wp-cardealer'));
				break;
			case 'filter-transmission':
				self::render_filter_tax_simple($key, $value, 'listing_transmission', esc_html__('Transmission', 'wp-cardealer'));
				break;
			case 'filter-price':
				if ( isset($value[0]) && isset($value[1]) ) {
					$from = WP_CarDealer_Price::format_price($value[0]);
					$to = WP_CarDealer_Price::format_price($value[1]);
					
					self::render_filter_result_item_simple( $from.' - '.$to, esc_html__('Price', 'wp-cardealer') );
				}
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_cardealer_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					self::render_filter_result_item_simple( $title, esc_html__('Distance', 'wp-cardealer') );
				}
				break;
			case 'filter-featured':
				$title = esc_html__('Yes', 'wp-cardealer');
				self::render_filter_result_item_simple( $title, esc_html__('Featured', 'wp-cardealer') );
				break;
			case 'filter-author':
				$user_info = get_userdata($value);
				if ( is_object($user_info) ) {
					$title = $user_info->display_name;
				} else {
					$title = $value;
				}
				self::render_filter_result_item_simple( $title, esc_html__('Author', 'wp-cardealer') );
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-cardealer-listings-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-cardealer'),
					'newest' => esc_html__('Newest', 'wp-cardealer'),
					'oldest' => esc_html__('Oldest', 'wp-cardealer'),
					'random' => esc_html__('Random', 'wp-cardealer'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				self::render_filter_result_item_simple( $title, esc_html__('Orderby', 'wp-cardealer') );
				break;
			default:
				$meta_obj = WP_CarDealer_Listing_Meta::get_instance(0);
				
				$label_key = str_replace('filter-', '', $key);
				$label_key = str_replace('filter-', '', $label_key);
				$prefix = '';
				if (preg_match("/-to/i", $key)) {
					$prefix = esc_html__('to', 'wp-cardealer');

					$label_key = str_replace('-to', '', $label_key);
				} elseif (preg_match("/-from/i", $key)) {
					$prefix = esc_html__('from', 'wp-cardealer');

					$label_key = str_replace('-from', '', $label_key);
				}

				$label = $meta_obj->get_post_meta_title($label_key);
				if ( empty($label) ) {
					$label = $label_key;
				}
				if ( $prefix ) {
					$label .= ' '.$prefix;
				}
				if ( is_array($value) ) {
					foreach ($value as $val) {
						self::render_filter_result_item_simple( $val, $label);
					}
				} else {
					self::render_filter_result_item_simple( $value, $label);
				}
				
				break;
		}
	}
}

WP_CarDealer_Listing_Filter::init();