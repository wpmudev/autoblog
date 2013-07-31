<?php
// Ensure you keep a backup of this file once you have it set up - in case it is replaced by a plugin upgrade.

// Uses a global table so that all entries can be managed by the network admin as well
if(!defined('AUTOBLOG_GLOBAL')) define( 'AUTOBLOG_GLOBAL', true );

// Processing will stop after 6 seconds (default) so as not to overload your server
if(!defined('AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT')) define( 'AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT', 60);

// Processing will stop after 6 seconds (default) so as not to overload your server
if(!defined('AUTOBLOG_PROCESSING_TIMELIMIT')) define( 'AUTOBLOG_PROCESSING_TIMELIMIT', 6);

// Processing will take place every minute
if(!defined('AUTOBLOG_PROCESSING_CHECKLIMIT')) define( 'AUTOBLOG_PROCESSING_CHECKLIMIT', 1);

// In a multisite install will attempt to process feeds for all sites rather than just local ones
if(!defined('AUTOBLOG_FORCE_PROCESS_ALL')) define( 'AUTOBLOG_FORCE_PROCESS_ALL', false);

// Uses a different, more processing intensive, method of adding tags to a post for sites that have tag based issues
if(!defined('AUTOBLOG_HANDLE_FAKE_TAGS')) define( 'AUTOBLOG_HANDLE_FAKE_TAGS', true);

// To see feeds from older versions of the plugin that have yet to be repaired.
if(!defined('AUTOBLOG_LAZY_ID')) define( 'AUTOBLOG_LAZY_ID', true);

// To switch from a CRON processing method set this to 'pageload' (default is 'cron' to use the wp-cron).
if(!defined('AUTOBLOG_PROCESSING_METHOD')) define( 'AUTOBLOG_PROCESSING_METHOD', 'cron');

// Information to use for duplicate checking - link or guid
if(!defined('AUTOBLOG_POST_DUPLICATE_CHECK')) define( 'AUTOBLOG_POST_DUPLICATE_CHECK', 'link');

// Information to use for duplicate checking - link or guid
if(!defined('AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS')) define( 'AUTOBLOG_EXTERNAL_PERMALINK_SKIP_FEEDS', '');


/*
*	The following configuration options are default for the featured image import and can be overridden by that add-on
*/

// Order to check images to pick which will be the one to be a featured image
if(!defined('AUTOBLOG_IMAGE_CHECK_ORDER')) define( 'AUTOBLOG_IMAGE_CHECK_ORDER', 'ASC');

// Only set an image as featured if it is wider than this setting
if(!defined('AUTOBLOG_FEATURED_IMAGE_MIN_WIDTH')) define( 'AUTOBLOG_FEATURED_IMAGE_MIN_WIDTH', 80);

// Only set an image as featured if it is taller than this setting
if(!defined('AUTOBLOG_FEATURED_IMAGE_MIN_HEIGHT')) define( 'AUTOBLOG_FEATURED_IMAGE_MIN_HEIGHT', 80);

?>