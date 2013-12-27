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
 * General backend module.
 *
 * @category Autoblog
 * @package Module
 *
 * @since 4.0.0
 */
class Autoblog_Module_Backend extends Autoblog_Module {

	const NAME = __CLASS__;

	/**
	 * Array of admin pages hooks.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var array
	 */
	private $_admin_pages = array();

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

		// setup menu
		$this->_add_action( 'network_admin_menu', 'register_admin_menu' );
		$this->_add_action( 'admin_menu', 'register_admin_menu' );

		// setup scripts
		$this->_add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 4.0.0
	 * @action admin_enqueue_scripts
	 *
	 * @access public
	 * @param string $page_hook The current page hook.
	 */
	public function enqueue_scripts( $page_hook ) {
		if ( !in_array( $page_hook, $this->_admin_pages ) ) {
			return;
		}

		// load styles
		wp_enqueue_style( 'autoblogadmincss', AUTOBLOG_ABSURL . 'css/autoblog.css', array(), Autoblog_Plugin::VERSION );
		wp_enqueue_style( 'autoblog-admin', AUTOBLOG_ABSURL . 'css/admin.css', array(), Autoblog_Plugin::VERSION );

		// dashboard page scripts
		if ( $page_hook == $this->_admin_pages['dashboard'] ) {
			wp_enqueue_style( 'autoblog-bootstrap-glyphs', AUTOBLOG_ABSURL . 'css/bootstrap-glyphs.min.css', array(), '3.0.2' );

			wp_enqueue_script( 'autoblog-slimscroll', AUTOBLOG_ABSURL . 'js/jquery.slimscroll.min.js', array( 'jquery' ), Autoblog_Plugin::VERSION, true );
			wp_enqueue_script( 'autoblog-google-jsapi', '//www.google.com/jsapi', null, null, true );
			wp_enqueue_script( 'autoblog-dashboard', AUTOBLOG_ABSURL . 'js/dashboard.js', array( 'jquery', 'autoblog-google-jsapi', 'autoblog-slimscroll' ), Autoblog_Plugin::VERSION, true );

			wp_localize_script( 'autoblog-dashboard', 'autoblog', array(
				'date_column'      => __( 'Date', 'autoblogtext' ),
				'processes_column' => __( 'Processed Feeds', 'autoblogtext' ),
				'imports_column'   => __( 'Imported Items', 'autoblogtext' ),
				'errors_column'    => __( 'Errors', 'autoblogtext' ),
			) );
		}

		// feeds page scripts
		if ( $page_hook == $this->_admin_pages['feeds'] ) {
			wp_enqueue_script( 'autoblog-feeds', AUTOBLOG_ABSURL . 'js/feeds.js', array( 'jquery' ), Autoblog_Plugin::VERSION, true );
		}
	}

	/**
	 * Registers admin menu items.
	 *
	 * @since 4.0.0
	 * @action network_admin_menu
	 * @action admin_menu
	 *
	 * @access public
	 */
	public function register_admin_menu() {
		global $wpmudev_notices;

		$is_network_admin = is_network_admin();
		$capability = $is_network_admin ? 'manage_network_options' : 'manage_options';

		// autoblog menu
		$page_title = __( 'Auto Blog', 'autoblogtext' );
		$icon = AUTOBLOG_ABSURL . 'images/menu.png';
		add_menu_page( $page_title, $page_title, $capability, 'autoblog', array( $this, 'handle_dashboard_page' ), $icon );

		// adding dashboad submenu page
		$page_title = __( 'Auto Blog Dashboard', 'autoblogtext' );
		$menu_title = __( 'Dashboard', 'autoblogtext' );
		$this->_admin_pages['dashboard'] = add_submenu_page( 'autoblog', $page_title, $menu_title, $capability, 'autoblog',  array( $this, 'handle_dashboard_page' ) );

		// all feeds page
		$page_title = __( 'All feeds', 'autoblogtext' );
		$this->_admin_pages['feeds'] = add_submenu_page( 'autoblog', $page_title, $page_title, $capability, 'autoblog_admin', array( $this, 'handle_feeds_page' ) );

		// addons page
		$page_title = __( 'Autoblog Add-ons', 'autoblogtext' );
		$menu_title = __( 'Add-ons', 'autoblogtext' );
		if ( $is_network_admin ) {
			$this->_admin_pages['addons'] = add_submenu_page( 'autoblog', $page_title, $menu_title, $capability, 'autoblog_addons', array( $this, 'handle_network_addons_page' ) );
			do_action( 'autoblog_network_menu' );
		} else {
			$this->_admin_pages['addons'] = add_submenu_page( 'autoblog', $page_title, $menu_title, $capability, 'autoblog_addons', array( $this, 'handle_addons_page' ) );
			do_action( 'autoblog_site_menu' );
		}

		do_action( 'autoblog_global_menu' );

		$pages = array();
		foreach ( $this->_admin_pages as $page ) {
			$pages[] = $is_network_admin ? "{$page}-network" : $page;
		}

		$wpmudev_notices[] = array(
			'id'      => 97,
			'name'    => 'AutoBlog',
			'screens' => $pages,
		);
	}

	/**
	 * Handles dashboard page
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function handle_dashboard_page() {
		do_action( 'autoblog_handle_dashboard_page' );
	}

	/**
	 * Handles feeds page
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function handle_feeds_page() {
		do_action( 'autoblog_handle_feeds_page' );
	}

	/**
	 * Handles network addons page
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function handle_network_addons_page() {
		do_action( 'autoblog_handle_network_addons_page' );
	}

	/**
	 * Handles addons page
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function handle_addons_page() {
		do_action( 'autoblog_handle_addons_page' );
	}

}
