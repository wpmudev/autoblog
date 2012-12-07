<?php
class autoblogcron {

	var $db;

	var $tables = array('autoblog');
	var $autoblog;

	var $debug = false;

	var $msgs = array();

	var $testingmsgs = array();

	var $siteid = 1;
	var $blogid = 1;

	var $checkperiod = '10mins';

	function __construct() {

		global $wpdb;

		// check if a modulous of 5 mintutes and if so add the init hook.
		$min = date("i");

		$this->db =& $wpdb;

		foreach($this->tables as $table) {
			$this->$table = autoblog_db_prefix($this->db, $table);
		}

		add_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'feed_cache'), 10, 2 );

		// override with option.
		$this->debug = get_autoblog_option('autoblog_debug', false);

		if(empty($this->db->siteid) || $this->db->siteid == 0) {
			$this->siteid = 1;
		} else {
			$this->siteid = $this->db->siteid;
		}

		if(empty($this->db->blogid) || $this->db->blogid == 0) {
			$this->blogid = 1;
		} else {
			$this->blogid = $this->db->blogid;
		}

		// Action to be called by the cron job
		if(defined('AUTOBLOG_PROCESSING_CHECKLIMIT') && AUTOBLOG_PROCESSING_CHECKLIMIT == 10) {
			$this->checkperiod = '10mins';
		} else {
			$this->checkperiod = '5mins';
		}
		add_action( 'init', array(&$this, 'set_up_schedule') );
		//add_action( 'autoblog_process_feeds', array(&$this, 'always_process_autoblog') );
		add_filter( 'cron_schedules', array(&$this, 'add_time_period') );

		// Add in filter for the_post to add in the source content at the bottom
		add_filter( 'the_content', array(&$this, 'append_original_source'), 999, 1 );

	}

	function autoblogcron() {
		$this->__construct();
	}

	function feed_cache($cacheperiod = false, $url = false) {

		return (int) AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT;
	}

	function add_time_period( $periods ) {

		if(!is_array($periods)) {
			$periods = array();
		}

		$periods['10mins'] = array( 'interval' => 600, 'display' => __('Every 10 Mins', 'autoblogtext') );
		$periods['5mins'] = array( 'interval' => 300, 'display' => __('Every 5 Mins', 'autoblogtext') );

		return $periods;
	}

	function set_up_schedule() {

		if(defined('AUTOBLOG_PROCESSING_METHOD') && AUTOBLOG_PROCESSING_METHOD == 'cron') {
			if ( !wp_next_scheduled( 'autoblog_process_all_feeds_for_cron' ) ) {
				wp_schedule_event(time(), $this->checkperiod, 'autoblog_process_all_feeds_for_cron');
			}
		} else {
			// Use an init method
			$this->process_autoblog();
		}


	}

	function get_autoblogentries($timestamp) {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('autoblog/autoblogpremium.php')) {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND nextcheck < %d AND nextcheck > 0 ORDER BY nextcheck ASC", $timestamp );
			} else {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND blog_id IN (" . implode(',', $blogs) . ") AND nextcheck < %d AND nextcheck > 0 ORDER BY nextcheck ASC", $timestamp );
			}
		} else {
			$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND blog_id IN (" . implode(',', $blogs) . ") AND nextcheck < %d AND nextcheck > 0 ORDER BY nextcheck ASC", $timestamp );
		}

		if(defined('AUTOBLOG_FORCE_PROCESS_ALL') && AUTOBLOG_FORCE_PROCESS_ALL === true) {
			// Override and force to grab all feeds from the site
			$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND nextcheck < %d AND nextcheck > 0 ORDER BY nextcheck ASC", $timestamp );
		}

		$results = $this->db->get_results($sql);

		return $results;

	}

	function get_autoblogentriesforids($ids) {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id IN (0, " . implode(',', $ids) . ") ORDER BY nextcheck ASC", $timestamp );

		$results = $this->db->get_results($sql);

		return $results;

	}

	function get_autoblogentry($id) {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id = %d ORDER BY feed_id ASC", $id );

		$results = $this->db->get_row($sql);

		return $results;

	}

	function record_msg() {

		$thetime = current_time('timestamp');

		$msgs = array(	"timestamp" => $thetime,
							"log" => $this->msgs
						);

		update_autoblog_option('autoblog_log_' . $thetime, $msgs);

		// Remove any old entries so we only keep the most recent 25
		clear_autoblog_logs();

	}

	function record_testingmsg() {

		$thetime = current_time('timestamp');

		$msgs = array(	"timestamp" => $thetime,
							"log" => $this->testingmsgs
						);

		update_autoblog_option('autoblog_last_test_log', $msgs);

	}

	function process_feeds($ids) {

		// grab the feeds
		$autoblogs = $this->get_autoblogentriesforids($ids);

		if(!empty($autoblogs)) {
			do_action('autoblog_pre_process_feeds');

			foreach( (array) $autoblogs as $key => $ablog) {
				$details = unserialize($ablog->feed_meta);
				if(!empty($details['url'])) {
					do_action('autoblog_pre_process_feed', $ablog->feed_id, $details);
					$this->process_feed($ablog->feed_id, $details);
					do_action('autoblog_post_process_feed', $ablog->feed_id, $details);
				}
			}

			do_action('autoblog_post_process_feeds');
		}

		if(!empty($this->msgs)) {
			$this->record_msg();
		}

		return true;

	}

	function test_the_feed($feed_id, $ablog) {

		do_action('autoblog_pre_test_feed', $feed_id, $ablog);
		$results = $this->test_feed($feed_id, $ablog);
		do_action('autoblog_post_test_feed', $feed_id, $ablog);

		if(!empty($this->testingmsgs)) {
			$this->record_testingmsg();
		} else {
			delete_autoblog_option('autoblog_last_test_log');
		}

		return true;
	}

	function process_the_feed($feed_id, $ablog) {

		do_action('autoblog_pre_process_feed', $feed_id, $ablog);
		$results = $this->process_feed($feed_id, $ablog);
		do_action('autoblog_post_process_feed', $feed_id, $ablog);

		if(!empty($this->msgs)) {
			$this->record_msg();
		} else {

		}

		return $results;
	}

	function check_feed_item($ablog, $item) {
		// Set up the defaults
		$post_title = trim( $item->get_title() );
		$post_content = trim( $item->get_content() );

		$matchall = true; $matchany = true; $matchphrase = true; $matchnone = true; $matchtags = true;

		if(!empty($ablog['allwords'])) {
			$words = array_map('trim', explode(',', $ablog['allwords']));
			$matchall = true;
			foreach((array) $words as $key => $word) {
				$words[$key] = "/" . $word . "/i";
				if(!preg_match_all($words[$key], $post_title . " " . $post_content, $matches)) {
					$matchall = false;
					break;
				}
			}
		}

		if(!empty($ablog['anywords'])) {
			$words = array_map('trim', explode(',', $ablog['anywords']));
			$matchany = false;
			foreach((array) $words as $key => $word) {
				$words[$key] = "/" . $word . "/i";
				if(preg_match_all($words[$key], $post_title . " " . $post_content, $matches)) {
					$matchany = true;
					break;
				}
			}
		}

		if(!empty($ablog['phrase'])) {
			$words = array($ablog['phrase']);
			$matchphrase = false;
			foreach((array) $words as $key => $word) {
				$words[$key] = "/" . trim($word) . "/i";
				if(preg_match_all($words[$key], $post_title . " " . $post_content, $matches)) {
					$matchphrase = true;
					break;
				}
			}
		}

		if(!empty($ablog['nonewords'])) {
			$words = array_map('trim', explode(',', $ablog['nonewords']));
			$matchnone = true;
			foreach((array) $words as $key => $word) {
				$words[$key] = "/" . trim($word) . "/i";
				if(preg_match_all($words[$key], $post_title . " " . $post_content, $matches)) {
					$matchnone = false;
					break;
				}
			}
		}

		if(!empty($ablog['anytags'])) {
			$words = array_map('trim', explode(',', $ablog['anytags']));
			$matchtags = true;

			$thecats = $item->get_categories();
			if(!empty($thecats)) {
				$posttags = array();
				foreach ($thecats as $category)
				{
						$posttags[] = trim( $category->get_label() );
				}

				foreach((array) $words as $key => $word) {
					if(!in_array($word, $posttags)) {
						$matchtags = false;
						break;
					}
				}
			} else {
				$matchtags = false;
			}
		}

		if($matchall && $matchany && $matchphrase && $matchnone && $matchtags) {
			return true;
		} else {
			return false;
		}
	}

	function category_exists($cat_name, $parent = 0) {
		$id = term_exists($cat_name, 'category', $parent);
		if ( is_array($id) )
			$id = $id['term_id'];
		return $id;
	}

	function test_feed($feed_id, $ablog) {

		$this->testingmsgs[] = __('<strong>Feed Testing Results</strong>', 'autoblogtext');

		// Load simple pie if required
		if ( !function_exists('fetch_feed') && file_exists(ABSPATH . WPINC . '/feed.php') ) {
			require_once (ABSPATH . WPINC . '/feed.php');
		}

		// Check again in case the load didn't work
		if ( !function_exists('fetch_feed') ) {
			$this->testingmsgs[] = __('<strong>Error:</strong> Can not locate the feed reading file.','autoblogtext');
		}

		if(empty($ablog['url'])) {

			$this->testingmsgs[] = __('<strong>Error:</strong> There is no URL setup for this feed','autoblogtext');

			return false;
		}

		$feed = fetch_feed($ablog['url']);

		if(!is_wp_error($feed)) {

			if(isset($ablog['poststoimport']) && (int) $ablog['poststoimport'] != 0) {
				$max = (int) $ablog['poststoimport'];
			} else {
				$max = $feed->get_item_quantity();
				if($max == 0) {

					// feed error
					$this->testingmsgs[] = __('<strong>Notice:</strong> I can not find any entries in your feed.','autoblogtext');

				}
			}
		} else {
			$max = 0;

			// feed error
			$this->testingmsgs[] = __('<strong>Error:</strong> ','autoblogtext') . $feed->get_error_message();

		}

		if(!empty($ablog['startfrom']) && $ablog['startfrom'] > time()) {
			// We aren't processing this feed yet
			// feed error
			$this->testingmsgs[] = __('<strong>Notice:</strong> We are not within the date period for processing this feed yet.','autoblogtext');

		}

		if(!empty($ablog['endon']) && $ablog['endon'] < time()) {
			// We aren't processing this feed yet

			// feed error
			$this->testingmsgs[] = __('<strong>Notice:</strong> We are not within the date period for processing this feed anymore.','autoblogtext');

		}

		$processed_count = 0;

		for ($x = 0; $x < $max; $x++) {
			$item = $feed->get_item($x);

			if(!is_object($item)) {
				// Smomething has gone wrong with this post item so we'll ignore it and try the next one instead
				continue;
			}

			// Switch to the correct blog
			if(!empty($ablog['blog']) && function_exists('switch_to_blog')) {
				switch_to_blog( (int) $ablog['blog'] );
				$bid = (int) $ablog['blog'];
			}

			// We are going to store the permalink for imported posts in a meta field so we don't import duplicates
			$results = $this->db->get_row( $this->db->prepare("SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = %s AND meta_value = %s", 'original_source', $item->get_permalink()) );

			$post_title = trim( $item->get_title() );
			$post_content = trim( $item->get_content() );

			if(count($results) > 0) {
				// This post already exists so we shall stop here
				if( $processed_count == 0) {
					// first item already exists for this feed
					$this->testingmsgs[] = __('<strong>Notice:</strong> I have already imported the first post in the feed "','autoblogtext') . $post_title . __('" so I would have stopped processing.','autoblogtext');
				} else {
					// reached an entry we have already imported
					$this->testingmsgs[] = __('<strong>Notice:</strong> Reached an already imported the post in the feed "','autoblogtext') . $post_title . __('" so I would have stopped processing.','autoblogtext');

				}

				break;
			}

			if(!$this->check_feed_item($ablog, $item)) {
				continue;
			}

			// Still here so lets process some stuff
			if($ablog['useexcerpt'] != '1') {
				// Create an excerpt
				$post_content = strip_tags($item->get_content());

				switch($ablog['excerptnumberof']) {
					case 'words':	$find = ' ';
									break;
					case 'sentences':
									$find = '.';
									break;
					case 'paragraphs':
									$find = "\n\n";
									break;
				}

				$splitcontent = explode($find, $post_content);
				$post_content = '';
				for($n = 0; $n < (int) $ablog['excerptnumber']; $n++) {
					if(isset($splitcontent[$n])) $post_content .= $splitcontent[$n] . $find;
				}
			}

			// Set up the author
			if($ablog['author'] == '0') {
				// Try the original author
				$author = $item->get_author(); if($author) $author = $author->get_name();
				if(function_exists('get_user_by') && !empty($author)) {
					$author = get_user_by( 'login', $author);
				} else {
					$author = false;
				}

				if(!$author) {
					$author = $ablog['altauthor'];
				}
			} else {
				// Use a different author
				$author = $ablog['author'];
			}

			// Set up the category
			if((int) $ablog['category'] >= 0) {
				// Grab the first main category
				$cats = array((int) $ablog['category']);
			} else {
				$cats = array();
			}

			$post_category = $cats;

			// Set up the tags
			$tags = array();
			if(!empty($ablog['tag'])) {
				$tags = array_map('trim', explode(',', $ablog['tag']));
			}

			switch($ablog['feedcatsare']) {
				case 'categories':	//$term = get_term_by('name', $cat_name, 'category');
									$thecats = array();
									$thecats = $item->get_categories();
									if(!empty($thecats)) {
										foreach ($thecats as $category)
										{
											$cat_name = trim( $category->get_label() );
											$term_id = $this->category_exists($cat_name);
											if(!empty($term_id)) {
												$post_category[] = $term_id;
											} else {
												// need to check and add cat if required
												if(isset($ablog['originalcategories']) && $ablog['originalcategories'] == '1') {
													// yes so add
													$term_id = wp_create_category($cat_name);
													if(!empty($term_id)) {
														$post_category[] = $term_id;
													}
												}
											}

										}
									}
									break;


				case 'tags':		// carry on as default as well
				default:
									$thecats = array();
									if(isset($ablog['originalcategories']) && $ablog['originalcategories'] == '1') {
										$thecats = $item->get_categories();
										if(!empty($thecats)) {
											foreach ($thecats as $category)
											{
													$tags[] = trim( $category->get_label() );
											}
										}
									}

									break;
			}

			$tax_input = array( "post_tag" => $tags);

			$post_status = $ablog['poststatus'];
			$post_type = $ablog['posttype'];

			if($ablog['postdate'] != 'existing') {
				$post_date = current_time('mysql');
				$post_date_gmt = current_time('mysql', 1);
			} else {
				$thedate = $item->get_date();
				$post_date = date('Y-m-d H:i:s', strtotime($thedate));
				$post_date_gmt = date('Y-m-d H:i:s', strtotime($thedate) + ( get_option( 'gmt_offset' ) * 3600 ));
			}

			// Check the post dates - just in case, we only want posts within our window
			$thedate = $item->get_date();
			$thepostdate = strtotime($thedate);
			if(!empty($ablog['startfrom']) && $ablog['startfrom'] > $thepostdate) {
				// We aren't processing this feed yet
				continue;
			}

			if(!empty($ablog['endon']) && $ablog['endon'] < $thepostdate) {
				// We aren't processing this feed yet
				continue;
			}

			// Move internal variables to correctly labelled ones
			$post_author = $author;
			$blog_ID = $bid;

			$post_data = compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'post_type', 'tax_input');

			$post_data = apply_filters( 'autoblog_pre_post_insert', $post_data, $ablog, $item );

			$this->testingmsgs[] = __('<strong>Found Post:</strong> I have found a post entititled : ','autoblogtext') . $post_title;

			$processed_count++;

		}

		// switch us back to the previous blog
		if(!empty($ablog['blog']) && function_exists('restore_current_blog')) {
			restore_current_blog();
		}

		$this->msgs[] = __('<strong>Processed:</strong> I would have processed ', 'autoblogtext') . $processed_count . __(' of the ', 'autoblogtext') . $max . __(' posts in the feed.', 'autoblogtext');

		return $processed_count;

	}

	function switch_off_verifyssl( $args, $url ) {

		$args['sslverify'] = false;

		return $args;
	}

	function process_feed($feed_id, $ablog) {

		// Load simple pie if required
		if ( !function_exists('fetch_feed') && file_exists(ABSPATH . WPINC . '/feed.php') ) {
			require_once (ABSPATH . WPINC . '/feed.php');
		}
		// Check again to make sure it is loaded
		if ( !function_exists('fetch_feed')) {
			$this->msgs[] = __('<strong>Error:</strong> Can not locate the feed reading file.','autoblogtext');
		}

		if(empty($ablog['url'])) {

			$this->msgs[] = __('<strong>Error:</strong> There is no URL setup for this feed - ','autoblogtext') . $ablog['title'];

			return false;
		}

		if(!empty($ablog['forcessl']) && $ablog['forcessl'] == 'no') {
			// Add a filter to remove the force sll check
			add_filter('http_request_args', array(&$this, 'switch_off_verifyssl'), 10, 2);
		}

		$feed = fetch_feed($ablog['url']);

		remove_filter('http_request_args', array(&$this, 'switch_off_verifyssl'), 10, 2);

		if(!is_wp_error($feed)) {

			if(isset($ablog['poststoimport']) && (int) $ablog['poststoimport'] != 0) {
				$max = (int) $ablog['poststoimport'];
			} else {
				$max = $feed->get_item_quantity();
				if($max == 0) {

					// feed error
					$this->msgs[] = __('<strong>Notice:</strong> I can not find any entries in your feed - ','autoblogtext') . $ablog['url'];

				}
			}
		} else {
			$max = 0;

			// feed error
			$this->msgs[] = __('<strong>Error:</strong> ','autoblogtext') . $feed->get_error_message();

		}

		if(!empty($ablog['startfrom']) && $ablog['startfrom'] > time()) {
			// We aren't processing this feed yet
			// feed error
			$this->msgs[] = __('<strong>Notice:</strong> We are not within the date period for processing this feed yet - ','autoblogtext') . $ablog['url'];

		}

		if(!empty($ablog['endon']) && $ablog['endon'] < time()) {
			// We aren't processing this feed yet

			// feed error
			$this->msgs[] = __('<strong>Notice:</strong> We are not within the date period for processing this feed anymore - ','autoblogtext') . $ablog['url'];

		}

		$processed_count = 0;

		for ($x = 0; $x < $max; $x++) {
			$item = $feed->get_item($x);

			if(!is_object($item)) {
				// Smomething has gone wrong with this post item so we'll ignore it and try the next one instead
				continue;
			}

			// Switch to the correct blog
			if(!empty($ablog['blog']) && function_exists('switch_to_blog')) {
				switch_to_blog( (int) $ablog['blog'] );
				$bid = (int) $ablog['blog'];
			}
			// We are going to store the permalink for imported posts in a meta field so we don't import duplicates
			$results = $this->db->get_row( $this->db->prepare("SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = %s AND meta_value = %s", 'original_source', $item->get_permalink()) );

			if(count($results) > 0) {
				// This post already exists so we shall stop here
				if( $processed_count == 0 ) {
					// first item already exists for this feed
					$this->msgs[] = __('<strong>Notice:</strong> There are no new entries in the feed - ','autoblogtext') . $ablog['url'];
				} else {
					$this->msgs[] = __('<strong>Notice:</strong> Reached an already imported entry so stopped processing the feed - ','autoblogtext') . $ablog['url'];
				}

				break;
			}

			$post_title = trim( $item->get_title() );
			$post_content = trim( $item->get_content() );

			if(!$this->check_feed_item($ablog, $item)) {
				continue;
			}

			// Still here so lets process some stuff
			if($ablog['useexcerpt'] != '1') {
				// Create an excerpt
				$post_content = strip_tags($item->get_content());

				switch($ablog['excerptnumberof']) {
					case 'words':	$find = ' ';
									break;
					case 'sentences':
									$find = '.';
									break;
					case 'paragraphs':
									$find = "\n\n";
									break;
				}

				$splitcontent = explode($find, $post_content);
				$post_content = '';
				for($n = 0; $n < (int) $ablog['excerptnumber']; $n++) {
					if(isset($splitcontent[$n])) $post_content .= $splitcontent[$n] . $find;
				}
			}

			// Set up the author
			if($ablog['author'] == '0') {
				// Try the original author
				$author = $item->get_author(); if($author) $author = $author->get_name();
				if(function_exists('get_user_by') && !empty($author)) {
					$author = get_user_by( 'login',$author);
				} else {
					$author = false;
				}

				if(!$author) {
					$author = $ablog['altauthor'];
				}
			} else {
				// Use a different author
				$author = $ablog['author'];
			}

			// Set up the category
			if((int) $ablog['category'] >= 0) {
				// Grab the first main category
				$cats = array((int) $ablog['category']);
			} else {
				$cats = array();
			}

			$post_category = $cats;

			// Set up the tags
			$tags = array();
			if(!empty($ablog['tag'])) {
				$tags = array_map('trim', explode(',', $ablog['tag']));
			}

			switch($ablog['feedcatsare']) {
				case 'categories':	//$term = get_term_by('name', $cat_name, 'category');
									$thecats = array();
									$thecats = $item->get_categories();
									if(!empty($thecats)) {
										foreach ($thecats as $category)
										{
											$cat_name = trim( $category->get_label() );
											$term_id = $this->category_exists($cat_name);
											if(!empty($term_id)) {
												$post_category[] = $term_id;
											} else {
												// need to check and add cat if required
												if($ablog['originalcategories'] == '1') {
													// yes so add
													$term_id = wp_create_category($cat_name);
													if(!empty($term_id)) {
														$post_category[] = $term_id;
													}
												}
											}

										}
									}
									break;


				case 'tags':		// carry on as default as well
				default:
									$thecats = array();
									if( isset($ablog['originalcategories']) && $ablog['originalcategories'] == '1') {
										$thecats = $item->get_categories();
										if(!empty($thecats)) {
											foreach ($thecats as $category)
											{
													$tags[] = trim( $category->get_label() );
											}
										}
									}

									break;
			}

			$tax_input = array( "post_tag" => $tags);

			$post_status = $ablog['poststatus'];
			$post_type = $ablog['posttype'];

			if($ablog['postdate'] != 'existing') {
				$post_date = current_time('mysql');
				$post_date_gmt = current_time('mysql', 1);
			} else {
				$thedate = $item->get_date();
				$post_date = date('Y-m-d H:i:s', strtotime($thedate));
				$post_date_gmt = date('Y-m-d H:i:s', strtotime($thedate) + ( get_option( 'gmt_offset' ) * 3600 ));
			}

			// Check the post dates - just in case, we only want posts within our window
			$thedate = $item->get_date();
			$thepostdate = strtotime($thedate);
			if(!empty($ablog['startfrom']) && $ablog['startfrom'] > $thepostdate) {
				// We aren't processing this feed yet
				continue;
			}

			if(!empty($ablog['endon']) && $ablog['endon'] < $thepostdate) {
				// We aren't processing this feed yet
				continue;
			}

			// Move internal variables to correctly labelled ones
			$post_author = $author;
			$blog_ID = $bid;

			$post_data = compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'post_type', 'tax_input');

			$post_data = apply_filters( 'autoblog_pre_post_insert', $post_data, $ablog, $item );
			$post_ID = wp_insert_post($post_data);

			if ( !is_wp_error( $post_ID ) ) {
				$processed_count++;

				update_post_meta( $post_ID , 'original_source', trim( $item->get_permalink() ) );
				update_post_meta( $post_ID , 'original_feed', trim( $ablog['url'] ) );
				update_post_meta( $post_ID , 'original_feed_title', trim( $ablog['title'] ) );
				update_post_meta( $post_ID , 'original_imported_time', time() );
				update_post_meta( $post_ID , 'original_feed_id', $feed_id );

				if(!empty($ablog['source'])) {
					// Add the original source to the bottom of the post
					$sourcecontent = "<a href='" . trim( $item->get_permalink() ) . "'";
					if(!empty($ablog['nofollow']) && addslashes($ablog['nofollow']) == '1') {
						$sourcecontent .= " rel='nofollow'";
					}
					$thesource = stripslashes($ablog['source']);
					$thesource = str_replace('%POSTURL%', trim( $item->get_permalink() ), $thesource);
					$thesource = str_replace('%FEEDURL%', trim( $ablog['url'] ), $thesource);
					$sourcecontent .= ">" . $thesource . "</a>";

					$sourcecontent = apply_filters( 'autoblog_source_link', $sourcecontent, $ablog );

					update_post_meta( $post_ID , 'original_source_link_html', $sourcecontent );
				}


				// Handle fake tags importing
				if( defined('AUTOBLOG_HANDLE_FAKE_TAGS') && AUTOBLOG_HANDLE_FAKE_TAGS == true ) {
					if(!empty($tags)) {
						foreach ($tags as $tag)
						{
							// Add tags one at a time - more processor and db intensive but, hey, what can you do?
							wp_set_post_tags( $post_ID, $tag, true );
						}
					}
				}

				do_action( 'autoblog_post_post_insert', $post_ID, $ablog, $item );

			} else {

				// error writing post
				$this->msgs[] = __('<strong>Error:</strong> ','autoblogtext') . $post_ID->get_error_message();

			}
		}

		// Update the next feed read date
		$update = array();
		$update['lastupdated'] = current_time('timestamp');
		$update['nextcheck'] = current_time('timestamp') + (intval($ablog['processfeed']) * 60);

		$this->db->update($this->autoblog, $update, array("feed_id" => $feed_id));
		// switch us back to the previous blog
		if(!empty($ablog['blog']) && function_exists('restore_current_blog')) {
			restore_current_blog();
		}

		$this->msgs[] = __('<strong>Processed:</strong> I have processed ', 'autoblogtext') . $processed_count . __(' of the ', 'autoblogtext') . $max . __(' posts in the feed - ', 'autoblogtext') . $ablog['url'];

		return $processed_count;

	}

	function always_process_autoblog() {

		global $wpdb;

		// grab the feeds
		$autoblogs = $this->get_autoblogentries(current_time('timestamp'));

		if(!empty($autoblogs)) {

			foreach( (array) $autoblogs as $key => $ablog) {

				$details = unserialize($ablog->feed_meta);
				$process = false;

				if(isset($details['processfeed']) && $details['processfeed'] > 0) {
					$process = true;
				} else {
					$process = false;
				}

				if($process) {
					do_action('autoblog_pre_process_feed', $ablog->feed_id, $details);
					$this->process_feed($ablog->feed_id, $details);
					do_action('autoblog_post_process_feed', $ablog->feed_id, $details);
				}

			}
		}

		if(!empty($this->msgs)) {
			$this->record_msg();
		}

	}

	/*
	* This process_autoblog function should not be used now as the system is switched over to using a cron job
	*/
	function process_autoblog() {

		global $wpdb;

		// Our starting time
		$timestart = current_time('timestamp');

		//Or processing limit
		$timelimit = AUTOBLOG_PROCESSING_TIMELIMIT; // max seconds for processing

		$lastprocessing = get_autoblog_option('autoblog_processing', strtotime('-1 week', current_time('timestamp')));

		if($lastprocessing < strtotime('-' . AUTOBLOG_PROCESSING_CHECKLIMIT . ' minutes', current_time('timestamp'))) {
			update_autoblog_option('autoblog_processing', current_time('timestamp'));

			// grab the feeds
			$autoblogs = $this->get_autoblogentries(current_time('timestamp'));

			foreach( (array) $autoblogs as $key => $ablog) {

				if(current_time('timestamp') > $timestart + $timelimit) {
					// time out
					$this->msgs[] = __('<strong>Notice:</strong> Processing stopped due to ' . $timelimit . ' second timeout.','autoblogtext');

					break;
				}

				$details = unserialize($ablog->feed_meta);
				$process = false;

				if(isset($details['processfeed']) && $details['processfeed'] > 0) {
					$process = true;
				} else {
					$process = false;
				}

				if($process) {
					do_action('autoblog_pre_process_feed', $ablog->feed_id, $details);
					$this->process_feed($ablog->feed_id, $details);
					do_action('autoblog_post_process_feed', $ablog->feed_id, $details);
				}

			}
		} else {

		}

		if(!empty($this->msgs)) {
			$this->record_msg();
		}

	}

	// This function appends the source to the bottom of the content if it exists.
	function append_original_source( $content ) {

		global $post;

		$source = get_post_meta( $post->ID, 'original_source_link_html', true );
		if(!empty($source)) {
			$content .= "<p>" . $source . "</p>";
		}


		return $content;

	}

}


?>