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
 * Addons page module.
 *
 * @category Autoblog
 * @package Module
 * @subpackage Page
 *
 * @since 4.0.0
 */
class Autoblog_Module_Page_Addons extends Autoblog_Module {

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

		$this->_add_action( 'autoblog_handle_addons_page', 'handle_addons_page' );
		$this->_add_action( 'autoblog_handle_network_addons_page', 'handle_network_addons_page' );
	}

	/**
	 * Handles addons page.
	 *
	 * @since 4.0.0
	 * @action autoblog_handle_feeds_page
	 *
	 * @access public
	 */
	public function handle_addons_page() {
		$table = new Autoblog_Table_Addons( array(
			'nonce'           => wp_create_nonce( 'autoblog_addons' ),
			'active'          => get_option( 'autoblog_activated_addons', array() ),
			'oposite'         => get_site_option( 'autoblog_networkactivated_addons', array() ),
			'actions'         => array(
				'activate'   => __( 'Activate', 'autoblogtext' ),
				'deactivate' => __( 'Deactivate', 'autoblogtext' ),
			),
		) );

		switch ( $table->current_action() ) {
			case 'activate':
				$this->_activate_addons( 'autoblog_activated_addons' );
				break;
			case 'deactivate':
				$this->_deactivate_addons( 'autoblog_activated_addons' );
				break;
			default:
				if ( filter_input( INPUT_GET, 'noheader', FILTER_VALIDATE_BOOLEAN ) ) {
					wp_redirect( add_query_arg( 'noheader', false ) );
					exit;
				}

				$template = new Autoblog_Render_Addons_Table();
				$template->table = $table;
				$template->render();
				break;
		}
	}

	/**
	 * Handles network addons page.
	 *
	 * @since 4.0.0
	 * @action autoblog_handle_feeds_page
	 *
	 * @access public
	 */
	public function handle_network_addons_page() {
		$table = new Autoblog_Table_Addons( array(
			'nonce'           => wp_create_nonce( 'autoblog_addons' ),
			'active'          => get_site_option( 'autoblog_networkactivated_addons', array() ),
			'oposite'         => array(),
			'actions'         => array(
				'activate'   => __( 'Activate', 'autoblogtext' ),
				'deactivate' => __( 'Deactivate', 'autoblogtext' ),
			),
		) );

		switch ( $table->current_action() ) {
			case 'activate':
				$this->_activate_addons( 'autoblog_networkactivated_addons', true );
				break;
			case 'deactivate':
				$this->_deactivate_addons( 'autoblog_networkactivated_addons', true );
				break;
			default:
				if ( filter_input( INPUT_GET, 'noheader', FILTER_VALIDATE_BOOLEAN ) ) {
					wp_redirect( add_query_arg( 'noheader', false ) );
					exit;
				}

				$template = new Autoblog_Render_Addons_Table();
				$template->table = $table;
				$template->render();
				break;
		}
	}

	/**
	 * Returns cleaned up URL to return to after action process.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access private
	 * @return string Cleaned up URL to return to after action process.
	 */
	private static function _get_back_ref() {
		return add_query_arg( array(
			'action'      => false,
			'noheader'    => false,
			'_wpnonce'    => false,
			'plugins'     => false,
			'deactivated' => false,
			'activated'   => false,
		) );
	}

	/**
	 * Activates addons.
	 *
	 * @sicne 4.0.0
	 *
	 * @access private
	 * @param string $option The option key to update.
	 */
	private function _activate_addons( $option, $site = false ) {
		check_admin_referer( 'autoblog_addons' );

		$backref = self::_get_back_ref();

		$addons = isset( $_REQUEST['plugins'] ) ? (array)$_REQUEST['plugins'] : array();
		$addons = array_filter( array_map( 'trim', $addons ) );
		if ( empty( $addons ) ) {
			wp_safe_redirect( $backref );
			exit;
		}

		if ( $site ) {
			update_site_option( $option, array_unique( array_merge( get_site_option( $option, array() ), $addons ) ) );
		} else {
			update_option( $option, array_unique( array_merge( get_option( $option, array() ), $addons ) ) );
		}

		wp_safe_redirect( add_query_arg( 'activated', 'true', $backref ) );
		exit;
	}

	/**
	 * Deactivates addons.
	 *
	 * @sicne 4.0.0
	 *
	 * @access private
	 * @param string $option The option key to update.
	 */
	private function _deactivate_addons( $option, $site = false ) {
		check_admin_referer( 'autoblog_addons' );

		$backref = self::_get_back_ref();

		$addons = isset( $_REQUEST['plugins'] ) ? (array)$_REQUEST['plugins'] : array();
		$addons = array_filter( array_map( 'trim', $addons ) );
		if ( empty( $addons ) ) {
			wp_safe_redirect( $backref );
			exit;
		}

		if ( $site ) {
			update_site_option( $option, array_unique( array_diff( get_site_option( $option, array() ), $addons ) ) );
		} else {
			update_option( $option, array_unique( array_diff( get_option( $option, array() ), $addons ) ) );
		}

		wp_safe_redirect( add_query_arg( 'deactivated', 'true', $backref ) );
		exit;
	}

}