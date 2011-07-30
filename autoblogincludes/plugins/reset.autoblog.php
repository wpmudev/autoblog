<?php
/*
Plugin Name: Autoblog Reset
Description: Allows the Autoblog timer to be reset.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

function AB_handle_reset_panel() {
	global $action, $page;

	wp_reset_vars( array('action', 'page') );

	$messages = array();
	$messages[1] = __('Autoblog has been reset.','autoblogtext');

	if(isset($_POST['action']) && esc_attr($_POST['action']) == 'reset') {
		check_admin_referer('update-autoblog-reset');

		delete_autoblog_option('autoblog_processing');
		$msg = 1;
	} else {
		$msg = $_GET['msg'];
	}

	$lastprocessing = get_autoblog_option('autoblog_processing', false);

	?>
	<div class='wrap nosubsub'>
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Autoblog Reset','autoblogtext'); ?></h2>

		<?php
		if ( isset($msg) ) {
			echo '<div id="message" class="updated fade"><p>' . $messages[(int) $msg] . '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}
		?>

		<form action='?page=<?php echo $page; ?>' method='post'>

			<?php
				wp_nonce_field('update-autoblog-reset');
			?>

			<h3><?php _e('Processing timestamp','autoblogtext'); ?></h3>

			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" valign='top'><?php _e('Autoblog processing last occured:','autoblogtext'); ?></th>
					<td valign='top'><strong>
						<?php
						if($lastprocessing === false || empty($lastprocessing)) {
							_e('Never', 'autoblogtext');
						} else {
							echo date("jS F Y H:i:s", $lastprocessing );
						}

						?></strong>
					</td>
				</tr>
			</tbody>
			</table>

			<p class="submit">
				<input type='hidden' name='action' value='reset' />
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Reset Now','autoblogtext') ?>" />
			</p>

		</form>

	</div> <!-- wrap -->
	<?php
}

function AB_reset_menu_add() {
	add_submenu_page('autoblog', __('Autoblog Reset','autoblogtext'), __('Reset Autoblog','autoblogtext'), 'manage_options', "autoblog_reset", 'AB_handle_reset_panel');

}

add_action('autoblog_site_menu', 'AB_reset_menu_add');

?>