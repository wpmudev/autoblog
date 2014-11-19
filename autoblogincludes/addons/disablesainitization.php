<?php
/*
Addon Name: Disable Sanitization
Description: Allows you to override feed content sanitization and force a feed to import bare content even if it has usually blocked tags. This can help with compatibility for unusual feeds. Use with caution.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

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

class A_DisableSanitization extends Autoblog_Addon {

	/**
	 * Constructor
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
    public function __construct() {
		parent::__construct();

		$this->_add_action( 'autoblog_feed_edit_form_end', 'add_feed_option', 12, 2 );
		$this->_add_action( 'autoblog_feed_pre_process_setup', 'set_feed_sanitize_object', 10, 2 );
	}

	/**
	 * Sets feed sanitize object depending on the feed settings.
	 *
	 * @since 4.0.0
	 * @action autoblog_feed_pre_process_setup 10 2
	 *
	 * @access public
	 * @param SimplePie $feed The SimplePie feed.
	 * @param array $details The feed details.
	 */
	public function set_feed_sanitize_object( SimplePie $feed, array $details ) {
		if ( isset( $details['disablesanitization'] ) && $details['disablesanitization'] == 1 ) {
			// setup sanitize filter. we need to manually override sanitize object
			// because it is already been initialized.
			$feed->sanitize = new Autoblog_SimplePie_Sanitize();
		}
	}

	/**
	 * Renders feed options.
	 *
	 * @since 4.0.0
	 * @action autoblog_feed_edit_form_end 10 2
	 *
	 * @access public
	 * @param string $key
	 * @param array $details The feed details.
	 */
	public function add_feed_option( $key, $details ) {
        $table = !empty( $details->feed_meta ) ? maybe_unserialize( $details->feed_meta ) : array();

		$this->_render_block_header( esc_html__( 'Disable Feed Sanitization', 'autoblogtext' ) );

		$this->_render_block_element(
			esc_html__( 'Disable Sanitization', 'autoblogtext' ),
			sprintf( '<input type="checkbox" name="abtble[disablesanitization]" value="1"%s>', checked( isset( $table['disablesanitization'] ) && $table['disablesanitization'] == '1', true, false ) ),
			__( 'Disable feed content sanitization only if you know what you are doing or you confident in the feed content.', 'autologtext' )
		);
    }

}

$a_disablesanitization = new A_DisableSanitization();