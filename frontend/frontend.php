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
			if (isset($this->frontend_args['redirect-noaccess'])) {
				$return = get_permalink( get_page_by_path( $this->frontend_args['redirect-noaccess'] ) );
			} else{
				$return = get_bloginfo('url');
			}
			ajax_response($result,$return);
		}
	}

	function shortcode_output($attribute){

		$form_class = '';

		if (isset($attribute['wizard'])) {
			$wizard = $attribute['wizard'];
			$next_step = $this->frontend_args['redirect-wizard'];
			$form_class .= ' wizard';
		} else {
			$wizard = FALSE;
			$next_step = $this->frontend_args['redirect-success'];
		}

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

		// get base data
		$args = $this->frontend_args;		

		if ( empty($_POST) || !wp_verify_nonce($_POST['_nonce'],'frontend_'.$args['post-type']) ) {
			echo 'Sorry, your nonce did not verify.';
			die();
		} else {

			// get base data
			global $current_user;
			$frontend_field = $args['fieldset'];

			// create or update ?
			if (isset($_POST['ID'])) {
				$edit = TRUE;
				$object_id = $_POST['ID'];
			} else {
				$edit = FALSE;
				$post_status = 'draft';
			}

			// set return value
			if (isset($_POST['return'])) {
				if (is_page( $_POST['return'] )) {
					$return = get_permalink( get_page_by_path( $_POST['return'] ) );
				} else {
					$return = call_user_func($_POST['return']);
				}
			} else {
				$return = $_POST['_wp_http_referer'];
			}

			// do delete early

			// merge fieldset's fields
			$current_field = array();
			foreach ($frontend_field as $key => $value){
				if (in_array($post_status, $value['status'])) {
					$current_field = array_merge($value['fields'], $current_field );
				}
			}

			// create or update the object first			
			$update_post_args = array();
			foreach ($current_field as $field_key => $field_detail) {
				if ($field_detail['method'] == 'base') {
					$update_post_args[$field_key] = $_POST[$field_key];
				}
			}

			if ($edit) {
				$update_post_args['ID'] = $object_id;

				$object_id = wp_update_post( $update_post_args );
			} else {
				$update_post_args['post_status'] = 'publish';
				$update_post_args['post_type'] = $args['post-type'];
				$object_id = wp_insert_post( $update_post_args );
			}

			// save other method
			foreach ($current_field as $field_key => $field_detail) {
				switch ($field_detail['method']) {
					case 'base':
						// do nothing
						break;
					case 'delete':
						// do nothing
						break;
					case 'meta':
						update_post_meta( $object_id, $field_key, $_POST[$field_key]);
						break;
					case 'taxonomy':
						wp_set_object_terms( $object_id, intval($_POST[$field_key]), $field_key);
						break;
					default:
						call_user_func_array($field_detail['method'], array($object_id, $field_key, $_POST[$field_key]));
						break;
				}





				// special way to save for checkbox
				// if (isset($_POST[$field_key]) && $field_detail['type'] == 'checkbox') {
				// 	update_user_meta( $_POST['user_id'], $field_key, $_POST[$field_key] );
				// } else {
				// 	delete_user_meta( $_POST['user_id'], $field_key);
				// }
				// if (isset($_POST[$field_key])) {
				// 	update_user_meta( $_POST['user_id'], $field_key, $_POST[$field_key] );
				// }

			}
			$result['type'] = 'success';
			$result['message'] = __('Updated.', 'cell-frontend');
			ajax_response($result,$return);
		}
	}


}


?>