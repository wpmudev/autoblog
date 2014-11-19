<?php
/*
Addon Name: Post formats addon
Description: Allows a post format to be selected for a feed.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class A_PostFormats extends Autoblog_Addon {

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_feed_edit_form_details_end', 'add_post_formats_options', 10, 2 );
		$this->_add_action( 'autoblog_post_post_insert', 'set_post_format', 10, 2 );
	}

	/**
	 * Renders options.
	 *
	 * @since 4.0.0
	 * @action autoblog_feed_edit_form_details_end 10 2
	 *
	 * @access public
	 * @param type $key
	 * @param array $details The array of feed options.
	 */
	public function add_post_formats_options( $key, $details ) {
		$table = maybe_unserialize( $details->feed_meta );

		// render block header
		$this->_render_block_header( __( 'Post format for new posts', 'autoblogtext' ) );

		// render block elements
		$element = '<select name="abtble[postformat]" class="field">';
		foreach ( get_post_format_strings() as $key => $format ) {
			$element .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( isset( $table['postformat'] ) && $table['postformat'] == $key, true, false ),
				esc_html( $format )
			);
		}
		$element .= '</select>';

		$this->_render_block_element( __( 'Select post format', 'autoblogtext' ), $element );
	}

	/**
	 * Sets post format.
	 *
	 * @since 4.0.0
	 * @action autoblog_post_post_insert 10 2
	 *
	 * @access public
	 * @param int $post_id The post id.
	 * @param array $details The array of feed settings.
	 */
	public function set_post_format( $post_id, $details ) {
		if ( !empty( $details['postformat'] ) ) {
			set_post_format( $post_id, $details['postformat'] );
		} else {
			set_post_format( $post_id, 'standard' );
		}
	}

}

new A_PostFormats;