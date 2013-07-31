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

		add_action( 'autoblog_feed_edit_form_end', array(&$this, 'add_image_options'), 10, 2 );


	}

	function A_FeatureImageCacheAddon() {
		$this->__construct();
	}

	function add_image_options( $key, $details ) {

		if(!empty($details->feed_meta)) {
			$table = maybe_unserialize($details->feed_meta);
		} else {
			$table = array();
		}

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Featured Image Importing','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Check images from','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[featuredimage]' class='field'>";
		echo "<option value='ASC'";
		if(apply_filters('autoblog_featuredimage_from', (isset($table['featuredimage']) ? $table['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER )) == 'ASC') {
			echo " selected='selected'";
		}
		echo ">" . __('top of post','autoblogtext') . "</option>";
		echo "<option value='DESC'";
		if(apply_filters('autoblog_featuredimage_from', (isset($table['featuredimage']) ? $table['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER )) == 'DESC') {
			echo " selected='selected'";
		}
		echo ">" . __('bottom of post','autoblogtext') . "</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Minimum featured image size','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[featured_min_width]' value='";
		echo apply_filters('autoblog_featuredimage_min_width', (isset($table['featured_min_width']) ? (int) $table['featured_min_width'] : AUTOBLOG_FEATURED_IMAGE_MIN_WIDTH ) );
		echo "' class='narrow field' style='width: 5em;' />";
		echo "&nbsp;" . __('pixels wide by','autoblogtext') . "&nbsp;";
		echo "<input type='text' name='abtble[featured_min_height]' value='";
		echo apply_filters('autoblog_featuredimage_min_height', (isset($table['featured_min_height']) ? (int) $table['featured_min_height'] : AUTOBLOG_FEATURED_IMAGE_MIN_HEIGHT ) );
		echo "' class='narrow field' style='width: 5em;' />";
		echo "&nbsp;" . __('pixels high','autoblogtext');
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
			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $img, $newimage);

			if(!empty($newimage[1][0])) {

				$theimg = $newimage[1][0];
				$parsed_url = mb_parse_url( $theimg );

				$theimg = str_replace( $parsed_url['host'] . '://' . $parsed_url['host'], get_option('siteurl'), $theimg );

				$this->db->query( $this->db->prepare("UPDATE {$this->db->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE ID = %d;", $orig_image, $theimg, $post_ID ) );
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

		$images = $this->get_remote_images_in_content( $post_content );

		if ( !empty($images) ) {

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

					$this->grab_image_from_url($newimage, $post_ID, $image);
				}

			}

			// Set the first image as the featured one - from a snippet at http://wpengineer.com/2460/set-wordpress-featured-image-automatically/
			$imageargs = array(
				'numberposts'    => -1,
				'order'          => apply_filters('autoblog_featuredimage_from', (isset($ablog['featuredimage']) ? $ablog['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER )), // DESC for the last image
				'post_mime_type' => 'image',
				'post_parent'    => $post_ID,
				'post_status'    => NULL,
				'post_type'      => 'attachment'
			);

			$cachedimages = get_children( $imageargs );
			if ( !empty($cachedimages) ) {
				foreach ( $cachedimages as $image_id => $image ) {
					$meta = wp_get_attachment_metadata( $image_id );
					if(!empty($meta)) {
						if($meta['width'] >= apply_filters('autoblog_featuredimage_min_width', (isset($table['featured_min_width']) ? (int) $table['featured_min_width'] : AUTOBLOG_FEATURED_IMAGE_MIN_WIDTH ) ) && $meta['height'] >= apply_filters('autoblog_featuredimage_min_height', (isset($table['featured_min_height']) ? (int) $table['featured_min_height'] : AUTOBLOG_FEATURED_IMAGE_MIN_HEIGHT ) ) ) {
							set_post_thumbnail( $post_ID, $image_id );
							// Exit from the loop
							break;
						}
					}

				}
			}

		}
		// Returning the $post_ID even though it's an action and we really don't need to

		return $post_ID;

	}

}

$afeatureimagecacheaddon = new A_FeatureImageCacheAddon();

?>