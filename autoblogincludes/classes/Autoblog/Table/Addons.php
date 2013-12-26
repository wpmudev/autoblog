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
 * Addons table class.
 *
 * @category Autoblog
 * @package Table
 *
 * @since 4.0.0
 */
class Autoblog_Table_Addons extends Autoblog_Table {

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
			'plural'     => 'plugins',
			'autoescape' => false,
		), $args ) );
	}

	/**
	 * Returns checkbox column value.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item The table row to display.
	 * @return string The value to display.
	 */
	public function column_cb( $item ) {
		return in_array( $item['Source'], $this->_args['oposite'] )
			? '<input type="checkbox" class="cb" disabled>'
			: sprintf( '<input type="checkbox" class="cb" name="%s[]" value="%s">', $this->_args['plural'], esc_attr( $item['Source'] ) );
	}

	/**
	 * Returns the addon title.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of addon data.
	 * @return string Addon title.
	 */
	public function column_addon( $item ) {
		$actions = array();

		if ( in_array( $item['Source'], $this->_args['oposite'] ) ) {
			$actions[] = sprintf( '<a class="disabled" href="#">%s</a>', __( 'Network Active', 'autoblogtext' ) );
		} else {
			if ( in_array( $item['Source'], $this->_args['active'] ) ) {
				$actions['deactivate'] = sprintf(
					'<a href="%s">%s</a>',
					add_query_arg( array(
						'action'               => 'deactivate',
						'noheader'             => 'true',
						'_wpnonce'             => $this->_args['nonce'],
						$this->_args['plural'] => urlencode( $item['Source'] ),
					) ),
					__( 'Deactivate', 'autoblogtext' )
				);
			} else {
				$actions['activate'] = sprintf(
					'<a href="%s">%s</a>',
					add_query_arg( array(
						'action'               => 'activate',
						'noheader'             => 'true',
						'_wpnonce'             => $this->_args['nonce'],
						$this->_args['plural'] => urlencode( $item['Source'] ),
					) ),
					__( 'Activate', 'autoblogtext' )
				);
			}
		}

		$actions = apply_filters( 'autoblog_addon_actions', $actions, $item );
		return sprintf( '<strong>%s</strong> %s', $item['Name'], $this->row_actions( $actions, true ) );
	}

	/**
	 * Returns the addon description.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $item Array of addon data.
	 * @return string The addon description.
	 */
	public function column_description( $item ) {
		return sprintf(
			'<div class="plugin-description"><p>%s</p></div><div class="%s second plugin-version-author-uri">%s <a href="%s" target="_blank">%s</a></div>',
			$item['Description'],
			in_array( $item['Source'], $this->_args['active'] ) || in_array( $item['Source'], $this->_args['oposite'] ) ? 'active' : 'inactive',
			__( 'Created By', 'autoblogtext' ),
			$item['AuthorURI'],
			$item['Author']
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
		return array(
			'cb'          => '<input type="checkbox" class="cb_all">',
			'addon'       => __( 'Addon', 'autoblogtext' ),
			'description' => __( 'Description', 'autoblogtext' ),
		);
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
		$row_class = in_array( $item['Source'], $this->_args['active'] ) || in_array( $item['Source'], $this->_args['oposite'] ) ? 'active' : 'inactive';

		echo '<tr class="' . $row_class . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Returns the associative array with the list of views available on this table.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @global wpdb $wpdb The database connection.
	 * @return array The array of views.
	 */
	public function get_views() {
		$active = $inactive = 0;
		for ( $i = 0, $len = count( $this->_args['all'] ); $i < $len; $i++ ) {
			if ( in_array( $this->_args['all'][$i], $this->_args['active'] ) || in_array( $this->_args['all'][$i], $this->_args['oposite'] ) ) {
				$active++;
			} else {
				$inactive++;
			}
		}

		$type = filter_input( INPUT_GET, 'type' );
		return array(
			'all' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				add_query_arg( array( 'type' => false, 'paged' => false ) ),
				!$type ? ' class="current"' : '',
				__( 'All', 'autoblogtext' ),
				$len
			),
			'active' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				add_query_arg( array( 'type' => 'active', 'paged' => false ) ),
				$type == 'active' ? ' class="current"' : '',
				__( 'Active', 'autoblogtext' ),
				$active
			),
			'inactive' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				add_query_arg( array( 'type' => 'inactive', 'paged' => false ) ),
				$type == 'inactive' ? ' class="current"' : '',
				__( 'Inactive', 'autoblogtext' ),
				$inactive
			),
		);
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
		parent::prepare_items();

		$per_page = 10;
		$offset = ( $this->get_pagenum() - 1 ) * $per_page;

		$items = array();
		$directory = AUTOBLOG_ABSPATH . 'autoblogincludes/addons/';
		if ( is_dir( $directory ) ) {
			if ( ( $dh = opendir( $directory ) ) ) {
				while ( ( $plugin = readdir( $dh ) ) !== false ) {
					if ( substr( $plugin, -4 ) == '.php' ) {
						$items[] = $plugin;
					}
				}
				closedir( $dh );
				sort( $items );

				$items = apply_filters( 'autoblog_available_addons', $items );
			}
		}

		$this->_args['all'] = $items;

		$type = filter_input( INPUT_GET, 'type' );
		if ( $type == 'active' || $type == 'inactive' ) {
			$filtered = array();
			for ( $i = 0, $len = count( $items ); $i < $len; $i++ ) {
				if ( $type == 'active' && ( in_array( $items[$i], $this->_args['active'] ) || in_array( $items[$i], $this->_args['oposite'] ) ) ) {
					$filtered[] = $items[$i];
				} elseif ( $type == 'inactive' && !( in_array( $items[$i], $this->_args['active'] ) || in_array( $items[$i], $this->_args['oposite'] ) ) ) {
					$filtered[] = $items[$i];
				}
			}

			$items = $filtered;
		}

		$this->items = array_slice( $items, $offset, $per_page );
		for ( $i = 0, $len = count( $this->items ); $i < $len; $i++ ) {
			$this->items[$i] = get_file_data( $directory . $this->items[$i], array(
				'Name'        => 'Addon Name',
				'Author'      => 'Author',
				'Description' => 'Description',
				'AuthorURI'   => 'Author URI',
				'Network'     => 'Network'
			), 'plugin' ) + array( 'Source' => $this->items[$i] );
		}

		usort( $this->items, array( $this, 'sort_items' ) );

		$total_items = count( $items );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	/**
	 * Sorts addons by name.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param array $a The first addon to compare with.
	 * @param array $b The second addon to compare.
	 * @return int Less than 0 if str1 is less than str2; More than 0 if str1 is greater than str2, and 0 if they are equal.
	 */
	public function sort_items( $a, $b ) {
		return strcmp( $a['Name'], $b['Name'] );
	}

}
