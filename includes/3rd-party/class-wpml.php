<?php
/**
 * WPML
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_WPML {
	
	public static function init() {
		if ( did_action( 'wpml_loaded' ) ) {
			add_action('save_post', array(__CLASS__, 'translate_post'));
			add_filter( 'wp-cardealer-get-custom-fields-key', array(__CLASS__, 'custom_fields_key'), 100, 1);
			add_filter( 'wp-cardealer-get-custom-fields-data', array(__CLASS__, 'get_custom_fields_data'), 100, 1 );

			add_filter( 'wp-cardealer-post-id', array(__CLASS__, 'get_post_id'), 10, 2 );
			add_filter( 'wp-cardealer-translations-post-ids', array(__CLASS__, 'get_all_translations_object_id'), 10 );
			
			add_filter( 'wp_cardealer_settings_listing_submission', array(__CLASS__, 'hide_page_selection'), 100 );
			add_filter( 'wp_cardealer_settings_pages', array(__CLASS__, 'hide_page_selection'), 100 );
			
			add_filter( 'wp-cardealer-current-lang', array(__CLASS__, 'get_listings_lang') );
		}
	}

	public static function get_listings_lang($lang) {
		return apply_filters( 'wpml_current_language', $lang );
	}
	
	public static function get_icl_object_id($post_id, $post_type) {
		if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
			$current_lang = apply_filters( 'wpml_current_language', NULL );
            $icl_post_id = icl_object_id($post_id, $post_type, false, $current_lang);

            if ($icl_post_id > 0) {
                $post_id = $icl_post_id;
            }
        }
        return $post_id;
	}

	public static function get_all_translations_object_id($post_id) {
		if ( function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') ) {
			global $sitepress;
			$trid = $sitepress->get_element_trid($post_id);
			$translations = $sitepress->get_element_translations($trid);
			$post_ids = array();
			if ( !empty($translations) ) {
				foreach ($translations as $key => $translation) {
					$post_ids[] = $translation->element_id;
				}
			} else {
				$post_ids = array($post_id);
			}
		} else {
			$post_ids = array($post_id);
		}
		
        return $post_ids;
	}
	
	public static function custom_fields_key($key) {
		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$current_lang = apply_filters( 'wpml_current_language', NULL );
		if ( $default_lang != $current_lang ) {
			$key = $key.'_'.$current_lang;
		}

		return $key;
	}

	public static function get_custom_fields_data($value) {
		if ( empty($value) ) {
			$value = get_option('wp_cardealer_fields_data', array());
		}
		return $value;
	}

	public static function get_post_id($post_id, $post_type = 'page') {
		return apply_filters( 'wpml_object_id', $post_id, $post_type, true );
	}

	public static function hide_page_selection($fields) {
		$default_lang = apply_filters( 'wpml_default_language', null );
		$current_lang = apply_filters( 'wpml_current_language', null );

		// Add filter only for non default languages.
		if ( $current_lang === $default_lang ) {
			return $fields;
		}

		$tab = '';
		if ( !empty($_GET['tab']) ) {
			$tab = '&tab='.$_GET['tab'];
		}
		
		$url_to_edit_page = admin_url( 'edit.php?post_type=listing&page=listing-settings'.$tab.'&lang=' . $default_lang );

		foreach ($fields as $key => $field) {
			if ( !empty($field['page-type']) && $field['page-type'] == 'page' ) {
				$fields[$key]['type'] = 'wp_cardealer_hidden';
				$fields[$key]['human_value'] = __( 'Page Not Set', 'wp-cardealer' );

				$current_value = get_option( $field['id'] );
				if ( $current_value ) {
					$page = get_post( apply_filters( 'wpml_object_id', $current_value, 'page' ) );

					if ( $page ) {
						$fields[$key]['human_value'] = $page->post_title;
					}
				}
				
				// translators: Placeholder (%s) is the URL to edit the primary language in WPML.
				$fields[$key]['desc'] = sprintf( __( '<a href="%s">Switch to primary language</a> to edit this setting.', 'wp-cardealer' ), $url_to_edit_page );
			}
		}
		return $fields;
	}

	public static function translate_post( $post_id ) {
		$prefix = WP_CARDEALER_LISTING_PREFIX;
		if ( ! isset( $_POST['submit-cmb-listing'] ) || empty( $_POST[$prefix.'post_type'] ) || 'listing' !== $_POST[$prefix.'post_type'] ) {
			return;
		}
	    global $iclTranslationManagement, $sitepress, $ICL_Pro_Translation;
	    if ( !isset( $iclTranslationManagement ) ) {
	        if(!class_exists('TranslationManagement')) {
	        	$file_management = ABSPATH.'wp-content/plugins/sitepress-multilingual-cms/inc/translation-management/translation-management.class.php';
	        	$file_translation = ABSPATH.'wp-content/plugins/sitepress-multilingual-cms/inc/translation-management/pro-translation.class.php';
	        	if ( file_exists($file_management) && file_exists($file_translation) ) {
	        		include($file_management);
	            	include($file_translation);
	        	} else {
	        		return;
	        	}
	            
	        }
	        $iclTranslationManagement = new TranslationManagement;
	        $ICL_Pro_Translation      = new ICL_Pro_Translation();
	    }
	 
	    // don't save for autosave
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	        return $post_id;
	    }
	    // save only for campaign
	    $post_type = get_post_type($post_id);
	    if ( !in_array($post_type, array('listing')) ) {
	        return $post_id;
	    }
	 	
	    // get languages
	    $langs = $sitepress->get_active_languages();
	    $current_lang = apply_filters( 'wpml_current_language', NULL );
	    unset($langs[$current_lang]);
	 	
	    // unhook this function so it doesn't loop infinitely
	 	remove_action('save_post', array(__CLASS__, 'translate_post'));

	    //now lets create duplicates of all new posts in all languages used for translations
	    foreach($langs as $language_code => $v){
	        $iclTranslationManagement->make_duplicate($post_id, $language_code);
	    }
	}
}

WP_CarDealer_WPML::init();