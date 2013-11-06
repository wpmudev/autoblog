<?php
/*
Plugin Name: AutoBlog
Version: 3.9.9.8
Plugin URI: http://premium.wpmudev.org/project/autoblog
Description: This plugin automatically posts content from RSS feeds to different blogs on your WordPress Multisite...
Author: Incsub
Author URI: http://premium.wpmudev.org/
WDP ID: 97
*/

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

// Modify the next line to change the user agent reported to web sites.
//define('SIMPLEPIE_USERAGENT', 'ABlogPremium/0.1 (Feed Parser; http://premium.wpmudev.org; Allow like Gecko) Build/1');

// Using SimplePie
//define('SIMPLEPIE_USERAGENT', 'PUT AGENT HERE');

define( 'AUTOBLOG_BASEFILE', __FILE__ );

require_once( dirname(__FILE__) . '/autoblogincludes/includes/config.php');
require_once( dirname(__FILE__) . '/autoblogincludes/includes/functions.php');
// Set up my location
set_autoblog_url(__FILE__);
set_autoblog_dir(__FILE__);

// Load them up
if(is_admin()) {
	include_once( dirname(__FILE__) . '/autoblogincludes/external/wpmudev-dash-notification.php');

	require_once( dirname(__FILE__) . '/autoblogincludes/includes/class_wd_help_tooltips.php');
	require_once( dirname(__FILE__) . '/autoblogincludes/classes/autoblogadmin.php');

	$abp = new autoblogpremium();
}

// Include the processing class
require_once( dirname(__FILE__) . '/autoblogincludes/classes/autoblogprocess.php');
$abc = new autoblogcron();

load_autoblog_addons();
load_networkautoblog_addons();
