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
 * @package  Addon
 *
 * @since    4.0.0
 */
class Autoblog_Addon_Image extends Autoblog_Addon {

	/**
	 * Returns remote images from content.
	 *
	 * @since  4.0.0
	 *
	 * @access protected
	 *
	 * @param string $content The feed item content.
	 *
	 * @return array The array of remote images.
	 */
	protected function _get_remote_images_from_content( $content ) {
		$images  = $matches = array();
		$siteurl = parse_url( get_option( 'siteurl' ) );

		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|is', $content, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$url  = str_replace( ' ', '%20', current( explode( '?', $url, 2 ) ) );
				$purl = autoblog_parse_mb_url( $url );
				if ( ! isset( $purl['host'] ) || $purl['host'] != $siteurl['host'] && preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url ) ) {
					// we seem to have an external images
					$images[] = $url;
				}
			}
		}

		return $images;
	}

	/**
	 * @param $content
	 *
	 * @return array
	 */
	protected function _get_remote_images_from_post_content( $content ) {
		$images  = $matches = array();
		$siteurl = parse_url( get_option( 'siteurl' ) );
		//look up images by DOM
		$doc         = new DOMDocument();
		$can_use_dom = @$doc->loadHTML( $content );
		if ( $can_use_dom == true ) {
			$tags = $doc->getElementsByTagName( 'img' );
			foreach ( $tags as $tag ) {
				$url = $tag->getAttribute( 'src' );
				$url = trim( $url );
				if ( ! empty( $url ) ) {
					$url = html_entity_decode( rawurldecode( $url ) );
					//no resouce url please
					if ( strpos( $url, '//' ) === 0 ) {
						$url = 'http:' . $url;
					}
					$purl = autoblog_parse_mb_url( $url );
					if ( ! isset( $purl['host'] ) || $purl['host'] != $siteurl['host'] ) {
						$images[] = $url;
					}
				}
			}
		} else {
			//can not parse DOM, use regex
			$images = $this->_get_remote_images_from_content( $content );
		}

		return $images;
	}

	/**
	 * @param $url
	 *
	 * @return bool
	 */
	protected function validate_images( $url ) {
		//validate images
		$sizes = @getimagesize( $url );

		if ( is_array( $sizes ) ) {
			$type = current( explode( '/', $sizes['mime'], 2 ) );
			if ( in_array( $sizes['mime'], get_allowed_mime_types() ) && $type == 'image' ) {
				return $url;
			}
		}

		//if the get fail, maybe 403 error, we try with other method
		$ext = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array(
			'jpg',
			'gif',
			'png',
			'jpeg'
		) ) ) {
			return $url;
		}

		//in some case, the url will have GET param, try the last with removing the param
		$url = strtok( $url, '?' );
		$ext = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array(
			'jpg',
			'gif',
			'png',
			'jpeg'
		) ) ) {
			return $url;
		}

		return false;
	}

	/**
	 * Grabs remote image.
	 *
	 * @since  4.0.0
	 *
	 * @access protected
	 *
	 * @param string $image The image URL.
	 * @param int $post_id The post id to attach the image to.
	 *
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
		$file_array['name']     = $this->_generate_image_name( $image );
		$file_array['tmp_name'] = $tmp;

		// do the validation and storage stuff
		$post     = get_post( $post_id );
		$image_id = media_handle_sideload( $file_array, $post_id, null, array( 'post_author' => $post->post_author ) );
		if ( is_wp_error( $image_id ) ) {
			@unlink( $file_array['tmp_name'] );
		} else {
			add_post_meta( $image_id, 'autoblog_orig_image', $image );
		}

		return $image_id;
	}

	protected function _generate_image_name( $image ) {
		$matches   = array();
		$file_name = '';
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
		if ( is_array( $matches ) && count( $matches ) ) {
			$file_name = str_replace( '%20', '-', basename( $matches[0] ) );
		} else {
			$sizes = getimagesize( $image );
			if ( is_array( $sizes ) && isset( $sizes['mime'] ) && ! empty( $sizes['mime'] ) ) {
				$ext = image_type_to_extension( $sizes[2] );

				$file_name = substr( sanitize_title( pathinfo( $image, PATHINFO_FILENAME ) ), 0, 254 ) . $ext;
			}
		}

		return $file_name;

	}

}