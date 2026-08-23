<?php
/**
 * Social: LinkedIn
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_CarDealer_Social_Linkedin {
    
    const _AUTHORIZE_URL = 'https://www.linkedin.com/uas/oauth2/authorization';

    const _TOKEN_URL = 'https://www.linkedin.com/uas/oauth2/accessToken';

    const _BASE_URL = 'https://api.linkedin.com/v2/me';

    // LinkedIn Application Key
    public $li_api_key;

    // LinkedIn Application Secret
    public $li_secret_key;

    // Stores Access Token
    public $access_token;

    // Stores OAuth Object
    public $oauth;

    // Stores the user redirect after login
    public $user_redirect = false;

    private $redirect_url = '';

    private $linkedin_user_datas;

    private $linkedin_user_email_datas;

    // Stores our LinkedIn options 
    public $li_options;

    private static $_instance = null;

    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->li_api_key = wp_cardealer_get_option( 'linkedin_api_client_id' );
        $this->li_secret_key = wp_cardealer_get_option( 'linkedin_api_client_secret' );

        if ( $this->is_linkedin_login_enabled() && !is_user_logged_in() ) {
            if (!session_id()) {
                session_start();
            }
            $this->li_options = array(
                'li_cancel_redirect_url' => '',
                'li_redirect_url' => '',
                'li_auto_profile_update' => '',
                'li_registration_redirect_url' => '',
                'li_logged_in_message' => '',
            );

            require_once WP_CARDEALER_PLUGIN_DIR . 'libraries/linkedin/linkedin_oauth2.class.php';

            // Create new Oauth client
            $this->oauth = new WP_CarDealer_OAuth2Client($this->li_api_key, $this->li_secret_key);

            // Set Oauth URLs
            $this->oauth->redirect_uri = home_url('/') . '?action=linkedin_login';
            $this->oauth->authorize_url = self::_AUTHORIZE_URL;
            $this->oauth->token_url = self::_TOKEN_URL;
            $this->oauth->api_base_url = self::_BASE_URL;

            // Set user token if user is logged in
            if (get_current_user_id()) {
                $this->oauth->access_token = get_user_meta(get_current_user_id(), 'wp_cardealer_access_token', true);
            }

            $this->process_linkedin_login();

            add_action( 'login_form', array( $this, 'display_login_btn') );
            
        }
    }

    public function is_linkedin_login_enabled() {
        if ( wp_cardealer_get_option('enable_linkedin_login') && ! empty( $this->li_api_key ) && ! empty( $this->li_secret_key ) ) {
            return true;
        }

        return false;
    }

    public function process_linkedin_login() {
        // If this is not a linkedin sign-in request, do nothing
        if (!$this->is_linkedin_signin()) {
            return;
        }

        // If this is a user sign-in request, but the user denied granting access, redirect to login URL
        if (isset($_REQUEST['error']) && $_REQUEST['error'] == 'access_denied') {

            // Get our cancel redirect URL
            $cancel_redirect_url = $this->li_options['li_cancel_redirect_url'];

            // Redirect to login URL if left blank
            if (empty($cancel_redirect_url)) {
                wp_redirect(home_url('/'));
            }

            // Redirect to our given URL
            wp_safe_redirect($cancel_redirect_url);
        }

        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        // Another error occurred, create an error log entry
        if (isset($_REQUEST['error'])) {
            $error = $_REQUEST['error'];
            $error_description = $_REQUEST['error_description'];
            error_log("WP LinkedIn Login Error\nError: $error\nDescription: $error_description");
        }

        // Get profile XML response
        $xml = $this->get_linkedin_profile();
        $xml = json_decode($xml, true);

        $email_xml = $this->get_linkedin_profile_email();
        $email_xml = json_decode($email_xml, true);

        if ($scope_type == 'openid') {
            if (!is_array($xml) || !isset($xml['given_name'])) {
                return false;
            }
        } else {
            if (!is_array($xml) || !isset($xml['id'])) {
                return false;
            }
        }

        $this->linkedin_user_datas = $xml;
        $this->linkedin_user_email_datas = $email_xml;

        // We first try to login the user
        $this->login_user();

        // Otherwise, we create a new account
        $this->create_user();
        
        if ( empty($this->redirect_url) ) {
            $user_dashboard_page_id = wp_cardealer_get_option('user_dashboard_page_id');
            $this->redirect_url = $user_dashboard_page_id > 0 ? get_permalink($user_dashboard_page_id) : home_url('/');
        }

        WP_CarDealer_Mixes::redirect($this->redirect_url);
    }

    private function is_linkedin_signin() {
        
        // If no action is requested or the action is not ours
        if (!isset($_REQUEST['action']) || ($_REQUEST['action'] != "linkedin_login")) {
            return false;
        }

        // If a code is not returned, and no error as well, then OAuth did not proceed properly
        if (!isset($_REQUEST['code']) && !isset($_REQUEST['error'])) {
            return false;
        }

        /*
         * Temporarily disabled this because we're getting two different states at random times

          // If state is not set, or it is different than what we expect there might be a request forgery
          if ( ! isset($_SESSION['li_api_state'] ) || $_REQUEST['state'] != $_SESSION['li_api_state']) {
          return false;
          }
         */

        // This is a LinkedIn signing-request - unset state and return true
        unset($_SESSION['li_api_state']);

        return true;
    }

    private function get_linkedin_profile() {

        global $wp_cardealer_openid_userprofile_data;

        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        // Use GET method since POST isn't working
        $this->oauth->curl_authenticate_method = 'GET';

        // Request access token
        $response = $this->oauth->authenticate($_REQUEST['code']);
        if ($response) {
            $this->access_token = $response->{'access_token'};
        }

        if ($scope_type == 'openid') {
            $firstLastName = wp_remote_get('https://api.linkedin.com/v2/userinfo', array(
                    'method' => 'GET',
                    'timeout' => 15,
                    'headers' => array('Authorization' => "Bearer ".$response->access_token),
                )
            );
            if(!is_wp_error($firstLastName) && isset($firstLastName['response']['code']) && 200 === $firstLastName['response']['code']){
                $xml = wp_remote_retrieve_body($firstLastName);
                $wp_cardealer_openid_userprofile_data = $xml;
            }
        } else {
            // Get first name, last name and email address, and load 
            // response into XML object
            $xml = ($this->oauth->get('https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))'));
        }

        return $xml;
    }

    private function get_linkedin_profile_email() {

        global $wp_cardealer_openid_userprofile_data;
        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        // Use GET method since POST isn't working
        $this->oauth->curl_authenticate_method = 'GET';

        // Request access token
        $response = $this->oauth->authenticate($_REQUEST['code']);
        
        if ($response) {
            $this->access_token = $response->{'access_token'};
        }

        if ($scope_type == 'openid') {
            $xml = $wp_cardealer_openid_userprofile_data;
        } else {
            $xml = ($this->oauth->get('https://api.linkedin.com/v2/clientAwareMemberHandles?q=members&projection=(elements*(primary,type,handle~))'));
        }
        
        return $xml;
    }

    private function login_user() {
        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        $linkedin_user_data = $this->linkedin_user_datas;
        if ($scope_type == 'openid') {
            $user_id = isset($linkedin_user_data['email']) ? $linkedin_user_data['email'] : '';
        } else {
            $user_id = isset($linkedin_user_data['id']) ? $linkedin_user_data['id'] : '';
        }
        
        // We look for the `eo_linkedin_id` to see if there is any match
        $wp_users = get_users(array(
            'meta_key' => 'wp_cardealer_linkedin_id',
            'meta_value' => $user_id,
            'number' => 1,
            'count_total' => false,
            'fields' => 'ids',
        ));

        if (empty($wp_users[0])) {
            return false;
        }

        wp_set_auth_cookie($wp_users[0]);

        do_action('wp_cardealer_after_linkedin_login', $wp_users[0]);

        WP_CarDealer_Mixes::redirect($this->redirect_url);
    }

    private function create_user() {
        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        $linkedin_user = $this->linkedin_user_datas;
        $linkedin_user_email = $this->linkedin_user_email_datas;
        
        $pitcure_url    = '';

        if ($scope_type == 'openid') {
            $pitcure_url = isset($linkedin_user_data['picture']) ? $linkedin_user_data['picture'] : '';
        } else {
            $img_array = isset($linkedin_user_email['profilePicture']['displayImage~']['elements']) ? $linkedin_user_email['profilePicture']['displayImage~']['elements'] : '';
            $pitcure_url = isset($img_array[3]['identifiers'][0]['identifier']) ? $img_array[3]['identifiers'][0]['identifier'] : '';
            if ($pitcure_url == '') {
                $pitcure_url = isset($img_array[2]['identifiers'][0]['identifier']) ? $img_array[3]['identifiers'][0]['identifier'] : '';
            }
            if ($pitcure_url == '') {
                $pitcure_url = isset($img_array[1]['identifiers'][0]['identifier']) ? $img_array[3]['identifiers'][0]['identifier'] : '';
            }
            if ($pitcure_url == '') {
                $pitcure_url = isset($img_array[0]['identifiers'][0]['identifier']) ? $img_array[3]['identifiers'][0]['identifier'] : '';
            }
        }

        if (!empty($pitcure_url)) {
            $pitcure_url = esc_url_raw($pitcure_url);

            $is_valid_domain = $this->is_valid_domain($pitcure_url);
            if (!$is_valid_domain) {
                $pitcure_url = '';
            }            
        }

        if ($scope_type == 'openid') {
            $linkedin_user_id = isset($linkedin_user_data['email']) ? $linkedin_user_data['email'] : '';
        } else {
            $linkedin_user_id = isset($linkedin_user_data['id']) ? $linkedin_user_data['id'] : '';
        }

        $first_name = $last_name = '';
        if ($scope_type == 'openid') {
            $first_name = isset($linkedin_user_data['given_name']) ? $linkedin_user_data['given_name'] : '';
            $last_name = isset($linkedin_user_data['family_name']) ? $linkedin_user_data['family_name'] : '';
            $email = isset($linkedin_user_data['email']) ? $linkedin_user_data['email'] : '';
        } else {
            if (!empty($linkedin_user['firstName']['localized'])) {
                foreach ($linkedin_user['firstName']['localized'] as $value) {
                    $first_name = $value;
                }
            }
            if (!empty($linkedin_user['lastName']['localized'])) {
                foreach ($linkedin_user['lastName']['localized'] as $value) {
                    $last_name = $value;
                }
            }

            $email = isset($linkedin_user_email['elements'][0]['handle~']['emailAddress']) ? $linkedin_user_email['elements'][0]['handle~']['emailAddress'] : '';
        }

        $_social_user_obj = get_user_by('email', $email);
        if (is_object($_social_user_obj) && isset($_social_user_obj->ID)) {
            update_user_meta($_social_user_obj->ID, 'wp_cardealer_linkedin_id', $linkedin_user_id);
            $this->login_user();
        }

        if ($first_name != '' && $last_name != '') {
            $name = $first_name . '_' . $last_name;
            $name = str_replace(array(' '), array('_'), $name);
            $username = sanitize_user(str_replace(' ', '_', strtolower($name)));
        } else {
            $username = $email;
        }

        if (username_exists($username)) {
            $username .= '_' . rand(10000, 99999);
        }

        ///
        $userdata = array(
            'user_login' => sanitize_user( $username ),
            'user_email' => sanitize_email( $email ),
            'user_pass' => wp_generate_password(),
        );
        $userdata = apply_filters('wp-cardealer-linkedin-login-userdata', $userdata, $linkedin_user);


        // Creating our user
        $user_id = wp_insert_user( $userdata );
        if ( ! is_wp_error( $user_id ) ) {
            
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'wp_cardealer_linkedin_id', $linkedin_user_id);
            update_user_meta($user_id, 'wp_cardealer_access_token', $this->access_token, true);

            if ($pitcure_url != '') {
                $candidate_id = WP_CarDealer_User_User::get_candidate_by_user_id($user_id);
                WP_CarDealer_User_Image::upload_attach_with_external_url($pitcure_url, $candidate_id);
            }

            do_action('wp_cardealer_after_linkedin_login', $user_id);

            wp_set_auth_cookie($user_id);
        } else {
            set_transient('wp_cardealer_linkedin_message', $user_id->get_error_message(), 60 * 60 * 24 * 30);
            echo $user_id->get_error_message();
            die;
        }
    }

    public function is_valid_domain($url) {
        $url = (false === strpos($url, '://')) ? 'http://' . $url : $url;    
        $headers = @get_headers($url);    
        return $headers && isset($headers[0]) && strpos($headers[0], '200') !== false;
    }

    // Returns LinkedIn login URL
    public function get_login_url($redirect = false) {
        $scope_type = wp_cardealer_get_option('linkedin_login_type', '');

        $scope_str = 'r_liteprofile r_emailaddress';
        if ($scope_type == 'openid') {
            $scope_str = 'openid,profile,email';
        }

        $state = wp_generate_password(12, false);
        $authorize_url = $this->oauth->authorizeUrl(array('scope' => $scope_str, 'state' => $state));

        // Store state in database in temporarily till checked back
        if (!isset($_SESSION['li_api_state'])) {
            $_SESSION['li_api_state'] = $state;
        }

        // Store redirect URL in session
        $_SESSION['li_api_redirect'] = $redirect;

        return $authorize_url;
    }

    public function display_message() {
        if ( get_transient('wp_cardealer_linkedin_message') ) {
            $message = get_transient('wp_cardealer_linkedin_message');
            echo '<div class="alert alert-danger linkedin-message">' . $message . '</div>';
            delete_transient('wp_cardealer_linkedin_message');
        }
    }

    public function display_login_btn() {
        if ( is_user_logged_in() ) {
            return;
        }
        ob_start();
        $this->display_message();
        ?>
        <div class="linkedin-login-btn-wrapper">
            <a class="linkedin-login-btn" href="<?php echo esc_url($this->get_login_url()); ?>"><i class="fab fa-linkedin-in"></i> <?php esc_html_e('Login with LinkedIn', 'wp-cardealer'); ?></a>
        </div>
        <?php
        $output = ob_get_clean();
        echo apply_filters('wp-cardealer-linkedin-login-btn', $output, $this);
    }

}


function wp_cardealer_social_linkedin() {
    WP_CarDealer_Social_Linkedin::get_instance();
}
add_action( 'init', 'wp_cardealer_social_linkedin' );