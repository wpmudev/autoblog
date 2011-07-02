<?php
/*
Plugin Name: Autoblog Reset
Description: Allows the Autoblog timer to be reset.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

function AB_handle_reset_panel() {
	global $action, $page;

	$messages = array();
	$messages[1] = __('Autoblog has been reset.','autoblogtext');

	$lastprocessing = get_autoblog_option('autoblog_processing', false));

	?>
	<div class='wrap nosubsub'>
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Autoblog Reset','autoblogtext'); ?></h2>

		<?php
		if ( isset($_GET['msg']) ) {
			echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}
		?>

		<form action='?page=<?php echo $page; ?>' method='post'>

			<?php
				wp_nonce_field('update-autoblog-reset');
			?>

			<h3><?php _e('Debug mode','autoblog'); ?></h3>
			<p><?php _e('Switch on debug mode and reporting.','autoblogtext'); ?></p>

			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e('Debug mode is','autoblog'); ?></th>
					<td>
						<?php
							$debug = get_site_option('autoblog_debug', false);
						?>
						<select name='debugmode' id='debugmode'>
							<option value="no" <?php if($debug == false) echo "selected='selected'"; ?>><?php _e('Disabled','autoblog'); ?></option>
							<option value="yes" <?php if($debug == true) echo "selected='selected'"; ?>><?php _e('Enabled','autoblog'); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Reset Now') ?>" />
			</p>

		</form>

	</div> <!-- wrap -->
	<?php
}

function AB_reset_menu_add() {
	add_submenu_page('autoblog', __('Autoblog Reset','autoblog'), __('Reset Autoblog','autoblog'), 'manage_options', "autoblog_reset", 'AB_handle_reset_panel');

}

add_action('autoblog_site_menu', 'AB_reset_menu_add');

?>