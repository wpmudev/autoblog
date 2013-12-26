<?php
/*
Addon Name: Debug Image Import
Description: Debugs the import of any images in a post to the media library and attaches them to the imported post.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_DebugImageCacheAddon extends Autoblog_Addon {

	var $sendto = '';
	var $msglog = array();

	/**
	 * Constructor
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_post_post_insert', 'check_post_for_images', 10, 3 );
		$this->_add_action( 'autoblog_feed_edit_form_end', 'add_image_options', 10, 2 );
	}

	/**
	 * Renders addon options.
	 *
	 * @since 4.0.0
	 * @action autoblog_feed_edit_form_end 10 2
	 *
	 * @access public
	 * @param type $key
	 * @param type $details
	 */
	public function add_image_options( $key, $details ) {
		$table = !empty( $details->feed_meta )
			? maybe_unserialize( $details->feed_meta )
			: array();

		// render block header
		$this->_render_block_header( __( 'Debug Image Importing', 'autoblogtext' ) );

		// render block items
		$this->_render_block_element(
			__( 'Email debug log to', 'autoblogtext' ),
			sprintf(
				'<input type="text" name="abtble[debugemail]" value="%s" class="long title field">',
				isset( $table['debugemail'] ) ? esc_attr( stripslashes( $table['debugemail'] ) ) : ''
			)
		);
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
	private function _get_remote_images_from_content( $content ) {
		$images = array();
		$siteurl = parse_url( get_option( 'siteurl' ) );

		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches ) ) {
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
	 * @param string $orig_image The original image URL.
	 */
	private function _grab_image_from_url( $image, $post_id, $orig_image = false ) {
		// Include the file and media libraries as they have the functions we want to use
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );
		// get the image
		$img = media_sideload_image( $image, $post_id );

		if ( !is_wp_error( $img ) ) {
			$this->msglog[] = __( "Successfully grabbed image - ", 'autoblogtext' ) . $image;

			preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $img, $newimage );

			if ( !empty( $newimage[1][0] ) ) {

				$theimg = $newimage[1][0];
				$parsed_url = autoblog_parse_mb_url( $theimg );

				if ( function_exists( 'get_blog_option' ) ) {
					$theimg = str_replace( $parsed_url['scheme'] . '://' . $parsed_url['host'], get_blog_option( $this->_wpdb->blogid, 'siteurl' ), $theimg );
				}

				$this->msglog[] = __( 'Replacing image url with - ', 'autoblogtext' ) . $theimg;

				$this->_wpdb->query( $this->_wpdb->prepare( "UPDATE {$this->_wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE ID = %d;", $orig_image, $theimg, $post_id ) );
			}
		} else {
			$this->msglog[] = __( "I came across an error grabbing image - ", 'autoblogtext' ) . $image;
			if ( method_exists( $img, 'get_error_message' ) ) {
				$this->msglog[] = $img->get_error_message();
			}
		}
	}

	/**
	 * Caches images on post saving.
	 *
	 * @since 4.0.0
	 * @action autoblog_post_post_insert 10 3
	 *
	 * @access public
	 * @param type $post_id
	 * @param type $details
	 * @param SimplePie_Item $item
	 */
	public function check_post_for_images( $post_id, $details, SimplePie_Item $item ) {
		if ( !isset( $details['debugemail'] ) || !is_email( $details['debugemail'] ) ) {
			return;
		}

		// Reload the content as we need to work with the full content not just the excerpts
		$post_content = trim( $item->get_content() );
		// Set the encoding to UTF8
		$post_content = html_entity_decode( $post_content, ENT_QUOTES, 'UTF-8' );
		// Backup in case we can't get the post content again from the item
		if ( empty( $post_content ) ) {
			// Get the post so we can edit it.
			$post = get_post( $post_id );
			$post_content = $post->post_content;
		}

		$images = $this->_get_remote_images_from_content( $post_content );
		if ( empty( $images ) ) {
			return;
		}

		$this->msglog = array();
		$this->msglog[] = sprintf( _x( 'Hello, I am processing the post %d', 'Hello, I am processing the post {post id}', 'autoblogtext' ), $post_id );
		$this->msglog[] = '';
		$this->msglog[] = $post_content;
		$this->msglog[] = '';
		$this->msglog[] = sprintf( _x( 'I found the following images - %s', 'I found the following images - {images list}', 'autoblogtext' ), print_r( $images, true ) );

		foreach ( $images as $image ) {
			if ( !preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image ) ) {
				continue;
			}

			$image_url = autoblog_parse_mb_url( $image );
			$feed_url = autoblog_parse_mb_url( $details['url'] );

			$newimage = $image;
			if ( empty( $image_url['host'] ) && !empty( $feed_url['host'] ) ) {
				// We need to add in a host name as the images look like they are relative to the feed
				$newimage = trailingslashit( $feed_url['host'] ) . ltrim( $newimage, '/' );
			}

			if ( empty( $image_url['scheme'] ) && !empty( $feed_url['scheme'] ) ) {
				$newimage = substr( $newimage, 0, 2 ) == '//'
					? $feed_url['scheme'] . ':' . $newimage
					: $feed_url['scheme'] . '://' . $newimage;
			}

			$this->msglog[] = _x( 'I am going to try to grab the image - %s', 'I am going to try to grab the image - {image URL}', 'autoblogtext' ) . $newimage;
			$this->_grab_image_from_url( $newimage, $post_id, $image );
		}

		// Send the debug email to the address
		@wp_mail( $details['debugemail'], __( 'Autoblog debug message', 'autoblogtext' ), implode( PHP_EOL, $this->msglog ) );
		$this->msglog = array();
	}

}

$adebugimagecacheaddon = new A_DebugImageCacheAddon();