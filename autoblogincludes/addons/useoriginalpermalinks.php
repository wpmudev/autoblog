<?php
/*
Addon Name: Enable External Permalinks for Autoblog
Description: This plugin will allow you to change the post permalink for your imported posts to an original URL
Author: Barry (Incsub)
Author URI: http://jakespurlock.com/2009/09/how-to-point-your-post-permalink-to-an-external-site/
Network: False
*/

function AB_external_permalink( $permalink ) {

	global $post;

	$thePostID = $post->ID;

	$internal_post = get_post( $thePostID );

	$title = $internal_post->post_title;

	$post_keys = array();
	$post_val  = array();
	$post_keys = get_post_custom_keys( $thePostID );

	if (!empty($post_keys)) {
		foreach ($post_keys as $pkey) {
			if ($pkey == 'original_source') {
				$post_val = get_post_custom_values( $pkey, $thePostID );
				break;
			}
		}

		if(empty($post_val)) {
			$link = $permalink;
		} else {
			$link = $post_val[0];
		}
	} else {
		$link = $permalink;
	}

	return $link;

}

add_filter('the_permalink','AB_external_permalink');
add_filter('the_permalink_rss','AB_external_permalink');

?>