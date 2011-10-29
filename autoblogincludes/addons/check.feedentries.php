<?php
/*
Addon Name: Check Feed Entries
Description: Check to make sure blog id entries match in each feed entry.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
Network: True
*/

function AB_add_check_column() {
	if(function_exists('is_network_admin') && is_network_admin()) {
		echo '<th scope="col" style="text-align: right;">';
		echo __('Feed Check','autoblogtext');
		echo '</th>';
	}
}
add_action('autoblog_admin_columns', 'AB_add_check_column', 1);

function AB_process_check_column( $table ) {
	if(function_exists('is_network_admin') && is_network_admin()) {
		echo '<td style="text-align: right;">';

		$details = maybe_unserialize($table->feed_meta);

		$blog_id = $table->blog_id;
		if((int) $details['blog'] == (int) $blog_id) {
			echo "<span style='color:green;'>" . __('Ok', 'autoblogtext') . "</span>";
		} else {
			echo "<span style='color:red;'>" . __('Problem', 'autoblogtext') . "</span>";
		}

		echo '</td>';
	}
}
add_action('autoblog_admin_columns_data', 'AB_process_check_column', 1);

?>