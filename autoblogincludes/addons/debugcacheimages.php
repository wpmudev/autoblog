<?php
/*
Addon Name: Debug Image Import
Description: Debugs the import of any images in a post to the media library and attaches them to the imported post.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_DebugImageCacheAddon {

	var $build = 1;

	var $db;

	// Enter your email address on the line below
	var $sendto = '';

	var $msglog = array();

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		add_action( 'autoblog_post_post_insert', array(&$this, 'check_post_for_images'), 10, 3 );

	}

	function A_DebugImageCacheAddon() {
		$this->__construct();
	}


	function get_remote_images_in_content( $content ) {

		$images = array();

		$siteurl = parse_url( get_option( 'siteurl' ) );

		preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches);

		foreach ($matches[1] as $url) {

			$purl = parse_url($url);

			if(!isset($purl['host']) || $purl['host'] != $siteurl['host']) {
				// we seem to have an external images
				$images[] = $url;
			} else {
				// local image so ignore
			}

		}

		return $images;
	}

	function grab_image_from_url( $image, $post_ID ) {

		// Include the file and media libraries as they have the functions we want to use
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );
		// get the image
		$img = media_sideload_image($image, $post_ID);

		if ( !is_wp_error($img) ) {
			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $img, $newimage);

			if(!empty($newimage[1][0])) {
				$this->db->query( $this->db->prepare("UPDATE {$this->db->posts} SET post_content = REPLACE(post_content, %s, %s);", $image, $newimage[1][0] ) );
			}
		}

		return $image;
	}

	/**
	 * Cache images on post's saving
	 */
	function check_post_for_images( $post_ID, $ablog, $item ) {

		// Get the post so we can edit it.
		$post = get_post( $post_ID );

		$images = $this->get_remote_images_in_content( $post->post_content );

		if ( !empty($images) ) {

			$this->msglog[] = "Step 1. Found the following images in post " . $post_ID . "- " . print_r($images, true);

			foreach ($images as $image) {

				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
				if(!empty($matches)) {

					$purl = parse_url( $image );
					if (empty($purl['scheme']) && substr( $image, 0 , 2 ) == '//') {
						$furl = parse_url( $ablog['url'] );
						if(!empty($furl['scheme'])) {
							// We should add in the scheme again - this should handle images starting //
							$image = $furl['scheme'] . ':' . $image;
						}
					}

					$this->grab_image_from_url($image, $post_ID);

				}

			}

		}
		// Returning the $post_ID even though it's an action and we really don't need to

		return $post_ID;

	}

}

$adebugimagecacheaddon = new A_DebugImageCacheAddon();

?>