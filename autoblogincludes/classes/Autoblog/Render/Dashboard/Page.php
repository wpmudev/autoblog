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
			<h2><?php esc_html_e( 'Auto Blog Dashboard', 'autoblogtext' ) ?></h2>

			<div class="autoblog-logs"><?php $this->_render_log_table() ?></div>
		</div><?php
	}

	/**
	 * Renders log table.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _render_log_table() {
		if ( empty( $this->log_records ) ) {
			return;
		}

		// dates
		foreach ( $this->log_records as $date => $feeds ) :
			?><div class="autoblog-log-date">
				<div class="autoblog-log-row"><?php echo $date ?></div><?php

				// feeds
				foreach ( $feeds as $feed_id => $feed ) :
					?><div class="autoblog-log-feed">
						<div class="autoblog-log-row">
							<?php $this->_render_feed_errros_info( $feed ) ?>
							<?php $this->_render_feed_iterations_info( $feed ) ?>
							<?php $this->_render_feed_imports_info( $feed ) ?>

							<span class="autoblog-log-feed-collapse autoblog-log-feed-collapse-down">&plusb;</span>
							<span class="autoblog-log-feed-collapse autoblog-log-feed-collapse-up">&minusb;</span>
							<a class="autoblog-log-feed-url" href="admin.php?page=autoblog_admin&action=edit&item=<?php echo $feed_id ?>" title="<?php esc_attr_e( 'Edit feed', 'autoblogtext' ) ?>">
								<?php echo esc_html( $feed['title'] ) ?>
							</a>
						</div><?php

						// logs
						foreach ( $feed['logs'] as $log ) :
							?><div class="autoblog-log-record"><?php $this->_render_log_row( $log ) ?></div><?php
						endforeach;

					?></div><?php
				endforeach;

			?></div><?php
		endforeach;
	}

	/**
	 * Renders feed imports amount information.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $feed The feed information.
	 */
	private function _render_feed_errros_info( $feed ) {
		$count = 0;
		foreach ( $feed['logs'] as $log ) {
			switch ( $log['log_type'] ) {
				case Autoblog_Plugin::LOG_POST_INSERT_FAILED:
				case Autoblog_Plugin::LOG_FETCHING_ERRORS:
				case Autoblog_Plugin::LOG_INVALID_FEED_URL:
					$count++;
					break;
			}
		}

		$class = !empty( $count ) ? ' autoblog-log-feed-info-active' : '';

		?><div class="autoblog-log-feed-info autoblog-log-feed-errors<?php echo $class ?>" title="<?php esc_attr_e( 'The amount of errors', 'autoblogtext' ) ?>">
			<span class="glyphicon glyphicon-warning-sign"></span> <?php echo number_format( $count ) ?>
		</div><?php
	}

	/**
	 * Renders count of feed iterations.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $feed The feed information.
	 */
	private function _render_feed_iterations_info( $feed ) {
		$count = 0;
		foreach ( $feed['logs'] as $log ) {
			switch ( $log['log_type'] ) {
				case Autoblog_Plugin::LOG_FEED_PROCESSED:
				case Autoblog_Plugin::LOG_FEED_PROCESSED_NO_RESULTS:
				case Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_EARLY:
				case Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_LATE:
				case Autoblog_Plugin::LOG_FETCHING_ERRORS:
				case Autoblog_Plugin::LOG_INVALID_FEED_URL:
					$count++;
					break;
			}
		}

		$class = !empty( $count ) ? ' autoblog-log-feed-info-active' : '';

		?><div class="autoblog-log-feed-info autoblog-log-feed-iterations<?php echo $class ?>" title="<?php esc_attr_e( 'The amount of times the feed has been procesed', 'autoblogtext' ) ?>">
			<span class="glyphicon glyphicon-dashboard"></span> <?php echo number_format( $count ) ?>
		</div><?php
	}

	/**
	 * Renders feed imports amount information.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $feed The feed information.
	 */
	private function _render_feed_imports_info( $feed ) {
		$count = array_sum(
			wp_list_pluck(
				wp_list_filter( $feed['logs'], array( 'log_type' => Autoblog_Plugin::LOG_FEED_PROCESSED ) ),
				'log_info'
			)
		);

		$class = !empty( $count ) ? ' autoblog-log-feed-info-active' : '';

		?><div class="autoblog-log-feed-info autoblog-log-feed-imports<?php echo $class ?>" title="<?php esc_attr_e( 'The amount of imported items during the day.', 'autoblogtext' ) ?>">
			<span class="glyphicon glyphicon-cloud-download"></span> <?php echo number_format( $count ) ?>
		</div><?php
	}

	/**
	 * Renders log record row.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $log Log record information.
	 */
	private function _render_log_row( $log ) {
		$glyph = $message = '';
		switch ( $log['log_type'] ) {
			case Autoblog_Plugin::LOG_INVALID_FEED_URL:
				$glyph = 'warning-sign';
				$message = esc_html__( 'Feed URL is invalid and cannot be processed.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_FETCHING_ERRORS:
				$glyph = 'warning-sign';
				$message = implode( '<br>', unserialize( $log['log_info'] ) );
				break;

			case Autoblog_Plugin::LOG_DUPLICATE_POST:
				$glyph = 'info-sign';
				$info = unsertialize( $log['log_info'] );
				$message = sprintf( esc_html__( 'has been already imported.', 'autoblogtext' ), esc_html( $info['title'] ) );
				break;

			case Autoblog_Plugin::LOG_POST_DOESNT_MATCH:
				$glyph = 'info-sign';
				$message = esc_html__( 'Feed item does not contain requested words or tags.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_POST_INSERT_FAILED:
				$glyph = 'warning-sign';
				$message = esc_html__( 'Feed item importing failed.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_POST_INSERT_SUCCESS:
				$glyph = 'ok-sign';
				$message = esc_html__( 'Feed item has been imported successfully.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_FEED_PROCESSED:
				$glyph = 'ok-sign';
				$imported = absint( $log['log_info'] );
				$message = sprintf(
					_n( '%s feed item was imported.', '%s feed items were imported.', $imported, 'autoblogtext' ),
					'<b>' . number_format( $imported ) . '</b>'
				);
				break;

			case Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_EARLY:
				$glyph = 'info-sign';
				$message = esc_html__( 'Feed has been skipped because it is too early to process it.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_LATE:
				$glyph = 'info-sign';
				$message = esc_html__( 'Feed has been skipped because it is too late to process it.', 'autoblogtext' );
				break;

			case Autoblog_Plugin::LOG_FEED_PROCESSED_NO_RESULTS:
				$glyph = 'info-sign';
				$message = esc_html__( 'No new items were found.', 'autoblogtext' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		?><div class="autoblog-log-row">
			<span class="autoblog-log-record-time"><?php echo esc_html( $log['log_at'] ) ?></span>

			<?php if ( $glyph ) : ?>
				<span class="glyphicon glyphicon-<?php echo $glyph ?>"></span>
			<?php endif; ?>

			<?php echo $message ?>
		</div><?php
	}

}