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
?>