<?php
/*
Addon Name: Featured Image Import
Description: Imports feed item featured image into the media library, attaches it to the imported post and marks it as featured image.
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

class A_FeatureImageCacheAddon {

	const SOURCE_THE_FIRST_IMAGE = 'ASC';
	const SOURCE_THE_LAST_IMAGE  = 'DESC';
	const SOURCE_MEDIA_THUMBNAIL = 'MEDIA';

	var $build = 1;

	/**
	 * The current database connection.
	 *
	 * @access private
	 * @var wpdb
	 */
	private $_db;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @global wpdb $wpdb The current database connection.
	 */
	public function __construct() {
		global $wpdb;

		$this->_db = $wpdb;

		add_action( 'autoblog_post_post_insert', array( $this, 'check_post_for_images' ), 10, 3 );
		add_action( 'autoblog_feed_edit_form_end', array( $this, 'render_image_options' ), 10, 2 );
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

		?><tr class="spacer">
			<td colspan="2" class="spacer">
				<span><?php _e( 'Featured Image Importing', 'autoblogtext' ) ?></span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="heading"><?php _e( 'Select a way to import featured image', 'autoblogtext' ) ?></td>
			<td valign="top">
				<?php foreach ( $options as $key => $label ) : ?>
				<div>
					<label>
						<input type="radio" name="abtble[featuredimage]" value="<?php echo $key ?>"<?php checked( $key, $selected_option ) ?>> <?php echo $label ?>
					</label>
				</div>
				<?php endforeach; ?>
			</td>
		</tr><?php
	}

	/**
	 * Finds all images in a feed content.
	 *
	 * @access private
	 * @param string $content The content of a feed item.
	 * @return array The array of images in the content.
	 */
	private function _get_remote_images_in_content( $content ) {
		$images = $matches = array();
		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				if ( filter_var( $url, FILTER_VALIDATE_URL ) && preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url ) ) {
					$images[] = $url;
				}
			}
		}

		return $images;
	}

	/**
	 * Downloads image and attaches it to a post.
	 *
	 * @access private
	 * @param string $image The image URL to download.
	 * @param int $post_id The post id to attach image to.
	 * @param string $orig_image The original image URL.
	 */
	private function _grab_image_from_url( $image, $post_id, $orig_image = false ) {
		// Include the file and media libraries as they have the functions we want to use
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Set a big timelimt for processing as we are pulling in potentially big files.
		set_time_limit( 600 );
		// Download file to temp location
		$tmp = download_url( $image );

		// add an extension if image URL doesn't have it
		$parts = explode( '?', $image );
		if ( !preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', current( $parts ) ) ) {
			$parts[0] .= '.png';
		}
		$image = implode( '?', $parts );

		// Set variables for storage
		// fix file filename for query strings
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff
		$id = media_handle_sideload( $file_array, $post_id );
		// If error storing permanently, unlink
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return false;
		}

		$newimage = array();
		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', wp_get_attachment_url( $id ), $newimage ) ) {
			if ( !empty( $newimage[1][0] ) ) {
				$theimg = $newimage[1][0];
				$parsed_url = parse_url( $theimg );
				if ( function_exists( 'get_blog_option' ) ) {
					$theimg = str_replace( "{$parsed_url['scheme']}://{$parsed_url['host']}", get_blog_option( $this->_db->blogid, 'siteurl' ), $theimg );
				}

				if ( $orig_image ) {
					$this->_db->query( $this->_db->prepare(
						"UPDATE {$this->_db->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE ID = %d",
						$orig_image,
						$theimg,
						$post_id
					) );
				}
			}
		}

		return $id;
	}

	/**
	 * Finds featured image and attached it to the post.
	 *
	 * @action autoblog_feed_edit_form_end
	 *
	 * @access public
	 * @param int $post_ID The post ID to attach featured image to.
	 * @param array $ablog The actual settings.
	 * @param SimplePie_Item $item The instance of SimplePie_Item class.
	 */
	public function check_post_for_images( $post_ID, $ablog, SimplePie_Item $item ) {
		$method = trim( isset( $ablog['featuredimage'] ) ? $ablog['featuredimage'] : AUTOBLOG_IMAGE_CHECK_ORDER );
		if ( empty( $method ) ) {
			return;
		}

		if ( $method == self::SOURCE_MEDIA_THUMBNAIL ) {
			$resutls = $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'thumbnail' );
			if ( isset( $resutls[0]['attribs']['']['url'] ) && filter_var( $resutls[0]['attribs']['']['url'], FILTER_VALIDATE_URL ) ) {
				$thumbnail_id = $this->_grab_image_from_url( $resutls[0]['attribs']['']['url'], $post_ID );
				if ( $thumbnail_id ) {
					set_post_thumbnail( $post_ID, $thumbnail_id );
				}
			}
			return;
		}

		// Reload the content as we need to work with the full content not just the excerpts
		$post_content = trim( html_entity_decode( $item->get_content(), ENT_QUOTES, 'UTF-8' ) );
		// Backup in case we can't get the post content again from the item
		if ( empty( $post_content ) ) {
			// Get the post so we can edit it.
			$post = get_post( $post_ID );
			if ( $post ) {
				$post_content = $post->post_content;
			}
		}

		$image = null;
		$images = $this->_get_remote_images_in_content( $post_content );
		switch ( $method ) {
			case self::SOURCE_THE_FIRST_IMAGE: $image = array_shift( $images ); break;
			case self::SOURCE_THE_LAST_IMAGE:  $image = array_pop( $images );   break;
		}

		if ( empty( $image ) ) {
			return;
		}

		$newimage = $image;
		$image_url = parse_url( $newimage );
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

		$thumbnail_id = $this->_grab_image_from_url( $newimage, $post_ID, $image );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $post_ID, $thumbnail_id );
		}
	}

}

// create an instance of add-on
$afeatureimagecacheaddon = new A_FeatureImageCacheAddon();