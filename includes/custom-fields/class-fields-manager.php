<?php
/**
 * Fields Manager
 *
 * @package    wp-cardealer
 * @author     Habq
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
 
class WP_CarDealer_Fields_Manager {

	public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_page' ), 1 );
        add_action( 'init', array(__CLASS__, 'init_hook'), 10 );
	}

    public static function register_page() {
        add_submenu_page( 'edit.php?post_type=listing', __( 'Fields Manager', 'wp-cardealer' ), __( 'Fields Manager', 'wp-cardealer' ), 'manage_options', 'listing-manager-fields-manager', array( __CLASS__, 'listing_output' ) );

        add_submenu_page( 'edit.php?post_type=dealer', __( 'Fields Manager', 'wp-cardealer' ), __( 'Fields Manager', 'wp-cardealer' ), 'manage_options', 'dealer-manager-fields-manager', array( __CLASS__, 'dealer_output' ) );

        add_submenu_page( 'users.php', __( 'Fields Manager', 'wp-cardealer' ), __( 'Fields Manager', 'wp-cardealer' ), 'manage_options', 'user-manager-fields-manager', array( __CLASS__, 'user_output' ) );
    }

    public static function init_hook() {
        // Ajax endpoints.
        add_action( 'wpcd_ajax_wp_cardealer_custom_field_html', array( __CLASS__, 'custom_field_html' ) );

        add_action( 'wpcd_ajax_wp_cardealer_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );

        // compatible handlers.
        // custom fields
        add_action( 'wp_ajax_wp_cardealer_custom_field_html', array( __CLASS__, 'custom_field_html' ) );
        add_action( 'wp_ajax_nopriv_wp_cardealer_custom_field_html', array( __CLASS__, 'custom_field_html' ) );

        add_action( 'wp_ajax_wp_cardealer_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );
        add_action( 'wp_ajax_nopriv_wp_cardealer_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );

        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'scripts' ), 1 );
    }

    public static function scripts() {
        wp_enqueue_style('font-awesome', WP_CARDEALER_PLUGIN_URL . 'assets/css/font-awesome.css');

        // icon
        wp_enqueue_style('jquery-fonticonpicker', WP_CARDEALER_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.min.css', array(), '1.0');
        wp_enqueue_style('jquery-fonticonpicker-bootstrap', WP_CARDEALER_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.bootstrap.min.css', array(), '1.0');
        wp_enqueue_script('jquery-fonticonpicker', WP_CARDEALER_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.min.js', array(), '1.0', true);

        wp_enqueue_style('wp-cardealer-custom-field-css', WP_CARDEALER_PLUGIN_URL . 'assets/admin/style.css');
        wp_register_script('wp-cardealer-custom-field', WP_CARDEALER_PLUGIN_URL.'assets/admin/functions.js', array('jquery', 'wp-color-picker'), '', true);

        $args = array(
            'plugin_url' => WP_CARDEALER_PLUGIN_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
        );
        wp_localize_script('wp-cardealer-custom-field', 'wp_cardealer_customfield_common_vars', $args);
        wp_enqueue_script('wp-cardealer-custom-field');

        wp_enqueue_script('jquery-ui-sortable');
    }

    public static function user_output() {
        self::output(WP_CARDEALER_USER_PREFIX);
    }

    public static function listing_output() {
        self::output(WP_CARDEALER_LISTING_PREFIX);
    }

    public static function dealer_output() {
        self::output(WP_CARDEALER_DEALER_PREFIX);
    }

    public static function output($prefix = WP_CARDEALER_LISTING_PREFIX) {
        self::save($prefix);
        ?>
        <h1><?php echo esc_html__('Fields manager', 'wp-cardealer'); ?></h1>

        <form class="listing-manager-options" method="post" action="" data-prefix="<?php echo esc_attr($prefix); ?>">
            
            <button type="submit" class="button button-primary" name="updateListingFieldManager"><?php esc_html_e('Update', 'wp-cardealer'); ?></button>
            
            <?php

            $rand_id = rand(123, 9878787);
            $default_fields = self::get_all_field_types();

            if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
                $available_fields = self::get_all_types_fields_available();
                $required_types = self::get_all_types_fields_required();
            } elseif ( $prefix == WP_CARDEALER_DEALER_PREFIX ) {
                $available_fields = self::get_dealer_all_types_fields_available();
                $required_types = self::get_dealer_all_types_fields_required();
            } else {
                $available_fields = self::get_user_all_types_fields_available();
                $required_types = self::get_user_all_types_fields_required();
            }

            $custom_all_fields_saved_data = self::get_custom_fields_data($prefix);

            ?>
            <div class="custom-fields-wrapper clearfix">
                            
                <div class="wp-cardealer-custom-field-form" id="wp-cardealer-custom-field-form-<?php echo esc_attr($rand_id); ?>">
                    <div class="box-wrapper">
                        <h3 class="title"><?php echo esc_html('List of Fields', 'wp-cardealer'); ?></h3>
                        <ul id="foo<?php echo esc_attr($rand_id); ?>" class="block__list block__list_words"> 
                            <?php

                            $count_node = 1000;
                            $output = '';
                            $all_fields_name_count = 0;
                            $disabled_fields = array();

                            if (is_array($custom_all_fields_saved_data) && sizeof($custom_all_fields_saved_data) > 0) {
                                $field_names_counter = 0;
                                $types = self::get_all_field_type_keys();
                                foreach ($custom_all_fields_saved_data as $key => $custom_field_saved_data) {
                                    $all_fields_name_count++;
                                    
                                    $li_rand_id = rand(454, 999999);

                                    $output .= '<li class="custom-field-class-' . $li_rand_id . '">';

                                    $fieldtype = $custom_field_saved_data['type'];

                                    $delete = true;
                                    $drfield_values = self::get_field_id($fieldtype, $required_types);
                                    $dvfield_values = self::get_field_id($fieldtype, $available_fields);


                                    if ( !empty($drfield_values) ) {
                                        $count_node ++;
                                        
                                        $delete = false;
                                        $field_values = wp_parse_args( $custom_field_saved_data, $drfield_values);
                                        if ( in_array( $fieldtype, array( $prefix.'title', $prefix.'expiry_date', $prefix.'featured', $prefix.'first_name', $prefix.'last_name', $prefix.'email' ) ) ) {

                                            $output .= apply_filters('wp_cardealer_custom_field_available_simple_html', $fieldtype, $count_node, $field_values);
                                        } elseif ( in_array( $fieldtype, array( $prefix.'description' ) ) ) {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_description_html', $fieldtype, $count_node, $field_values);
                                        } elseif ( in_array($fieldtype, apply_filters( 'wp_cardealer_list_files_type', array( $prefix.'avatar' ) ) )) {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_files_html', $fieldtype, $count_node, $field_values);
                                        } else {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_'.$fieldtype.'_html', $fieldtype, $count_node, $field_values);
                                        }
                                    } elseif ( !empty($dvfield_values) ) {
                                        $count_node ++;
                                        $field_values = wp_parse_args( $custom_field_saved_data, $dvfield_values);

                                        $dtypes = apply_filters( 'wp_cardealer_list_simple_type', array( $prefix.'featured', $prefix.'year', $prefix.'address', $prefix.'map_location', $prefix.'price', $prefix.'price_prefix', $prefix.'price_suffix', $prefix.'price_custom', $prefix.'mileage', $prefix.'engine_size', $prefix.'vin', $prefix.'video', $prefix.'first_name', $prefix.'last_name', $prefix.'email', $prefix.'phone', $prefix.'whatsapp', $prefix.'url', $prefix.'hours', $prefix.'tagline' ) );

                                        if ( in_array( $fieldtype, $dtypes) ) {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_simple_html', $fieldtype, $count_node, $field_values);

                                        } elseif ( in_array( $fieldtype, apply_filters( 'wp_cardealer_list_tax_type', array( $prefix.'condition', $prefix.'label', $prefix.'category', $prefix.'type', $prefix.'color', $prefix.'cylinder', $prefix.'door', $prefix.'drive_type', $prefix.'fuel_type', $prefix.'make', $prefix.'model', $prefix.'offer_type', $prefix.'transmission' ) ) ) ) {
                                            
                                            $output .= apply_filters('wp_cardealer_custom_field_available_tax_html', $fieldtype, $count_node, $field_values);

                                        } elseif ( in_array( $fieldtype, apply_filters( 'wp_cardealer_list_tax_feature_type', array( $prefix.'feature' ) ) ) ) {
                                            
                                            $output .= apply_filters('wp_cardealer_custom_field_available_tax_feature_html', $fieldtype, $count_node, $field_values);

                                        } elseif ( in_array($fieldtype, apply_filters( 'wp_cardealer_list_files_type', array( $prefix.'featured_image', $prefix.'gallery', $prefix.'attachments', $prefix.'avatar', $prefix.'photos' ) ) )) {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_files_html', $fieldtype, $count_node, $field_values);
                                        } else {
                                            $output .= apply_filters('wp_cardealer_custom_field_available_'.$fieldtype.'_html', $fieldtype, $count_node, $field_values);
                                        }
                                        $disabled_fields[] = $fieldtype;
                                    } elseif ( in_array($fieldtype, $types) ) {
                                        $count_node ++;
                                        if ( in_array( $fieldtype, array('text', 'textarea', 'wysiwyg', 'number', 'url', 'email', 'checkbox') ) ) {
                                            $output .= apply_filters('wp_cardealer_custom_field_text_html', $fieldtype, $count_node, $custom_field_saved_data);
                                        } elseif ( in_array( $fieldtype, array('select', 'multiselect', 'radio') ) ) {
                                            $output .= apply_filters('wp_cardealer_custom_field_opts_html', $fieldtype, $count_node, $custom_field_saved_data);
                                        } else {
                                            $output .= apply_filters('wp_cardealer_custom_field_'.$fieldtype.'_html', $fieldtype, $count_node, $custom_field_saved_data);
                                        }
                                    }

                                    $output .= apply_filters('wp_cardealer_custom_field_actions_html', $li_rand_id, $count_node, $fieldtype, $delete);
                                    $output .= '</li>';
                                }
                            } else {
                                foreach ($required_types as $field_values) {
                                    $count_node ++;
                                    $li_rand_id = rand(454, 999999);
                                    $output .= '<li class="custom-field-class-' . $li_rand_id . '">';
                                    $output .= apply_filters('wp_cardealer_custom_field_available_simple_html', $field_values['id'], $count_node, $field_values);

                                    $output .= apply_filters('wp_cardealer_custom_field_actions_html', $li_rand_id, $count_node, $field_values['id'], false);
                                    $output .= '</li>';
                                }
                            }
                            echo force_balance_tags($output);
                            ?>
                        </ul>

                        <button type="submit" class="button button-primary" name="updateListingFieldManager"><?php esc_html_e('Update', 'wp-cardealer'); ?></button>

                        <div class="input-field-types">
                            <h3><?php esc_html_e('Create a custom field', 'wp-cardealer'); ?></h3>
                            <div class="input-field-types-wrapper">
                                <select name="field-types" class="wp-cardealer-field-types">
                                    <?php foreach ($default_fields as $group) { ?>
                                        <optgroup label="<?php echo esc_attr($group['title']); ?>">
                                            <?php foreach ($group['fields'] as $value => $label) { ?>
                                                <option value="<?php echo esc_attr($value); ?>"><?php echo $label; ?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php } ?>
                                </select>
                                <button type="button" class="button btn-add-field" data-randid="<?php echo esc_attr($rand_id); ?>"><?php esc_html_e('Create', 'wp-cardealer'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wp-cardealer-form-field-list wp-cardealer-list">
                    <h3 class="title"><?php esc_html_e('Available Fields', 'wp-cardealer'); ?></h3>
                    <?php if ( !empty($available_fields) ) { ?>
                        <ul>
                            <?php foreach ($available_fields as $field) { ?>
                                <li class="<?php echo esc_attr($field['id']); ?> <?php echo esc_attr(in_array($field['id'], $disabled_fields) ? 'disabled' : ''); ?>">
                                    <a class="wp-cardealer-custom-field-add-available-field" data-fieldtype="<?php echo esc_attr($field['id']); ?>" data-randid="<?php echo esc_attr($rand_id); ?>" href="javascript:void(0);" data-fieldlabel="<?php echo esc_attr($field['name']); ?>">
                                        <span class="icon-wrapper">
                                            <i class="fas fa-plus"></i>
                                        </span>
                                        <?php echo esc_html($field['name']); ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
                <div class="clearfix" style="clear: both;"></div>
            </div>

            <script>
                var global_custom_field_counter = <?php echo intval($all_fields_name_count); ?>;
                jQuery(document).ready(function () {
                    
                    jQuery('#foo<?php echo esc_attr($rand_id); ?>').sortable({
                        group: "words",
                        animation: 150,
                        handle: ".field-intro",
                        cancel: ".form-group-wrapper"
                    });
                });
            </script>
        </form>
        <?php
    }

    public static function get_field_id($id, $fields) {
        if ( !empty($fields) && is_array($fields) ) {
            foreach ($fields as $field) {
                if ( $field['id'] == $id ) {
                    return $field;
                }
            }
        }
        return array();
    }

    public static function get_all_field_types() {
        $fields = apply_filters( 'wp_cardealer_get_default_field_types', array(
            array(
                'title' => esc_html__('Direct Input', 'wp-cardealer'),
                'fields' => array(
                    'text' => esc_html__('Text', 'wp-cardealer'),
                    'textarea' => esc_html__('Textarea', 'wp-cardealer'),
                    'wysiwyg' => esc_html__('WP Editor', 'wp-cardealer'),
                    'date' => esc_html__('Date', 'wp-cardealer'),
                    'number' => esc_html__('Number', 'wp-cardealer'),
                    'url' => esc_html__('Url', 'wp-cardealer'),
                    'email' => esc_html__('Email', 'wp-cardealer'),
                )
            ),
            array(
                'title' => esc_html__('Choices', 'wp-cardealer'),
                'fields' => array(
                    'select' => esc_html__('Select', 'wp-cardealer'),
                    'multiselect' => esc_html__('Multiselect', 'wp-cardealer'),
                    'checkbox' => esc_html__('Checkbox', 'wp-cardealer'),
                    'radio' => esc_html__('Radio Buttons', 'wp-cardealer'),
                )
            ),
            array(
                'title' => esc_html__('Form UI', 'wp-cardealer'),
                'fields' => array(
                    'heading' => esc_html__('Heading', 'wp-cardealer')
                )
            ),
            array(
                'title' => esc_html__('Others', 'wp-cardealer'),
                'fields' => array(
                    'file' => esc_html__('File', 'wp-cardealer')
                )
            ),
        ));
        
        return $fields;
    }

    public static function get_all_field_type_keys() {
        $fields = self::get_all_field_types();
        $return = array();
        foreach ($fields as $group) {
            foreach ($group['fields'] as $key => $value) {
                $return[] = $key;
            }
        }

        return apply_filters( 'wp_cardealer_get_all_field_types', $return );
    }

    public static function get_all_types_fields_required() {
        $prefix = WP_CARDEALER_LISTING_PREFIX;
        $fields = array(
            array(
                'name'              => __( 'Listing Title', 'wp-cardealer' ),
                'id'                => $prefix . 'title',
                'type'              => 'text',
                'disable_check' => true,
                'required' => true,
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Listing Description', 'wp-cardealer' ),
                'id'                => $prefix . 'description',
                'type'              => 'textarea',
                'options'           => array(
                    'media_buttons' => false,
                    'textarea_rows' => 8,
                    'wpautop' => true,
                    'tinymce'       => array(
                        'plugins'                       => 'lists,paste,tabfocus,wplink,wordpress',
                        'paste_as_text'                 => true,
                        'paste_auto_cleanup_on_paste'   => true,
                        'paste_remove_spans'            => true,
                        'paste_remove_styles'           => true,
                        'paste_remove_styles_if_webkit' => true,
                        'paste_strip_class_attributes'  => true,
                    ),
                ),
                'disable_check' => true,
                'required' => true,
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Expiry Date', 'wp-cardealer' ),
                'id'                => $prefix . 'expiry_date',
                'type'              => 'text_date',
                'date_format'       => 'Y-m-d',
                'disable_check' => true,
                'show_in_submit_form' => false,
                'show_in_admin_edit' => true,
            ),
            array(
                'name'              => __( 'Featured', 'wp-cardealer' ),
                'id'                => $prefix . 'featured',
                'type'              => 'checkbox',
                'description'       => __( 'Featured listings will be sticky during searches, and can be styled differently.', 'wp-cardealer' ),
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_checkbox'),
                'show_compare'      => true
            ),
        );
        return apply_filters( 'wp-cardealer-type-required-fields', $fields );
    }

    public static function get_all_types_fields_available() {
        $prefix = WP_CARDEALER_LISTING_PREFIX;
        $fields = array(
            
            array(
                'name'              => __( 'Featured Image', 'wp-cardealer' ),
                'id'                => $prefix . 'featured_image',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'multiple_files'    => false,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
            ),
            array(
                'name'              => __( 'Gallery', 'wp-cardealer' ),
                'id'                => $prefix . 'gallery',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'multiple_files'          => true,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
            ),
            array(
                'name'              => __( 'Attachments', 'wp-cardealer' ),
                'id'                => $prefix . 'attachments',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'multiple_files'    => true,
                'mime_types'        => array( 'pdf', 'docx', 'doc' ),
                'show_compare'      => false
            ),

            // location
            array(
                'name'              => __( 'Friendly Address', 'wp-cardealer' ),
                'id'                => $prefix . 'address',
                'type'              => 'text',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input'),
                'show_compare'      => true
            ),
            array(
                'id'                => $prefix . 'map_location',
                'name'              => __( 'Map Location', 'wp-cardealer' ),
                'type'              => 'pw_map',
                'sanitization_cb'   => 'pw_map_sanitise',
                'split_values'      => true,
            ),

            // price
            array(
                'name'              => __( 'Price', 'wp-cardealer' ),
                'id'                => $prefix . 'price',
                'type'              => 'text',
                'placeholder'       => __( 'e.g. 25000 (USD)', 'wp-cardealer' ),
                'desc'              => __( 'Enter the price in US dollars (USD). The website displays this amount in Tomans using the BrsApi live rate.', 'wp-cardealer' ),
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_listing_price'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Price Prefix', 'wp-cardealer' ),
                'id'                => $prefix . 'price_prefix',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Price Suffix', 'wp-cardealer' ),
                'id'                => $prefix . 'price_suffix',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Price Custom', 'wp-cardealer' ),
                'id'                => $prefix . 'price_custom',
                'type'              => 'text',
            ),

            // listing detail
            array(
                'name'              => __( 'Year', 'wp-cardealer' ),
                'id'                => $prefix . 'year',
                'type'              => 'text',
                'attributes'        => array(
                    'type'              => 'number',
                    'min'               => 0,
                    'pattern'           => '\d*',
                ),
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_year_built_range_slider'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Mileage', 'wp-cardealer' ),
                'id'                => $prefix . 'mileage',
                'type'              => 'text',
                'attributes'        => array(
                    'type'              => 'number',
                    'min'               => 0,
                    'pattern'           => '\d*',
                ),
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_range_slider'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Engine Size', 'wp-cardealer' ),
                'id'                => $prefix . 'engine_size',
                'type'              => 'text',
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'VIN', 'wp-cardealer' ),
                'id'                => $prefix . 'vin',
                'type'              => 'text',
                'show_compare'      => true,
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input'),
            ),
            array(
                'name'              => __( 'Video link', 'wp-cardealer' ),
                'id'                => $prefix . 'video',
                'type'              => 'text',
                'description'       => __( 'Enter Youtube or Vimeo url.', 'wp-cardealer' ),
            ),


            // Taxonomies
            array(
                'name'              => __( 'Category', 'wp-cardealer' ),
                'id'                => $prefix . 'category',
                'type'              => 'pw_taxonomy_select',
                'taxonomy'          => 'listing_category',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_select'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Color', 'wp-cardealer' ),
                'id'                => $prefix . 'color',
                'type'              => 'pw_taxonomy_select',
                'taxonomy'          => 'listing_color',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_select'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Type', 'wp-cardealer' ),
                'id'                => $prefix . 'type',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_type',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_select'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Condition', 'wp-cardealer' ),
                'id'                => $prefix . 'condition',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_condition',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_select'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Label', 'wp-cardealer' ),
                'id'                => $prefix . 'label',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_label',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_select'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Cylinder', 'wp-cardealer' ),
                'id'                => $prefix . 'cylinder',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_cylinder',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Door', 'wp-cardealer' ),
                'id'                => $prefix . 'door',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_door',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Drive Type', 'wp-cardealer' ),
                'id'                => $prefix . 'drive_type',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_drive_type',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Features', 'wp-cardealer' ),
                'id'                => $prefix . 'feature',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_feature',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Fuel Type', 'wp-cardealer' ),
                'id'                => $prefix . 'fuel_type',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_fuel_type',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Make', 'wp-cardealer' ),
                'id'                => $prefix . 'make',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_make',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Model', 'wp-cardealer' ),
                'id'                => $prefix . 'model',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_model',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Offer Type', 'wp-cardealer' ),
                'id'                => $prefix . 'offer_type',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_offer_type',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            array(
                'name'              => __( 'Transmission', 'wp-cardealer' ),
                'id'                => $prefix . 'transmission',
                'type'              => 'pw_taxonomy_multiselect',
                'taxonomy'          => 'listing_transmission',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
                'show_compare'      => true
            ),
            
        );
        return apply_filters( 'wp-cardealer-type-available-fields', $fields );
    }

    public static function get_dealer_all_types_fields_required() {
        $prefix = WP_CARDEALER_DEALER_PREFIX;
        $fields = array(
            
            array(
                'name'              => __( 'Email', 'wp-cardealer' ),
                'id'                => $prefix . 'email',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Description', 'wp-cardealer' ),
                'id'                => $prefix . 'description',
                'type'              => 'textarea',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Full Name', 'wp-cardealer' ),
                'id'                => $prefix . 'title',
                'type'              => 'text',
                'default'           => '',
                'field_call_back' => array( 'WP_CarDealer_Abstract_Filter', 'filter_field_input'),
            ),
        );
        return apply_filters( 'wp-cardealer-dealer-type-required-fields', $fields );
    }

    public static function get_dealer_all_types_fields_available() {
        $prefix = WP_CARDEALER_DEALER_PREFIX;
        $fields = array(
            array(
                'name'              => __( 'Featured Image', 'wp-cardealer' ),
                'id'                => $prefix . 'featured_image',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'file_multiple'    => false,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
                'allow_mime_types' => array(
                    'image/gif', 'image/jpeg', 'image/png'
                ),
                'object_type'       => 'user'
            ),
            array(
                'name'              => __( 'Tagline', 'wp-cardealer' ),
                'id'                => $prefix . 'tagline',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Phone', 'wp-cardealer' ),
                'id'                => $prefix . 'phone',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Whatsapp', 'wp-cardealer' ),
                'id'                => $prefix . 'whatsapp',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Website', 'wp-cardealer' ),
                'id'                => $prefix . 'url',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Photos', 'wp-cardealer' ),
                'id'                => $prefix . 'photos',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'multiple_files'          => true,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
                'object_type'       => 'user'
            ),
            array(
                'name'              => __( 'Friendly Address', 'wp-cardealer' ),
                'id'                => $prefix . 'address',
                'type'              => 'text',
            ),
            array(
                'id'                => $prefix . 'map_location',
                'name'              => __( 'Map Location', 'wp-cardealer' ),
                'type'              => 'pw_map',
                'sanitization_cb'   => 'pw_map_sanitise',
                'split_values'      => true,
                'object_type'       => 'user'
            ),
            array(
                'name'              => __( 'Hours', 'boxcar' ),
                'id'                => $prefix . 'hours',
                'type'              => 'wpcd_hours',
            )
        );

        return apply_filters( 'wp-cardealer-dealer-type-available-fields', $fields );
    }

    public static function get_user_all_types_fields_required() {
        $prefix = WP_CARDEALER_USER_PREFIX;
        $fields = array(
            array(
                'name'              => __( 'Avatar', 'wp-cardealer' ),
                'id'                => $prefix . 'avatar',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'file_multiple'    => false,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
                'allow_mime_types' => array(
                    'image/gif', 'image/jpeg', 'image/png'
                ),
                'object_type'       => 'user'
            ),
            array(
                'name'              => __( 'Email', 'wp-cardealer' ),
                'id'                => $prefix . 'email',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Description', 'wp-cardealer' ),
                'id'                => $prefix . 'description',
                'type'              => 'textarea',
                'default'           => '',
            ),
            array(
                'name'              => __( 'First Name', 'wp-cardealer' ),
                'id'                => $prefix . 'first_name',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Last Name', 'wp-cardealer' ),
                'id'                => $prefix . 'last_name',
                'type'              => 'text',
                'default'           => '',
            ),
        );
        return apply_filters( 'wp-cardealer-user-type-required-fields', $fields );
    }

    public static function get_user_all_types_fields_available() {
        $prefix = WP_CARDEALER_USER_PREFIX;
        $fields = array(
            
            array(
                'name'              => __( 'Phone', 'wp-cardealer' ),
                'id'                => $prefix . 'phone',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Whatsapp', 'wp-cardealer' ),
                'id'                => $prefix . 'whatsapp',
                'type'              => 'text',
            ),
            array(
                'name'              => __( 'Website', 'wp-cardealer' ),
                'id'                => $prefix . 'url',
                'type'              => 'text',
                'default'           => '',
            ),
            array(
                'name'              => __( 'Photos', 'wp-cardealer' ),
                'id'                => $prefix . 'photos',
                'type'              => 'wp_cardealer_file',
                'ajax'              => true,
                'multiple_files'          => true,
                'mime_types'        => array( 'gif', 'jpeg', 'jpg', 'jpg|jpeg|jpe', 'png' ),
                'object_type'       => 'user'
            ),
            array(
                'name'              => __( 'Friendly Address', 'wp-cardealer' ),
                'id'                => $prefix . 'address',
                'type'              => 'text',
            ),
            array(
                'id'                => $prefix . 'map_location',
                'name'              => __( 'Map Location', 'wp-cardealer' ),
                'type'              => 'pw_map',
                'sanitization_cb'   => 'pw_map_sanitise',
                'split_values'      => true,
                'object_type'       => 'user'
            )
        );

        return apply_filters( 'wp-cardealer-user-type-available-fields', $fields );
    }

    public static function get_custom_fields_data($prefix) {
        $meta_key = self::get_custom_fields_key($prefix);
        return apply_filters( 'wp-cardealer-get-custom-fields-data', get_option($meta_key, array()), $prefix );
    }

    public static function get_custom_fields_key($prefix) {
        if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
            $prefix = '';
        }
        return apply_filters( 'wp-cardealer-get-custom-fields-key', 'wp_cardealer'.$prefix.'_fields_data' );
    }

    public static function get_display_hooks() {
        $hooks = array(
            '' => esc_html__('Choose a position', 'wp-cardealer'),
            'wp-cardealer-single-listing-description' => esc_html__('Single Listing - Description', 'wp-cardealer'),
            'wp-cardealer-single-listing-details' => esc_html__('Single Listing - Details', 'wp-cardealer'),
            'wp-cardealer-single-listing-feature' => esc_html__('Single Listing - Features', 'wp-cardealer'),
            'wp-cardealer-single-listing-contact-form' => esc_html__('Single Listing - Contact Form', 'wp-cardealer'),
            'wp-cardealer-single-listing-video' => esc_html__('Single Listing - Video', 'wp-cardealer'),
            'wp-cardealer-single-listing-attachments' => esc_html__('Single Listing - Attachments', 'wp-cardealer'),
        );
        return apply_filters( 'wp-cardealer-get-custom-fields-display-hooks', $hooks );
    }

    public static function save($prefix) {
        if ( isset( $_POST['updateListingFieldManager'] ) ) {

            $custom_field_final_array = $counts = array();
            $field_index = 0;

            foreach ($_POST['wp-cardealer-custom-fields-type'] as $field_type) {
                $custom_fields_id = isset($_POST['wp-cardealer-custom-fields-id'][$field_index]) ? $_POST['wp-cardealer-custom-fields-id'][$field_index] : '';
                $counter = 0;
                if ( isset($counts[$field_type]) ) {
                    $counter = $counts[$field_type];
                }
                $custom_field_final_array[] = self::custom_field_ready_array($counter, $field_type, $custom_fields_id);
                $counter++;
                $counts[$field_type] = $counter;
                $field_index++;
            }
            
            $meta_key = self::get_custom_fields_key($prefix);
            update_option($meta_key, $custom_field_final_array);
            
        }
    }

    public static function custom_field_ready_array($array_counter = 0, $field_type = '', $custom_fields_id = '') {
        $custom_field_element_array = array();
        $custom_field_element_array['type'] = $field_type;
        if ( !empty($_POST["wp-cardealer-custom-fields-{$field_type}"]) ) {
            foreach ($_POST["wp-cardealer-custom-fields-{$field_type}"] as $field => $value) {
                if ( isset($value[$custom_fields_id]) ) {
                    $custom_field_element_array[$field] = $value[$custom_fields_id];
                } elseif ( isset($value[$array_counter]) ) {
                    $custom_field_element_array[$field] = $value[$array_counter];
                }
            }
        }
        return $custom_field_element_array;
    }

    public static function custom_field_html() {
        $fieldtype = $_POST['fieldtype'];
        $global_custom_field_counter = $_REQUEST['global_custom_field_counter'];
        $li_rand_id = rand(454, 999999);
        $html = '<li class="custom-field-class-' . $li_rand_id . '">';
        $types = self::get_all_field_type_keys();
        if ( in_array($fieldtype, $types) ) {
            if ( in_array( $fieldtype, array('text', 'textarea', 'wysiwyg', 'number', 'url', 'email', 'checkbox') ) ) {
                $html .= apply_filters( 'wp_cardealer_custom_field_text_html', $fieldtype, $global_custom_field_counter, '' );
            } elseif ( in_array( $fieldtype, array('select', 'multiselect', 'radio') ) ) {
                $html .= apply_filters( 'wp_cardealer_custom_field_opts_html', $fieldtype, $global_custom_field_counter, '' );
            } else {
                $html .= apply_filters('wp_cardealer_custom_field_'.$fieldtype.'_html', $fieldtype, $global_custom_field_counter, '');
            }
        }
        // action btns
        $html .= apply_filters('wp_cardealer_custom_field_actions_html', $li_rand_id, $global_custom_field_counter, $fieldtype);
        $html .= '</li>';
        echo json_encode( array('html' => $html) );
        wp_die();
    }

    public static function custom_field_available_html() {
        $prefix = $_REQUEST['prefix'];

        $fieldtype = $_POST['fieldtype'];
        $global_custom_field_counter = $_REQUEST['global_custom_field_counter'];
        $li_rand_id = rand(454, 999999);
        $html = '<li class="custom-field-class-' . $li_rand_id . '">';


        if ( $prefix == WP_CARDEALER_LISTING_PREFIX ) {
            $types = self::get_all_types_fields_available();
        } elseif ( $prefix == WP_CARDEALER_DEALER_PREFIX ) {
            $types = self::get_dealer_all_types_fields_available();
        } elseif ( $prefix == WP_CARDEALER_USER_PREFIX ) {
            $types = self::get_user_all_types_fields_available();
        }

        $dfield_values = self::get_field_id($fieldtype, $types);
        // echo "<pre>".print_r($fieldtype,1)."</pre>";
        if ( !empty($dfield_values) ) {

            $dtypes = apply_filters( 'wp_cardealer_list_simple_type', array( $prefix.'featured', $prefix.'year', $prefix.'address', $prefix.'map_location', $prefix.'price', $prefix.'price_prefix', $prefix.'price_suffix', $prefix.'price_custom', $prefix.'mileage', $prefix.'engine_size', $prefix.'vin', $prefix.'video', $prefix.'first_name', $prefix.'last_name', $prefix.'email', $prefix.'phone', $prefix.'whatsapp', $prefix.'url', $prefix.'hours', $prefix.'tagline' ) );

            if ( in_array( $fieldtype, $dtypes ) ) {
                $html .= apply_filters( 'wp_cardealer_custom_field_available_simple_html', $fieldtype, $global_custom_field_counter, $dfield_values );


            } elseif ( in_array( $fieldtype, apply_filters( 'wp_cardealer_list_tax_type', array($prefix.'condition', $prefix.'label', $prefix.'category', $prefix.'type', $prefix.'color', $prefix.'cylinder', $prefix.'door', $prefix.'drive_type', $prefix.'feature', $prefix.'fuel_type', $prefix.'make', $prefix.'model', $prefix.'offer_type', $prefix.'transmission') ) ) ) {
                
                $html .= apply_filters( 'wp_cardealer_custom_field_available_tax_html', $fieldtype, $global_custom_field_counter, $dfield_values );

            } elseif ( in_array( $fieldtype, apply_filters( 'wp_cardealer_list_tax_feature_type', array( $prefix.'feature' ) ) ) ) {
                
                $html .= apply_filters( 'wp_cardealer_custom_field_available_tax_feature_html', $fieldtype, $global_custom_field_counter, $dfield_values );

            } elseif ( in_array( $fieldtype, apply_filters( 'wp_cardealer_list_file_type', array($prefix.'featured_image', $prefix.'gallery', $prefix.'attachments', $prefix.'photos') ) ) ) {
                
                $html .= apply_filters( 'wp_cardealer_custom_field_available_file_html', $fieldtype, $global_custom_field_counter, $dfield_values );
            
            } else {
                $html .= apply_filters( 'wp_cardealer_custom_field_available_'.$fieldtype.'_html', $fieldtype, $global_custom_field_counter, $dfield_values );
            }
        }

        // action btns
        $html .= apply_filters('wp_cardealer_custom_field_actions_html', $li_rand_id, $global_custom_field_counter, $fieldtype);
        $html .= '</li>';
        echo json_encode(array('html' => $html));
        wp_die();
    }

}

WP_CarDealer_Fields_Manager::init();


