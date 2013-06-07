<?php

/**
 * Register
 *
 * @package default
 * @author Dion
 **/

include_once 'helper.php';

class CellFrontend {

	function __construct($args) {

		// get the profile args
		$this->frontend_args = $args;
		$this->preset_fields = array('post_author','post_date','post_date_gmt','post_content','post_title','post_excerpt','post_status','comment_status','post_name','post_parent','post_type');

		// add a shortcode
		add_shortcode('cell-frontend-'.$this->frontend_args['post-type'], array( $this, 'shortcode_output'));

		// add a redirect for logged out user
		add_action('template_redirect', array( $this, 'redirect_user'));

		// add login ajax handler function
		add_action('wp_ajax_frontend_'.$this->frontend_args['post-type'], array( $this, 'process_frontend'));

	}
	
	function redirect_user(){
		if (isset($this->frontend_args['page-create']) && is_page($this->frontend_args['page-create']) && !is_user_logged_in()){
			$result['type'] = 'error';
			$result['message'] = __('Please login.', 'cell-frontend');
			if (isset($this->frontend_args['page-redirect'])) {
				$return = get_permalink( get_page_by_path( $this->frontend_args['page-redirect'] ) );
			} else{
				$return = get_bloginfo('url');
			}
			ajax_response($result,$return);
		}
	}

	function shortcode_output(){

		$args = $this->frontend_args;

		if(is_user_logged_in() && isset($this->frontend_args['fieldset'])){

			wp_enqueue_script('frontend-script', plugins_url('cell-frontend/js/frontend.js'), array('jquery'), '1.0', true);
			wp_enqueue_style( 'cell-frontend-styles', plugins_url( 'cell-frontend/css/cell-frontend.css' ) );

			if (isset($this->frontend_args['include-script'])) {
				if (is_array($this->frontend_args['include-script'])) {
					foreach ($this->frontend_args['include-script'] as $value) {
						wp_enqueue_script( $value );
					}
				} else {
					wp_enqueue_script( $this->frontend_args['include-script'] );
				}
			}

			ob_start();
				include('views/frontend-form.php');
				$frontend_form = ob_get_contents();
			ob_end_clean();

			return $frontend_form;		
		} else {
			return false;
		}
	}

	function process_frontend() {

	// 	if (isset($this->frontend_args['fieldset'])) {
	// 		$profile_field = $this->frontend_args['fieldset'];
	// 	} else {
	// 		return false;
	// 	}

	// 	if ( empty($_POST) || !wp_verify_nonce($_POST['profile_nonce'],'frontend_profile') ) {
	// 		echo 'Sorry, your nonce did not verify.';
	// 		die();
	// 	} else {

	// 		// set return value
	// 		$return = $_POST['_wp_http_referer'];

	// 		// save new email & password, if exist
	// 		if ($_POST['user_email'] != $_POST['user_email_old'] && is_email( $_POST['user_email'] )) {
	// 			if (email_exists( $_POST['user_email'] )) {
	// 				$result['type'] = 'error';
	// 				$result['message'] = __('Email already used.', 'cell-frontend');
	// 				ajax_response($result,$return);
	// 			}
	// 			$userdata['user_email'] = $_POST['user_email'];
	// 			$userdata['ID'] = $_POST['user_id'];
	// 			$update_user = true;
	// 		}
	// 		if ($_POST['user_password'] != '') {
	// 			if ($_POST['user_password'] != $_POST['user_password_retype']) {
	// 				$result['type'] = 'error';
	// 				$result['message'] = __('Password did not match.', 'cell-frontend');
	// 				ajax_response($result,$return);
	// 			} else{
	// 				$userdata['user_pass'] = $_POST['user_password'];
	// 				$userdata['ID'] = $_POST['user_id'];
	// 				$update_user = true;
	// 			}
	// 		}
	// 		if (isset($update_user)) {
	// 			wp_update_user( $userdata );
	// 		}


	// 		// merge fieldset's fields
	// 		$user_fields = array();
	// 		foreach ($profile_field as $key => $value){
	// 			$user_fields = array_merge($user_fields, $value['fields']);
	// 		}
	// 		// save each field
	// 		foreach ($user_fields as $field_key => $field_detail) {
	// 			// special way to save for checkbox
	// 			if (isset($_POST[$field_key]) && $field_detail['type'] == 'checkbox') {
	// 				update_user_meta( $_POST['user_id'], $field_key, $_POST[$field_key] );
	// 			} else {
	// 				delete_user_meta( $_POST['user_id'], $field_key);
	// 			}
	// 			if (isset($_POST[$field_key])) {
	// 				update_user_meta( $_POST['user_id'], $field_key, $_POST[$field_key] );
	// 			}
	// 		}


	// 		$result['type'] = 'success';
	// 		$result['message'] = __('Profile updated.', 'cell-frontend');
	// 		ajax_response($result,$return);
	// 	}
	}


}


?>