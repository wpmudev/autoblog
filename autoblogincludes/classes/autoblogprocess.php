<?php
class autoblogcron {

	var $db;

	var $tables = array('autoblog');
	var $autoblog;

	function __construct() {

		global $wpdb;

		// check if a modulous of 5 mintutes and if so add the init hook.
		$min = date("i");

		add_action('init', array(&$this,'process_autoblog'));

		$this->db =& $wpdb;

		foreach($this->tables as $table) {
			$this->$table = $this->db->base_prefix . $table;
		}

		add_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'feed_cache') );

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

	function process_feeds($ids) {

		// grab the feeds
		$autoblogs = $this->get_autoblogentriesforids($ids);

		$lastprocessing = get_site_option('autoblog_processing', strtotime('-1 week'));
		if($lastprocessing == 'yes' || $lastprocessing == 'no' || $lastprocessing == 'np') {
			$lastprocessing = strtotime('-1 hour');
			update_site_option('autoblog_processing', $lastprocessing);
		}

		if(!empty($autoblogs) && $lastprocessing <= strtotime('-30 minutes')) {
			update_site_option('autoblog_processing', time());

			foreach( (array) $autoblogs as $key => $ablog) {

				$details = unserialize($ablog->feed_meta);

				if(!empty($details['url'])) {

					$this->process_feed($ablog->feed_id, $details);

				}

			}
		}

		return true;

	}

	function process_feed($feed_id, $ablog) {

		// Load simple pie if required
		if ( !function_exists('fetch_feed') ) require_once (ABSPATH . WPINC . '/feed.php');

		$feed = fetch_feed($ablog['url']);

		if(!is_wp_error($feed)) {
			$max = $feed->get_item_quantity();
		} else {
			$max = 0;
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
				break;
			}

			$post_title = $item->get_title();
			$post_content = $item->get_content();

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
							$posttags[] = $category->get_label();
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
							$tags[] = $category->get_label();
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

			if(!empty($ablog['source'])) {
				// Add the original source to the bottom of the post
				$post_content .= "<p><a href='" . $item->get_permalink() . "'";
				if(!empty($ablog['nofollow']) && addslashes($ablog['nofollow']) == '1') {
					$post_content .= " rel='nofollow'";
				}
				$post_content .= ">" . stripslashes($ablog['source']) . "</a></p>";
			}

			// Move internal variables to correctly labelled ones
			$post_author = $author;
			$blog_ID = $bid;

			$post_data = compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'tax_input');

			$post_ID = wp_insert_post($post_data);

			if ( !is_wp_error( $post_ID ) ) {
				update_post_meta( $post_ID , 'original_source', $item->get_permalink() );
			}
		}

		// Update the next feed read date
		$update = array();
		$update['lastupdated'] = time();
		$update['nextcheck'] = time() + (intval($ablog['processfeed']) * 60);

		$this->db->update($this->autoblog, $update, array("feed_id" => $feed_id));


		return true;

	}

	function process_autoblog() {

		global $wpdb;

		$debug = get_site_option('autoblog_debug', 'no');

		// grab the feeds
		$autoblogs = $this->get_autoblogentries(time());

		// Our starting time
		$timestart = time();

		//Or processing limit
		$timelimit = 3; // max seconds for processing

		$lastprocessing = get_site_option('autoblog_processing', strtotime('-1 week'));
		if($lastprocessing == 'yes' || $lastprocessing == 'no' || $lastprocessing == 'np') {
			$lastprocessing = strtotime('-1 hour');
			update_site_option('autoblog_processing', $lastprocessing);
		}

		if(!empty($autoblogs) && $lastprocessing <= strtotime('-30 minutes')) {
			update_site_option('autoblog_processing', time());

			foreach( (array) $autoblogs as $key => $ablog) {

				if(time() > $timestart + $timelimit) {
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

				}

			}
		}

	}

}

function process_autoblog() {
	global $abc;

	$abc->process_autoblog();
}

function process_feed($id, $details) {

	global $abc;

	return $abc->process_feed($id, $details);

}

function process_feeds($ids) {

	global $abc;

	return $abc->process_feeds($ids);

}
?>