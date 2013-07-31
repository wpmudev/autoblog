<?php
/*
Addon Name: Enable External Permalinks for Autoblog
Description: This plugin will allow you to change the post permalink for your imported posts to an original URL
Author: Barry (Incsub)
Author URI: http://jakespurlock.com/2009/09/how-to-point-your-post-permalink-to-an-external-site/
Network: False
*/

function AB_external_permalink( $permalink, $post, $leavename ) {

	if( is_object($post) && isset($post->ID) ) {

		$original_link = get_post_meta( $post->ID, 'original_source', true );

		if( !empty($original_link) ) {

			// Check for the feed id and whether it's on the skip link
			if( defined('AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS') && AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS != '' ) {
				$skipfeeds = explode( ',', AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS );

				if(!empty($skipfeeds)) {
					$original_feed_id = get_post_meta( $post->ID, 'original_feed_id', true );
					if( !in_array( $original_feed_id, $skipfeeds ) ) {
						return $original_link;
					}
				} else {
					return $original_link;
				}
			} else {
				return $original_link;
			}

		}

	}

	return $permalink;

}

add_filter( 'post_link', 'AB_external_permalink', 10, 3 );

?>