<?php
/*
Plugin Name: AutoBlog
Version: 4.0.5.beta.2
Plugin URI: http://premium.wpmudev.org/project/autoblog
Description: This plugin automatically posts content from RSS feeds to different blogs on your WordPress Multisite...
Author: Incsub
Author URI: http://premium.wpmudev.org/
WDP ID: 97
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

// prevent reloading the plugin, if it has been already loaded
if ( class_exists( 'Autoblog_Plugin', false ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/autoblogincludes/extra/wpmudev-dash-notification.php';

/**
 * Encodes URL component.
 *
 * @param array $matches Matches array.
 * @return string Encoded URL component.
 */
function autoblog_encode_url_component( $matches ) {
	return urlencode( $matches[0] );
}

/**
 * Parses URL addresses contained multibyte characters.
 *
 * @param string $url The URL to parse.
 * @return array The URL components.
 */
function autoblog_parse_mb_url( $url ) {
	return array_map( 'urldecode', parse_url( preg_replace_callback( '%[^:/?#&=\.]+%usD', 'autoblog_encode_url_component', $url ) ) );
}

/**
 * Sets plugin constatns.
 *
 * @since 4.0.0
 */
function autoblog_setup_constants() {
	if ( defined( 'AUTOBLOG_BASEFILE' ) ) {
		return;
	}

	define( 'AUTOBLOG_BASEFILE', __FILE__ );
	define( 'AUTOBLOG_ABSURL',   plugins_url( '/autoblogincludes/', __FILE__ ) );
	define( 'AUTOBLOG_ABSPATH',  dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

	// Processing will stop after 6 seconds (default) so as not to overload your server
	if ( !defined( 'AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT' ) ) {
		define( 'AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT', 60 );
	}

	// Feed fetching will stop after 10 seconds (default) so as not to overload your server
	if ( !defined( 'AUTOBLOG_FEED_FETCHING_TIMEOUT' ) ) {
		define( 'AUTOBLOG_FEED_FETCHING_TIMEOUT', 10 );
	}

	// To switch from a CRON processing method set this to 'pageload' (default is 'cron' to use the wp-cron).
	if ( !defined( 'AUTOBLOG_PROCESSING_METHOD' ) ) {
		define( 'AUTOBLOG_PROCESSING_METHOD', 'cron' );
	}

	// Information to use for duplicate checking - link or guid
	if ( !defined( 'AUTOBLOG_POST_DUPLICATE_CHECK' ) ) {
		define( 'AUTOBLOG_POST_DUPLICATE_CHECK', 'link' );
	}

	// Information to use for duplicate checking - link or guid
	if ( !defined( 'AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS' ) ) {
		define( 'AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS', '' );
	}

	// Order to check images to pick which will be the one to be a featured image
	if ( !defined( 'AUTOBLOG_IMAGE_CHECK_ORDER' ) ) {
		define( 'AUTOBLOG_IMAGE_CHECK_ORDER', 'ASC' );
	}

	// The time to live for dashboard cache, default is 30 minutes
	if ( !defined( 'AUTOBLOG_DASHBOARD_CACHE_TTL' ) ) {
		define( 'AUTOBLOG_DASHBOARD_CACHE_TTL', HOUR_IN_SECONDS / 2 );
	}

	// The amount of days to keep log alive
	if ( !defined( 'AUTOBLOG_DASHBOARD_LOG_TTL' ) ) {
		define( 'AUTOBLOG_DASHBOARD_LOG_TTL', 2 );
	}
}

/**
 * Setups database related constants.
 *
 * @since 4.0.0
 *
 * @global wpdb $wpdb The instance of database connection.
 */
function autoblog_setup_db_constants() {
	global $wpdb;

	if ( defined( 'AUTOBLOG_TABLE_FEEDS' ) ) {
		return;
	}

	$feeds_table = 'autoblog';
	$logs_table = 'autoblog_log';

	$prefix = isset( $wpdb->base_prefix ) ? $wpdb->base_prefix : $wpdb->prefix;

	define( 'AUTOBLOG_TABLE_FEEDS', $prefix . $feeds_table );
	define( 'AUTOBLOG_TABLE_LOGS', $prefix . $logs_table );

	// MultiDB compatibility, register global tables
	if ( defined( 'MULTI_DB_VERSION' ) && function_exists( 'add_global_table' ) ) {
		add_global_table( $feeds_table );
		add_global_table( $logs_table );
	}
}

/**
 * Automatically loads classes for the plugin. Checks a namespace and loads only
 * approved classes.
 *
 * @since 4.0.0
 *
 * @param string $class The class name to autoload.
 * @return boolean Returns TRUE if the class is located. Otherwise FALSE.
 */
function autoblog_autoloader( $class ) {
	$basedir = dirname( __FILE__ );
	$namespaces = array( 'Autoblog', 'WPMUDEV' );
	foreach ( $namespaces as $namespace ) {
		if ( substr( $class, 0, strlen( $namespace ) ) == $namespace ) {
			$filename = $basedir . str_replace( '_', DIRECTORY_SEPARATOR, "_autoblogincludes_classes_{$class}.php" );
			if ( is_readable( $filename ) ) {
				require $filename;
				return true;
			}
		}
	}

	return false;
}

/**
 * Instantiates the plugin and setup all modules.
 *
 * @since 4.0.0
 */
function autoblog_launch() {
	// setup constatns
	autoblog_setup_constants();
	// setup database constants
	autoblog_setup_db_constants();

	// instantiate the plugin
	$plugin = Autoblog_Plugin::instance();

	// set general modules
	$plugin->set_module( Autoblog_Module_System::NAME );
	$plugin->set_module( Autoblog_Module_Cron::NAME );

	// conditional modules
	if ( is_admin() ) {
		// set admin modules
		$plugin->set_module( Autoblog_Module_Backend::NAME );

		$plugin->set_module( Autoblog_Module_Page_Feeds::NAME );
		$plugin->set_module( Autoblog_Module_Page_Addons::NAME );
		$plugin->set_module( Autoblog_Module_Page_Dashboard::NAME );
	} else {
		// set front end
		$plugin->set_module( Autoblog_Module_Frontend::NAME );
	}
}

// register autoloader function
spl_autoload_register( 'autoblog_autoloader' );

// launch the plugin
autoblog_launch();