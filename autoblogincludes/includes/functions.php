<?php
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}
/* --------------------------------------------------------------------- */

function set_autoblog_url($base) {

	global $autoblog_url;

	if(defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$autoblog_url = trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/autoblog/' . basename($base))) {
		$autoblog_url = trailingslashit(WP_PLUGIN_URL . '/autoblog');
	} else {
		$autoblog_url = trailingslashit(WP_PLUGIN_URL . '/autoblog');
	}

}

function set_autoblog_dir($base) {

	global $autoblog_dir;

	if(defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$autoblog_dir = trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/autoblog/' . basename($base))) {
		$autoblog_dir = trailingslashit(WP_PLUGIN_DIR . '/autoblog');
	} else {
		$autoblog_dir = trailingslashit(WP_PLUGIN_DIR . '/autoblog');
	}


}

function autoblog_url($extended) {

	global $autoblog_url;

	return $autoblog_url . $extended;

}

function autoblog_dir($extended) {

	global $autoblog_dir;

	return $autoblog_dir . $extended;


}

function get_autoblog_option($key, $default = false) {

	if(defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
		return get_site_option($key, $default);
	} else {
		return get_option($key, $default);
	}

}

function update_autoblog_option($key, $value) {

	if(defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
		return update_site_option($key, $value);
	} else {
		return update_option($key, $value);
	}

}

function delete_autoblog_option($key) {

	if(defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
		return delete_site_option($key);
	} else {
		return delete_option($key);
	}

}

function autoblog_db_prefix(&$wpdb, $table) {

	if( defined('AUTOBLOG_GLOBAL') && AUTOBLOG_GLOBAL == true ) {
		if(!empty($wpdb->base_prefix)) {
			return $wpdb->base_prefix . $table;
		} else {
			return $wpdb->prefix . $table;
		}
	} else {
		return $wpdb->prefix . $table;
	}

}

function get_autoblog_addons() {
	if ( is_dir( autoblog_dir('autoblogincludes/addons') ) ) {
		if ( $dh = opendir( autoblog_dir('autoblogincludes/addons') ) ) {
			$auto_plugins = array ();
			while ( ( $plugin = readdir( $dh ) ) !== false )
				if ( substr( $plugin, -4 ) == '.php' )
					$auto_plugins[] = $plugin;
			closedir( $dh );
			sort( $auto_plugins );

			return apply_filters('autoblog_available_addons', $auto_plugins);

		}
	}

	return false;
}

function load_autoblog_addons() {

	$plugins = get_option('autoblog_activated_addons', array());

	if ( is_dir( autoblog_dir('autoblogincludes/addons') ) ) {
		if ( $dh = opendir( autoblog_dir('autoblogincludes/addons') ) ) {
			$auto_plugins = array ();
			while ( ( $plugin = readdir( $dh ) ) !== false )
				if ( substr( $plugin, -4 ) == '.php' )
					$auto_plugins[] = $plugin;
			closedir( $dh );
			sort( $auto_plugins );

			$auto_plugins = apply_filters('autoblog_available_addons', $auto_plugins);

			foreach( $auto_plugins as $auto_plugin ) {
				if(in_array($auto_plugin, (array) $plugins)) {
					include_once( autoblog_dir('autoblogincludes/addons/' . $auto_plugin) );
				}
			}

		}
	}
}

function load_networkautoblog_addons() {

	if(!function_exists('is_multisite') || !is_multisite()) {
		return;
	}

	$plugins = get_blog_option(1, 'autoblog_networkactivated_addons', array());

	if ( is_dir( autoblog_dir('autoblogincludes/addons') ) ) {
		if ( $dh = opendir( autoblog_dir('autoblogincludes/addons') ) ) {
			$auto_plugins = array ();
			while ( ( $plugin = readdir( $dh ) ) !== false )
				if ( substr( $plugin, -4 ) == '.php' )
					$auto_plugins[] = $plugin;
			closedir( $dh );
			sort( $auto_plugins );

			$auto_plugins = apply_filters('autoblog_available_addons', $auto_plugins);

			foreach( $auto_plugins as $auto_plugin ) {
				if(in_array($auto_plugin, (array) $plugins)) {
					include_once( autoblog_dir('autoblogincludes/addons/' . $auto_plugin) );
				}
			}

		}
	}
}

function load_all_autoblog_addons() {
	if ( is_dir( autoblog_dir('autoblogincludes/addons') ) ) {
		if ( $dh = opendir( autoblog_dir('autoblogincludes/addons') ) ) {
			$auto_plugins = array ();
			while ( ( $plugin = readdir( $dh ) ) !== false )
				if ( substr( $plugin, -4 ) == '.php' )
					$auto_plugins[] = $plugin;
			closedir( $dh );
			sort( $auto_plugins );

			$auto_plugins = apply_filters('autoblog_available_addons', $auto_plugins);

			foreach( $auto_plugins as $auto_plugin )
				include_once( autoblog_dir('autoblogincludes/addons/' . $auto_plugin) );
		}
	}
}

// Reltaive time function from http://stackoverflow.com/questions/2690504/php-producing-relative-date-time-from-timestamps
function autoblog_time2str($ts)
	{
		if(!ctype_digit($ts))
			$ts = strtotime($ts);

		$diff = current_time( 'timestamp' ) - $ts;
		if($diff == 0)
			return __('now', 'autoblogtext');
		elseif($diff > 0)
		{
			$day_diff = floor($diff / 86400);
			if($day_diff == 0)
			{
				if($diff < 60) return __('just now', 'autoblogtext');
				if($diff < 120) return __('1 minute ago', 'autoblogtext');
				if($diff < 3600) return floor($diff / 60) . __(' minutes ago', 'autoblogtext');
				if($diff < 7200) return __('1 hour ago', 'autoblogtext');
				if($diff < 86400) return floor($diff / 3600) . __(' hours ago', 'autoblogtext');
			}
			if($day_diff == 1) return __('Yesterday', 'autoblogtext');
			if($day_diff < 7) return $day_diff . __(' days ago', 'autoblogtext');
			if($day_diff < 31) return ceil($day_diff / 7) . __(' weeks ago', 'autoblogtext');
			if($day_diff < 60) return __('last month', 'autoblogtext');
			return date('F Y', $ts);
		}
		else
		{
			$diff = abs($diff);
			$day_diff = floor($diff / 86400);
			if($day_diff == 0)
			{
				if($diff < 120) return __('in a minute', 'autoblogtext');
				if($diff < 3600) return __('in ', 'autoblogtext') . floor($diff / 60) . __(' minutes', 'autoblogtext');
				if($diff < 7200) return __('in an hour', 'autoblogtext');
				if($diff < 86400) return __('in ', 'autoblogtext') . floor($diff / 3600) . __(' hours', 'autoblogtext');
			}
			if($day_diff == 1) return __('Tomorrow', 'autoblogtext');
			if($day_diff < 4) return date('l', $ts);
			if($day_diff < 7 + (7 - date('w'))) return __('next week', 'autoblogtext');
			if(ceil($day_diff / 7) < 4) return __('in ', 'autoblogtext') . ceil($day_diff / 7) . __(' weeks', 'autoblogtext');
			if(date('n', $ts) == date('n') + 1) return __('next month', 'autoblogtext');
			return date('F Y', $ts);
		}
	}
?>