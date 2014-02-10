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

			'clone' => sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( array(
					'action'               => 'duplicate',
					'_wpnonce'             => $this->_args['nonce'],
					'noheader'             => 'true',
					$this->_args['plural'] => $item['feed_id'],
				) ),
				__( 'Clone', 'autoblogtext' )
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
		if ( empty( $item['feed_meta']['posttype'] ) ) {
			return esc_html__( 'unknown', 'autoblogtext' );
		}

		$post_type = get_post_type_object( $item['feed_meta']['posttype'] );
		if ( is_null( $post_type ) ) {
			return $item['feed_meta']['posttype'];
		}

		return get_post_type_labels( $post_type )->name;
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
			$blogs[$key] = sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) ),
				get_option( 'blogname' )
			);
		}

		return $blogs[$key];
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		$need_switch = !empty( $item['blog_id'] ) && $item['blog_id'] != get_current_blog_id();

		// switch to feed blog
		if ( $need_switch && function_exists( 'switch_to_blog' ) ) {
			switch_to_blog( $item['blog_id'] );
			$this->_args['date_i18n_format'] = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}

		// render single row
		parent::single_row( $item );

		// restore current blog
		if ( $need_switch && function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
			$this->_args['date_i18n_format'] = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}
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
			$this->_convert_time_to_str( $item['lastupdated'] + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
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
		$next_check = Autoblog_Plugin::use_cron()
			? wp_next_scheduled( Autoblog_Plugin::SCHEDULE_PROCESS, array( absint( $item['feed_id'] ) ) )
			: absint( $item['nextcheck'] );

		if ( $next_check ) {
			$next_check += get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$current_time = current_time( 'timestamp' );
			if ( $next_check < $current_time ) {
				$next_check = $current_time;
			}
		}

		return $next_check
			? sprintf( '<code title="%s">%s</code>', date_i18n( $this->_args['date_i18n_format'], $next_check ), $this->_convert_time_to_str( $next_check ) )
			: __( 'Never', 'autoblogtext' );
	}

	/**
	 * Returns column value.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item The table row to display.
	 * @param string $column_name The column id to render.
	 * @return string The value to display.
	 */
	public function column_default( $item, $column_name ) {
		$default = rand( 0, 9999999 );

		$value = apply_filters( 'autoblog_feed_table_column_' . $column_name . '_value', $default, $item );
		$value = apply_filters( 'autoblog_feed_table_column_value', $value, $item, $column_name );

		return $value == $default
			? parent::column_default( $item, $column_name )
			: $value;
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
			'cb'    => '<input type="checkbox" class="cb_all">',
			'title' => __( 'Feed', 'autoblogtext' ),
		);

		if ( is_network_admin() ) {
			$columns['blogname']    = __( 'Target Blog', 'autoblogtext' );
		}

		$columns['post_type'] = __( 'Post Type', 'autoblogtext' );
		$columns['lastupdated'] = __( 'Last Processed', 'autoblogtext' );
		$columns['nextcheck']   = __( 'Next Check', 'autoblogtext' );

		return apply_filters( 'autoblog_feed_table_columns', $columns );
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
			!is_network_admin() ? ' AND blog_id IN (' . implode( ', ', $blogs ) . ')' : '',
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
