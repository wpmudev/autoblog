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
 * Fake SimplePie sanitize class.
 *
 * @since 4.0.0
 *
 * @category Autoblog
 * @package SimplePie
 */
class Autoblog_SimplePie_Sanitize extends WP_SimplePie_Sanitize_KSES {

	/**
	 * Doesn't sanitizes the content.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $data Income data to sanitize.
	 * @param int $type The type of content to sanitize.
	 * @param type $base
	 * @return string Unchanged income data.
	 */
	public function sanitize( $data, $type, $base = '' ) {
		return $data;
	}

}