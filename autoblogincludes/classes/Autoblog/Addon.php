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
 * Base class for autoblog addon.
 *
 * @category Autoblog
 * @package Addon
 *
 * @since 4.0.0
 */
class Autoblog_Addon {

	/**
	 * The database connection.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @var wpdb
	 */
	protected $_wpdb;

	/**
	 * The array of registered actions hooks.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var array
	 */
	private $_actions = array();

	/**
	 * The array of registered filters hooks.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var array
	 */
	private $_filters = array();

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @global wpdb $wpdb The database connection.
	 */
	public function __construct() {
		global $wpdb;
		$this->_wpdb = $wpdb;
	}

	/**
	 * Renders block title.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param string $block_title The title for a block.
	 */
	protected function _render_block_header( $block_title ) {
		echo '<tr class="spacer">';
			echo '<td colspan="2" class="spacer">';
				echo '<span>', $block_title, '</span>';
			echo '</td>';
		echo '</tr>';
	}

	/**
	 * Renders block element.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param string $label The label.
	 * @param string $element The element.
	 * @param string $help The optional help text.
	 */
	protected function _render_block_element( $label, $element, $help = false ) {
		echo '<tr>';
			echo '<td valign="top" class="heading">', $label, "</td>";
			echo '<td valign="top">';
				echo $element;
				if ( $help ) {
					$tips = new WPMUDEV_Help_Tooltips();
					$tips->set_icon_url( AUTOBLOG_ABSURL . 'images/information.png' );
					echo ' ', $tips->add_tip( $help );
				}
				echo '</td>';
		echo '</tr>';
	}

	/**
	 * Builds and returns hook key.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access private
	 * @param array $args The hook arguments.
	 * @return string The hook key.
	 */
	private static function _get_hook_key( array $args ) {
		return md5( implode( '/', $args ) );
	}

	/**
	 * Registers an action hook.
	 *
	 * @since 4.0.0
	 * @uses add_action() To register action hook.
	 *
	 * @access protected
	 * @param string $tag The name of the action to which the $method is hooked.
	 * @param string $method The name of the method to be called.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Autoblog_Addon
	 */
	protected function _add_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$args = func_get_args();
		$this->_actions[self::_get_hook_key( $args )] = $args;

		add_action( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Removes an action hook.
	 *
	 * @since 4.0.0
	 * @uses remove_action() To remove action hook.
	 *
	 * @access protected
	 * @param string $tag The name of the action to which the $method is hooked.
	 * @param string $method The name of the method to be called.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Autoblog_Addon
	 */
	protected function _remove_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		add_action( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Registers a filter hook.
	 *
	 * @since 4.0.0
	 * @uses add_filter() To register filter hook.
	 *
	 * @access protected
	 * @param string $tag The name of the filter to hook the $method to.
	 * @param type $method The name of the method to be called when the filter is applied.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Autoblog_Addon
	 */
	protected function _add_filter( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$args = func_get_args();
		$this->_filters[self::_get_hook_key( $args )] = $args;

		add_filter( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Removes a filter hook.
	 *
	 * @since 4.0.0
	 * @uses remove_filter() To remove filter hook.
	 *
	 * @access protected
	 * @param string $tag The name of the filter to remove the $method to.
	 * @param type $method The name of the method to remove.
	 * @param int $priority optional. The priority of the function (default: 10).
	 * @param int $accepted_args optional. The number of arguments the function accepts (default: 1).
	 * @return Autoblog_Addon
	 */
	protected function _remove_filter( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		remove_filter( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Unbinds all hooks previously registered for actions and/or filters.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param boolean $actions TRUE to unbind all actions hooks.
	 * @param boolean $filters TRUE to unbind all filters hooks.
	 */
	public function unbind( $actions = true, $filters = true ) {
		$types = array();

		if ( $actions ) {
			$types['_actions'] = 'remove_action';
		}

		if ( $filters ) {
			$types['_filters'] = 'remove_filter';
		}

		foreach ( $types as $hooks => $method ) {
			foreach ( $this->$hooks as $hook ) {
				call_user_func_array( $method, $hook );
			}
		}
	}

	/**
	 * Get raw html of a simple pie content, needed in many case
	 *
	 * @param SimplePie_Item $item
	 *
	 * @return string
	 */
	public function get_simplepie_item_raw(SimplePie_Item $item ) {
		$content_namespaces = array(
			SIMPLEPIE_NAMESPACE_ATOM_10                => 'content',
			SIMPLEPIE_NAMESPACE_ATOM_03                => 'content',
			SIMPLEPIE_NAMESPACE_RSS_10_MODULES_CONTENT => 'encoded'
		);

		$summary_namespaces = array(
			SIMPLEPIE_NAMESPACE_ATOM_10 => 'summary',
			SIMPLEPIE_NAMESPACE_ATOM_03 => 'summary',
			SIMPLEPIE_NAMESPACE_RSS_10  => 'description',
			SIMPLEPIE_NAMESPACE_RSS_20  => 'description',
			SIMPLEPIE_NAMESPACE_DC_11   => 'description',
			SIMPLEPIE_NAMESPACE_DC_10   => 'description',
			SIMPLEPIE_NAMESPACE_ITUNES  => 'summary',
			SIMPLEPIE_NAMESPACE_ITUNES  => 'subtitle',
			SIMPLEPIE_NAMESPACE_RSS_090 => 'description',
		);

		$raw_content = '';
		foreach ( $content_namespaces as $key => $val ) {
			$return = $item->get_item_tags( $key, $val );
			if ( $return ) {
				$raw_content = $return[0]['data'];
				break;
			}
		}
		if ( empty( $raw_content ) ) {
			//if raw content still empty, get from summary
			foreach ( $summary_namespaces as $key => $val ) {
				$return = $item->get_item_tags( $key, $val );
				if ( $return ) {
					$raw_content = $return[0]['data'];
					break;
				}
			}
		}

		return $raw_content;
	}

}