<?php

	global $current_user;
	$current_view = get_queried_object();

	$frontend_field = $args['fieldset'];

	if ($current_view->post_type == $args['post-type']) {
		// editing a post type
		$edit = TRUE;
		$status = $current_view->post_status;
	} else {
		// creating a post type
		$edit = FALSE;
		$status = 'draft';
	}

?>
<form id="frontend-<?php echo $args['post-type'] ?>" name="frontend-<?php echo $args['post-type'] ?>" class="well form-horizontal" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post" enctype="multipart/form-data">
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
						if (isset($user_meta[$field_key][0])) {
							$current_value = $user_meta[$field_key][0];
						} else {
							$current_value = '';
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
					?>
					<?php
						switch ($field_value['type']) {
							case 'text':
								?>
									<div class="control-group">
										<label class="control-label" for="<?php echo $field_key ?>"><?php echo $field_value['title'] ?></label>
										<div class="controls">
											<input type="text" class="input-xlarge <?php echo $added_class ?>" id="<?php echo $field_key ?>" name="<?php echo $field_key ?>" value="<?php echo $current_value ?>" <?php echo $additional_attr ?>>
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
					?>
				<?php endforeach ?>
			</fieldset>
		<?php endif ?>
	<?php endforeach ?>
	<div class="form-actions">
		<button type="submit" class="btn btn-primary"><?php _e('Save', 'cell-frontend') ?></button>
		<?php wp_nonce_field('frontend_'. $args['post-type'],'_nonce'); ?>
		<input name="action" value="frontend_<?php echo $args['post-type'] ?>" type="hidden">
		<?php if ($edit): ?>
			<input name="ID" value="<?php echo $current_view->ID ?>" type="hidden">	
			<input name="post_status" value="<?php echo $current_view->post_status ?>" type="hidden">
		<?php endif ?>
	</div>
</form>