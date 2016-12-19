<?php

/*
Addon Name: Featured Image Import
Description: Imports feed item featured image into the media library, attaches it to the imported post and marks it as featured image.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class A_FeatureImageCacheAddon extends Autoblog_Addon_Image {

	const SOURCE_THE_FIRST_IMAGE = 'ASC';
	const SOURCE_THE_LAST_IMAGE = 'DESC';
	const SOURCE_MEDIA_THUMBNAIL = 'MEDIA';
	const SOURCE_ENCLOSURE = 'ENCLOSURE';

	/**
	 * Constructor.
	 *
	 * @since  4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_post_post_insert', 'check_post_for_images', 10, 3 );
		$this->_add_action( 'autoblog_post_post_update', 'check_post_for_images', 10, 3 );
		$this->_add_action( 'autoblog_feed_edit_form_end', 'render_image_options', 10, 2 );
	}

	/**
	 * Renders add-on's options.
	 *
	 * @action autoblog_feed_edit_form_end 10 2
	 *
	 * @access public
	 *
	 * @param type $key
	 * @param type $details
	 */
	public function render_image_options( $key, $details ) {
		$table = ! empty( $details->feed_meta )
			? maybe_unserialize( $details->feed_meta )
			: array();

		$selected_option = apply_filters( 'autoblog_featuredimage_from', isset( $table['featuredimage'] ) ? $table['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER );
		$options         = array(
			''                           => __( "Don't import featured image", 'autobogtext' ),
			self::SOURCE_MEDIA_THUMBNAIL => __( 'Use media:thumbnail tag of a feed item', 'autoblogtext' ),
			self::SOURCE_ENCLOSURE       => __( 'Use enclosure tag of a feed item', 'autoblogtext' ),
			self::SOURCE_THE_FIRST_IMAGE => __( 'Find the first image within content of a feed item', 'autoblogtext' ),
			self::SOURCE_THE_LAST_IMAGE  => __( 'Find the last image within content of a feed item', 'autoblogtext' ),
		);

		$radio = '';
		foreach ( $options as $key => $label ) {
			$radio .= sprintf(
				'<div><label><input type="radio" name="abtble[featuredimage]" value="%s"%s> %s</label></div>',
				esc_attr( $key ),
				checked( $key, $selected_option, false ),
				esc_html( $label )
			);
		}
		$radio .= '<br>';

		$thumbnail_src = '';
		$thumbnail_id  = isset( $table['featureddefault'] ) ? absint( $table['featureddefault'] ) : 0;
		if ( $thumbnail_id ) {
			$image = wp_get_attachment_image_src( $thumbnail_id, 'medium' );
			if ( ! empty( $image ) ) {
				$thumbnail_src = $image[0];
			}
		}

		$default = sprintf(
			'<input type="hidden" name="abtble[featureddefault]" value="%s">
			<button type="button" class="button button-secondary" id="featureddefault_select">%s</button>
			<button type="button" class="button button-secondary" id="featureddefault_delete">%s</button>
			<div><img src="%s" style="width:150px;height:auto;margin-top:10px;"></div>',
			$thumbnail_id,
			__( 'Select Default Image', 'autoblogtext' ),
			__( 'Delete Image', 'autoblogtext' ),
			$thumbnail_src
		);

		// render block header
		$this->_render_block_header( __( 'Featured Image Importing', 'autoblogtext' ) );

		// render block elements
		$this->_render_block_element( __( 'Select a way to import featured image', 'autoblogtext' ), $radio );
		$this->_render_block_element( __( 'Default thumbnail image', 'autoblogtext' ), $default );
	}

	/**
	 * Finds image in media:thumbnail tag.
	 *
	 * @since  4.0.6
	 *
	 * @access private
	 *
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 * @param int $post_id The post ID to attach featured image to.
	 * @param array $details The actual feed settings.
	 */
	private function _check_post_for_media_thumbnail_images( SimplePie_Item $item, $post_id, $details ) {
		$set     = false;
		$resutls = $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'thumbnail' );
		if ( isset( $resutls[0]['attribs']['']['url'] ) && filter_var( $resutls[0]['attribs']['']['url'], FILTER_VALIDATE_URL ) ) {
			$thumbnail_id = $this->_download_image( $resutls[0]['attribs']['']['url'], $post_id );
			if ( $thumbnail_id ) {
				$set = set_post_thumbnail( $post_id, $thumbnail_id );
			}
		}

		if ( ! $set ) {
			//the thumbnail can be in a media group, so we will parse again
			$resutls = $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'group' );
			if ( isset( $resutls[0]['child'][ SIMPLEPIE_NAMESPACE_MEDIARSS ]['thumbnail'][0]['attribs'] ) ) {
				$thumb = $resutls[0]['child'][ SIMPLEPIE_NAMESPACE_MEDIARSS ]['thumbnail'][0]['attribs'];
				$thumb = array_shift( $thumb );
				if ( is_array( $thumb ) && isset( $thumb['url'] ) ) {
					$thumbnail_id = $this->_download_image( $thumb['url'], $post_id );
					$set          = set_post_thumbnail( $post_id, $thumbnail_id );

				}
			}
		}

		if ( ! $set ) {
			$this->_set_default_image( $post_id, $details );
		}
	}

	/**
	 * Finds image in enclosure tag.
	 *
	 * @since  4.0.6
	 *
	 * @access private
	 *
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 * @param int $post_id The post ID to attach featured image to.
	 * @param array $details The actual feed settings.
	 */
	private function _check_post_for_enclosure_images( SimplePie_Item $item, $post_id, $details ) {
		$set = false;

		$enclosure = $item->get_enclosure();
		if ( is_a( $enclosure, 'SimplePie_Enclosure' ) ) {
			$link      = $enclosure->get_link();
			var_dump($enclosure->embed());
			$file_type = wp_check_filetype( $link );
			$mime_type = $file_type['type'];
			$type      = current( explode( '/', $mime_type, 2 ) );
			if ( in_array( $mime_type, get_allowed_mime_types() ) && $type == 'image' && filter_var( $link, FILTER_VALIDATE_URL ) ) {
				$thumbnail_id = $this->_download_image( $link, $post_id );
				if ( $thumbnail_id ) {
					$set = set_post_thumbnail( $post_id, $thumbnail_id );
				}
			}
		}

		if ( ! $set ) {

		}

		if ( ! $set ) {
			$this->_set_default_image( $post_id, $details );
		}
	}

	/**
	 * Findes featured image in the item content.
	 *
	 * @since  4.0.6
	 *
	 * @access private
	 *
	 * @param string $method The method of finding.
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 * @param int $post_id The post ID to attach featured image to.
	 * @param array $details The actual feed settings.
	 */
	private function _check_post_for_content_images( $method, SimplePie_Item $item, $post_id, $details ) {
		$images = $this->_get_remote_images_from_post_content( html_entity_decode( $item->get_content(), ENT_QUOTES, 'UTF-8' ) );
		if ( ! empty( $images ) ) {
			foreach ( $images as $key => $value ) {
				if ( $this->validate_images( $value ) == false ) {
					unset( $images[ $key ] );
				}
			}
		}

		if ( empty( $images ) ) {
			//we will use the raw content for getting images
			$images = $this->_get_remote_images_from_post_content( $this->get_simplepie_item_raw( $item ) );
			if ( ! empty( $images ) ) {
				foreach ( $images as $key => $value ) {
					if ( $this->validate_images( $value ) == false ) {
						unset( $images[ $key ] );
					}
				}
			}
		}

		if ( ! is_array( $images ) || count( $images ) == 0 ) {
			$this->_set_default_image( $post_id, $details );

			return;
		}

		$image = null;
		switch ( $method ) {
			case self::SOURCE_THE_FIRST_IMAGE:
				$image = array_shift( $images );
				break;
			case self::SOURCE_THE_LAST_IMAGE:
				$image = array_pop( $images );
				break;
		}

		if ( empty( $image ) ) {


			return;
		}

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );

		$newimage  = $image;
		$image_url = autoblog_parse_mb_url( $newimage );
		$blog_url  = parse_url( $details['url'] );

		if ( empty( $image_url['host'] ) && ! empty( $blog_url['host'] ) ) {
			// We need to add in a host name as the images look like they are relative to the feed
			$newimage = trailingslashit( $blog_url['host'] ) . ltrim( $newimage, '/' );
		}

		if ( empty( $image_url['scheme'] ) && ! empty( $blog_url['scheme'] ) ) {
			$newimage = substr( $newimage, 0, 2 ) == '//'
				? $blog_url['scheme'] . ':' . $newimage
				: $blog_url['scheme'] . '://' . $newimage;
		}

		$thumbnail_id = $this->_download_image( $newimage, $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		} else {
			$this->_set_default_image( $post_id, $details );
		}
	}

	/**
	 * Finds featured image and attached it to the post.
	 *
	 * @since  4.0.0
	 * @action autoblog_post_post_insert 10 3
	 *
	 * @access public
	 *
	 * @param int $post_id The post ID to attach featured image to.
	 * @param array $details The actual feed settings.
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 */
	public function check_post_for_images( $post_id, $details, SimplePie_Item $item ) {
		$method = trim( isset( $details['featuredimage'] ) ? $details['featuredimage'] : '' );
		if ( empty( $method ) ) {
			return;
		}

		if ( $method == self::SOURCE_MEDIA_THUMBNAIL ) {
			$this->_check_post_for_media_thumbnail_images( $item, $post_id, $details );
		} elseif ( $method == self::SOURCE_ENCLOSURE ) {
			$this->_check_post_for_enclosure_images( $item, $post_id, $details );
		} else {
			$this->_check_post_for_content_images( $method, $item, $post_id, $details );
		}
	}

	/**
	 * Sets default thumbnail if it has been selected.
	 *
	 * @since  4.0.4
	 *
	 * @access private
	 *
	 * @param int $post_id The post id.
	 * @param array $details The feed details.
	 */
	private function _set_default_image( $post_id, $details ) {
		$default = isset( $details['featureddefault'] ) ? absint( $details['featureddefault'] ) : 0;
		if ( $default ) {
			$image = wp_get_attachment_image( $default, 'medium' );
			if ( ! empty( $image ) ) {
				set_post_thumbnail( $post_id, $default );
			}
		}
	}

}

// create an instance of add-on
$afeatureimagecacheaddon = new A_FeatureImageCacheAddon();