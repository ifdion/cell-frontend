<?php

	global $current_user;
	$current_view = get_queried_object();

	$frontend_field = $args['fieldset'];

	if (isset($current_view->post_type) && $current_view->post_type == $args['post-type']) {
		// editing a post type
		$edit = TRUE;
		$status = $current_view->post_status;
		$post_data = $current_view;
		$post_meta = get_post_meta( $current_view->ID);

	} else {
		// creating a post type
		$edit = FALSE;
		$status = 'draft';

		//set to current tax if is in tax view
		if (is_tax()) {
			$in_term = TRUE;
			$term = $current_view;
		}
	}


?>
<form id="frontend-<?php echo $args['post-type'] ?>" name="frontend-<?php echo $args['post-type'] ?>" class="well <?php echo $form_class ?>" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post" enctype="multipart/form-data">
	<?php foreach ($frontend_field as $key => $value): ?>
		<?php if ($value['public'] == true  && in_array($status, $value['status'])): ?>
			<?php
				if (isset($value['show-on'])){
					$data_show_on = 'data-show-on="'. $value['show-on'] .'"';
				} else {
					$data_show_on = '';
				}
			?>
			<fieldset id="<?php echo $key ?>" <?php echo $data_show_on ?>>
				<legend><h4><?php echo $value['title'] ?></h4></legend>
				<?php foreach ($value['fields'] as $field_key => $field_value): ?>
					<?php

						$current_value = '';
						if ($edit) {
							switch ($field_value['method']) {
								case 'meta':
									if (isset($post_meta[$field_key][0])) {
										$current_value = $post_meta[$field_key][0];
									}
									break;
								case 'base':
									if (isset($post_data->$field_key)) {
										$current_value = $post_data->$field_key;
									}
									break;
								case 'taxonomy':
									$terms = wp_get_object_terms( $post_data->ID, $field_key);
									if (isset($terms[0])) {
										$current_value = $terms[0]->term_id;
									}
									break;
								default:
									if (isset($field_value['value'])) {
										$current_value = call_user_func_array($field_value['value'], array($post_data->ID,$field_key));
									}
									break;
							}
						}

						// set additional class
						$added_class = ' ';
						if (isset($field_value['attr']['class'])) {
							$added_class = ' '.$field_value['attr']['class'];
							unset($field_value['attr']['class']);
						}
						// set additional attributes
						$additional_attr = '';
						if (isset($field_value['attr'])) {
							foreach ($field_value['attr'] as $attr_key => $attr_value) {
								$additional_attr .= ' '.$attr_key.'="'.$attr_value.'"';
							}
						}

						if ($edit == FALSE && isset($in_term) && $field_key == $term->taxonomy) {
							echo '<input type="hidden" name="'. $field_key .'" value="'. $term->term_id .'"/>';
						} else {

							switch ($field_value['type']) {
								case 'text':
									?>
										<div class="control-group">
											<label class="control-label" for="<?php echo $field_key ?>"><?php echo $field_value['title'] ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge <?php echo $added_class ?>" id="<?php echo $field_key ?>" name="<?php echo $field_key ?>" value="<?php echo $current_value ?>" <?php echo $additional_attr ?>>
												<?php if ($edit): ?>
													<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $current_value ?>"/>
												<?php endif ?>
											</div>
										</div>
									<?php
								break;
								case 'datepicker':
									?>

										<div class="control-group">
											<label class="control-label" for="<?php echo $field_key ?>"><?php echo $field_value['title'] ?></label>
											<div class="controls">
												<?php
													if ($current_value == '') {
														$formated_date = date( 'd-m-Y' );
													} else {
														$formated_date = date( 'd-m-Y', strtotime( $current_value ) );	
														$formated_time = date( 'H:i:s', strtotime( $current_value ) );
													}

												?>
												<input type="text" class="input-xlarge datepicker <?php echo $added_class ?>" id="<?php echo $field_key ?>" name="<?php echo $field_key ?>" value="<?php echo $formated_date ?>" <?php echo $additional_attr ?> data-date-format="dd-mm-yyyy">
												<?php if ($edit): ?>
													<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $formated_date ?>"/>
													<input type="hidden" name="<?php echo $field_key ?>_time" value="<?php echo $formated_time ?>"/>
												<?php endif ?>


											</div>
										</div>
									<?php
								break;
								case 'textarea':
									?>
										<div class="control-group">
											<label class="control-label" for="<?php echo $field_key ?>"><?php echo $field_value['title'] ?></label>
											<div class="controls">
												<textarea type="checkbox" class="input-xlarge <?php echo $added_class ?>" id="<?php echo $field_key ?>" name="<?php echo $field_key ?>" <?php echo $additional_attr ?>><?php echo $current_value ?></textarea>
												<?php if ($edit): ?>
													<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $current_value ?>"/>
												<?php endif ?>
											</div>
										</div>
									<?php
								break;
								case 'checkbox':
									?>
										<div class="control-group">
											<div class="controls">
												<label class="checkbox" for="<?php echo $field_key ?>">
													<input type="checkbox" class="<?php echo $added_class ?>" id="<?php echo $field_key ?>" name="<?php echo $field_key ?>" value="1" <?php checked($current_value, 1) ?> <?php echo $additional_attr ?>>
													<?php echo $field_value['title'] ?>
												</label>
												<?php if ($edit): ?>
													<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $current_value ?>"/>
												<?php endif ?>
											</div>
										</div>
									<?php
								break;
								case 'radio':
									?>
										<div class="control-group">
											<label class="control-label"><?php echo $field_value['title'] ?></label>
											<div class="controls">
												<?php if (isset($field_value['option'])): ?>
													<?php foreach ($field_value['option'] as $option_value => $option_title): ?>
														<label class="radio inline">
															<input type="radio" id="<?php echo $field_key.'-'.$field_value['option'] ?>" class="<?php echo $added_class ?>"value="<?php echo $option_value ?>" name="<?php echo $field_key ?>" <?php checked($current_value, $option_value) ?> <?php echo $additional_attr ?>> <?php echo $option_title ?>
														</label>
													<?php endforeach ?>
													<?php if ($edit): ?>
														<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $current_value ?>"/>
													<?php endif ?>
												<?php else: ?>
													<?php _e( 'Missing options.','cell-frontend' ) ?>
												<?php endif ?>
											</div>
										</div>
									<?php
								break;
								case 'select':
									?>
										<div class="control-group">
											<label class="control-label"><?php echo $field_value['title'] ?></label>
											<div class="controls">
												<?php if (isset($field_value['option'])): ?>
													<?php
														if (!is_array($field_value['option'])) {
															$option = call_user_func_array($field_value['option'],array($current_user->ID));
														} else {
															$option = $field_value['option'];
														}
													?>
													<select name="<?php echo $field_key ?>" class="<?php echo $added_class ?>" <?php echo $additional_attr ?>>
														<?php foreach ($option as $option_value => $option_title): ?>
															<option value="<?php echo $option_value ?>"  <?php selected($current_value, $option_value) ?> > <?php echo $option_title ?></option>
														<?php endforeach ?>
													</select>
													<?php if ($edit): ?>
														<input type="hidden" name="<?php echo $field_key ?>_old" value="<?php echo $current_value ?>"/>
													<?php endif ?>
												<?php else: ?>
													<?php _e( 'Missing options.','cell-frontend' ) ?>
												<?php endif ?>
											</div>
										</div>
									<?php
								break;
								default:
								break;
							}
						}
					?>
				<?php endforeach ?>
			</fieldset>
		<?php endif ?>
	<?php endforeach ?>
	<div class="form-actions">
		<button type="submit" class="btn btn-primary"><?php _e('Save', 'cell-frontend') ?></button>
		<?php wp_nonce_field('frontend_'. $args['post-type'],'_nonce'); ?>
		<input name="action" value="frontend_<?php echo $args['post-type'] ?>" type="hidden">
		<input name="return" value="<?php echo $next_step ?>" type="hidden">

		<?php if ($edit): ?>
			<input name="ID" value="<?php echo $current_view->ID ?>" type="hidden">	
			<input name="post_status" value="<?php echo $current_view->post_status ?>" type="hidden">
		<?php endif ?>
	</div>
</form>