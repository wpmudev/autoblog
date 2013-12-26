<?php
/*
Addon Name: Featured Image Import
Description: Imports feed item featured image into the media library, attaches it to the imported post and marks it as featured image.
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

class A_FeatureImageCacheAddon extends Autoblog_Addon_Image {

	const SOURCE_THE_FIRST_IMAGE = 'ASC';
	const SOURCE_THE_LAST_IMAGE  = 'DESC';
	const SOURCE_MEDIA_THUMBNAIL = 'MEDIA';

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_post_post_insert', 'check_post_for_images', 10, 3 );
		$this->_add_action( 'autoblog_feed_edit_form_end', 'render_image_options', 10, 2 );
	}

	/**
	 * Renders add-on's options.
	 *
	 * @action autoblog_post_post_insert
	 *
	 * @access public
	 * @param type $key
	 * @param type $details
	 */
	public function render_image_options( $key, $details ) {
		$table = !empty( $details->feed_meta )
			? maybe_unserialize( $details->feed_meta )
			: array();

		$selected_option = apply_filters( 'autoblog_featuredimage_from', isset( $table['featuredimage'] ) ? $table['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER );
		$options = array(
			''                           => __( "Don't import featured image", 'autobogtext' ),
			self::SOURCE_MEDIA_THUMBNAIL => __( 'Use media:thumbnail tag of a feed item', 'autoblogtext' ),
			self::SOURCE_THE_FIRST_IMAGE => __( 'Find the first image withing content of a feed item', 'autoblogtext' ),
			self::SOURCE_THE_LAST_IMAGE  => __( 'Find the last image withing content of a feed item', 'autoblogtext' ),
		);

		$element = '';
		foreach ( $options as $key => $label ) {
			$element .= sprintf(
				'<div><label><input type="radio" name="abtble[featuredimage]" value="%s"%s> %s</label></div>',
				esc_attr( $key ),
				checked( $key, $selected_option, false ),
				esc_html( $label )
			);
		}

		// render block header
		$this->_render_block_header( __( 'Featured Image Importing', 'autoblogtext' ) );

		// render block elements
		$this->_render_block_element( __( 'Select a way to import featured image', 'autoblogtext' ), $element );
	}

	/**
	 * Finds featured image and attached it to the post.
	 *
	 * @action autoblog_feed_edit_form_end
	 *
	 * @access public
	 * @param int $post_id The post ID to attach featured image to.
	 * @param array $ablog The actual settings.
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 */
	public function check_post_for_images( $post_id, $ablog, SimplePie_Item $item ) {
		$method = trim( isset( $ablog['featuredimage'] ) ? $ablog['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER );
		if ( empty( $method ) ) {
			return;
		}

		if ( $method == self::SOURCE_MEDIA_THUMBNAIL ) {
			$resutls = $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'thumbnail' );
			if ( isset( $resutls[0]['attribs']['']['url'] ) && filter_var( $resutls[0]['attribs']['']['url'], FILTER_VALIDATE_URL ) ) {
				$thumbnail_id = $this->_download_image( $resutls[0]['attribs']['']['url'], $post_id );
				if ( $thumbnail_id ) {
					set_post_thumbnail( $post_id, $thumbnail_id );
				}
			}
			return;
		}

		$post = get_post( $post_id );
		$images = $this->_get_remote_images_from_content( $post->post_content );
		if ( empty( $images ) ) {
			return;
		}

		$image = null;
		switch ( $method ) {
			case self::SOURCE_THE_FIRST_IMAGE: $image = array_shift( $images ); break;
			case self::SOURCE_THE_LAST_IMAGE:  $image = array_pop( $images );   break;
		}

		if ( empty( $image ) ) {
			return;
		}

		// Include the file and media libraries as they have the functions we want to use
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );

		$newimage = $image;
		$image_url = autoblog_parse_mb_url( $newimage );
		$blog_url = parse_url( $ablog['url'] );

		if ( empty( $image_url['host'] ) && !empty( $blog_url['host'] ) ) {
			// We need to add in a host name as the images look like they are relative to the feed
			$newimage = trailingslashit( $blog_url['host'] ) . ltrim( $newimage, '/' );
		}

		if ( empty( $image_url['scheme'] ) && !empty( $blog_url['scheme'] ) ) {
			$newimage = substr( $newimage, 0, 2 ) == '//'
				? $blog_url['scheme'] . ':' . $newimage
				: $blog_url['scheme'] . '://' . $newimage;
		}

		$thumbnail_id = $this->_download_image( $newimage, $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		}
	}

}

// create an instance of add-on
$afeatureimagecacheaddon = new A_FeatureImageCacheAddon();