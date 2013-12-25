<?php
/*
Addon Name: Check Feed Entries
Description: Check to make sure blog id entries match in each feed entry.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
Network: True
*/

add_filter( 'autoblog_feed_table_columns', 'autoblog_add_check_column' );
function autoblog_add_check_column( $columns ) {
	if ( is_network_admin() ) {
		$columns['feed_check'] = __( 'Feed Check', 'autoblogtext' );
	}

	return $columns;
}

add_filter( 'autoblog_feed_table_column_feed_check_value', 'autoblog_process_check_column', 10, 2 );
function autoblog_process_check_column( $value, $feed ) {
	if ( is_network_admin() ) {
		$details = maybe_unserialize( $feed['feed_meta'] );
		$value = (int)$details['blog'] == (int)$feed['blog_id']
			? "<span style='color:green;'>" . __( 'Ok', 'autoblogtext' ) . "</span>"
			: "<span style='color:red;'>" . __( 'Problem', 'autoblogtext' ) . "</span>";
	}

	return $value;
}
