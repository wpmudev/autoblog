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
 * Feeds table class.
 *
 * @category Autoblog
 * @package Table
 *
 * @since 4.0.0
 */
class Autoblog_Table_Feeds extends Autoblog_Table {

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $args The array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array_merge( array(
			'autoescape'       => false,
			'next_schedule'    => wp_next_scheduled( Autoblog_Plugin::SCHEDULE_PROCESS ),
			'date_i18n_format' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		), $args ) );
	}

	/**
	 * Returns the feed title.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of feed data.
	 * @return string Feed title.
	 */
	public function column_title( $item ) {
		$title = !empty( $item['feed_meta']['title'] )
			? $item['feed_meta']['title']
			: esc_html__( 'unknown', 'autoblogtext' );

		$edit_link = add_query_arg( array( 'action' => 'edit', $this->_args['single'] => $item['feed_id'] ) );

		$actions = apply_filters( 'autoblog_networkadmin_actions', array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				$edit_link,
				__( 'Edit', 'autoblogtext' )
			),

			'process' => sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( array(
					'action'               => 'process',
					'_wpnonce'             => $this->_args['nonce'],
					'noheader'             => 'true',
					$this->_args['plural'] => $item['feed_id'],
				) ),
				__( 'Process', 'autoblogtext' )
			),

			'test' => sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( array(
					'action'               => 'test',
					'_wpnonce'             => $this->_args['nonce'],
					'noheader'             => 'true',
					$this->_args['single'] => $item['feed_id'],
				) ),
				__( 'Test Improt', 'autoblogtext' )
			),

			'validate' => sprintf(
				'<a href="http://validator.w3.org/feed/check.cgi?url=%s" target="_blank">%s</a>',
				urlencode( $item['feed_meta']['url'] ),
				__( 'Validate', 'autoblogtext' )
			),

			'delete' => sprintf(
				'<a href="%s" onclick="return showNotice.warn();">%s</a>',
				add_query_arg( array(
					'action'               => 'delete',
					'_wpnonce'             => $this->_args['nonce'],
					'noheader'             => 'true',
					$this->_args['plural'] => $item['feed_id'],
				) ),
				__( 'Delete', 'autoblogtext' )
			),
		), $item['feed_id'], $item );

		return sprintf( '<a href="%s"><b>%s</b></a> %s', $edit_link, $title, $this->row_actions( $actions ) );
	}

	/**
	 * Returns post type associated with the feed.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of feed data.
	 * @return string Post type.
	 */
	public function column_post_type( $item ) {
		return !empty( $item['feed_meta']['posttype'] )
			? $item['feed_meta']['posttype']
			: esc_html__( 'unknown', 'autoblogtext' );
	}

	/**
	 * Returns blog name associated with the feed.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of feed data.
	 * @return string Blog name.
	 */
	public function column_blogname( $item ) {
		static $blogs = array();

		$post_type = isset( $item['feed_meta']['posttype'] )
			? $item['feed_meta']['posttype']
			: 'post';

		$key = $item['blog_id'] . $post_type;

		if ( !isset( $blogs[$key] ) ) {
			switch_to_blog( $item['blog_id'] );
			$blogs[$key] = sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) ),
				get_option( 'blogname' )
			);
			restore_current_blog();
		}

		return $blogs[$key];
	}

	/**
	 * Returns the feed last processed time.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of feed data.
	 * @return string Interval from last processed time.
	 */
	public function column_lastupdated( $item ) {
		if ( $item['lastupdated'] == 0 ) {
			return '<code>' . __( 'Never', 'autoblogtext' ) . '</code>';
		}

		return sprintf(
			'<code title="%s">%s</code>',
			date_i18n( $this->_args['date_i18n_format'], $item['lastupdated'] ),
			$this->_convert_time_to_str( $item['lastupdated'] )
		);
	}

	/**
	 * Returns the feed next check time.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of feed data.
	 * @return string Next check time.
	 */
	public function column_nextcheck( $item ) {
		$next_check = $item['nextcheck'];
		if ( $next_check == 0 ) {
			return '<code>' . __( 'Never', 'autoblogtext' ) . '</code>';
		}

		if ( $this->_args['next_schedule'] && $this->_args['next_schedule'] > $next_check ) {
			$next_check = $this->_args['next_schedule'];
		}

		return sprintf(
			'<code title="%s">%s</code>',
			date_i18n( $this->_args['date_i18n_format'], $next_check ),
			$this->_convert_time_to_str( $next_check )
		);
	}

	/**
	 * Returns tabel columns.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @return array The array of table columns to display.
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" class="cb_all">',
			'title'     => __( 'Feed', 'autoblogtext' ),
			'post_type' => __( 'Post Type', 'autoblogtext' ),
		);

		if ( $this->_args['is_network_wide'] ) {
			$columns['blogname']    = __( 'Target Blog', 'autoblogtext' );
			$columns['lastupdated'] = __( 'Last Processed *', 'autoblogtext' );
			$columns['nextcheck']   = __( 'Next Check *', 'autoblogtext' );
		} else {
			$columns['lastupdated'] = __( 'Last Processed', 'autoblogtext' );
			$columns['nextcheck']   = __( 'Next Check', 'autoblogtext' );
		}

		return $columns;
	}

	/**
	 * Calculates interval between specific timestamp and current time.
	 *
	 * @sicne 4.0.0
	 *
	 * @access private
	 * @param int|string $timestamp Timestamp or stringular date/time.
	 * @return string Interval value.
	 */
	private function _convert_time_to_str( $timestamp ) {
		if ( !ctype_digit( $timestamp ) ) {
			$timestamp = strtotime( $timestamp );
		}

		$diff = current_time( 'timestamp' ) - $timestamp;
		if ( $diff == 0 ) {
			return __( 'now', 'autoblogtext' );
		}

		if ( $diff > 0 ) {
			$day_diff = floor( $diff / DAY_IN_SECONDS );
			if ( $day_diff == 0 ) {
				if ( $diff < MINUTE_IN_SECONDS ) {
					return __( 'just now', 'autoblogtext' );
				}

				if ( $diff < 2 * MINUTE_IN_SECONDS ) {
					return __( '1 minute ago', 'autoblogtext' );
				}

				if ( $diff < HOUR_IN_SECONDS ) {
					return floor( $diff / MINUTE_IN_SECONDS ) . __( ' minutes ago', 'autoblogtext' );
				}

				if ( $diff < 2 * HOUR_IN_SECONDS ) {
					return __( '1 hour ago', 'autoblogtext' );
				}

				if ( $diff < DAY_IN_SECONDS ) {
					return floor( $diff / HOUR_IN_SECONDS ) . __( ' hours ago', 'autoblogtext' );
				}
			}

			if ( $day_diff == 1 ) {
				return __( 'Yesterday', 'autoblogtext' );
			}

			if ( $day_diff < 7 ) {
				return $day_diff . __( ' days ago', 'autoblogtext' );
			}

			if ( $day_diff < 31 ) {
				return ceil( $day_diff / 7 ) . __( ' weeks ago', 'autoblogtext' );
			}

			if ( $day_diff < 60 ) {
				return __( 'last month', 'autoblogtext' );
			}

			return date( 'F Y', $timestamp );
		}

		$diff = abs( $diff );
		$day_diff = floor( $diff / DAY_IN_SECONDS );
		if ( $day_diff == 0 ) {
			if ( $diff < 2 * MINUTE_IN_SECONDS ) {
				return __( 'in a minute', 'autoblogtext' );
			}

			if ( $diff < HOUR_IN_SECONDS ) {
				return __( 'in ', 'autoblogtext' ) . floor( $diff / MINUTE_IN_SECONDS ) . __( ' minutes', 'autoblogtext' );
			}

			if ( $diff < 2 * HOUR_IN_SECONDS ) {
				return __( 'in an hour', 'autoblogtext' );
			}

			if ( $diff < DAY_IN_SECONDS ) {
				return __( 'in ', 'autoblogtext' ) . floor( $diff / HOUR_IN_SECONDS ) . __( ' hours', 'autoblogtext' );
			}
		}
		if ( $day_diff == 1 ) {
			return __( 'Tomorrow', 'autoblogtext' );
		}

		if ( $day_diff < 4 ) {
			return date( 'l', $timestamp );
		}

		if ( $day_diff < 7 + ( 7 - date( 'w' ) ) ) {
			return __( 'next week', 'autoblogtext' );
		}

		if ( ceil( $day_diff / 7 ) < 4 ) {
			return __( 'in ', 'autoblogtext' ) . ceil( $day_diff / 7 ) . __( ' weeks', 'autoblogtext' );
		}

		if ( date( 'n', $timestamp ) == date( 'n' ) + 1 ) {
			return __( 'next month', 'autoblogtext' );
		}

		return date( 'F Y', $timestamp );
	}

	/**
	 * Fetches records from database.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @global wpdb $wpdb The database connection.
	 */
	public function prepare_items() {
		global $wpdb;

		parent::prepare_items();

		$per_page = 10;
		$offset = ( $this->get_pagenum() - 1 ) * $per_page;

		$sites = array( empty( $wpdb->siteid ) || $wpdb->siteid == 0 ? 1 : $wpdb->siteid );
		$blogs = array( get_current_blog_id() );
		if ( defined( 'AUTOBLOG_LAZY_ID' ) && filter_var( AUTOBLOG_LAZY_ID, FILTER_VALIDATE_BOOLEAN ) ) {
			$sites[] = 0;
			$blogs[] = 0;
		}

		$this->items = $wpdb->get_results( sprintf( "
			SELECT SQL_CALC_FOUND_ROWS *
			  FROM %s
			 WHERE site_id IN (%s)%s
			 ORDER BY feed_id DESC
			 LIMIT %d
			OFFSET %d
			",
			AUTOBLOG_TABLE_FEEDS,
			implode( ', ', $sites ),
			!$this->_args['is_network_wide'] ? ' AND blog_id IN (' . implode( ', ', $blogs ) . ')' : '',
			$per_page,
			$offset
		), ARRAY_A );

		foreach ( $this->items as &$item ) {
			$item['id'] = $item['feed_id'];
			$item['feed_meta'] = @unserialize( $item['feed_meta'] );
		}

		$total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}
