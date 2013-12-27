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
 * Dashboard page module.
 *
 * @category Autoblog
 * @package Module
 * @subpackage Page
 *
 * @since 4.0.0
 */
class Autoblog_Module_Page_Dashboard extends Autoblog_Module {

	const NAME = __CLASS__;
	const ACTION_REGENERATE_PAGE = 'regenerate';

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param Autoblog_Plugin $plugin The instance of the plugin.
	 */
	public function __construct( Autoblog_Plugin $plugin ) {
		parent::__construct( $plugin );
		$this->_add_action( 'autoblog_handle_dashboard_page', 'handle_dashboard_page' );
	}

	/**
	 * Handles dashboard page.
	 *
	 * @since 4.0.0
	 * @action autoblog_handle_dashboard_page
	 *
	 * @access public
	 */
	public function handle_dashboard_page() {
		$template = new Autoblog_Render_Dashboard_Page();

		// check page regeneration
		$nonce_action = 'regenerate-dashboard-' . get_current_user_id();
		if ( filter_input( INPUT_GET, 'action' ) == self::ACTION_REGENERATE_PAGE && check_admin_referer( $nonce_action ) ) {
			$template->delete_cache();

			wp_safe_redirect( add_query_arg( array(
				'action'   => false,
				'_wpnonce' => false,
				'noheader' => false,
			), wp_get_referer() ) );
			exit;
		}

		// set regenerate page url
		$template->regenerate_url = wp_nonce_url( add_query_arg( array(
			'action'   => self::ACTION_REGENERATE_PAGE,
			'noheader' => 'true',
		) ), $nonce_action );

		// try to fetch html from cache first
		if ( !filter_input( INPUT_GET, 'nocache', FILTER_VALIDATE_BOOLEAN ) ) {
			$html = $template->get_html_from_cahce();
			if ( $html !== false ) {
				echo $html;
				return;
			}
		}

		// clean up log records older than a week
		$this->_wpdb->query( sprintf( 'DELETE FROM %s WHERE cron_id < %d', AUTOBLOG_TABLE_LOGS, strtotime( '-1 week' ) ) );

		// html is not cached, so we need to build it and cache it
		$template->log_records = $this->_get_log_records();

		// enable output caching
		$template->cache_output( true );
		$template->set_cache_ttl( 5 * MINUTE_IN_SECONDS );

		// render template
		$template->render();
	}

	/**
	 * Returns prepared array of feeds to use in log fetching.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @return array Array of feeds.
	 */
	private function _get_log_feeds() {
		$resutls = (array)$this->_wpdb->get_results( sprintf(
			is_network_admin()
				? 'SELECT * FROM %s WHERE site_id = %d'
				: 'SELECT * FROM %s WHERE site_id = %d AND blog_id = %d',
			AUTOBLOG_TABLE_FEEDS,
			!empty( $this->_wpdb->siteid ) ? $this->_wpdb->siteid : 1,
			get_current_blog_id()
		), ARRAY_A );

		$feeds = array();
		foreach ( $resutls as $result ) {
			$details = unserialize( $result['feed_meta'] );
			$feeds[$result['feed_id']] = array(
				'title'   => $details['title'],
				'url'     => $details['url'],
				'blog_id' => absint( $result['blog_id'] ),
			);
		}

		return $feeds;
	}

	/**
	 * Returns logs for last week.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @return array The array of log records for the last week.
	 */
	private function _get_log_records() {
		$feeds = $this->_get_log_feeds();
		if ( empty( $feeds ) ) {
			return array();
		}

		$records = $this->_wpdb->get_results( sprintf(
			'SELECT * FROM %s WHERE feed_id IN (%s) AND cron_id >= %d ORDER BY log_at DESC, log_type DESC',
			AUTOBLOG_TABLE_LOGS,
			implode( ', ', array_keys( $feeds ) ),
			strtotime( '-7 days' )
		), ARRAY_A );

		if ( empty( $records ) ) {
			return array();
		}

		$log_records = $date_items = array();
		$date_pattern = 'Y-m-d';

		$record = current( $records );
		while( $record != false ) {
			if ( !isset( $date_items[$record['feed_id']] ) ) {
				if ( isset( $feeds[$record['feed_id']] ) ) {
					$date_items[$record['feed_id']] = $feeds[$record['feed_id']];
					$date_items[$record['feed_id']]['logs'] = array();
				}
			}

			$date_items[$record['feed_id']]['logs'][] = $record;
			$last_cron_date = date( $date_pattern, $record['cron_id'] );

			$record = next( $records );
			if ( $record == false || $last_cron_date != date( $date_pattern, $record['cron_id'] ) ) {
				ksort( $date_items );
				$log_records[strtotime( $last_cron_date )] = $date_items;
				$date_items = array();
			}
		}

		return $log_records;
	}

}