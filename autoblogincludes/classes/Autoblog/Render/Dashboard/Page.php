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
	 * Constructor.
	 *
	 * @sicne 4.0.0
	 *
	 * @access public
	 * @param array $data The array of data associated with current template.
	 */
	public function __construct( $data = array() ) {
		parent::__construct( $data );
		$this->_use_network_cache = is_network_admin();
	}

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
				<?php esc_html_e( 'Autoblog Dashboard', 'autoblogtext' ) ?>
				<a class="add-new-h2" href="<?php echo esc_url( $this->export_log_url ) ?>"><?php esc_html_e( 'Export Log', 'autoblogtext' ) ?></a>
				<a class="add-new-h2" href="<?php echo esc_url( $this->clear_log_url ) ?>" onclick="return confirm('<?php esc_html_e( 'Do you really want to delete log records?', 'autoblogtext' ) ?>')">
					<?php esc_html_e( 'Clear Log', 'autoblogtext' ) ?>
				</a>
			</h2>

			<div class="autoblog-charts"><?php $this->_render_charts() ?></div>
			<div class="autoblog-logs">
				<?php if ( !empty( $this->log_records ) ) : ?>
					<?php $this->_render_log_table() ?>
				<?php else : ?>
					<?php $this->_render_empty_message() ?>
				<?php endif; ?>
			</div>
		</div><?php
	}

	/**
	 * Renders message that the log is empty.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _render_empty_message() {
		?><div id="autoblog-empty-box">
			<h1><?php esc_html_e( 'No log records were found.', 'autoblogtext' ) ?></h1>
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
		$this->time_pattern = get_option( 'time_format' );

		$switch_to_blog = function_exists( 'switch_to_blog' );
		$restore_blog = function_exists( 'restore_current_blog' );

		$date_pattern = get_option( 'date_format' );

		// dates
		foreach ( $this->log_records as $date => $feeds ) :
			?><div id="autoblog-log-date-<?php echo date( 'Y-n-j', $date ) ?>" class="autoblog-log-date">
				<div class="autoblog-log-row">
					<i class="autoblog-log-date-icon glyphicon glyphicon-calendar"></i>  <?php echo date( $date_pattern, $date ) ?>
				</div><?php

				// feeds
				foreach ( $feeds as $feed ) :
					if ( !empty( $feed['blog_id'] ) && $switch_to_blog ) :
						switch_to_blog( $feed['blog_id'] );
						$this->time_pattern = get_option( 'time_format' );
					endif;

					?><div class="autoblog-log-feed">
						<div class="autoblog-log-row">
							<?php $this->_render_feed_errros_info( $feed ) ?>
							<?php $this->_render_feed_iterations_info( $feed ) ?>
							<?php $this->_render_feed_imports_info( $feed ) ?>

							<span class="autoblog-log-feed-collapse autoblog-log-feed-collapse-down">&plusb;</span>
							<span class="autoblog-log-feed-collapse autoblog-log-feed-collapse-up">&minusb;</span>
							<span class="autoblog-log-feed-url"><?php echo esc_html( $feed['title'] ) ?></span>
						</div>
						<div class="autoblog-log-feed-records"><?php

						// logs
						$tick = true; $cron_id = false;
						foreach ( $feed['logs'] as $log ) :
							if ( $cron_id != false && $cron_id != $log['cron_id'] ) :
								$tick = !$tick;
							endif;

							?><div class="autoblog-log-record autoblog-log-record-<?php echo $log['log_type'], $tick ? ' autoblog-log-record-alt' : '' ?>">
								<?php $this->_render_log_row( $log ) ?>
							</div><?php

							$cron_id = $log['cron_id'];
						endforeach;

						?></div>
					</div><?php

					if ( !empty( $feed['blog_id'] ) && $restore_blog ) :
						restore_current_blog();
						$this->time_pattern = get_option( 'time_format' );
					endif;
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
				case Autoblog_Plugin::LOG_PROCESSING_ERRORS:
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
				$glyph = 'thumbs-up';
				$info = unserialize( $log['log_info'] );
				$message = sprintf(
					esc_html_x( '%s has been already imported.', '{Post title} has been already imported.', 'autoblogtext' ),
					sprintf( '<a href="%s" target="_blank"><b>%s</b></a>', esc_url( $info[$info['checked']] ), esc_html( $info['title'] ) )
				);

				$permalink = get_permalink( $info['post_id'] );
				if ( $permalink ) {
					$message .= sprintf( ' <a href="%s" target="_blank"><small>(%s)</small></a>', $permalink, esc_html__( 'view post', 'autoblogtext' ) );
				}
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
				$info = unserialize( $log['log_info'] );
				$message = sprintf(
					esc_html_x( '%s has been imported successfully.', '{Post title} has been imported successfully.', 'autoblogtext' ),
					sprintf( '<a href="%s" target="_blank"><b>%s</b></a>', esc_url( $info['link'] ), esc_html( $info['title'] ) )
				);

				$permalink = get_permalink( $info['post_id'] );
				if ( $permalink ) {
					$message .= sprintf( ' <a href="%s" target="_blank"><small>(%s)</small></a>', $permalink, esc_html__( 'view post', 'autoblogtext' ) );
				}
				break;

			case Autoblog_Plugin::LOG_POST_UPDATE_SUCCESS:
				$glyph = 'refresh';
				$info = unserialize( $log['log_info'] );
				$message = sprintf(
					esc_html_x( '%s has been updated successfully.', '{Post title} has been updated successfully.', 'autoblogtext' ),
					sprintf( '<a href="%s" target="_blank"><b>%s</b></a>', esc_url( $info['link'] ), esc_html( $info['title'] ) )
				);

				$permalink = get_permalink( $info['post_id'] );
				if ( $permalink ) {
					$message .= sprintf( ' <a href="%s" target="_blank"><small>(%s)</small></a>', $permalink, esc_html__( 'view post', 'autoblogtext' ) );
				}
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
				$message = esc_html__( 'Feed processing was finished. No new items were imported.', 'autoblogtext' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		$special_types = array(
			Autoblog_Plugin::LOG_INVALID_FEED_URL,
			Autoblog_Plugin::LOG_FETCHING_ERRORS,
			Autoblog_Plugin::LOG_FEED_PROCESSED,
			Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_EARLY,
			Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_LATE,
			Autoblog_Plugin::LOG_FEED_PROCESSED_NO_RESULTS,
		);

		?><div class="autoblog-log-row">
			<span class="autoblog-log-record-time<?php echo !in_array( $log['log_type'], $special_types ) ? ' autoblog-log-record-time-alt' : '' ?>">
				<?php echo esc_html( date( $this->time_pattern, $log['log_at'] + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ?>
			</span>

			<?php if ( $glyph ) : ?>
				<span class="glyphicon glyphicon-<?php echo $glyph ?>"></span>
			<?php endif; ?>

			<?php echo $message ?>
		</div><?php
	}

	/**
	 * Returns HTML from cache.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @return string|boolean HTML on success, otherwise FALSE.
	 */
	public function get_html_from_cahce() {
		$cache = parent::get_html_from_cahce();
		if ( $cache !== false ) {
			$expire = get_option( '_transient_timeout_' . $this->_get_cache_key() );

			$cache .= '<div class="autoblog-cache-info">';

			if ( $expire !== false ) {
				$expire += get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
				$cache .= sprintf(
					_x( '* This page has been taken from cache and will be regenerated at %s on %s.', '... and will be regenerated at {14:01:54} on {Wednesday, 25-Dec-13}.', 'autoblogtext' ),
					date( get_option( 'time_format' ), $expire ),
					date( get_option( 'date_format' ), $expire )
				);
			} else {
				$cache .= __( '* This page has been taken from cache.', 'autoblogtext' );
			}

			$cache .= sprintf( ' <a href="%s">%s</a>', esc_url( $this->regenerate_url ), __( 'Regenerate the page.', 'autoblogtext' ) );
			$cache .= '</div>';
		}

		return $cache;
	}

	/**
	 * Renders dashboard charts.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _render_charts() {
		?><div id="autoblog-dashboard-chart">
			<div class="autoblog-spinner">
				<div class="autoblog-spinner-cube1"></div>
				<div class="autoblog-spinner-cube2"></div>
			</div>
		</div><?php
	}

}