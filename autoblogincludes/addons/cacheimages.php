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
	 * @since  4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_post_post_insert', 'import_post_images', 10, 3 );
		$this->_add_action( 'autoblog_post_post_update', 'import_post_images', 10, 3 );
	}

	/**
	 * Imports post images.
	 *
	 * @since  4.0.0
	 * @action autoblog_post_post_insert 10 2
	 * @action autoblog_post_post_update 10 2
	 *
	 * @access public
	 *
	 * @param int   $post_id The post id.
	 * @param array $details The feed settings.
	 */
	public function import_post_images( $post_id, $details, $item ) {
		$post       = get_post( $post_id );
		$new_images = $this->_import_post_images( $post->post_content, $details );
		if ( count( $new_images ) ) {
			$post->post_content = str_replace( array_keys( $new_images ), array_values( $new_images ), $post->post_content );
			wp_update_post( $post->to_array() );
		} else {
			//something happen, we will need to use the raw content, please note that simplepie will
			//sanitize content, this is the most case cause google news image don't display
			$new_images = $this->_import_post_images( $this->_get_simplepie_item_raw( $item ), $details );
			if ( count( $new_images ) ) {
				$replaced_content   = $this->_replace_content_with_new_images( $new_images, $post->post_content );
				$post->post_content = $replaced_content;
				wp_update_post( $post->to_array() );
			}
		}
	}

	public function _import_post_images( $content, $details ) {
		$images = $this->_get_remote_images_from_post_content( $content );
		if ( empty( $images ) ) {
			return;
		}

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );

		$new_images = array();
		foreach ( $images as $image ) {
			if ( $this->validate_images( $image ) == false ) {
				continue;
			}

			$newimage = $image;

			// Parse the image url
			$purl = autoblog_parse_mb_url( $newimage );
			// Parse the feed url
			$furl = autoblog_parse_mb_url( $details['url'] );

			if ( empty( $purl['host'] ) && ! empty( $furl['host'] ) ) {
				// We need to add in a host name as the images look like they are relative to the feed
				$newimage = trailingslashit( $furl['host'] ) . ltrim( $newimage, '/' );
			}

			if ( empty( $purl['scheme'] ) && ! empty( $furl['scheme'] ) ) {
				$newimage = substr( $newimage, 0, 2 ) == '//'
					? $furl['scheme'] . ':' . $newimage
					: $furl['scheme'] . '://' . $newimage;
			}
			$newimage_id = $this->_download_image( $newimage, $post_id );
			if ( $newimage_id ) {
				$new_images[$image] = wp_get_attachment_url( $newimage_id );
			}

			return $new_images;
		}
	}

	private function _replace_content_with_new_images( $new_images, $content ) {
		$new_images = array_values( $new_images );
		//get the image by index
		$doc         = new DOMDocument();
		$can_use_dom = @$doc->loadHTML( $content );
		if ( $can_use_dom ) {
			$imgs = $doc->getElementsByTagName( 'img' );
			foreach ( $imgs as $key => $img ) {
				//replace the old source with new source
				$new_source = @$new_images[$key];
				if ( ! empty( $new_source ) ) {
					$img->setAttribute( 'src', $new_source );
				}
			}

			return $doc->saveHTML();
		}

		return $content;
	}


	private function _get_simplepie_item_raw( $item ) {
		$content_namespaces = array(
			SIMPLEPIE_NAMESPACE_ATOM_10                => 'content',
			SIMPLEPIE_NAMESPACE_ATOM_03                => 'content',
			SIMPLEPIE_NAMESPACE_RSS_10_MODULES_CONTENT => 'content'
		);

		$summary_namespaces = array(
			SIMPLEPIE_NAMESPACE_ATOM_10 => 'summary',
			SIMPLEPIE_NAMESPACE_ATOM_03 => 'summary',
			SIMPLEPIE_NAMESPACE_RSS_10  => 'description',
			SIMPLEPIE_NAMESPACE_RSS_20  => 'description',
			SIMPLEPIE_NAMESPACE_DC_11   => 'description',
			SIMPLEPIE_NAMESPACE_DC_10   => 'description',
			SIMPLEPIE_NAMESPACE_ITUNES  => 'summary',
			SIMPLEPIE_NAMESPACE_ITUNES  => 'subtitle',
			SIMPLEPIE_NAMESPACE_RSS_090 => 'description',
		);

		$raw_content = '';
		foreach ( $content_namespaces as $key => $val ) {
			$return = $item->get_item_tags( $key, $val );
			if ( $return ) {
				$raw_content = $return[0]['data'];
			}
		}

		//if raw content still empty, get from summary
		foreach ( $summary_namespaces as $key => $val ) {
			$return = $item->get_item_tags( $key, $val );
			if ( $return ) {
				$raw_content = $return[0]['data'];
			}
		}

		return $raw_content;
	}

}

$aimagecacheaddon = new A_ImageCacheAddon();