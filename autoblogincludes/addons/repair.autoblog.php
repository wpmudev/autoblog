<?php
/*
Addon Name: Repair Autoblog Feeds
Description: Sets the feed blog and site ids to the main ids.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

function AB_handle_repair_panel() {
	global $action, $page, $wpdb;

	wp_reset_vars( array('action', 'page') );

	$messages = array();
	$messages[1] = __('Autoblog has been repaired.','autoblogtext');

	if(isset($_POST['action']) && esc_attr($_POST['action']) == 'repair') {
		check_admin_referer('update-autoblog-repair');

		$sql = $wpdb->prepare( "UPDATE " . autoblog_db_prefix($wpdb, 'autoblog') . " SET site_id = 1 WHERE site_id = 0" );
		$wpdb->query($sql);

		$sql = $wpdb->prepare( "UPDATE " . autoblog_db_prefix($wpdb, 'autoblog') . " SET blog_id = 1 WHERE blog_id = 0" );
		$wpdb->query($sql);

		$sql = $wpdb->prepare( "UPDATE " . autoblog_db_prefix($wpdb, 'autoblog') . " SET nextcheck = UNIX_TIMESTAMP() WHERE nextcheck < UNIX_TIMESTAMP()" );
		$wpdb->query($sql);

		delete_autoblog_option('autoblog_processing');

		$msg = 1;
	} else {
		$msg = $_GET['msg'];
	}

	?>
	<div class='wrap nosubsub'>
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Autoblog Repair','autoblogtext'); ?></h2>

		<?php
		if ( isset($msg) ) {
			echo '<div id="message" class="updated fade"><p>' . $messages[(int) $msg] . '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}
		?>

		<form action='?page=<?php echo $page; ?>' method='post'>

			<?php
				wp_nonce_field('update-autoblog-repair');
			?>

			<h3><?php _e('Repair the Autoblog feeds','autoblogtext'); ?></h3>

			<p><?php _e('If Feeds do not show or process then running a repair will reset the relevant id and timestamp fields.','autoblogtext'); ?></p>

			<p class="submit">
				<input type='hidden' name='action' value='repair' />
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Repair Now','autoblogtext') ?>" />
			</p>

		</form>

	</div> <!-- wrap -->
	<?php
}

function AB_repair_menu_add() {
	add_submenu_page('autoblog', __('Repair Autoblog','autoblogtext'), __('Repair Autoblog','autoblogtext'), 'manage_options', "autoblog_repair", 'AB_handle_repair_panel');

}

add_action('autoblog_site_menu', 'AB_repair_menu_add');

?>