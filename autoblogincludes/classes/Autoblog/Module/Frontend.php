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

/**
 * Front end module.
 *
 * @category Autoblog
 * @package Module
 *
 * @since 4.0.0
 */
class Autoblog_Module_Frontend extends Autoblog_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param Autoblog_Plugin $plugin The plugin instance.
	 */
	public function __construct( Autoblog_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_filter( 'the_content', 'add_original_feed', PHP_INT_MAX-1 );
	}

	/**
	 * Updates post content by adding original URL link if need be.
	 *
	 * @since 4.0.0
	 * @filter the_content PHP_INT_MAX
	 *
	 * @access public
	 * @global WP_Post $post Current post object.
	 * @param string $content Initial post content.
	 * @return string Updated post content.
	 */
	public function add_original_feed( $content ) {
		global $post;

		if ( empty( $post ) ) {
			return $content;
		}

		$source = trim( get_post_meta( $post->ID, 'original_source_link_html', true ) );
		if ( !empty( $source ) ) {
			$content .= "<p>{$source}</p>";
		}

		return $content;
	}

}