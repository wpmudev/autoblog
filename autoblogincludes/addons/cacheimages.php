<?php
/*
Addon Name: Image Import
Description: Imports any images in a post to the media library and attaches them to the imported post.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_ImageCacheAddon extends Autoblog_Addon_Image {

	/**
	 * Constructor
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_post_post_insert', 'import_post_images', 10, 2 );
		$this->_add_action( 'autoblog_post_post_update', 'import_post_images', 10, 2 );
	}

	/**
	 * Imports post images.
	 *
	 * @since 4.0.0
	 * @action autoblog_post_post_insert 10 2
	 * @action autoblog_post_post_update 10 2
	 *
	 * @access public
	 * @param int $post_id The post id.
	 * @param array $details The feed settings.
	 */
	public function import_post_images( $post_id, $details ) {
		$post = get_post( $post_id );
		$images = $this->_get_remote_images_from_content( $post->post_content );
		if ( empty( $images ) ) {
			return;
		}

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );

		$new_images = array();
		foreach ( $images as $image ) {
			if ( !preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image ) ) {
				continue;
			}

			$newimage = $image;

			// Parse the image url
			$purl = autoblog_parse_mb_url( $newimage );
			// Parse the feed url
			$furl = autoblog_parse_mb_url( $details['url'] );

			if ( empty( $purl['host'] ) && !empty( $furl['host'] ) ) {
				// We need to add in a host name as the images look like they are relative to the feed
				$newimage = trailingslashit( $furl['host'] ) . ltrim( $newimage, '/' );
			}

			if ( empty( $purl['scheme'] ) && !empty( $furl['scheme'] ) ) {
				$newimage = substr( $newimage, 0, 2 ) == '//'
					? $furl['scheme'] . ':' . $newimage
					: $furl['scheme'] . '://' . $newimage;
			}

			$newimage_id = $this->_download_image( $newimage, $post_id );
			if ( $newimage_id ) {
				$new_images[$image] = wp_get_attachment_url( $newimage_id );
			}
		}

		$post->post_content = str_replace( array_keys( $new_images ), array_values( $new_images ), $post->post_content );
		wp_update_post( $post->to_array() );
	}

}

$aimagecacheaddon = new A_ImageCacheAddon();