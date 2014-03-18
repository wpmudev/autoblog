<?php
/*
Addon Name: Use External Permalinks
Description: This plugin will allow you to change the post permalink for your imported posts to an original URL
Author: Barry (Incsub)
Author URI: http://jakespurlock.com/2009/09/how-to-point-your-post-permalink-to-an-external-site/
Network: False
*/

add_filter( 'post_link', 'autoblog_external_permalink', 10, 2 );
add_filter( 'post_type_link', 'autoblog_external_permalink', 10, 2 );
function autoblog_external_permalink( $permalink, $post ) {
	if ( !is_object( $post ) || !isset( $post->ID ) ) {
		return $permalink;
	}

	$original_link = get_post_meta( $post->ID, 'original_source', true );
	if ( empty( $original_link ) ) {
		return $permalink;
	}

	// Check for the feed id and whether it's on the skip link
	if ( defined( 'AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS' ) && AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS != '' ) {
		$skipfeeds = explode( ',', AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS );
		if ( !empty( $skipfeeds ) ) {
			$original_feed_id = get_post_meta( $post->ID, 'original_feed_id', true );
			if ( !in_array( $original_feed_id, $skipfeeds ) ) {
				return $original_link;
			}
		}
	}

	return $original_link;
}
