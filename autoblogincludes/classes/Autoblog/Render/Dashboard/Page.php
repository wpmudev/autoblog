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
 * Dashboard page class.
 *
 * @category Autoblog
 * @package Render
 * @subpackage Feeds
 *
 * @since 4.0.0
 */
class Autoblog_Render_Dashboard_Page extends Autoblog_Render {

	/**
	 * Renders table template.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function _to_html() {
		?><div class="wrap">
			<div class="icon32" id="icon-edit"><br></div>
			<h2>
				<?php esc_html_e( 'Auto Blog Dashboard', 'autoblogtext' ) ?>
			</h2>

			<div id="dashboard-widgets-wrap">

				<div class="metabox-holder" id="dashboard-widgets">
					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="normal-sortables">
							<?php $this->_news() ?>
							<?php $this->_report() ?>
						</div>
					</div>

					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="side-sortables">
							<?php $this->_stats() ?>
						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column3-sortables" style="">
						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column4-sortables" style="">
						</div>
					</div>
				</div>

				<div class="clear"></div>
			</div>
		</div><?php
	}

	private function _news() {
		$plugin = get_plugin_data( AUTOBLOG_ABSPATH . ('autoblogpremium.php') );

		?><div class="postbox ">
			<h3 class="hndle"><span><?php _e( 'Autoblog', 'autoblogtext' ); ?></span></h3>
			<div class="inside">
				<p>
					<?php _e( 'You are running Autoblog version ', 'autoblogtext' ) ?> <strong><?php echo esc_html( $plugin['Version'] ) ?></strong>
				</p>
			</div>
		</div><?php
	}

	private function _report() {
		?><div class="postbox ">
			<h3 class="hndle"><span><?php _e( 'Processing Report', 'autoblogtext' ); ?></span></h3>
			<div class="inside">
			<?php if ( !empty( $this->logs ) ) : ?>
				<?php foreach ( $this->logs as $log ) : ?>
					<p>
						<strong><?php echo date( 'Y-m-d \a\t H:i', (int)$log['timestamp'] ) ?></strong><br>
						<?php if ( !empty( $log['log'] ) ) : ?>
							<?php foreach ( $log['log'] as $key => $l ) : ?>
								&#8226; <?php echo  $l ?><br>
							<?php endforeach; ?>
						<?php endif; ?>
					</p>
				<?php endforeach; ?>
			<?php else : ?>
				<p>
					<?php _e( 'No processing reports are available, either you have not processed a feed or everything is running smoothly.', 'autoblogtext' ) ?>
				</p>
			<?php endif; ?>
			</div>
		</div><?php
	}

	private function _stats() {
		?><div class="postbox ">
			<h3 class="hndle"><span><?php _e( 'Statistics - posts per day', 'autoblogtext' ) ?></span></h3>
			<div class="inside">
				<?php if ( empty( $this->feeds ) ) : ?>
					<p><?php _e( 'You need to set up some feeds before we can produce statistics.', 'autoblogtext' ) ?></p>
				<?php else : ?>
					<?php foreach ( $this->feeds as $a ) : ?>
						<?php $feed = unserialize( $a->feed_meta ) ?>
						<p><strong><?php echo esc_html( $feed['title'] ) ?> - <?php echo substr( $feed['url'], 0, 30 ) ?></strong></p>
						<div id='feedchart-<?php echo $a->feed_id ?>' class='dashchart'></div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div><?php
	}

}