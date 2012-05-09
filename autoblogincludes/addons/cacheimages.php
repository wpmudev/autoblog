<?php
/*
Addon Name: Import images
Description: Imports any images in a post to the media library and attaches them to the imported post.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_ImageCacheAddon {

	var $build = 1;

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		add_action( 'autoblog_post_post_insert', array(&$this, 'check_post_for_images'), 10, 3 );

	}

	function A_ImageCacheAddon() {
		$this->__construct();
	}


	function get_remote_images_in_content( $content ) {

		$images = array();

		$siteurl = parse_url( get_option( 'siteurl' ) );

		preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches);

		foreach ($matches[1] as $url) {

			$purl = parse_url($url);

			if($purl['host'] != $siteurl['host']) {
				// we seem to have an external images
				$images[] = $url;
			} else {
				// local image so ignore
			}

		}

		return $images;
	}

	function grab_image_from_url( $image, $post_ID ) {

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

		return $theimage;
	}

	/**
	 * Cache images on post's saving
	 */
	function check_post_for_images( $post_ID, $ablog, $item ) {

		// Get the post so we can edit it.
		$post = get_post( $post_ID );

		$images = $this->get_remote_images_in_content( $post->post_content );

		if ( !empty($images) ) {
			// Include the file and media libraries as they have the functions we want to use
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			foreach ($images as $image) {
				$this->grab_image_from_url($image, $post_ID);
			}

		}
		// Returning the $post_ID even though it's an action and we really don't need to

		return $post_ID;

	}

}

$aimagecacheaddon = new A_ImageCacheAddon();

?>