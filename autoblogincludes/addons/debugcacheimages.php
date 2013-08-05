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

		add_action( 'autoblog_feed_edit_form_end', array(&$this, 'add_image_options'), 10, 2 );

	}

	function A_DebugImageCacheAddon() {
		$this->__construct();
	}

	function add_image_options( $key, $details ) {

		if(!empty($details->feed_meta)) {
			$table = maybe_unserialize($details->feed_meta);
		} else {
			$table = array();
		}

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Debug Image Importing','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Email debug log to','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[debugemail]' value='" . esc_attr(stripslashes((isset($table['debugemail']) ? $table['debugemail'] : '') ) ) . "' class='long title field' />";
		echo "</td>";
		echo "</tr>\n";

	}

	function get_remote_images_in_content( $content ) {

		$images = array();

		$siteurl = parse_url( get_option( 'siteurl' ) );

		preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches);

		foreach ($matches[1] as $url) {

			$purl = mb_parse_url($url);

			if(!isset($purl['host']) || $purl['host'] != $siteurl['host']) {
				// we seem to have an external images
				$images[] = $url;
			} else {
				// local image so ignore
			}

		}

		return $images;
	}

	function grab_image_from_url( $image, $post_ID, $orig_image = false ) {

		// Include the file and media libraries as they have the functions we want to use
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );
		// get the image
		$img = media_sideload_image($image, $post_ID);

		if ( !is_wp_error($img) ) {
			$this->msglog[] = __("Successfully grabbed image - ", 'autoblogtext') . $image;

			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $img, $newimage);

			if(!empty($newimage[1][0])) {

				$theimg = $newimage[1][0];
				$parsed_url = mb_parse_url( $theimg );

				$theimg = str_replace( $parsed_url['host'] . '://' . $parsed_url['host'], get_option('siteurl'), $theimg );

				$this->db->query( $this->db->prepare("UPDATE {$this->db->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE ID = %d;", $orig_image, $theimg, $post_ID ) );

			}
		} else {
			$this->msglog[] = __("I came across an error grabbing image - ", 'autoblogtext') . $image;
			if(method_exists( $img, 'get_error_message')) {
				$this->msglog[] = $img->get_error_message();
			}

		}

		return $image;
	}

	/**
	 * Cache images on post's saving
	 */
	function check_post_for_images( $post_ID, $ablog, $item ) {

		// Reload the content as we need to work with the full content not just the excerpts
		$post_content = trim( $item->get_content() );
		// Set the encoding to UTF8
		$post_content = html_entity_decode($post_content, ENT_QUOTES, 'UTF-8');

		// Backup in case we can't get the post content again from the item
		if( empty($post_content) ) {
			// Get the post so we can edit it.
			$post = get_post( $post_ID );
			$post_content = $post->post_content;
		}

		$this->msglog[] = __('Hello, I am processing the post ', 'autoblogtext') . $post_ID; $this->msglog[] = '';
		$this->msglog[] = $post_content; $this->msglog[] = '';

		$images = $this->get_remote_images_in_content( $post_content );

		if ( !empty($images) ) {

			$this->msglog[] = __("I found the following images - ", 'autoblogtext') . print_r($images, true);

			foreach ($images as $image) {

				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
				if(!empty($matches)) {

					$newimage = $image;

					// Parse the image url
					$purl = mb_parse_url( $newimage );
					// Parse the feed url
					$furl = mb_parse_url( $ablog['url'] );

					if(empty($purl['host']) && !empty($furl['host'])) {
						// We need to add in a host name as the images look like they are relative to the feed
						$newimage = trailingslashit($furl['host']) . ltrim($newimage, '/');

					}

					if (empty($purl['scheme']) && !empty( $furl['scheme'] ) ) {

							if( substr( $newimage, 0 , 2 ) == '//' ) {
								$newimage = $furl['scheme'] . ':' . $newimage;
							} else {
								$newimage = $furl['scheme'] . '://' . $newimage;
							}

					}


					$this->msglog[] = __("I am going to try to grab the image - ", 'autoblogtext') . $newimage;

					$this->grab_image_from_url($newimage, $post_ID, $image);

				}

			}

		}

		// Check if we have an email address and send the message
		if(isset($ablog['debugemail']) && is_email($ablog['debugemail'])) {
			// Send the debug email to the address
			@wp_mail( $ablog['debugemail'], __('Autoblog debug message','autoblogtext'), implode( "\n", $this->msglog ));
			$this->msglog = array();
		}

		// Returning the $post_ID even though it's an action and we really don't need to

		return $post_ID;

	}

}

$adebugimagecacheaddon = new A_DebugImageCacheAddon();

?>