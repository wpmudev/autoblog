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
 * The core plugin class.
 *
 * @category Autoblog
 *
 * @since 4.0.0
 */
class Autoblog_Plugin {

	const NAME    = 'autoblog';
	const VERSION = '4.0.0';

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var Autoblog_Plugin
	 */
	private static $_instance = null;

	/**
	 * The array of registered modules.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var array
	 */
	private $_modules = array();

	/**
	 * Private constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Private clone method.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function __clone() {}

	/**
	 * Returns singletone instance of the plugin.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access public
	 * @return Autoblog_Plugin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new Autoblog_Plugin();
		}

		return self::$_instance;
	}

	/**
	 * Returns a module if it was registered before. Otherwise NULL.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $name The name of the module to return.
	 * @return Autoblog_Module|null Returns a module if it was registered or NULL.
	 */
	public function get_module( $name ) {
		return isset( $this->_modules[$name] ) ? $this->_modules[$name] : null;
	}

	/**
	 * Determines whether the module has been registered or not.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $name The name of a module to check.
	 * @return boolean TRUE if the module has been registered. Otherwise FALSE.
	 */
	public function has_module( $name ) {
		return isset( $this->_modules[$name] );
	}

	/**
	 * Register new module in the plugin.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $module The name of the module to use in the plugin.
	 */
	public function set_module( $class ) {
		$this->_modules[$class] = new $class( $this );
	}

	/**
	 * Determines whether the plugin is network activated or not.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access public
	 * @return boolean TRUE if the plugin is network activated, otherwise FALSE.
	 */
	public static function is_network_wide() {
		return ( is_multisite() && is_plugin_active_for_network( plugin_basename( AUTOBLOG_BASEFILE ) ) ) || filter_var( AUTOBLOG_GLOBAL, FILTER_VALIDATE_BOOLEAN );
	}

}