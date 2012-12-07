<?php
/*
Addon Name: Featured Image Import
Description: Imports any images in a post to the media library and attaches them to the imported post, making the first one a featured image.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_FeatureImageCacheAddon {

	var $build = 1;

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		add_action( 'autoblog_post_post_insert', array(&$this, 'check_post_for_images'), 10, 3 );

	}

	function A_FeatureImageCacheAddon() {
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
				$this->db->query( $this->db->prepare("UPDATE {$this->db->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE ID = %d;", $image, $newimage[1][0], $post_ID ) );
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
			// Include the file and media libraries as they have the functions we want to use
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			foreach ($images as $image) {

				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
				if(!empty($matches)) {
					$this->grab_image_from_url($image, $post_ID);
				}

			}

			// Set the first image as the featured one - from a snippet at http://wpengineer.com/2460/set-wordpress-featured-image-automatically/
			$imageargs = array(
				'numberposts'    => 1,
				'order'          => 'ASC', // DESC for the last image
				'post_mime_type' => 'image',
				'post_parent'    => $post_ID,
				'post_status'    => NULL,
				'post_type'      => 'attachment'
			);

			$cachedimages = get_children( $imageargs );
			if ( !empty($cachedimages) ) {
				foreach ( $cachedimages as $image_id => $image ) {
					set_post_thumbnail( $post_ID, $image_id );
				}
			}

		}
		// Returning the $post_ID even though it's an action and we really don't need to

		return $post_ID;

	}

}

$afeatureimagecacheaddon = new A_FeatureImageCacheAddon();

?>