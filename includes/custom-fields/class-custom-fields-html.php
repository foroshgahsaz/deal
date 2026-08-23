<?php
/**
 * Custom Field HTML
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if (!defined('ABSPATH')) {
    die;
}

// main plugin class
class WP_CarDealer_CustomFieldHTML {
    
    public static $packages;
    

    public static function init() {
        add_filter('wp_cardealer_custom_field_text_html', array(__CLASS__, 'field_text_html_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_date_html', array(__CLASS__, 'field_text_html_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_opts_html', array(__CLASS__, 'field_opts_html_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_file_html', array(__CLASS__, 'field_file_html_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_heading_html', array(__CLASS__, 'field_heading_html_callback'), 1, 3);

        // available fields
        add_filter('wp_cardealer_custom_field_available_simple_html', array(__CLASS__, 'field_available_simple_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_available_tax_html', array(__CLASS__, 'field_available_tax_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_available_tax_feature_html', array(__CLASS__, 'field_available_tax_feature_callback'), 1, 3);

        add_filter('wp_cardealer_custom_field_available_file_html', array(__CLASS__, 'field_available_file_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_available_files_html', array(__CLASS__, 'field_available_files_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_available_description_html', array(__CLASS__, 'field_available_listing_description_callback'), 1, 3);
        add_filter('wp_cardealer_custom_field_available_select_option_html', array(__CLASS__, 'field_available_listing_select_option_callback'), 1, 3);
        // actions
        add_filter('wp_cardealer_custom_field_actions_html', array(__CLASS__, 'field_actions_html_callback'), 1, 4);
    }
    
    public static function yes_no_opts(){
        return array(
            '' => __('No', 'wp-cardealer'),
            'yes' => __('Yes', 'wp-cardealer'),
        );
    }

    public static function get_packages() {
        if ( empty(self::$packages) ) {
            $query_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page'   => -1,
                'order'            => 'asc',
                'orderby'          => 'menu_order',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => apply_filters( 'wp-cardealer-packages-type', array('listing_package') ),
                    ),
                ),
            );
            $packages = get_posts( $query_args );
            $return = array();
            foreach ($packages as $package) {
                $return[$package->ID] = $package->post_title;
            }
            self::$packages = $return;
        }
        return self::$packages;
    }
    
    public static function field_text_html_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = isset($field_data['id']) ? $field_data['id'] : 'custom-'.$type.'-'.$field_counter;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';
        

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>

            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />

                <?php
                    self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                    self::text( $prefix.'[id][]', esc_html__('Key', 'wp-cardealer'), $id_val, '', 'wp-cardealer-custom-field-key');
                    self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                    self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                    
                    // self::checkbox( $prefix.'[show_in_submit_form][]', esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val);
                    // self::checkbox( $prefix.'[show_in_admin_edit][]', esc_html__('Show in admin edit page', 'wp-cardealer'), $show_in_admin_edit_val);

                    self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                    self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                    // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                    self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                    // packages
                    $packages = self::get_packages();
                    if ( $packages ) {
                        $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                        $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                        self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                        self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                    }
                    // hook
                    $hook_display = isset($field_data['hook_display']) ? $field_data['hook_display'] : '';
                    $opts = WP_CarDealer_Fields_Manager::get_display_hooks();
                    self::select( $prefix.'[hook_display][]', $opts, esc_html__('Position Display', 'wp-cardealer'), $hook_display, '', false, true);

                    do_action('wp_cardealer_custom_field_text_html_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_opts_html_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = isset($field_data['id']) ? $field_data['id'] : 'custom-'.$type.'-'.$field_counter;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $text_field_options = stripslashes(isset($field_data['options']) ? $field_data['options'] : '');

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>

            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />

                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[id][]', esc_html__('Key', 'wp-cardealer'), $id_val, '', 'wp-cardealer-custom-field-key');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                self::textarea( $prefix.'[options][]', esc_html__('Options', 'wp-cardealer'), $text_field_options, esc_html__('Add each option in a new line.', 'wp-cardealer'));

                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // self::checkbox( $prefix.'[show_in_submit_form][]', esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val);
                // self::checkbox( $prefix.'[show_in_admin_edit][]', esc_html__('Show in admin edit page', 'wp-cardealer'), $show_in_admin_edit_val);

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                // hook
                $hook_display = isset($field_data['hook_display']) ? $field_data['hook_display'] : '';
                $opts = WP_CarDealer_Fields_Manager::get_display_hooks();
                self::select( $prefix.'[hook_display][]', $opts, esc_html__('Position Display', 'wp-cardealer'), $hook_display, '', false, true);

                do_action('wp_cardealer_custom_field_opts_html_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_heading_html_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $label_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $key_val = isset($field_data['id']) ? $field_data['id'] : 'custom-'.$type.'-'.$field_counter;

        $text_field_icon = isset($field_data['icon']) ? $field_data['icon'] : '';

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-heading-container">
            <?php self::header_html($type, $rand, $label_val); ?>
            
            <div class="field-data form-group-wrapper" id="heading-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="heading" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $label_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[id][]', esc_html__('Key', 'wp-cardealer'), $key_val, '', 'wp-cardealer-custom-field-key');
                ?>

                <div class="form-group">
                    <label>
                        <?php echo esc_html__('Icon', 'wp-cardealer'); ?>:
                    </label>
                    <div class="input-field">
                        <?php
                        $icon_id = rand(1000000, 99999999);

                        echo self::icon_picker($text_field_icon, $icon_id, $prefix.'[icon][]');
                        ?>
                    </div>
                </div>


                <?php
                    $number_column_val = isset($field_data['number_columns']) ? $field_data['number_columns'] : '1';
                    $columns = array(
                        '1' => __('1 Column', 'wp-cardealer'),
                        '2' => __('2 Column', 'wp-cardealer'),
                        '3' => __('3 Column', 'wp-cardealer'),
                        '4' => __('4 Column', 'wp-cardealer'),
                    );
                    self::select( $prefix.'[number_columns][]', $columns, esc_html__('Columns Inner', 'wp-cardealer'), $number_column_val, '', false);

                    // packages
                    $packages = self::get_packages();
                    if ( $packages ) {
                        $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                        $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                        self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                        self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                    }

                    do_action('wp_cardealer_custom_field_heading_html_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_file_html_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = isset($field_data['id']) ? $field_data['id'] : 'custom-'.$type.'-'.$field_counter;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $file_limit = isset($field_data['file_limit']) ? $field_data['file_limit'] : 5;

        $text_field_multiple_files = isset($field_data['multiple_files']) ? $field_data['multiple_files'] : '';
        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $text_field_allow_types = isset($field_data['allow_types']) ? $field_data['allow_types'] : (isset($field_data['mime_types']) ? $field_data['mime_types'] : '');

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>

            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />

                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[id][]', esc_html__('Key', 'wp-cardealer'), $id_val, '', 'wp-cardealer-custom-field-key');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);

                self::number( $prefix.'[file_limit][]', esc_html__('File limit', 'wp-cardealer'), $file_limit);

                $mime_types = get_allowed_mime_types();
                self::select( $prefix.'[allow_types]['.$field_counter.'][]', $mime_types, esc_html__('Allowed file types', 'wp-cardealer'), $text_field_allow_types, '', true);

                self::checkbox( $prefix.'[multiple_files][]', esc_html__('Multiple files', 'wp-cardealer'), $text_field_multiple_files);
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                // hook
                $hook_display = isset($field_data['hook_display']) ? $field_data['hook_display'] : '';
                $opts = WP_CarDealer_Fields_Manager::get_display_hooks();
                self::select( $prefix.'[hook_display][]', $opts, esc_html__('Position Display', 'wp-cardealer'), $hook_display, '', false, true);

                do_action('wp_cardealer_custom_field_file_html_callback');
                ?>
                
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_simple_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Available Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_simple_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_tax_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $text_field_select_type = isset($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);

                $opts = array(
                    'pw_taxonomy_select' => esc_html__('Term Select', 'wp-cardealer'),
                    'pw_taxonomy_multiselect' => esc_html__('Term Multiselect', 'wp-cardealer'),
                    'taxonomy_multicheck' => esc_html__('Term Checklist', 'wp-cardealer'),
                    'taxonomy_radio' => esc_html__('Term Radio Button', 'wp-cardealer'),
                );
                self::select( $prefix.'[select_type][]', $opts, esc_html__('Template', 'wp-cardealer'), $text_field_select_type);
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_tax_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_tax_feature_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $text_field_select_type = isset($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);

                $opts = array(
                    'pw_taxonomy_select_parent' => esc_html__('Term Select', 'wp-cardealer'),
                    'pw_taxonomy_multiselect_parent' => esc_html__('Term Multiselect', 'wp-cardealer'),
                    'pw_taxonomy_checkbox_parent' => esc_html__('Term Checklist', 'wp-cardealer'),
                );
                self::select( $prefix.'[select_type][]', $opts, esc_html__('Template', 'wp-cardealer'), $text_field_select_type);
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_tax_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_file_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Available Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $file_limit = isset($field_data['file_limit']) ? $field_data['file_limit'] : 5;

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $text_field_allow_types = isset($field_data['allow_types']) ? $field_data['allow_types'] : (isset($field_data['mime_types']) ? $field_data['mime_types'] : '');

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                
                self::number( $prefix.'[limit_file][]', esc_html__('Limit Files', 'wp-cardealer'), $file_limit);

                $mime_types = get_allowed_mime_types();
                
                self::select( $prefix.'[allow_types]['.$field_counter.'][]', $mime_types, esc_html__('Allowed file types', 'wp-cardealer'), $text_field_allow_types, '', true);

                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_file_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_files_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Available Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $file_limit = isset($field_data['file_limit']) ? $field_data['file_limit'] : 5;

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $text_field_allow_types = isset($field_data['allow_types']) ? $field_data['allow_types'] : (isset($field_data['mime_types']) ? $field_data['mime_types'] : '');

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                
                self::number( $prefix.'[file_limit][]', esc_html__('File limit', 'wp-cardealer'), $file_limit);

                $mime_types = get_allowed_mime_types();
                self::select( $prefix.'[allow_types]['.$field_counter.'][]', $mime_types, esc_html__('Allowed file types', 'wp-cardealer'), $text_field_allow_types, '', true);

                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_file_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_listing_description_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Custom Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $text_field_select_type = isset($field_data['select_type']) ? $field_data['select_type'] : 'textarea';

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;
        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />

                <?php

                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);

                $opts = array(
                    'textarea' => esc_html__('Textarea', 'wp-cardealer'),
                    'wysiwyg' => esc_html__('WP Editor', 'wp-cardealer'),
                );
                self::select( $prefix.'[select_type][]', $opts, esc_html__('Template', 'wp-cardealer'), $text_field_select_type);
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_listing_description_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_available_listing_select_option_callback($type, $field_counter, $field_data) {
        ob_start();
        $rand = $field_counter;

        $name_val = stripslashes(isset($field_data['name']) ? $field_data['name'] : 'Available Field');
        $id_val = $type;
        $placeholder_val = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description_val = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $text_field_options = stripslashes(isset($field_data['options']) ? $field_data['options'] : '');

        $required_val = isset($field_data['required']) ? $field_data['required'] : '';
        $show_in_submit_form_val = isset($field_data['show_in_submit_form']) ? $field_data['show_in_submit_form'] : 'yes';
        $show_in_admin_edit_val = isset($field_data['show_in_admin_edit']) ? $field_data['show_in_admin_edit'] : 'yes';

        $disable_check_val = isset($field_data['disable_check']) ? $field_data['disable_check'] : false;

        $prefix = 'wp-cardealer-custom-fields-'.$type;
        ?>
        <div class="wp-cardealer-custom-field-container wp-cardealer-custom-field-<?php echo esc_attr($type); ?>-container">
            <?php self::header_html($type, $rand, $name_val); ?>
            <?php self::hidden( $prefix.'[id][]', $id_val, 'wp-cardealer-custom-field-key'); ?>
            <div class="field-data form-group-wrapper" id="<?php echo esc_attr($type); ?>-field-wraper<?php echo esc_html($rand); ?>" style="display:none;">
                <input type="hidden" name="wp-cardealer-custom-fields-type[]" value="<?php echo esc_attr($type); ?>" />
                <input type="hidden" name="wp-cardealer-custom-fields-id[]" value="<?php echo esc_html($field_counter); ?>" />
                
                <?php
                self::text( $prefix.'[name][]', esc_html__('Label', 'wp-cardealer'), $name_val, '', 'wp-cardealer-custom-field-label');
                self::text( $prefix.'[placeholder][]', esc_html__('Placeholder', 'wp-cardealer'), $placeholder_val);
                self::text( $prefix.'[description][]', esc_html__('Description', 'wp-cardealer'), $description_val);
                self::textarea( $prefix.'[options][]', esc_html__('Options', 'wp-cardealer'), $text_field_options, esc_html__('Add each option in a new line.', 'wp-cardealer'));
                self::select( $prefix.'[show_in_submit_form][]', self::yes_no_opts(), esc_html__('Show in submit form', 'wp-cardealer'), $show_in_submit_form_val );
                self::select( $prefix.'[show_in_admin_edit][]', self::yes_no_opts(), esc_html__('Show in admin form', 'wp-cardealer'), $show_in_admin_edit_val );

                // self::checkbox( $prefix.'[required][]', esc_html__('Required', 'wp-cardealer'), $required_val);
                self::select( $prefix.'[required][]', self::yes_no_opts(), esc_html__('Required', 'wp-cardealer'), $required_val, '', false, false );

                // packages
                $packages = self::get_packages();
                if ( $packages ) {
                    $show_in_package = isset($field_data['show_in_package']) ? $field_data['show_in_package'] : '';
                    $package_display = isset($field_data['package_display']) ? $field_data['package_display'] : '';
                    self::checkbox( $prefix.'[show_in_package][]', esc_html__('Enable package visibility', 'wp-cardealer'), $show_in_package, false, true, 'show_in_package');
                    self::select( $prefix.'[package_display]['.$field_counter.'][]', $packages, esc_html__('Packages', 'wp-cardealer'), $package_display, esc_html__('Choose Packages to show this field insubmit form.', 'wp-cardealer'), true, true, 'show_if_show_in_package');
                }

                do_action('wp_cardealer_custom_field_available_simple_callback');
                ?>

            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function field_actions_html_callback($li_rand, $rand, $field_type, $delete = true) {
        ob_start();
        ?>
        <div class="actions">
            <a href="javascript:void(0);" class="custom-fields-edit <?php echo esc_attr($field_type); ?>-field<?php echo esc_attr($rand); ?>" ><i  class="dashicons dashicons-edit" aria-hidden="true"></i></a>
            <?php if ($delete) { ?>
                <a href="javascript:void(0);" class="custom-fields-remove" data-randid="<?php echo esc_attr($li_rand) ?>" data-fieldtype="<?php echo esc_attr($field_type); ?>"><i  class="dashicons dashicons-trash" aria-hidden="true"></i></a>
            <?php } ?>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public static function header_html($type, $rand, $label_val) {
        ?>
        <div class="field-intro">
            <?php $field_dyn_name = $label_val != '' ? '<b>(' . $label_val . ')</b>' : '' ?>
            <a href="javascript:void(0);" class="<?php echo esc_attr($type); ?>-field<?php echo esc_attr($rand); ?>" >
                <?php echo wp_kses(sprintf(__('%s Field %s', 'wp-cardealer'), $type, $field_dyn_name), array('b' => array())); ?>
            </a>
        </div>
        <?php
    }

    public static function text($name, $title = '', $value = '', $desc = '', $inputclass = '', $fullwidth = false) {
        ?>
        <div class="form-group <?php echo esc_attr($fullwidth ? 'fullwidth' : ''); ?>">
            <label><?php echo $title; ?></label>
            <div class="input-field">
                <input type="text" name="<?php echo $name ;?>" value="<?php echo esc_attr($value); ?>" <?php echo trim($inputclass ? 'class="'.$inputclass.'"' : ''); ?>/>
                <?php if ( !empty($desc) ) { ?>
                    <span class="desc"><?php echo $desc; ?></span>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    public static function number($name, $title = '', $value = '', $desc = '', $inputclass = '', $fullwidth = false) {
        ?>
        <div class="form-group <?php echo esc_attr($fullwidth ? 'fullwidth' : ''); ?>">
            <label><?php echo $title; ?></label>
            <div class="input-field">
                <input type="number" name="<?php echo $name ;?>" value="<?php echo esc_attr($value); ?>" <?php echo trim($inputclass ? 'class="'.$inputclass.'"' : ''); ?>/>
                <?php if ( !empty($desc) ) { ?>
                    <span class="desc"><?php echo $desc; ?></span>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    public static function hidden($name, $value = '', $inputclass = '') {
        ?>
        <input type="hidden" name="<?php echo $name ;?>" value="<?php echo esc_attr($value); ?>" <?php echo trim($inputclass ? 'class="'.$inputclass.'"' : ''); ?>/>
        <?php
    }

    public static function textarea($name, $title = '', $value = '', $desc = '', $fullwidth = false) {
        ?>
        <div class="form-group <?php echo esc_attr($fullwidth ? 'fullwidth' : ''); ?>">
            <label><?php echo $title; ?></label>
            <div class="input-field">
                <textarea name="<?php echo $name ;?>"><?php echo esc_html($value); ?></textarea>
                <?php if ( !empty($desc) ) { ?>
                    <span class="desc"><?php echo $desc; ?></span>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    public static function checkbox($name, $title = '', $value = '', $disabled = false, $fullwidth = true, $inputclass = '') {
        ?>
        <div class="form-group <?php echo esc_attr($fullwidth ? 'fullwidth' : ''); ?>">
            <label>
                <input <?php echo trim($inputclass ? 'class="'.$inputclass.'"' : ''); ?> type="checkbox" name="<?php echo $name ;?>" value="yes" <?php echo ($value == 'yes' ? 'checked="checked"' : ''); ?> <?php echo ($disabled ? 'readonly="readonly"' : ''); ?>/>
                <?php echo $title; ?>
            </label>
        </div>
        <?php
    }

    public static function select($name, $opts, $title = '', $values = '', $desc = '', $multiple = false, $fullwidth = false, $wrapperclass = '') {
        if ( !is_array($values) ) {
            $values = array($values);
        }
        ?>
        <div class="form-group <?php echo esc_attr($fullwidth ? 'fullwidth' : ''); ?> <?php echo trim($wrapperclass ? $wrapperclass : ''); ?>">
            <label><?php echo $title; ?></label>
            <div class="input-field">
                <select name="<?php echo $name ;?>" <?php echo ($multiple ? 'multiple="multiple"' : ''); ?>>
                    <?php
                    if ( !empty($opts) && is_array($opts) ) {
                        $opts = self::sort_array_by_array( $opts, $values );
                        foreach ($opts as $key => $text) { ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php self::selected($key, $values); ?>><?php echo $text; ?></option>
                        <?php }
                    } ?>
                </select>
                <?php if ( !empty($desc) ) { ?>
                    <span class="desc"><?php echo $desc; ?></span>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    public static function selected($val, $defaults) {
        if ( is_array($defaults) ) {
            if ( in_array($val, $defaults) ) {
                echo 'selected="selected"';
            }
        } else {
            if ( $val == $defaults ) {
                echo 'selected="selected"';
            }
        }
    }

    public static function icon_picker($value = '', $id = '', $name = '', $class = 'wp-cardealer-icon-pickerr') {
        $html = "
        <script>
        jQuery(document).ready(function ($) {
            setTimeout(function(){
                var e9_element = $('#icon_picker_".$id."').fontIconPicker({
                    theme: 'fip-bootstrap',
                    source: wp_cardealer_all_loaded_icons
                });
            }, 100);
        });
        </script>";

        $html .= '<input type="text" id="icon_picker_' . $id . '" class="' . $class . '" name="' . $name . '" value="' . $value . '">';

        return $html;
    }

    public static function sort_array_by_array( array $array, array $orderArray ) {
        $ordered = array();

        foreach ( $orderArray as $key ) {
            if ( array_key_exists( $key, $array ) ) {
                $ordered[ $key ] = $array[ $key ];
                unset( $array[ $key ] );
            }
        }

        return $ordered + $array;
    }
}

WP_CarDealer_CustomFieldHTML::init();