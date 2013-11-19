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
 * Dashboard page module.
 *
 * @category Autoblog
 * @package Module
 * @subpackage Page
 *
 * @since 4.0.0
 */
class Autoblog_Module_Page_Dashboard extends Autoblog_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param Autoblog_Plugin $plugin The instance of the plugin.
	 */
	public function __construct( Autoblog_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_action( 'autoblog_handle_dashboard_page', 'handle_dashboard_page' );
	}

	/**
	 * Handles dashboard page.
	 *
	 * @since 4.0.0
	 * @action autoblog_handle_dashboard_page
	 *
	 * @access public
	 */
	public function handle_dashboard_page() {
		// logs
		$logs = array();

		// feeds
		$sites = array( empty( $this->_wpdb->siteid ) || $this->_wpdb->siteid == 0 ? 1 : $this->_wpdb->siteid );
		$blogs = array( get_current_blog_id() );
		if ( defined( 'AUTOBLOG_LAZY_ID' ) && AUTOBLOG_LAZY_ID == true ) {
			$sites[] = 0;
			$blogs[] = 0;
		}

		if ( is_network_admin() ) {
			$sql = "SELECT * FROM " . AUTOBLOG_TABLE_FEEDS . " WHERE site_id IN (" . implode( ',', $sites ) . ") ORDER BY feed_id DESC";
		} else {
			$sql = "SELECT * FROM " . AUTOBLOG_TABLE_FEEDS . " WHERE site_id IN (" . implode( ',', $sites ) . ") AND blog_id IN (" . implode( ',', $blogs ) . ") ORDER BY feed_id DESC";
		}

		$feeds = $this->_wpdb->get_results( $sql );

		// template
		$template = new Autoblog_Render_Dashboard_Page();
		$template->logs = $logs;
		$template->feeds = $feeds;
		$template->render();
	}

}