<?php
class autoblogcron {

	var $db;

	var $tables = array('autoblog');
	var $autoblog;

	var $debug = false;

	var $errors = array();

	function __construct() {

		global $wpdb;

		// check if a modulous of 5 mintutes and if so add the init hook.
		$min = date("i");

		add_action('init', array(&$this,'process_autoblog'));

		$this->db =& $wpdb;

		foreach($this->tables as $table) {
			$this->$table = autoblog_db_prefix($this->db, $table);
		}

		add_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'feed_cache') );

		// override with option.
		$this->debug = get_autoblog_option('autoblog_debug', false);

	}

	function autoblogcron() {
		$this->__construct();
	}

	function feed_cache($ignore) {
		return 5*60;
	}

	function get_autoblogentries($timestamp) {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id = %d AND nextcheck < %d AND nextcheck > 0  ORDER BY nextcheck ASC", $this->db->siteid, $timestamp );

		$results = $this->db->get_results($sql);

		return $results;

	}

	function get_autoblogentriesforids($ids) {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id = %d AND feed_id IN (0, " . implode(',', $ids) . ") ORDER BY nextcheck ASC", $this->db->siteid, $timestamp );

		$results = $this->db->get_results($sql);

		return $results;

	}

	function get_autoblogentry($id) {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id = %d AND feed_id = %d ORDER BY feed_id ASC", $this->db->siteid, $id );

		$results = $this->db->get_row($sql);

		return $results;

	}

	function record_error() {

		$thetime = time();

		$errors = array(	"timestamp" => $thetime,
							"log" => $this->errors
						);

		update_autoblog_option('autoblog_log_' . $thetime, $errors);

	}

	function process_feeds($ids) {

		// grab the feeds
		$autoblogs = $this->get_autoblogentriesforids($ids);

		$lastprocessing = get_autoblog_option('autoblog_processing', strtotime('-1 week'));
		if($lastprocessing == 'yes' || $lastprocessing == 'no' || $lastprocessing == 'np') {
			$lastprocessing = strtotime('-1 hour');
			update_autoblog_option('autoblog_processing', $lastprocessing);
		}

		if(!empty($autoblogs) && $lastprocessing <= strtotime('-30 minutes')) {
			update_autoblog_option('autoblog_processing', time());

			foreach( (array) $autoblogs as $key => $ablog) {

				$details = unserialize($ablog->feed_meta);

				if(!empty($details['url'])) {

					$this->process_feed($ablog->feed_id, $details);

				}

			}
		}

		if(!empty($this->errors)) {
			$this->record_error();
		}

		return true;

	}

	function process_the_feed($feed_id, $ablog) {

		$results = $this->process_feed($feed_id, $ablog);

		if(!empty($this->errors)) {
			$this->record_error();
		}

		return $results;
	}

	function process_feed($feed_id, $ablog) {

		// Load simple pie if required
		if ( !function_exists('fetch_feed') ) require_once (ABSPATH . WPINC . '/feed.php');

		if(empty($ablog['url'])) {
			if($this->debug) {
				// feed error
				$this->errors[] = __('Error: No URL found for a feed','autoblog');
			}
			return false;
		}

		$feed = fetch_feed($ablog['url']);

		if(!is_wp_error($feed)) {
			$max = $feed->get_item_quantity();
			if($max == 0) {
				if($this->debug) {
					// feed error
					$this->errors[] = __('Notice: No entries retrieved for feed - ','autoblog') . $ablog['url'];
				}
			}
		} else {
			$max = 0;
			if($this->debug) {
				// feed error
				$this->errors[] = __('Error: ','autoblog') . $feed->get_error_message();
			}
		}

		if(!empty($ablog['startfrom']) && $ablog['startfrom'] > time()) {
			// We aren't processing this feed yet
			if($this->debug) {
				// feed error
				$this->errors[] = __('Date margin: Not processing feed yet - ','autoblog') . $ablog['url'];
			}
		}

		if(!empty($ablog['endon']) && $ablog['endon'] < time()) {
			// We aren't processing this feed yet
			if($this->debug) {
				// feed error
				$this->errors[] = __('Date margin: Stopped processing feed - ','autoblog') . $ablog['url'];
			}
		}

		for ($x = 0; $x < $max; $x++) {
			$item = $feed->get_item($x);

			// Switch to the correct blog
			if(!empty($ablog['blog'])) {
				switch_to_blog( (int) $ablog['blog'] );
				$bid = (int) $ablog['blog'];
			}
			// We are going to store the permalink for imported posts in a meta field so we don't import duplicates
			$results = $this->db->get_row( $this->db->prepare("SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = %s AND meta_value = %s", 'original_source', $item->get_permalink()) );

			if(count($results) > 0) {
				// This post already exists so we shall stop here
				if($this->debug) {
					// first item already exists for this feed
					$this->errors[] = __('Notice: No new entries in feed - ','autoblog') . $ablog['url'];
				}
				break;
			}

			$post_title = trim( $item->get_title() );
			$post_content = trim( $item->get_content() );

			// Set up the defaults
			$matchall = true; $matchany = true; $matchphrase = true; $matchnone = true; $matchtags = true;

			if(!empty($ablog['allwords'])) {
				$words = explode(',', $ablog['allwords']);
				$matchall = true;
				foreach((array) $words as $key => $word) {
					$words[$key] = "/" . trim($word) . "/i";
					if(!preg_match_all($words[$key], $post_title . " " . $post_content, $matches)) {
						$matchall = false;
						break;
					}
				}
			}

			if(!empty($ablog['anywords'])) {
				$words = explode(',', $ablog['anywords']);
				$matchany = false;
				foreach((array) $words as $key => $word) {
					$words[$key] = "/" . trim($word) . "/i";
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
				$words = explode(',', $ablog['nonewords']);
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
				$words = explode(',', $ablog['anytags']);
				$matchtags = true;

				$thecats = $item->get_categories();
				if(!empty($thecats)) {
					$posttags = array();
					foreach ($thecats as $category)
					{
							$posttags[] = trim( $category->get_label() );
					}

					foreach((array) $words as $key => $word) {
						if(!in_array(trim($word), $posttags)) {
							$matchtags = false;
							break;
						}
					}
				} else {
					$matchtags = false;
				}
			}

			if($matchall && $matchany && $matchphrase && $matchnone && $matchtags) {
			} else {
				// Go to the next one
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
				$tags = explode(',', $ablog['tag']);
				foreach($tags as $key => $tag) {
					$tags[$key] = trim($tag);
				}
			}

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

			$tax_input = array( "post_tag" => $tags);

			$post_status = $ablog['poststatus'];

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

			if(!empty($ablog['source'])) {
				// Add the original source to the bottom of the post
				$post_content .= "<p><a href='" . trim( $item->get_permalink() ) . "'";
				if(!empty($ablog['nofollow']) && addslashes($ablog['nofollow']) == '1') {
					$post_content .= " rel='nofollow'";
				}
				$thesource = stripslashes($ablog['source']);
				$thesource = str_replace('%POSTURL%', trim( $item->get_permalink() ), $thesource);
				$thesource = str_replace('%FEEDURL%', trim( $ablog['url'] ), $thesource);
				$post_content .= ">" . $thesource . "</a></p>";
			}

			// Move internal variables to correctly labelled ones
			$post_author = $author;
			$blog_ID = $bid;

			$post_data = compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'tax_input');

			$post_ID = wp_insert_post($post_data);

			if ( !is_wp_error( $post_ID ) ) {
				update_post_meta( $post_ID , 'original_source', trim( $item->get_permalink() ) );
				update_post_meta( $post_ID , 'original_feed', trim( $ablog['url'] ) );
			} else {
				if($this->debug) {
					// error writing post
					$this->errors[] = __('Error: ','autoblog') . $post_ID->get_error_message();
				}
			}
		}

		// Update the next feed read date
		$update = array();
		$update['lastupdated'] = time();
		$update['nextcheck'] = time() + (intval($ablog['processfeed']) * 60);

		$this->db->update($this->autoblog, $update, array("feed_id" => $feed_id));
		// switch us back to the previous blog
		if(!empty($ablog['blog'])) {
			restore_current_blog();
		}

		return true;

	}

	function process_autoblog() {

		global $wpdb;

		// grab the feeds
		$autoblogs = $this->get_autoblogentries(time());

		// Our starting time
		$timestart = time();

		//Or processing limit
		$timelimit = 3; // max seconds for processing

		$lastprocessing = get_autoblog_option('autoblog_processing', strtotime('-1 week'));
		if($lastprocessing == 'yes' || $lastprocessing == 'no' || $lastprocessing == 'np') {
			$lastprocessing = strtotime('-1 hour');
			update_autoblog_option('autoblog_processing', $lastprocessing);
		}

		if(!empty($autoblogs) && $lastprocessing <= strtotime('-30 minutes')) {
			update_autoblog_option('autoblog_processing', time());

			foreach( (array) $autoblogs as $key => $ablog) {

				if(time() > $timestart + $timelimit) {
					if($this->debug) {
						// time out
						$this->errors[] = __('Notice: Processing stopped due to ' . $timelimit . ' second timeout.','autoblog');
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
					$this->process_feed($ablog->feed_id, $details);
				} else {
					if($this->debug) {
						// no uri or not processing
						if(empty($details['url'])) {
							$this->errors[] = __('Error: No URL found for a feed.','autoblog');
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

}

function process_autoblog() {
	global $abc;

	$abc->process_autoblog();
}

function process_feed($id, $details) {

	global $abc;

	return $abc->process_the_feed($id, $details);

}

function process_feeds($ids) {

	global $abc;

	return $abc->process_feeds($ids);

}
?>