<?php

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

function clear_autoblog_logs( $startat = 25, $number = 100 ) {

	global $wpdb;

	if(defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
		$sql = $wpdb->prepare( "SELECT meta_id FROM {$wpdb->sitemeta} WHERE site_id = %d AND meta_key LIKE %s ORDER BY meta_id DESC LIMIT %d, %d", $wpdb->siteid, "autoblog_log_%", $startat, $number );
		$ids = $wpdb->get_col( $sql );

		if(!empty($ids)) {
			$sql2 = $this->db->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d AND meta_id IN (" . implode(',', $ids) . ")", $wpdb->siteid);
			$wpdb->query( $sql2 );
		}
	} else {
		$sql = $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT %d, %d", "autoblog_log_%", $startat, $number );
		$ids = $wpdb->get_col( $sql );

		if(!empty($ids)) {
			$sql2 = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_id IN (" . implode(',', $ids) . ")" );
			$wpdb->query( $sql2 );
		}
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

	function ab_process_feed($id, $details) {

		global $abc;

		return $abc->process_the_feed($id, $details);

	}

	function ab_process_feeds($ids) {

		global $abc;

		return $abc->process_feeds($ids);

	}

	function ab_process_autoblog() {
		global $abc;

		$abc->process_autoblog();
	}

	function ab_always_process_autoblog() {
		global $abc;

		$abc->always_process_autoblog();
	}

	function ab_process_autoblog_for_cron() {
		global $abc;

		if(defined('AUTOBLOG_PROCESSING_METHOD') && AUTOBLOG_PROCESSING_METHOD == 'cron') {
			$abc->always_process_autoblog();
		}
	}

	add_action( 'autoblog_process_all_feeds_for_cron', 'ab_process_autoblog_for_cron' );

	function ab_test_feed($id, $details) {

		global $abc;

		return $abc->test_the_feed($id, $details);

	}

	/**
	 * Build Latest SimplePie object based on RSS or Atom feed from URL.
	 *
	 * @since 2.8
	 *
	 * @param string $url URL to retrieve feed
	 * @return WP_Error|SimplePie WP_Error object on failure or SimplePie object on success
	 */
	function fetch_autoblog_feed($url) {

		// Include the latest simplepie class
		require_once( autoblog_dir('autoblogincludes/external/autoloader.php') );

		$feed = new SimplePie();
		$feed->set_feed_url($url);
		//$feed->set_cache_class('WP_Feed_Cache');
		//$feed->set_file_class('WP_SimplePie_File');
		//$feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', 43200, $url));
		do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
		$feed->init();
		$feed->handle_content_type();

		if ( $feed->error() )
			return new WP_Error('simplepie-error', $feed->error());

		return $feed;
	}
?>