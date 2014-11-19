<?php
/*
Addon Name: Append text to post
Description: Allows some text to be appended to each post with variable placeholders
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
 */

class A_appendtexttopost extends Autoblog_Addon {

	/**
	 * The array of keywords available to use in the footer content.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access private
	 * @var array
	 */
	private static $_keywords = array(
		'%ORIGINALPOSTURL%'     => 'original_source',
		'%FEEDURL%'             => 'original_feed',
		'%FEEDTITLE%'           => 'original_feed_title',
		'%POSTIMPORTEDTIME%'    => 'original_imported_time',
		'%FEEDID%'              => 'original_feed_id',
		'%ORIGINALPOSTGUID%'    => 'original_guid',
		'%ORIGINALAUTHORNAME%'  => 'original_author_name',
		'%ORIGINALAUTHOREMAIL%' => 'original_author_email',
		'%ORIGINALAUTHORLINK%'  => 'original_author_link',
	);

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_feed_edit_form_end', 'add_footer_options', 10, 2 );
		$this->_add_filter( 'the_content', 'append_footer_content', 11 );
	}

	/**
	 * Renders addon options.
	 *
	 * @since 4.0.0
	 * @action autoblog_feed_edit_form_end 10 2
	 *
	 * @access public
	 * @param type $key
	 * @param type $details
	 */
	public function add_footer_options( $key, $details ) {
		$table = !empty( $details ) ? maybe_unserialize( $details->feed_meta ) : array();

		$label = sprintf(
			'%s<br><br>%s<br><br><em>%s</em>',
			__( 'Post footer text', 'autoblogtext' ),
			__( 'You can use the following placeholders in your footer:', 'autoblogtext' ),
			implode( '<br>', array_keys( self::$_keywords ) )
		);

		ob_start();
		wp_editor( isset( $table['footertext'] ) ? stripcslashes( $table['footertext'] ) : '', "abtble-footertext", array(
			"textarea_name" => "abtble[footertext]",
			"textarea_rows" => 10,
		) );
		$element = ob_get_clean();

		// render block header
		$this->_render_block_header( __( 'Append text to post content', 'autoblogtext' ) );

		// render block elements
		$this->_render_block_element( $label, $element );
	}

	/**
	 * Appends footer content to a post.
	 *
	 * @since 4.0.0
	 * @filter the_content 11
	 *
	 * @access public
	 * @global WP_Post $post The current post object.
	 * @param string $content The content of the post object.
	 * @return Updated content with appended footer.
	 */
	public function append_footer_content( $content ) {
		global $post;
		if ( !is_object( $post ) || empty( $post->ID ) ) {
			return $content;
		}

		// Get the feed id so we can get hold of the footer content
		$feed_id = get_post_meta( $post->ID, 'original_feed_id', true );
		if ( !empty( $feed_id ) ) {
			// We have a feed id so the post was imported, so grab the footer content
			$feed = $this->_wpdb->get_row( sprintf( "SELECT * FROM %s WHERE feed_id = %d", AUTOBLOG_TABLE_FEEDS, $feed_id ) );
			$table = !empty( $feed ) ? maybe_unserialize( $feed->feed_meta ) : array();

			// If the footer isn't empty then we will use it
			if ( !empty( $table['footertext'] ) ) {
				$footertext = stripcslashes( $table['footertext'] );
				// Do the search and replace of variables
				$stripos_funct = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';
				foreach ( self::$_keywords as $key => $meta_name ) {
					$position = $stripos_funct( $footertext, $key );
					if ( $position !== false ) {
						$footertext = str_replace( $key, get_post_meta( $post->ID, $meta_name, true ), $footertext );
					}
				}

				// Add the footer to the bottom of the content
				$content .= do_shortcode( $footertext );
			}
		}

		return $content;
	}

}

$aappendtexttopost = new A_appendtexttopost();