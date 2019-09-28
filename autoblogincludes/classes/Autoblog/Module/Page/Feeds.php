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
 * Feeds pages module.
 *
 * @category Autoblog
 * @package Module
 * @subpackage Page
 *
 * @since 4.0.0
 */
class Autoblog_Module_Page_Feeds extends Autoblog_Module {

	const NAME = __CLASS__;

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

		$this->_add_action( 'autoblog_handle_feeds_page', 'handle' );

		// ajax actions
		$this->_add_ajax_action( 'autoblog-get-blog-categories', 'get_blog_categories' );
		$this->_add_ajax_action( 'autoblog-get-blog-authors', 'get_blog_authors' );
	}

	/**
	 * Returns blog categories list.
	 *
	 * @since 4.0.0
	 * @action wp_ajax_autoblog-get-blog-categories
	 *
	 * @access public
	 */
	public function get_blog_categories() {
		$bid = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		if ( !$bid ) {
			wp_send_json_error();
		}

		if ( function_exists( 'switch_to_blog' ) ) {
			switch_to_blog( $bid );
		}

		$categories = get_categories();

		if ( function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}

		$data = array();
		$data[] = array(
			'term_id' => '-1',
			'name'    => __( 'None', 'autoblogtext' )
		);

		foreach ( $categories as $category ) {
			$data[] = array(
				'term_id' => $category->term_id,
				'name'    => $category->name,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Returns blog authors list.
	 *
	 * @sicne 4.0.0
	 * @action wp_ajax_autoblog-get-blog-authors
	 *
	 * @access public
	 */
	public function get_blog_authors() {
		$bid = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		if ( !$bid ) {
			wp_send_json_error();
		}

		$data = array();
		$blogusers = get_users( array(
			'blog_id' => $bid,
		) );

		foreach ( $blogusers as $buser ) {
			$data[] = array(
				'user_id'    => $buser->user_id,
				'user_login' => $buser->user_login,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Handles feeds page.
	 *
	 * @since 4.0.0
	 * @action autoblog_handle_feeds_page
	 *
	 * @access public
	 */
	public function handle() {
		$table = new Autoblog_Table_Feeds( array(
			'nonce'           => wp_create_nonce( 'autoblog_feeds' ),
			'actions'         => array(
				'process' => __( 'Process', 'autoblogtext' ),
				'delete'  => __( 'Delete', 'autoblogtext' ),
			),
		) );

		switch ( $table->current_action() ) {
			case 'add':
				$this->_handle_feed_form();
				break;
			case 'edit':
				$this->_handle_feed_form( filter_input( INPUT_GET, 'item', FILTER_VALIDATE_INT ) );
				break;
			case 'process':
				$this->_process_feeds();
				break;
			case 'delete':
				$this->_delete_feeds();
				break;
			case 'duplicate':
				$this->_duplicate_feed();
				break;
			default:
				if ( filter_input( INPUT_GET, 'noheader', FILTER_VALIDATE_BOOLEAN ) ) {
					wp_redirect( add_query_arg( 'noheader', false ) );
					exit;
				}

				$template = new Autoblog_Render_Feeds_Table();
				$template->table = $table;
				$template->render();
				break;
		}
	}

	/**
	 * Duplicates feed and redirects to feed edit page.
	 *
	 * @since 4.0.4
	 *
	 * @access private
	 */
	private function _duplicate_feed() {
		check_admin_referer( 'autoblog_feeds' );

		$feed_id = filter_input( INPUT_GET, 'items', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		if ( !$feed_id ) {
			wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] );
			exit;
		}

		$feed = $this->_wpdb->get_row( sprintf( 'SELECT * FROM %s WHERE feed_id = %d', AUTOBLOG_TABLE_FEEDS, $feed_id ), ARRAY_A );
		if ( !$feed ) {
			wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] );
			exit;
		}

		unset( $feed['feed_id'] );
		$this->_wpdb->insert( AUTOBLOG_TABLE_FEEDS, $feed );
		$feed_id = $this->_wpdb->insert_id;

		wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] . '&action=edit&item=' . $feed_id );
		exit;
	}

	/**
	 * Reschedules feed.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $feed The feed data.
	 */
	private function _reschedule_feed( $feed ) {
		// switch blog if need be
		$switched = false;
		if ( $feed['blog_id'] != get_current_blog_id() && function_exists( 'switch_to_blog' ) ) {
			$switched = true;
			switch_to_blog( $feed['blog_id'] );
		}

		// unschedule previous event
		$next_job = wp_next_scheduled( Autoblog_Plugin::SCHEDULE_PROCESS, array( $feed['feed_id'] ) );
		if ( $next_job ) {
			wp_unschedule_event( $next_job, Autoblog_Plugin::SCHEDULE_PROCESS, array( $feed['feed_id'] ) );
		}

		// schedule new event
		if ( $feed['nextcheck'] > 0 ) {
			wp_schedule_single_event( $feed['nextcheck'], Autoblog_Plugin::SCHEDULE_PROCESS, array( $feed['feed_id'] ) );
		}

		// restore blug if need be
		if ( $switched && function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}
	}

	/**
	 * Saves the feed data.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $feed The array of old feed data.
	 */
	private function _save_feed( $feed ) {
		check_admin_referer( 'autoblog_feeds' );

		$post = $_POST['abtble'];
		if ( !empty( $post['startfromday'] ) && !empty( $post['startfrommonth'] ) && !empty( $post['startfromyear'] ) ) {
			$post['startfrom'] = strtotime( "{$post['startfromyear']}-{$post['startfrommonth']}-{$post['startfromday']}" );
		}

		if ( !empty( $post['endonday'] ) && !empty( $post['endonmonth'] ) && !empty( $post['endonyear'] ) ) {
			$post['endon'] = strtotime( "{$post['endonyear']}-{$post['endonmonth']}-{$post['endonday']}" );
		}

		$feed['feed_meta'] = serialize( $post );
		$feed['blog_id'] = absint( $post['blog'] );
		$feed['nextcheck'] = isset( $post['processfeed'] ) && intval( $post['processfeed'] ) > 0
			? current_time( 'timestamp', 1 ) + absint( $post['processfeed'] ) * MINUTE_IN_SECONDS
			: 0;

		$action = 'created';
		$result = 'false';
		if ( isset( $feed['feed_id'] ) ) {
			$action = 'updated';
			$feed['feed_id'] = absint( $feed['feed_id'] );
			if ( $this->_wpdb->update( AUTOBLOG_TABLE_FEEDS, $feed, array( 'feed_id' => $feed['feed_id'] ) ) ) {
				$result = 'true';
				do_action( 'autoblog_feed_updated', $feed );
			}
		} else {
			$feed['site_id'] = get_current_network_id();
			if ( $this->_wpdb->insert( AUTOBLOG_TABLE_FEEDS, $feed ) ) {
				$result = 'true';
				$feed['feed_id'] = $this->_wpdb->insert_id;
				do_action( 'autoblog_feed_created', $feed );
			}
		}

		if ( $result == 'true' ) {
			$this->_reschedule_feed( $feed );
		}

		wp_safe_redirect( add_query_arg( $action, $result, 'admin.php?page=' . filter_input( INPUT_GET, 'page' ) ) );
		exit;
	}

	/**
	 * Handles feed create/edit form.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param int $feed_id The id of a feed to edit.
	 * @return boolean TRUE to prevent table rendering.
	 */
	private function _handle_feed_form( $feed_id = false ) {
		$feed = $feed_data = array();
		if ( $feed_id ) {
			$feed = $this->_wpdb->get_row( sprintf(
				is_network_admin()
					? 'SELECT * FROM %s WHERE feed_id = %d LIMIT 1'
					: 'SELECT * FROM %s WHERE feed_id = %d AND blog_id = %d LIMIT 1',
				AUTOBLOG_TABLE_FEEDS,
				$feed_id,
				get_current_blog_id()
			), ARRAY_A );

			if ( !$feed ) {
				wp_die( __( 'Feed not found.', 'autoblogtext' ) );
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$this->_save_feed( $feed );
		}

		if ( !empty( $feed ) ) {
			$feed_data = @unserialize( $feed['feed_meta'] );
			$feed_data['feed_meta'] = $feed['feed_meta'];
			$feed_data['feed_id'] = $feed_id;
		}

		if ( empty( $feed_data['blog'] ) ) {
			$feed_data['blog'] = get_current_blog_id();
		}

		if ( empty( $feed_data['posttype'] ) ) {
			$feed_data['posttype'] = 'post';
		}

		do_action( 'autoblog_feed_edit', $feed );

		$template = new Autoblog_Render_Feeds_Form( $feed_data );
		$template->render();

		return true;
	}

	/**
	 * Deletes feeds.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _delete_feeds() {
		check_admin_referer( 'autoblog_feeds' );

		$feeds = isset( $_REQUEST['items'] ) ? (array)$_REQUEST['items'] : array();
		$feeds = array_filter( array_map( 'intval', $feeds ) );
		if ( empty( $feeds ) ) {
			wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] );
			exit;
		}

		$feed_ids = implode( ', ', $feeds );
		$query = is_network_admin()
			? 'DELETE FROM %s WHERE feed_id IN (%s)'
			: 'DELETE FROM %s WHERE feed_id IN (%s) AND blog_id = %d';

		$this->_wpdb->query( sprintf( $query, AUTOBLOG_TABLE_FEEDS, $feed_ids, get_current_blog_id() ) );
		$this->_wpdb->query( sprintf( 'DELETE FROM %s WHERE feed_id IN (%s)', AUTOBLOG_TABLE_LOGS, $feed_ids ) );

		foreach ( $feeds as $feed_id ) {
			do_action( 'autoblog_feed_deleted', $feed_id );
		}

		wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] . '&deleted=true' );
		exit;
	}

	/**
	 * Processes feeds.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function _process_feeds() {
		check_admin_referer( 'autoblog_feeds' );

		$feeds = isset( $_REQUEST['items'] ) ? (array)$_REQUEST['items'] : array();
		$feeds = array_filter( array_map( 'intval', $feeds ) );
		if ( empty( $feeds ) ) {
			wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] );
			exit;
		}

		if ( Autoblog_Plugin::use_cron() ) {
			wp_schedule_single_event( time(), Autoblog_Plugin::SCHEDULE_PROCESS, array( $feeds, true ) );
			wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] . '&launched=true' );
			exit;
		}

		do_action( Autoblog_Plugin::SCHEDULE_PROCESS, $feeds, true );

		wp_safe_redirect( 'admin.php?page=' . $_REQUEST['page'] . '&processed=true' );
		exit;
	}

}
