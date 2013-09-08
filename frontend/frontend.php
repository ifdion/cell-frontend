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

		//add editing endpoint
		add_action( 'init', array($this, 'editing_endpoint') );
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

			wp_enqueue_script('bootstrap-datepicker', plugins_url('cell-frontend/js/bootstrap-datepicker.js'), array('bootstrap','jquery'), '1.0', true);

			wp_enqueue_script('frontend-script', plugins_url('cell-frontend/js/frontend.js'), array('bootstrap', 'bootstrap-datepicker','jquery'), '1.0', true);
			
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

			// check for unique post title, if is post-type-term
			if (isset($args['post-type-term']) && $args['post-type-term'] == TRUE) {
				if (isset($_POST['post_title_old']) && $_POST['post_title_old'] != $_POST['post_title']) {
					if (unique_post_title($_POST['post_title'])) {
					} else {
						$return = $_POST['_wp_http_referer'];
						$result['type'] = 'error';
						$result['message'] = __('Already exist.', 'cell-frontend');
						ajax_response($result,$return);
					}
				}
			}

			$process_result = $this->procedural($_POST);

			do_action( 'after_ajax_frontend_'.$args['post-type'], $process_result);

			$result['type'] = 'success';
			$result['message'] = __('Updated.', 'cell-frontend');
			ajax_response($result,$return);
		}
	}

	function procedural($input){

		// get base data
		$args = $this->frontend_args;

		// get base data
		global $current_user;
		$all_field = $args['fieldset'];

		// do delete early
		if (isset($input['delete']) && $input['delete'] == 1) {
			// wp_trash_post( $input['ID'] );
			wp_delete_post( $input['ID'] );
		}

		// merge fieldset's fields
		$current_field = array();
		foreach ($all_field as $key => $value){
			$current_field = array_merge($value['fields'], $current_field );
		}

		// // create or update ?
		if (isset($input['ID'])) {
			$edit = TRUE;
			$object_id = $input['ID'];
		} else {
			$edit = FALSE;
			$post_status = 'draft';
		}

		// check for unique post title, if is post-type-term
		if (isset($args['post-type-term']) && $args['post-type-term'] == TRUE) {
			if (isset($input['post_title_old']) && $input['post_title_old'] != $input['post_title']) {
				if (!unique_post_title($input['post_title'])) {
					return FALSE;
				}
			}
		}

		// check for 'base ' fields which determine a create or update in post table
		$update_post_args = array();
		foreach ($current_field as $field_key => $field_detail) {
			// empty *_old fields for post create
			if (!isset($input[$field_key.'_old'])) {
				$input[$field_key.'_old'] = '';
			}

			// saves empty checkbox
			if($field_detail['type'] == 'checkbox' && $field_key != 'delete' && !isset($input[$field_key])){
				$input[$field_key] = 0;
			}
			if ($field_detail['method'] == 'base' && ($input[$field_key] != $input[$field_key.'_old'])) {
				$update_post_args[$field_key] = $input[$field_key];
			}
			if (isset($update_post_args['post_name'])) {
				$update_post_args['post_name'] = $input['post_title'];
			}
		}

		// reformat date input to default date and time
		if (isset($update_post_args['post_date'])) {
			if (isset($input['post_date_time'])) {
				$post_time = $input['post_date_time'];
			} else {
				$post_time = date( 'H:i:s' );
			}

			$post_date = date( 'Y-m-d H:i:s', strtotime( $update_post_args['post_date'] .' '.$post_time) );
			$update_post_args['post_date'] = $post_date;
		}

		// create or update the object first
		if ($edit) {
			if ((count($update_post_args) > 0)) {
				$update_post_args['ID'] = $object_id;
				$object_id = wp_update_post( $update_post_args );
			}
		} else {
			$update_post_args['post_status'] = 'publish';
			$update_post_args['post_type'] = $args['post-type'];
			$object_id = wp_insert_post( $update_post_args );
		}

		// save generated field
		if (isset($args['fieldset']['generated'])) {
			foreach ($args['fieldset']['generated']['fields'] as $field_key => $field_detail) {
				call_user_func_array($field_detail['method'], array($object_id, $field_key));
			}
		}

		// echo '<pre>';
		// print_r($current_field);
		// print_r($input);
		// echo '</pre>';
		// wp_die( 'test saving checkbox' );

		// save other method
		foreach ($current_field as $field_key => $field_detail) {
			if (isset($input[$field_key])) {
				switch ($field_detail['method']) {
					case 'base':
						// do nothing
						break;
					case 'delete':
						// do nothing
						break;
					case 'meta':
						if ($input[$field_key] == 0) {
							delete_post_meta( $object_id, $field_key);
						} else {
							update_post_meta( $object_id, $field_key, $input[$field_key]);
						}
						break;
					case 'taxonomy':
						wp_set_object_terms( $object_id, intval($input[$field_key]), $field_key);
						break;
					default:
						call_user_func_array($field_detail['method'], array($object_id, $field_key, $input[$field_key]));
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
		}

		do_action( 'after_frontend_'.$args['post-type'], $object_id);

		return $object_id;
	}

	function editing_endpoint() {
		add_rewrite_endpoint( 'edit', EP_PERMALINK );
	}
}


?>