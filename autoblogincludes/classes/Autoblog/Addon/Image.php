<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Base class for autoblog images related addon.
 *
 * @category Autoblog
 * @package Addon
 *
 * @since 4.0.0
 */
class Autoblog_Addon_Image extends Autoblog_Addon {

	/**
	 * Returns remote images from content.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param string $content The feed item content.
	 * @return array The array of remote images.
	 */
	protected function _get_remote_images_from_content( $content ) {
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
	 * @access protected
	 * @param string $image The image URL.
	 * @param int $post_id The post id to attach the image to.
	 * @return string|boolean Local image URL on success, otherwise FALSE.
	 */
	protected function _download_image( $image, $post_id ) {
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
			return $query->next_post()->ID;
		}

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
		} else {
			add_post_meta( $image_id, 'autoblog_orig_image', $image );
		}

		return $image_id;
	}

}