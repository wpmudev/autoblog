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
	 * Determines whether schedules were deregistered or not.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var boolean
	 */
	private $_deregistered_schedules = false;

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

		// upgrade the plugin
		$this->_upgrade();

		// load text domain
		$this->_add_action( 'plugins_loaded', 'load_textdomain' );

		// load network wide and blog wide addons
		$this->_add_action( 'plugins_loaded', 'load_addons' );
		$this->_add_action( 'plugins_loaded', 'load_network_addons' );

		// setup cron stuff
		$this->_add_action( 'shutdown', 'register_schedules' );
		register_deactivation_hook( AUTOBLOG_BASEFILE, array( $this, 'deregister_schedules' ) );
	}

	/**
	 * Registers scheduled events.
	 *
	 * @since 4.0.0
	 * @action shutdown
	 *
	 * @access public
	 */
	public function register_schedules() {
		if ( $this->_deregistered_schedules || ( defined( 'AUTOBLOG_PROCESSING_METHOD' ) && AUTOBLOG_PROCESSING_METHOD != 'cron' ) ) {
			return;
		}

		$minutes = defined( 'AUTOBLOG_PROCESSING_CHECKLIMIT' ) ? absint( AUTOBLOG_PROCESSING_CHECKLIMIT ) : 5;
		$interval = $minutes ? $minutes * MINUTE_IN_SECONDS : 300;

		// process feeds job
		if ( !wp_next_scheduled( Autoblog_Plugin::SCHEDULE_PROCESS ) ) {
			wp_schedule_single_event( time() + $interval, Autoblog_Plugin::SCHEDULE_PROCESS  );
		}
	}

	/**
	 * Deregisters scheduled events on plugin deactivation.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function deregister_schedules() {
		$this->_deregistered_schedules = true;

		// process feeds job
		$next_job = wp_next_scheduled( Autoblog_Plugin::SCHEDULE_PROCESS );
		if ( $next_job ) {
			wp_unschedule_event( $next_job, Autoblog_Plugin::SCHEDULE_PROCESS  );
		}
	}

	/**
	 * Performs upgrade plugin evnironment to up to date version.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _upgrade() {
		$filter = 'autoblog_database_upgrade';
		$option = 'autoblog_database_version';

		// fetch current database version
		$db_version = get_site_option( $option );
		if ( $db_version === false ) {
			$db_version = '0.0.0';
			update_site_option( $option, $db_version );
		}

		// check if current version is equal to database version, then there is nothing to upgrade
		if ( version_compare( $db_version, Autoblog_Plugin::VERSION, '=' ) ) {
			return;
		}

		// add upgrade functions
		$this->_add_filter( $filter, 'setup_database', 1 );
		$this->_add_filter( $filter, 'upgrade_to_4_0_0', 10 );

		// upgrade database version to current plugin version
		$db_version = apply_filters( $filter, $db_version );
		$db_version = version_compare( $db_version, Autoblog_Plugin::VERSION, '>=' )
			? $db_version
			: Autoblog_Plugin::VERSION;

		update_site_option( $option, $db_version );
	}

	/**
	 * Setups database table.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $current_version The current plugin version.
	 * @return string Unchanged version.
	 */
	public function setup_database( $current_version ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = '';

		if ( !empty( $this->_wpdb->charset ) ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $this->_wpdb->charset;
		}

		if ( !empty( $this->_wpdb->collate ) ) {
			$charset_collate .= ' COLLATE ' . $this->_wpdb->collate;
		}

		dbDelta( array(
			// feeds
			sprintf(
				'CREATE TABLE %s (
				  feed_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				  site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
				  blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
				  feed_meta TEXT,
				  active INT DEFAULT NULL,
				  nextcheck BIGINT UNSIGNED DEFAULT NULL,
				  lastupdated BIGINT UNSIGNED DEFAULT NULL,
				  PRIMARY KEY  (feed_id),
				  KEY site_id (site_id),
				  KEY blog_id (blog_id),
				  KEY nextcheck (nextcheck)
				) %s;',
				AUTOBLOG_TABLE_FEEDS,
				$charset_collate
			),

			// logs
			sprintf(
				'CREATE TABLE %s (
				  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				  feed_id BIGINT UNSIGNED NOT NULL,
				  cron_id BIGINT UNSIGNED NOT NULL,
				  log_at BIGINT UNSIGNED NOT NULL,
				  log_type TINYINT UNSIGNED NOT NULL,
				  log_info TEXT,
				  PRIMARY KEY  (log_id),
				  KEY feed_id (feed_id),
				  KEY cron_id (cron_id),
				  KEY feed_log_type (feed_id, log_type)
				) %s;',
				AUTOBLOG_TABLE_LOGS,
				$charset_collate
			),
		) );

		return $current_version;
	}

	/**
	 * Upgrades the plugin to the version 4.0.0
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $current_version The current plugin version.
	 * @return string Unchanged version.
	 */
	public function upgrade_to_4_0_0( $current_version ) {
		$this_version = '4.0.0';
		if ( version_compare( $current_version, $this_version, '>=' ) ) {
			return $current_version;
		}

		// remove deprecated options
		delete_site_option( 'autoblog_installed' );
		delete_option( 'autoblog_installed' );

		// remove deprecated logs
		$this->_wpdb->query( "DELETE FROM {$this->_wpdb->options} WHERE option_name LIKE 'autoblog_log_%'" );
		$this->_wpdb->query( "DELETE FROM {$this->_wpdb->sitemeta} WHERE site_id = {$this->_wpdb->siteid} AND meta_key LIKE 'autoblog_log_%'" );

		// remove deprecated scheduled event
		$next_schedule = wp_next_scheduled( 'autoblog_process_all_feeds_for_cron' );
		if ( $next_schedule ) {
			wp_unschedule_event( $next_schedule, 'autoblog_process_all_feeds_for_cron' );
		}

		return $this_version;
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
