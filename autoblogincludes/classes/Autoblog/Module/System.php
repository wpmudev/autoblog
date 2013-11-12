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
 * System module.
 *
 * @category Autoblog
 * @package Module
 *
 * @since 4.0.0
 */
class Autoblog_Module_System extends Autoblog_Module {

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

		$this->_add_action( 'plugins_loaded', 'load_textdomain' );

		$this->_add_action( 'plugins_loaded', 'load_addons' );
		$this->_add_action( 'plugins_loaded', 'load_network_addons' );
	}

	/**
	 * Loads text domain.
	 *
	 * @since 4.0.0
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'autoblogtext', false, dirname( plugin_basename( AUTOBLOG_BASEFILE ) ) . '/autoblogincludes/languages/' );
	}

	/**
	 * Loads autoblog addons.
	 *
	 * @since 4.0.0
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function load_addons() {
		$directory = AUTOBLOG_ABSPATH . 'autoblogincludes/addons/';
		if ( !is_dir( $directory ) ) {
			return;
		}

		if ( ( $dh = opendir( $directory ) ) ) {
			$auto_plugins = array();
			while ( ( $plugin = readdir( $dh ) ) !== false ) {
				if ( substr( $plugin, -4 ) == '.php' ) {
					$auto_plugins[] = $plugin;
				}
			}
			closedir( $dh );
			sort( $auto_plugins );

			$plugins = (array)get_option( 'autoblog_activated_addons', array() );
			$auto_plugins = apply_filters( 'autoblog_available_addons', $auto_plugins );

			foreach ( $auto_plugins as $auto_plugin ) {
				if ( in_array( $auto_plugin, $plugins ) ) {
					include_once $directory . $auto_plugin;
				}
			}
		}
	}

	/**
	 * Loads network wide autoblog addons.
	 *
	 * @since 4.0.0
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function load_network_addons() {
		$directory = AUTOBLOG_ABSPATH . 'autoblogincludes/addons/';
		if ( !is_multisite() || !is_dir( $directory ) ) {
			return;
		}

		if ( ( $dh = opendir( $directory ) ) ) {
			$auto_plugins = array();
			while ( ( $plugin = readdir( $dh ) ) !== false ) {
				if ( substr( $plugin, -4 ) == '.php' ) {
					$auto_plugins[] = $plugin;
				}
			}

			closedir( $dh );
			sort( $auto_plugins );

			$auto_plugins = apply_filters( 'autoblog_available_addons', $auto_plugins );
			$plugins = (array)get_blog_option( 1, 'autoblog_networkactivated_addons', array() );

			foreach ( $auto_plugins as $auto_plugin ) {
				if ( in_array( $auto_plugin, $plugins ) ) {
					include_once $directory . $auto_plugin;
				}
			}
		}
	}

}
