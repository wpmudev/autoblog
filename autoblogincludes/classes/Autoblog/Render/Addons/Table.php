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
 * Render class for addons table.
 *
 * @category Autoblog
 * @package Render
 * @subpackage Feeds
 *
 * @since 4.0.0
 */
class Autoblog_Render_Addons_Table extends Autoblog_Render {

	/**
	 * Renders table template.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function _to_html() {
		$this->table->prepare_items();

		?><div class="wrap">
			<div class="icon32" id="icon-edit"><br></div>
			<h2>
				<?php esc_html_e( 'Autoblog Addons', 'autoblogtext' ) ?>
			</h2>

			<?php $this->_render_messages() ?>

			<form class="autoblog-table" action="<?php echo add_query_arg( 'noheader', 'true' ) ?>" method="post">
				<?php wp_nonce_field( 'autoblog_addons' ) ?>
				<?php $this->table->views() ?>
				<?php $this->table->display() ?>
			</form>
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
		if ( isset( $_GET['activated'] ) ) {
			if ( filter_input( INPUT_GET, 'activated', FILTER_VALIDATE_BOOLEAN ) ) {
				echo '<div class="updated fade"><p>', esc_html__( 'The addon(s) has been activated successfully.', 'autoblogtext' ), '</p></div>';
			} else {
				echo '<div class="error fade"><p>', esc_html__( 'The addon(s) has not been activated.', 'autoblogtext' ), '</p></div>';
			}
		}

		if ( isset( $_GET['deactivated'] ) ) {
			if ( filter_input( INPUT_GET, 'deactivated', FILTER_VALIDATE_BOOLEAN ) ) {
				echo '<div class="updated fade"><p>', esc_html__( 'The addon(s) has been deactivated successfully.', 'autoblogtext' ), '</p></div>';
			} else {
				echo '<div class="error fade"><p>', esc_html__( 'The addon(s) has not been deactivated.', 'autoblogtext' ), '</p></div>';
			}
		}
	}

}