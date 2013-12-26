<?php
/*
Addon Name: Image Import
Description: Imports any images in a post to the media library and attaches them to the imported post.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_ImageCacheAddon extends Autoblog_Addon {

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
	 * Returns remote images from content.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param string $content The feed item content.
	 * @return array The array of remote images.
	 */
	private function _get_remote_images_in_content( $content ) {
		$images = $matches = array();
		$siteurl = parse_url( get_option( 'siteurl' ) );

		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|is', $content, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$purl = autoblog_parse_mb_url( $url );
				if ( !isset( $purl['host'] ) || $purl['host'] != $siteurl['host'] ) {
					// we seem to have an external images
					$images[] = $url;
				}
			}
		}

		return $images;
	}

	/**
	 * Grabs remote image.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param string $image The image URL.
	 * @param int $post_id The post id to attach the image to.
	 * @return string|boolean Local image URL on success, otherwise FALSE.
	 */
	private function _download_image( $image, $post_id ) {
		$query = new WP_Query( array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_query'  => array(
				array(
					'key'     => 'autoblog_orig_image',
					'value'   => $image,
					'compare' => '='
				)
			)
		) );

		if ( $query->have_posts() ) {
			$image_id = $query->next_post()->ID;
		} else {
			// Download file to temp location
			$tmp = download_url( $image );
			if ( is_wp_error( $tmp ) ) {
				return false;
			}

			// Set variables for storage, fix file filename for query strings
			$matches = array();
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
			$file_array['name'] = basename( $matches[0] );
			$file_array['tmp_name'] = $tmp;

			// do the validation and storage stuff
			$image_id = media_handle_sideload( $file_array, $post_id );
			if ( is_wp_error( $image_id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $image_id;
			}

			add_post_meta( $image_id, 'autoblog_orig_image', $image );
		}

		return wp_get_attachment_url( $image_id );
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
	 * @param array $details The feed details.
	 */
	public function import_post_images( $post_id, $ablog ) {
		$post = get_post( $post_id );
		$images = $this->_get_remote_images_in_content( $post->post_content );
		if ( empty( $images ) ) {
			return;
		}

		// Include the file and media libraries as they have the functions we want to use
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

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
			$furl = autoblog_parse_mb_url( $ablog['url'] );

			if ( empty( $purl['host'] ) && !empty( $furl['host'] ) ) {
				// We need to add in a host name as the images look like they are relative to the feed
				$newimage = trailingslashit( $furl['host'] ) . ltrim( $newimage, '/' );
			}

			if ( empty( $purl['scheme'] ) && !empty( $furl['scheme'] ) ) {
				$newimage = substr( $newimage, 0, 2 ) == '//'
					? $furl['scheme'] . ':' . $newimage
					: $furl['scheme'] . '://' . $newimage;
			}

			$newimage = $this->_download_image( $newimage, $post_id );
			if ( $newimage ) {
				$new_images[$image] = $newimage;
			}
		}

		$post->post_content = str_replace( array_keys( $new_images ), array_values( $new_images ), $post->post_content );
		wp_update_post( $post->to_array() );
	}

}

$aimagecacheaddon = new A_ImageCacheAddon();