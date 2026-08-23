<?php
/**
 * Submit Form
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Submit_Form extends WP_CarDealer_Abstract_Form {
	public $form_name = 'wp_cardealer_listing_submit_form';
	

	private static $_instance = null;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		add_action( 'wp', array( $this, 'process' ) );

		$this->get_steps();

		if ( !empty( $_REQUEST['submit_step'] ) ) {
			$step = is_numeric( $_REQUEST['submit_step'] ) ? max( absint( $_REQUEST['submit_step'] ), 0 ) : array_search( intval( $_REQUEST['submit_step'] ), array_keys( $this->steps ), true );
			$this->step = $step;
		}

		$this->listing_id = ! empty( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;

		if ( ! WP_CarDealer_User::is_user_can_edit_listing( $this->listing_id ) ) {
			$this->listing_id = 0;
		}

		do_action('wp-cardealer-submit-listing-construct', $this);

		add_filter( 'cmb2_meta_boxes', array( $this, 'fields_front' ) );
	}

	public function get_steps() {
		$this->steps = apply_filters( 'wp_cardealer_submit_listing_steps', array(
			'submit'  => array(
				'view'     => array( $this, 'form_output' ),
				'handler'  => array( $this, 'submit_process' ),
				'priority' => 10,
			),
			'preview' => array(
				'view'     => array( $this, 'preview_output' ),
				'handler'  => array( $this, 'preview_process' ),
				'priority' => 20,
			),
			'done'    => array(
				'before_view' => array( $this, 'done_handler' ),
				'view'     => array( $this, 'done_output' ),
				'priority' => 30,
			)
		));

		uasort( $this->steps, array( 'WP_CarDealer_Mixes', 'sort_array_by_priority' ) );

		return $this->steps;
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
		if ( isset( $_POST[ $prefix . 'title' ] ) ) {
			$post_id = $this->listing_id;

			$post_status = 'preview';
			if ( ! empty( $post_id ) ) {
				$old_post = get_post( $post_id );
				$post_date = $old_post->post_date;
				$old_post_status = get_post_status( $post_id );
				if ( $old_post_status === 'draft' ) {
					$post_status = 'preview';
				} else {
					$post_status = $old_post_status;
				}
			} else {
				$post_date = '';
			}

			$data = array(
				'post_title'     => sanitize_text_field( $_POST[ $prefix . 'title' ] ),
				'post_author'    => get_current_user_id(),
				'post_status'    => $post_status,
				'post_type'      => 'listing',
				'post_date'      => $post_date,
				'post_content'   => wp_kses_post( $_POST[ $prefix . 'description' ] ),
				'comment_status' => 'open'
			);

			$new_post = true;
			if ( !empty( $post_id ) ) {
				$data['ID'] = $post_id;
				$new_post = false;
			}

			do_action( 'wp-cardealer-process-submission-before-save', $post_id, $this );

			$data = apply_filters('wp-cardealer-process-submission-data', $data, $post_id);
			
			$this->errors = $this->submission_validate($data);
			if ( sizeof($this->errors) ) {
				return;
			}

			$post_datas = $_POST;

			$post_id = wp_insert_post( $data, true );

			$_POST = $post_datas;
			
			if ( ! empty( $post_id ) ) {
				
				$_POST['object_id'] = $post_id; // object_id in POST contains page ID instead of listing ID
				$post_datas['object_id'] = $post_id;

				$cmb->save_fields( $post_id, 'post', $post_datas );

				// Create featured image
				$featured_image = get_post_meta( $post_id, $prefix . 'featured_image', true );
				
				if ( ! empty( $post_datas[ 'current_' . $prefix . 'featured_image' ] ) ) {
					$img_id = get_post_meta( $post_id, $prefix . 'featured_image_img', true );
					if ( !empty($featured_image) ) {
						if ( is_array($featured_image) ) {
							$img_id = $featured_image[0];
						} elseif ( is_numeric($featured_image) ) {
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

				do_action( 'wp-cardealer-process-submission-after-save', $post_id );

				if ( $new_post ) {
					setcookie( 'listing_add_new_update', 'new' );
				} else {
					setcookie( 'listing_add_new_update', 'update' );
				}
				$this->listing_id = $post_id;
				$this->step ++;

			} else {
				if( $new_post ) {
					$this->errors[] = __( 'Can not create listing', 'wp-cardealer' );
				} else {
					$this->errors[] = __( 'Can not update listing', 'wp-cardealer' );
				}
			}
		}

		return;
	}

	public function submission_validate( $data ) {
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
		$error = apply_filters('wp-cardealer-submission-validate', $error);
		return $error;
	}

	public function preview_output() {
		global $post;

		if ( $this->listing_id ) {
			$post              = get_post( $this->listing_id ); // WPCS: override ok.
			$post->post_status = 'preview';

			setup_postdata( $post );

			do_action('wp-cardealer-before-preview-listing', $post);

			echo WP_CarDealer_Template_Loader::get_template_part( 'submission/listing-submit-preview', array(
				'post_id' => $this->listing_id,
				'listing_id'         => $this->listing_id,
				'step'           => $this->get_step(),
				'form_obj'           => $this,
			) );
			wp_reset_postdata();
		}
	}

	public function preview_process() {
		if ( ! $_POST ) {
			return;
		}

		if ( !isset( $_POST['security-listing-submit-preview'] ) || ! wp_verify_nonce( $_POST['security-listing-submit-preview'], 'wp-cardealer-listing-submit-preview-nonce' )  ) {
			$this->errors[] = esc_html__('Your nonce did not verify.', 'wp-cardealer');
			return;
		}

		if ( isset( $_POST['continue-edit-listing'] ) ) {
			$this->step --;
		} elseif ( isset( $_POST['continue-submit-listing'] ) ) {
			$listing = get_post( $this->listing_id );

			if ( in_array( $listing->post_status, array( 'preview', 'expired' ), true ) ) {
				// Reset expiry.
				delete_post_meta( $listing->ID, WP_CARDEALER_LISTING_PREFIX.'expiry_date' );

				// Update listing listing.
				$review_before = wp_cardealer_get_option( 'submission_requires_approval' );
				$post_status = 'publish';
				if ( $review_before == 'on' ) {
					$post_status = 'pending';
				}

				$update_listing                  = array();
				$update_listing['ID']            = $listing->ID;
				$update_listing['post_status']   = apply_filters( 'wp_cardealer_submit_listing_post_status', $post_status, $listing );
				$update_listing['post_date']     = current_time( 'mysql' );
				$update_listing['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_listing['post_author']   = get_current_user_id();

				wp_update_post( $update_listing );
			}

			$this->step ++;
		}
	}

	public function done_output() {
		$listing = get_post( $this->listing_id );
		
		echo WP_CarDealer_Template_Loader::get_template_part( 'submission/listing-submit-done', array(
			'post_id' => $this->listing_id,
			'listing'	  => $listing,
		) );
	}

	public function done_handler() {
		do_action( 'wp_cardealer_listing_submit_done', $this->listing_id );
		
		if ( ! empty( $_COOKIE['listing_add_new_update'] ) ) {
			$listing_add_new_update = $_COOKIE['listing_add_new_update'];

			if ( wp_cardealer_get_option('admin_notice_add_new_listing') ) {
				$listing = get_post($this->listing_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email_from, $email_from );
				$email_to = get_option( 'admin_email', false );
				$subject = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_add_new_listing', 'subject');
				$content = WP_CarDealer_Email::render_email_vars(array('listing' => $listing), 'admin_notice_add_new_listing', 'content');
				
				WP_CarDealer_Email::wp_mail( $email_to, $subject, $content, $headers );
			}
			
			setcookie( 'listing_add_new_update', '', time() - HOUR_IN_SECONDS );
		}
	}
}

function wp_cardealer_submit_form() {
	if ( ! empty( $_POST['wp_cardealer_listing_submit_form'] ) ) {
		WP_CarDealer_Submit_Form::get_instance();
	}
}

add_action( 'init', 'wp_cardealer_submit_form' );