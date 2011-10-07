<?php
// Ensure you keep a backup of this file once you have it set up - in case it is replaced by a plugin upgrade.

// Uses a global table so that all entries can be managed by the network admin as well
if(!defined('AUTOBLOG_GLOBAL')) define( 'AUTOBLOG_GLOBAL', true );

// Processing will stop after 3 seconds (default) so as not to overload your server
if(!defined('AUTOBLOG_PROCESSING_TIMELIMIT')) define( 'AUTOBLOG_PROCESSING_TIMELIMIT', 6);

// Processing will take place every 30 minutes.
if(!defined('AUTOBLOG_PROCESSING_CHECKLIMIT')) define( 'AUTOBLOG_PROCESSING_CHECKLIMIT', 10);

// In a multisite install will attempt to process feeds for all sites rather than just local ones
if(!defined('AUTOBLOG_FORCE_PROCESS_ALL')) define( 'AUTOBLOG_FORCE_PROCESS_ALL', false);

// Will check for feeds to process on every page load rather than using the limit defined above
if(!defined('AUTOBLOG_PROCESS_EVERY_PAGE_LOAD')) define( 'AUTOBLOG_PROCESS_EVERY_PAGE_LOAD', false);

if(!defined('AUTOBLOG_HANDLE_FAKE_TAGS')) define( 'AUTOBLOG_HANDLE_FAKE_TAGS', false);

if(!defined('AUTOBLOG_LAZY_ID')) define( 'AUTOBLOG_LAZY_ID', true);
?>