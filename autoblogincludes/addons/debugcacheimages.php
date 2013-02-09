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

		$table = maybe_unserialize($details->feed_meta);

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
			$this->msglog[] = "Successfully grabbed image - " . $image;

			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $img, $newimage);

			if(!empty($newimage[1][0])) {
				$this->db->query( $this->db->prepare("UPDATE {$this->db->posts} SET post_content = REPLACE(post_content, %s, %s);", $image, $newimage[1][0] ) );
			}
		} else {
			$this->msglog[] = "I came across an error grabbing image - " . $image;
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

		// Get the post so we can edit it.
		$post = get_post( $post_ID );

		$this->msglog[] = __('Hello, I am processing the post ', 'autoblogtext') . $post_ID; $this->msglog[] = '';
		$this->msglog[] = $post->post_content; $this->msglog[] = '';

		$images = $this->get_remote_images_in_content( $post->post_content );

		if ( !empty($images) ) {

			$this->msglog[] = "I found the following images - " . print_r($images, true);

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

					$this->msglog[] = "I am going to try to grab the image - " . $image;

					$this->grab_image_from_url($image, $post_ID);

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