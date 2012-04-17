<?php
class autoblogcron {

	var $db;

	var $tables = array('autoblog');
	var $autoblog;

	var $debug = false;

	var $errors = array();

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

		add_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'feed_cache') );

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

	function feed_cache($ignore) {
		return 5*60;
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
		if ( !wp_next_scheduled( 'autoblog_process_feeds' ) ) {
				wp_schedule_event(time(), $this->checkperiod, 'autoblog_process_feeds');
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

	function record_error() {

		$thetime = current_time('timestamp');

		$errors = array(	"timestamp" => $thetime,
							"log" => $this->errors
						);

		update_autoblog_option('autoblog_log_' . $thetime, $errors);

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

		if(!empty($this->errors)) {
			$this->record_error();
		}

		return true;

	}

	function process_the_feed($feed_id, $ablog) {

		do_action('autoblog_pre_process_feed', $feed_id, $ablog);
		$results = $this->process_feed($feed_id, $ablog);
		do_action('autoblog_post_process_feed', $feed_id, $ablog);

		if(!empty($this->errors)) {
			$this->record_error();
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

	function process_feed($feed_id, $ablog) {

		// Load simple pie if required
		if ( !function_exists('fetch_feed') ) require_once (ABSPATH . WPINC . '/feed.php');

		if(empty($ablog['url'])) {
			if($this->debug) {
				// feed error
				$this->errors[] = __('Error: No URL found for a feed','autoblogtext');
			}
			return false;
		}

		$feed = fetch_feed($ablog['url']);

		if(!is_wp_error($feed)) {

			if(isset($ablog['poststoimport']) && (int) $ablog['poststoimport'] != 0) {
				$max = (int) $ablog['poststoimport'];
			} else {
				$max = $feed->get_item_quantity();
				if($max == 0) {
					if($this->debug) {
						// feed error
						$this->errors[] = __('Notice: No entries retrieved for feed - ','autoblogtext') . $ablog['url'];
					}
				}
			}
		} else {
			$max = 0;
			if($this->debug) {
				// feed error
				$this->errors[] = __('Error: ','autoblogtext') . $feed->get_error_message();
			}
		}

		if(!empty($ablog['startfrom']) && $ablog['startfrom'] > time()) {
			// We aren't processing this feed yet
			if($this->debug) {
				// feed error
				$this->errors[] = __('Date margin: Not processing feed yet - ','autoblogtext') . $ablog['url'];
			}
		}

		if(!empty($ablog['endon']) && $ablog['endon'] < time()) {
			// We aren't processing this feed yet
			if($this->debug) {
				// feed error
				$this->errors[] = __('Date margin: Stopped processing feed - ','autoblogtext') . $ablog['url'];
			}
		}

		for ($x = 0; $x < $max; $x++) {
			$item = $feed->get_item($x);

			// Switch to the correct blog
			if(!empty($ablog['blog']) && function_exists('switch_to_blog')) {
				switch_to_blog( (int) $ablog['blog'] );
				$bid = (int) $ablog['blog'];
			}
			// We are going to store the permalink for imported posts in a meta field so we don't import duplicates
			$results = $this->db->get_row( $this->db->prepare("SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = %s AND meta_value = %s", 'original_source', $item->get_permalink()) );

			if(count($results) > 0) {
				// This post already exists so we shall stop here
				if($this->debug) {
					// first item already exists for this feed
					$this->errors[] = __('Notice: No new entries in feed - ','autoblogtext') . $ablog['url'];
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
				if(function_exists('get_userdatabylogin') && !empty($author)) {
					$author = get_userdatabylogin($author);
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
									if($ablog['originalcategories'] == '1') {
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
			do_action( 'autoblog_post_post_insert', $post_ID, $ablog, $item );

			if ( !is_wp_error( $post_ID ) ) {
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

			} else {
				if($this->debug) {
					// error writing post
					$this->errors[] = __('Error: ','autoblog') . $post_ID->get_error_message();
				}
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

		return true;

	}

	function always_process_autoblog() {

		global $wpdb;

		// grab the feeds
		$autoblogs = $this->get_autoblogentries(current_time('timestamp'));

		// Our starting time
		$timestart = current_time('timestamp');

		if(!empty($autoblogs)) {

			foreach( (array) $autoblogs as $key => $ablog) {

				if(time() > $timestart + $timelimit) {
					if($this->debug) {
						// time out
						$this->errors[] = __('Notice: Processing stopped due to ' . $timelimit . ' second timeout.','autoblogtext');
					}
					break;
				}

				$details = unserialize($ablog->feed_meta);
				$process = false;

				if(isset($details['processfeed']) && $details['processfeed'] > 0) {
					$process = true;
				} else {
					$process = false;
				}

				if($process && !empty($details['url'])) {
					do_action('autoblog_pre_process_feed', $ablog->feed_id, $details);
					$this->process_feed($ablog->feed_id, $details);
					do_action('autoblog_post_process_feed', $ablog->feed_id, $details);
				} else {
					if($this->debug) {
						// no uri or not processing
						if(empty($details['url'])) {
							$this->errors[] = __('Error: No URL found for a feed.','autoblogtext');
						}
					}
				}

			}
		} else {
			if($this->debug) {
				// empty list or not processing
			}
		}

		if(!empty($this->errors)) {
			$this->record_error();
		}

	}

	/*
	* This process_autoblog function should not be used now as the system is switched over to using a cron job
	*/
	function process_autoblog() {

		global $wpdb;

		// grab the feeds
		$autoblogs = $this->get_autoblogentries(current_time('timestamp'));

		// Our starting time
		$timestart = current_time('timestamp');

		//Or processing limit
		$timelimit = AUTOBLOG_PROCESSING_TIMELIMIT; // max seconds for processing

		$lastprocessing = get_autoblog_option('autoblog_processing', strtotime('-1 week', current_time('timestamp')));
		if($lastprocessing == 'yes' || $lastprocessing == 'no' || $lastprocessing == 'np') {
			$lastprocessing = strtotime('-1 hour', current_time('timestamp'));
			update_autoblog_option('autoblog_processing', $lastprocessing);
		}

		if(!empty($autoblogs) && $lastprocessing <= strtotime('-' . AUTOBLOG_PROCESSING_CHECKLIMIT . ' minutes', current_time('timestamp'))) {
			update_autoblog_option('autoblog_processing', current_time('timestamp'));

			foreach( (array) $autoblogs as $key => $ablog) {

				if(time() > $timestart + $timelimit) {
					if($this->debug) {
						// time out
						$this->errors[] = __('Notice: Processing stopped due to ' . $timelimit . ' second timeout.','autoblogtext');
					}
					break;
				}

				$details = unserialize($ablog->feed_meta);
				$process = false;

				if(isset($details['processfeed']) && $details['processfeed'] > 0) {
					$process = true;
				} else {
					$process = false;
				}

				if($process && !empty($details['url'])) {
					do_action('autoblog_pre_process_feed', $ablog->feed_id, $details);
					$this->process_feed($ablog->feed_id, $details);
					do_action('autoblog_post_process_feed', $ablog->feed_id, $details);
				} else {
					if($this->debug) {
						// no uri or not processing
						if(empty($details['url'])) {
							$this->errors[] = __('Error: No URL found for a feed.','autoblogtext');
						}
					}
				}

			}
		} else {
			if($this->debug) {
				// empty list or not processing
			}
		}

		if(!empty($this->errors)) {
			$this->record_error();
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

function ab_process_feed($id, $details) {

	global $abc;

	return $abc->process_the_feed($id, $details);

}

function ab_process_feeds($ids) {

	global $abc;

	return $abc->process_feeds($ids);

}

function ab_process_autoblog() {
	global $abc;

	$abc->process_autoblog();
}

function ab_always_process_autoblog() {
	global $abc;

	$abc->always_process_autoblog();
}
add_action( 'autoblog_process_feeds', 'ab_always_process_autoblog' );


?>