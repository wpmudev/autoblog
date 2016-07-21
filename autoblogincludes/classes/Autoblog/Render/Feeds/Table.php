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
* Render class for feeds table.
*
* @category Autoblog
* @package Render
* @subpackage Feeds
*
* @since 4.0.0
*/
class Autoblog_Render_Feeds_Table extends Autoblog_Render {

	/**
	* Renders table template.
	*
	* @since 4.0.0
	*
	* @access protected
	*/
	protected function _to_html() {
		$add_link = add_query_arg( array(
		'action'   => 'add',
		'paged'    => false,
		'noheader' => false,
		) );

		?><div class="wrap">
			<div class="icon32" id="icon-edit"><br></div>
			<h2>
				<?php esc_html_e( 'Autoblog Feeds', 'autoblogtext' ) ?>
				<a class="add-new-h2" href="<?php echo esc_url( $add_link ) ?>"><?php esc_html_e( 'Add New','autoblogtext' ) ?></a>
			</h2>

			<?php $this->_render_messages() ?>

			<?php $this->_test_cron() ?>


			<?php do_action( 'autoblog_before_feeds_table' ) ?>
			<form class="autoblog-table" action="<?php echo add_query_arg( 'noheader', 'true' ) ?>" method="post">
				<?php wp_nonce_field( 'autoblog_feeds' ) ?>
				<?php $this->table->prepare_items() ?>
				<?php $this->table->views() ?>
				<?php $this->table->display() ?>
			</form>
			<?php do_action( 'autoblog_after_feeds_table' ) ?>
		</div><?php
	}

	/**
	* Renders messages.
	*
	* @since 4.0.0
	*
	* @access private
	*/
	private function _render_messages() {
		if ( isset( $_GET['created'] ) ) {
			if ( filter_input( INPUT_GET, 'created', FILTER_VALIDATE_BOOLEAN ) ) {
				echo '<div class="updated fade"><p>', esc_html__( 'The feed has been created successfully.', 'autoblogtext' ), '</p></div>';
			} else {
				echo '<div class="error fade"><p>', esc_html__( 'The feed has not been created.', 'autoblogtext' ), '</p></div>';
			}
		}

		if ( isset( $_GET['updated'] ) ) {
			if ( filter_input( INPUT_GET, 'updated', FILTER_VALIDATE_BOOLEAN ) ) {
				echo '<div class="updated fade"><p>', esc_html__( 'The feed has been updated successfully.', 'autoblogtext' ), '</p></div>';
			} else {
				echo '<div class="error fade"><p>', esc_html__( 'The feed has not been updated.', 'autoblogtext' ), '</p></div>';
			}
		}

		if ( isset( $_GET['deleted'] ) && filter_input( INPUT_GET, 'deleted', FILTER_VALIDATE_BOOLEAN ) ) {
			echo '<div class="updated fade"><p>', esc_html__( 'The feed(s) has been deleted successfully.', 'autoblogtext' ), '</p></div>';
		}

		if ( isset( $_GET['processed'] ) && filter_input( INPUT_GET, 'processed', FILTER_VALIDATE_BOOLEAN ) ) {
			echo '<div class="updated fade"><p>', esc_html__( 'The feed(s) has been processed successfully. Visit the Autoblog Dashboard for details.', 'autoblogtext' ), '</p></div>';
		}

		if ( isset( $_GET['launched'] ) && filter_input( INPUT_GET, 'launched', FILTER_VALIDATE_BOOLEAN ) ) {
			echo '<div class="updated fade"><p>', esc_html__( 'The feed(s) processing has been launched in the background. Visit the Autoblog Dashboard for details.', 'autoblogtext' ), '</p></div>';
		}
	}

	private function _test_cron(){
		if ( Autoblog_Plugin::use_cron() ) {

			$cron_url = add_query_arg( 'doing_wp_cron', '', site_url( 'wp-cron.php' ) );
			$response = wp_remote_post( $cron_url );

			if( is_wp_error($response)){
				$error_message = $response->get_error_message();
				printf('<div class="error fade"><p>%s %s</p></div>', __('Problem communicating with WP_CRON: ', 'autoblogtext'), $error_message );
			} else {
				if( ($response['response']['code'] != 200)
				&& !(defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON )
				) {
					echo '<div class="error fade"><p>', wp_kses( __( 'The Cron processing file "wp-cron.php" cannot be reached from code. This may be due to your firewall blocking local loopback calls.
					<br />You may be able to work around the firewall by adding this line to your wp-config.php file.
					<p><code>define("ALTERNATE_WP_CRON", true);</code></p>
					If that does not clear the problem, then disable using Cron and enable the "pageload" method by adding
					<p><code>		define( "AUTOBLOG_PROCESSING_METHOD", "pageload" );</code></p>to wp-config.php', 'autoblogtext' ), wp_kses_allowed_html('post') ), '</p></div>';
				}
			}
		}

	}
}