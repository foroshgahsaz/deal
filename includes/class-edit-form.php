<?php
/**
 * Edit Form
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Edit_Form extends WP_CarDealer_Abstract_Form {
	
	public $form_name = 'wp_cardealer_listing_edit_form';
	
	private static $_instance = null;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		add_action( 'wp', array( $this, 'submit_process' ) );

		$this->listing_id = ! empty( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;

		if ( ! WP_CarDealer_User::is_user_can_edit_listing( $this->listing_id ) ) {
			$this->listing_id = 0;
		}

		parent::__construct();
	}

	public function output( $atts = array() ) {
		ob_start();
		$this->form_output();
		$output = ob_get_clean();
		return $output;
	}

	public function submit_process() {
		$prefix = WP_CARDEALER_LISTING_PREFIX;
		if ( ! isset( $_POST['submit-cmb-listing'] ) || empty( $_POST[$prefix.'post_type'] ) || 'listing' !== $_POST[$prefix.'post_type'] ) {
			return;
		}
		
		$cmb = cmb2_get_metabox( $prefix . 'fields_front' );
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
			return;
		}
		// Setup and sanitize data
		if ( isset( $_POST[ $prefix . 'title' ] ) && !empty($this->listing_id) ) {
			$post_id = $this->listing_id;

			$old_post = get_post( $post_id );
			$post_date = $old_post->post_date;
			$old_post_status = get_post_status( $post_id );
			if ( $old_post_status === 'draft' ) {
				$post_status = 'preview';
			} elseif ( $old_post_status === 'publish' ) {
				$review_before = wp_cardealer_get_option( 'user_edit_published_submission' );
				$post_status = 'publish';
				if ( $review_before == 'yes_moderated' ) {
					$post_status = 'pending';
				}
			} else {
				$post_status = $old_post_status;
			}

			$data = array(
				'post_title'     => sanitize_text_field( $_POST[ $prefix . 'title' ] ),
				'post_author'    => get_current_user_id(),
				'post_status'    => $post_status,
				'post_type'      => 'listing',
				'post_date'      => $post_date,
				'post_content'   => wp_kses_post( $_POST[ $prefix . 'description' ] ),
				'ID' 			 => $post_id,
				'comment_status' => 'open'
			);

			do_action( 'wp-cardealer-process-edit-listing-before-save', $post_id, $this );

			$data = apply_filters('wp-cardealer-process-edit-listing-data', $data, $post_id);
			
			$this->errors = $this->edit_validate($data);
			if ( sizeof($this->errors) ) {
				return;
			}
			$post_datas = $_POST;
			$post_id = wp_insert_post( $data, true );
			$_POST = $post_datas;
			if ( ! empty( $post_id ) && ! empty( $_POST['object_id'] ) ) {

				$_POST['object_id'] = $post_id; // object_id in POST contains page ID instead of listing ID

				$cmb->save_fields( $post_id, 'post', $_POST );

				// Create featured image
				$featured_image = get_post_meta( $post_id, $prefix . 'featured_image', true );
				if ( ! empty( $_POST[ 'current_' . $prefix . 'featured_image' ] ) ) {
					$img_id = get_post_meta( $post_id, $prefix . 'featured_image_img', true );
					if ( !empty($featured_image) ) {
						if ( is_array($featured_image) ) {
							$img_id = $featured_image[0];
						} elseif ( is_integer($featured_image) ) {
							$img_id = $featured_image;
						} else {
							$img_id = WP_CarDealer_Image::get_attachment_id_from_url($featured_image);
						}
						set_post_thumbnail( $post_id, $img_id );
					} else {
						update_post_meta( $post_id, $prefix . 'featured_image', null );
						delete_post_thumbnail( $post_id );
					}
				} else {
					update_post_meta( $post_id, $prefix . 'featured_image', null );
					delete_post_thumbnail( $post_id );
				}

				do_action( 'wp-cardealer-process-edit-listing-after-save', $post_id );
				
				// send email
				if ( wp_cardealer_get_option('admin_notice_updated_listing') ) {
					$listing = get_post($this->listing_id);
					$email_from = get_option( 'admin_email', false );
					
					$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email_from, $email_from );
					$email_to = get_option( 'admin_email', false );
					$subject = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_updated_listing', 'subject');
					$content = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_updated_listing', 'content');
					
					WP_CarDealer_Email::wp_mail( $email_to, $subject, $content, $headers );
				}
				$this->success_msg[] = __( 'Your changes have been saved.', 'wp-cardealer' );
			} else {
				$this->errors[] = __( 'Can not update listing', 'wp-cardealer' );
			}
		}

		return;
	}

	public function edit_validate( $data ) {
		$error = array();
		if ( empty($data['post_author']) ) {
			$error[] = __( 'Please login to submit listing', 'wp-cardealer' );
		}
		if ( empty($data['post_title']) ) {
			$error[] = __( 'Title is required.', 'wp-cardealer' );
		}
		if ( empty($data['post_content']) ) {
			$error[] = __( 'Description is required.', 'wp-cardealer' );
		}

		$error = apply_filters('wp-cardealer-edit-validate', $error);

		return $error;
	}

}

function wp_cardealer_edit_form() {
	if ( ! empty( $_POST['wp_cardealer_listing_edit_form'] ) ) {
		WP_CarDealer_Edit_Form::get_instance();
	}
}

add_action( 'init', 'wp_cardealer_edit_form' );