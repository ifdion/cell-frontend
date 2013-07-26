<?php

function unique_post_title($post_title){
	global $wpdb;

	$posts = $wpdb->get_results(
		"SELECT ID
		FROM $wpdb->posts
		WHERE post_status = 'publish'
		AND post_title='$post_title' ORDER BY ID DESC LIMIT 0,1"
	);

	if (isset($posts[0])) {
		$unique = 0 ; 
	} else {
		$unique = 1 ; 
	}

	return $unique;
}

?>