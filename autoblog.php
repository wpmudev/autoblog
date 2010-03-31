<?php
/*
Plugin Name: Auto Blog 2.0.1
Version: 2.0.1
Plugin URI: http://premium.wpmudev.org
Description: An automatic blog feed reading plugin.
Author: Barry Getty (Incsub)
Author URI: http://caffeinatedb.com
WDP ID: 96
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Modify the next line to change the user agent reported to web sites.
//define('SIMPLEPIE_USERAGENT', 'ABlogPremium/0.1 (Feed Parser; http://premium.wpmudev.org; Allow like Gecko) Build/1');

// Using SimplePie
//define('SIMPLEPIE_USERAGENT', 'PUT AGENT HERE');

class autoblogpremium {

	var $build = 4;

	var $db;

	var $mylocation = "";
	var $plugindir = "";
	var $base_uri = '';

	var $mypages = array('autoblog_admin');

	var $tables = array('autoblog');
	var $autoblog;


	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		$this->detect_location(1);

		// Installation functions
		register_activation_hook(__FILE__, array(&$this, 'install'));

		add_action('init', array(&$this, 'initialise_plugin'));
		add_action('init', array(&$this, 'handle_ajax'));

		add_action('admin_menu', array(&$this,'add_adminmenu'));

		foreach($this->tables as $table) {
			$this->$table = $this->db->base_prefix . $table;
		}

		// check for installation
		if(get_site_option('autoblog_installed', 0) < $this->build) {
			// create the database table
			$this->install();
		}

	}

	function autoblogpremium() {
		$this->__construct();
	}

	function detect_location($level = 1) {
			$directories = explode(DIRECTORY_SEPARATOR,dirname(__FILE__));

			$mydir = array();
			for($depth = $level; $depth >= 1; $depth--) {
				$mydir[] = $directories[count($directories)-$depth];
			}

			$mydir = implode('/', $mydir);
			$this->mylocation = $mydir . DIRECTORY_SEPARATOR . basename(__FILE__);

			if(defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . $this->mylocation)) {
				$this->plugindir = WP_PLUGIN_URL;
				$this->base_uri = $this->plugindir . '/' . $directories[count($directories)-$level] . '/';
			} else {
				$this->plugindir = WPMU_PLUGIN_URL;
				$this->base_uri = $this->plugindir . '/'; // . $directories[count($directories)-$level] . '/';
			}

		}

	function install() {

			if($this->db->get_var( "SHOW TABLES LIKE '" . $this->autoblog . "' ") != $this->autoblog) {
				$sql = "CREATE TABLE `" . $this->autoblog . "` (
				  	  `feed_id` bigint(20) NOT NULL auto_increment,
					  `site_id` bigint(20) default NULL,
					  `blog_id` bigint(20) default NULL,
					  `feed_meta` text,
					  `active` int(11) default NULL,
					  `nextcheck` bigint(20) default NULL,
					  `lastupdated` bigint(20) default NULL,
					  PRIMARY KEY  (`feed_id`),
					  KEY `site_id` (`site_id`),
					  KEY `blog_id` (`blog_id`),
					  KEY `nextcheck` (`nextcheck`)
					)";

				$this->db->query($sql);
			}

			update_site_option('autoblog_installed', $this->build);

	}

	function initialise_plugin() {

		if(get_site_option('autoblog_installed', 1) < $this->build) {
			$this->install();
		}

		if(in_array(addslashes($_GET['page']), $this->mypages)) {
			wp_enqueue_style('autoblogadmincss', $this->base_uri . 'autoblogincludes/styles/autoblog.css', array(), $this->build);
			//wp_enqueue_script('jquery-form');
			wp_enqueue_script('qtip', $this->base_uri . 'autoblogincludes/js/jquery.qtip-1.0.0-rc3.min.js', array('jquery'), $this->build);
			wp_enqueue_script('autoblogadminjs', $this->base_uri . 'autoblogincludes/js/autoblogadmin.js', array('jquery'), $this->build);
		}

	}

	function handle_ajax() {

		if(isset($_REQUEST['namespace']) && addslashes($_REQUEST['namespace']) == '_autoblogadmin' && isset($_REQUEST['call']) && addslashes($_REQUEST['call']) == '_ajax' && is_user_logged_in()) {

				switch(addslashes($_REQUEST['action'])) {
					case "_getblogcategorylist":
						$bid = addslashes($_GET['id']);
						if($bid != "") {
							switch_to_blog($bid);
							$cat = get_categories('get=all');
							$cu = array();
							foreach($cat as $key => $ct) {
								$cu[] = array('term_id' => $ct->term_id, 'name' => $ct->name);
							}
							$result = array('errorcode' => '200', 'data' => $cu);
						} else {
							$result = array('errorcode' => '500', 'message' => 'No blog.');
						}
						$this->return_json($result);
						break;
					case "_getblogauthorlist":
						$bid = addslashes($_GET['id']);
						if($bid != "") {
							$blogusers = get_users_of_blog( $bid );
							$bu = array();
							foreach($blogusers as $key => $buser) {
								$bu[] = array('user_id' => $buser->user_id, 'user_login' => $buser->user_login);
							}
							$result = array('errorcode' => '200', 'data' => $bu);
						} else {
							$result = array('errorcode' => '500', 'message' => 'No blog.');
						}
						$this->return_json($result);
						break;
					case "_getblogtaglist":
						$bid = addslashes($_GET['id']);
						if($bid != "") {
							switch_to_blog($bid);
							$cat = get_terms( 'post_tag', '' );
							$cu = array();
							foreach($cat as $key => $ct) {
								$cu[] = array('term_id' => $ct->term_id, 'name' => $ct->name);
							}
							$result = array('errorcode' => '200', 'data' => $cu);
						} else {
							$result = array('errorcode' => '500', 'message' => 'No blog.');
						}
						$this->return_json($result);
						break;
				}
				exit(); // Or bad things happen

		}

	}

	function return_json($results) {

		if(isset($_GET['callback'])) {
			echo addslashes($_GET['callback']) . " (";
		}

		if(function_exists('json_encode')) {
			echo json_encode($results);
		} else {
			// PHP4 version
			require_once(ABSPATH."wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
			$json_obj = new Moxiecode_JSON();
			echo $json_obj->encode($results);
		}

		if(isset($_GET['callback'])) {
			echo ")";
		}

	}

	function add_adminmenu() {
		add_submenu_page('wpmu-admin.php', __('Auto Blog','autoblog'), __('Auto Blog','autoblog'), 10, "autoblog_admin", array(&$this,'handle_admin_page'));
	}

	function show_table($key, $details) {

		global $blog_id;

		$table = maybe_unserialize($details->feed_meta);

		echo '<div class="postbox" id="ab-' . $details->feed_id . '">';

		echo '<h3 class="hndle"><span>' . __('Feed : ','autoblogtext') . stripslashes($table['title']) . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		// Title
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Your Title','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[title]' value='" . htmlentities(stripslashes($table['title']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='Enter a memorable title.'></a>";
		echo "</td>";
		echo "</tr>";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='" . htmlentities(stripslashes($table['url']),ENT_QUOTES, 'UTF-8') . "' class='long url field' />" . "<a href='#' class='info' title='Enter the feed URL'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2'>&nbsp;</td></tr>";

		// Blogs
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add posts to','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[blog]' class='field blog'>";
		$blogs = $this->get_blogs_of_site();
		if($blogs) {
			foreach( $blogs as $bkey => $blog) {
				echo "<option value='$bkey'";
				if($table['blog'] == $blog->id) {
					echo " selected='selected'";
				} else {
					echo "";
				}
				echo ">" . $blog->domain . $blog->path . "</option>";
			}
		}
		echo "</select>" . "<a href='#' class='info' title='Select a blog to add the post to.'></a>";

		echo "</td>";
		echo "</tr>";

		// Status
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Default status for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[poststatus]' class='field'>";
		echo "<option value='publish'"; echo $table['poststatus'] == 'publish' ? " selected='selected'" : "";  echo ">" . __('Published') . "</option>";
		echo "<option value='pending'"; echo  $table['poststatus'] == 'pending' ? " selected='selected'" : "";  echo ">" . __('Pending Review') . "</option>";
		echo "<option value='draft'"; echo  $table['poststatus'] == 'draft' ? " selected='selected'" : "";  echo ">" . __('Draft') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Select the status the imported posts will have in the blog.'></a>";

		echo "</td>";
		echo "</tr>";

		// Post dates
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set the date for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[postdate]' class='field'>";
		echo "<option value='current'"; echo $table['postdate'] == 'current' ? " selected='selected'" : "";  echo ">" . __('Imported date') . "</option>";
		echo "<option value='existing'"; echo  $table['postdate'] == 'existing' ? " selected='selected'" : "";  echo ">" . __('Original posts date') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Select the date imported posts will have.'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Author details','autoblogtext') . "</span></td></tr>";

		$blogusers = get_users_of_blog( $table['blog'] );

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set author for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[author]' class='field author'>";
		echo "<option value='0'"; echo  ($table['author'] == '0') ? " selected='selected'" : ""; echo ">" . __('Use feed author','autoblogtext') . "</option>";

		if($blogusers) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->user_id . "'"; echo ($table['author'] == $bloguser->user_id) ? " selected='selected'" : ""; echo ">";
				echo $bloguser->user_login;
				echo "</option>";
			}
		}
		echo "</select>" . "<a href='#' class='info' title='Select the author you want to use for the posts, or attempt to use the original feed author.'></a>";

		//print_r($blogusers);

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('If author in feed does not exist locally use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[altauthor]' class='field altauthor'>";
		reset($blogusers);
		if($blogusers) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->user_id . "'"; echo ($table['altauthor'] == $bloguser->user_id) ? " selected='selected'" : ""; echo ">";
				echo $bloguser->user_login;
				echo "</option>";
			}
		}
		echo "</select>" . "<a href='#' class='info' title='If the feed author does not exist in your blog then use this author.'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Categories and Tags','autoblogtext') . "</span></td></tr>";

		//
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Assign posts to this category','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		switch_to_blog($table['blog']);
		wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'abtble[category]', 'orderby' => 'name', 'selected' => $table['category'], 'hierarchical' => true, 'show_option_none' => __('None'), 'class' => 'field cat'));
		restore_current_blog();

		echo "<a href='#' class='info' title='Assign this category to the imported posts.'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='" . htmlentities(stripslashes($table['tag']),ENT_QUOTES, 'UTF-8') . "' class='long tag field' />" . "<a href='#' class='info' title='Enter a comma separated list of tags to add.'></a>";
		echo "<br/><input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
		if($table['originalcategories'] == '1') echo " checked='checked'";
		echo "/>&nbsp;<span>" . __('Use original feeds tags as well (adding if necessary).','autoblogtext') . "</span>" . "<a href='#' class='info' title='Imported and use the tags originally associated with the post.'></a>";


		//print_r($tags);
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post Filtering','autoblogtext') . "</span></td></tr>";
		echo "<tr><td colspan='2'><p>" . __('Include posts that contain (separate words with commas)','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('All of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[allwords]' value='" . htmlentities(stripslashes($table['allwords']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have ALL of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='" . htmlentities(stripslashes($table['anywords']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have ANY of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='" . htmlentities(stripslashes($table['phrase']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have this exact phrase in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='" . htmlentities(stripslashes($table['nonewords']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must NOT have any of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='" . htmlentities(stripslashes($table['anytags']),ENT_QUOTES, 'UTF-8') . "' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must be marked with any of these categories or tags.'></a>";
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";




		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>";
		//echo "<tr><td colspan='2'><p>" . __('','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'"; echo ($table['useexcerpt'] == '1') ? " selected='selected'" : ""; echo ">" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'"; echo ($table['useexcerpt'] == '2') ? " selected='selected'" : ""; echo ">" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Use the full post (if available) or create an excerpt.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('For excerpts use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[excerptnumber]' value='" . htmlentities(stripslashes($table['excerptnumber']),ENT_QUOTES, 'UTF-8') . "' class='narrow field' style='width: 3em;' />";
		echo "&nbsp;<select name='abtble[excerptnumberof]' class='field'>";
		echo "<option value='words'"; echo ($table['excerptnumberof'] == 'words') ? " selected='selected'" : ""; echo ">" . __('Words','autoblogtext') . "</option>";
		echo "<option value='sentences'"; echo ($table['excerptnumberof'] == 'sentences') ? " selected='selected'" : ""; echo ">" . __('Sentences','autoblogtext') . "</option>";
		echo "<option value='paragraphs'"; echo ($table['excerptnumberof'] == 'paragraphs') ? " selected='selected'" : ""; echo ">" . __('Paragraphs','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Specify the size of the excerpt to create (if selected)'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='" . htmlentities(stripslashes($table['source']),ENT_QUOTES, 'UTF-8') . "' class='long source field' />" . "<a href='#' class='info' title='If you want to link back to original source, enter a phrase to use here.'></a>";
		echo "<br/>";
		echo "<input type='checkbox' name='abtble[nofollow]' value='1' ";
		if($table['nofollow'] == '1') echo "checked='checked' ";
		echo "/>&nbsp;<span>" . __('Ensure this link is a nofollow one','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";


		echo "<tr><td colspan='2' class='spacer'><span>" . __('Feed Processing','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Process this feed','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[processfeed]' class='field'>";
		echo "<option value='0'"; echo ($table['processfeed'] == '0') ? " selected='selected'" : ""; echo ">" . __('Never (paused)','autoblogtext') . "</option>";
		echo "<option value='30'"; echo ($table['processfeed'] == '30') ? " selected='selected'" : ""; echo ">" . __('every 30 minutes','autoblogtext') . "</option>";
		echo "<option value='60'"; echo ($table['processfeed'] == '60') ? " selected='selected'" : ""; echo ">" . __('every hour','autoblogtext') . "</option>";
		echo "<option value='90'"; echo ($table['processfeed'] == '90') ? " selected='selected'" : ""; echo ">" . __('every 1 hour 30 minutes','autoblogtext') . "</option>";
		echo "<option value='120'"; echo ($table['processfeed'] == '120') ? " selected='selected'" : ""; echo ">" . __('every 2 hours','autoblogtext') . "</option>";
		echo "<option value='150'"; echo ($table['processfeed'] == '150') ? " selected='selected'" : ""; echo ">" . __('every 2 hours 30 minutes','autoblogtext') . "</option>";
		echo "<option value='300'"; echo ($table['processfeed'] == '300') ? " selected='selected'" : ""; echo ">" . __('every 5 hours','autoblogtext') . "</option>";
		echo "<option value='1449'"; echo ($table['processfeed'] == '1449') ? " selected='selected'" : ""; echo ">" . __('every day','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	function show_table_template($key = '') {

		global $blog_id;

		if(empty($key)) {
			echo '<div class="postbox blanktable" id="blanktable" style="display: none;">';
		} else {
			echo '<div class="postbox" id="ab-' . $key . '">';
		}


		echo '<h3 class="hndle"><span>' . __('New Feed','autoblogtext') . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		// Title
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Your Title','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[title]' value='' class='long title field' />" . "<a href='#' class='info' title='Enter a memorable title.'></a>";
		echo "</td>";
		echo "</tr>";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='' class='long url field' />" . "<a href='#' class='info' title='Enter the feed URL'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2'>&nbsp;</td></tr>";

		// Blogs
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add posts to','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[blog]' class='field blog'>";
		$blogs = $this->get_blogs_of_site();
		foreach( (array) $blogs as $bkey => $blog) {
			echo "<option value='$bkey'>" . $blog->domain . $blog->path . "</option>";
		}
		echo "</select>" . "<a href='#' class='info' title='Select a blog to add the post to.'></a>";

		echo "</td>";
		echo "</tr>";

		// Status
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Default status for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[poststatus]' class='field'>";
		echo "<option value='publish'>" . __('Published') . "</option>";
		echo "<option value='pending'>" . __('Pending Review') . "</option>";
		echo "<option value='draft'>" . __('Draft') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Select the status the imported posts will have in the blog.'></a>";

		echo "</td>";
		echo "</tr>";

		// Post dates
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set the date for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[postdate]' class='field'>";
		echo "<option value='current'>" . __('Imported date') . "</option>";
		echo "<option value='existing'>" . __('Original posts date') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Select the date imported posts will have.'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Author details','autoblogtext') . "</span></td></tr>";

		$blogusers = get_users_of_blog( $blog_id );

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set author for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[author]' class='field author'>";
		echo "<option value='0'>" . __('Use feed author','autoblogtext') . "</option>";

		if($blogusers) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->user_id . "'>";
				echo $bloguser->user_login;
				echo "</option>";
			}
		}
		echo "</select>" . "<a href='#' class='info' title='Select the author you want to use for the posts, or attempt to use the original feed author.'></a>";

		//print_r($blogusers);

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('If author in feed does not exist locally use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[altauthor]' class='field altauthor'>";
		reset($blogusers);
		if($blogusers) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->user_id . "'>";
				echo $bloguser->user_login;
				echo "</option>";
			}
		}
		echo "</select>" . "<a href='#' class='info' title='If the feed author does not exist in your blog then use this author.'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Categories and Tags','autoblogtext') . "</span></td></tr>";

		//
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Assign posts to this category','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'abtble[category]', 'orderby' => 'name', 'selected' => '', 'hierarchical' => true, 'show_option_none' => __('None'), 'class' => 'field cat'));
		echo "<a href='#' class='info' title='Assign this category to the imported posts.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='' class='long tag field' />" . "<a href='#' class='info' title='Enter a comma separated list of tags to add.'></a>";
		echo "<br/><input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
		echo "/>&nbsp;<span>" . __('Use original feeds tags as well (adding if necessary).','autoblogtext') . "</span>" . "<a href='#' class='info' title='Imported and use the tags originally associated with the post.'></a>";


		//print_r($tags);
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post Filtering','autoblogtext') . "</span></td></tr>";
		echo "<tr><td colspan='2'><p>" . __('Include posts that contain (separate words with commas)','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('All of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[allwords]' value='' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have ALL of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have ANY of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must have this exact phrase in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must NOT have any of these words in the title or content.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='' class='long title field' />" . "<a href='#' class='info' title='A post to be imported must be marked with any of these categories or tags.'></a>";
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";




		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>";
		//echo "<tr><td colspan='2'><p>" . __('','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'>" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'>" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Use the full post (if available) or create an excerpt.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('For excerpts use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[excerptnumber]' value='' class='narrow field' style='width: 3em;' />";
		echo "&nbsp;<select name='abtble[excerptnumberof]' class='field'>";
		echo "<option value='words'>" . __('Words','autoblogtext') . "</option>";
		echo "<option value='sentences'>" . __('Sentences','autoblogtext') . "</option>";
		echo "<option value='paragraphs'>" . __('Paragraphs','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Specify the size of the excerpt to create (if selected)'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='' class='long source field' />" . "<a href='#' class='info' title='If you want to link back to original source, enter a phrase to use here.'></a>";
		echo "<br/>";
		echo "<input type='checkbox' name='abtble[nofollow]' value='1' />&nbsp;<span>" . __('Ensure this link is a nofollow one','autoblogtext') . "</span>";

		echo "</td>";
		echo "</tr>";


		echo "<tr><td colspan='2' class='spacer'><span>" . __('Feed Processing','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Process this feed','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[processfeed]' class='field'>";
		echo "<option value='0'>" . __('Never (paused)','autoblogtext') . "</option>";
		echo "<option value='30'>" . __('every 30 minutes','autoblogtext') . "</option>";
		echo "<option value='60'>" . __('every hour','autoblogtext') . "</option>";
		echo "<option value='90'>" . __('every 1 hour 30 minutes','autoblogtext') . "</option>";
		echo "<option value='120'>" . __('every 2 hours','autoblogtext') . "</option>";
		echo "<option value='150'>" . __('every 2 hours 30 minutes','autoblogtext') . "</option>";
		echo "<option value='300'>" . __('every 5 hours','autoblogtext') . "</option>";
		echo "<option value='1449'>" . __('every day','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.'></a>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	function get_autoblogentries() {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id = %d ORDER BY feed_id ASC", $this->db->siteid );

		$results = $this->db->get_results($sql);

		return $results;

	}

	function get_autoblogentry($id) {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id = %d AND feed_id = %d ORDER BY feed_id ASC", $this->db->siteid, $id );

		$results = $this->db->get_row($sql);

		return $results;

	}

	function deletefeed($id) {

		$sql = $this->db->prepare( "DELETE FROM {$this->autoblog} WHERE site_id = %d AND feed_id = %d", $this->db->siteid, $id);

		return $this->db->query($sql);

	}

	function processfeed($id) {
		//echo $id . "-";
		return true;
	}

	function deletefeeds($ids) {

		$sql = $this->db->prepare( "DELETE FROM {$this->autoblog} WHERE site_id = %d AND feed_id IN (0, " . implode(',', $ids) . ")", $this->db->siteid);

		return $this->db->query($sql);
	}

	function processfeeds($ids) {

		if(is_array($ids)) {
			foreach($ids as $id) {
				$this->processfeed($id);
			}
			return true;
		} elseif(is_numeric($id)) {
			return $this->processfeed($id);
		}

	}

	function handle_addnew_page() {

		$stamp = time();

		echo "<div class='wrap'>";

		// Show the heading
		echo '<div class="icon32" id="icon-edit"><br/></div>';
		echo "<h2>" . __('Auto Blog Feeds','autoblogtext') . "</h2>";

		echo "<br/>";

		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='action' value='autoblog' />";
		echo "<input type='hidden' name='stamp' value='" . $stamp . "' />";

		wp_nonce_field( 'autoblog' );

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="savenew" value="' . __('Save feed', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='wpmu-admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		$this->show_table_template($stamp);

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="savenew" value="' . __('Add New', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='wpmu-admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		echo "</div>";

	}

	function handle_edit_page($id) {

		$feed = $this->get_autoblogentry($id);

		echo "<div class='wrap'>";

		// Show the heading
		echo '<div class="icon32" id="icon-edit"><br/></div>';
		echo "<h2>" . __('Auto Blog Feeds','autoblogtext') . "</h2>";

		echo "<br/>";

		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='action' value='autoblog' />";
		echo "<input type='hidden' name='feed_id' value='" . $feed->feed_id . "' />";

		wp_nonce_field( 'autoblog' );

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="save" value="' . __('Update feed', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='wpmu-admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		$this->show_table($id, $feed);

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="save" value="' . __('Update feed', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='wpmu-admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		echo '</div>';

		echo "</div>";

	}

	function handle_admin_page() {

		$showlist = true;

		if(isset($_POST['action']) && addslashes($_POST['action']) == 'autoblog') {

			check_admin_referer('autoblog');

			if(isset($_POST['add'])) {
				// We are adding a new feed
				$this->handle_addnew_page();
				$showlist = false;
			} else {
				// We are doing something else

				//save
				if(!empty($_POST['savenew'])) {
					// Adding a new feed

					$feed = array();
					$feed['feed_meta'] = serialize($_POST['abtble']);
					$feed['lastupdated'] = 0;

					if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
						$feed['nextcheck'] = time() + (intval($_POST['abtble']['processfeed']) * 60);
					} else {
						$feed['nextcheck'] = 0;
					}


					$feed['site_id'] = $this->db->siteid;
					$feed['blog_id'] = $this->db->blogid;

					if($this->db->insert($this->autoblog, $feed)) {

						echo '<div id="message" class="updated fade"><p>' . sprintf(__("Your feed has been added.", 'autoblogtext')) . '</p></div>';
					} else {
						echo '<div id="message" class="error fade"><p>' . sprintf(__("Your feed could not be added.", 'autoblogtext')) . '</p></div>';
					}

				}

				if(!empty($_POST['save'])) {
					// Saving a feed
					$feed = array();
					$feed['feed_meta'] = serialize($_POST['abtble']);
					if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
						$feed['nextcheck'] = time() + (intval($_POST['abtble']['processfeed']) * 60);
					} else {
						$feed['nextcheck'] = 0;
					}

					if($this->db->update($this->autoblog, $feed, array( "feed_id" => mysql_real_escape_string($_POST['feed_id'])) ) ) {
						echo '<div id="message" class="updated fade"><p>' . sprintf(__("Your feed has been updated.", 'autoblogtext')) . '</p></div>';
					} else {
						echo '<div id="message" class="error fade"><p>' . sprintf(__("Your feed could not be updated.", 'autoblogtext')) . '</p></div>';
					}

				}

				if(!empty($_POST['delete'])) {
					$deletekeys = (array) $_POST['deletecheck'];
					if(!empty($_POST['select'])) {
						$todelete = array();
						foreach($_POST['select'] as $key => $value) {
							$todelete[] = mysql_real_escape_string($value);
						}
						if($this->deletefeeds($todelete)) {
							echo '<div id="message" class="updated fade"><p>' . sprintf(__("The selected feeds have been deleted.", 'autoblogtext')) . '</p></div>';
						} else {
							echo '<div id="message" class="error fade"><p>' . sprintf(__("Please select a feed to delete.", 'autoblogtext')) . '</p></div>';
						}

					} else {
						echo '<div id="message" class="error fade"><p>' . sprintf(__("Please select a feed to delete.", 'autoblogtext')) . '</p></div>';
					}


				}

				if(!empty($_POST['process'])) {
					if(!empty($_POST['select'])) {
						$toprocess = array();
						foreach($_POST['select'] as $key => $value) {
							$toprocess[] = mysql_real_escape_string($value);
						}
						if(process_feeds($toprocess)) {
							echo '<div id="message" class="updated fade"><p>' . sprintf(__("The selected feeds have been processed.", 'autoblogtext')) . '</p></div>';
						} else {
							echo '<div id="message" class="error fade"><p>' . sprintf(__("Please select a feed to process.", 'autoblogtext')) . '</p></div>';
						}

					} else {
						echo '<div id="message" class="error fade"><p>' . sprintf(__("Please select a feed to process.", 'autoblogtext')) . '</p></div>';
					}

				}

				$showlist = true;

			}

		} else {
			// Edit a feed
			if(isset($_GET['edit']) && is_numeric(addslashes($_GET['edit']))) {
				$this->handle_edit_page(addslashes($_GET['edit']));
				$showlist = false;
			}
			// Delete a feed
			if(isset($_GET['delete']) && is_numeric(addslashes($_GET['delete']))) {
				check_admin_referer('autoblogdelete');
				if($this->deletefeed(addslashes($_GET['delete']))) {
					echo '<div id="message" class="updated fade"><p>' . sprintf(__("Your feed has been deleted.", 'autoblogtext')) . '</p></div>';
				} else {
					echo '<div id="message" class="error fade"><p>' . sprintf(__("Your feed could not be deleted.", 'autoblogtext')) . '</p></div>';
				}
				$showlist = true;
			}
			// Process a feed
			if(isset($_GET['process']) && is_numeric(addslashes($_GET['process']))) {
				check_admin_referer('autoblogprocess');

				$feed = $this->get_autoblogentry(addslashes($_GET['process']));

				if(!empty($feed->feed_meta)) {
					$details = unserialize($feed->feed_meta);
					if(process_feed($feed->feed_id, $details)) {
						echo '<div id="message" class="updated fade"><p>' . sprintf(__("The feed has been processed.", 'autoblogtext')) . '</p></div>';
					} else {
						echo '<div id="message" class="error fade"><p>' . sprintf(__("Your feed could not be processed.", 'autoblogtext')) . '</p></div>';
					}
				}
				$showlist = true;

			}
		}

		if($showlist) {
			echo "<div class='wrap'>";

			// Show the heading
			echo '<div class="icon32" id="icon-edit"><br/></div>';
			echo "<h2>" . __('Auto Blog Feeds','autoblogtext') . "</h2>";

			echo "<br/>";

			echo "<form action='' method='post'>";
			echo "<input type='hidden' name='action' value='autoblog' />";

			wp_nonce_field( 'autoblog' );

			echo '<div class="tablenav">';
			echo '<div class="alignleft">';
			echo '<input class="button-secondary delete save" type="submit" name="add" value="' . __('Add New', 'autoblogtext') . '" />';
			echo '<input class="button-secondary addnew process" type="submit" name="process" value="' . __('Process selected', 'autoblogtext') . '" />';
			echo '</div>';

			echo '<div class="alignright">';
			echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'autoblogtext') . '" />';
			echo '</div>';

			echo '</div>';

			// New table based layout
			echo '<table width="100%" cellpadding="3" cellspacing="3" class="widefat" style="width: 100%;">';
			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col" class="manage-column column-cb check-column">';
			echo "<input type='checkbox' name='select-all' id='select-all' value='all' />";
			echo '</th>';
			echo '<th scope="col">';
			echo __('Feed title','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check','autoblogtext');
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tfoot>';
			echo '<tr>';
			echo '<th scope="col" class="manage-column column-cb check-column">';
			echo '&nbsp;';
			echo '</th>';
			echo '<th scope="col">';
			echo __('Feed title','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check','autoblogtext');
			echo '</th>';
			echo '</tr>';
			echo '</tfoot>';

			echo '<tbody id="the-list">';

			$autoblogs = $this->get_autoblogentries();

			if(!empty($autoblogs)) {

				foreach($autoblogs as $key => $table) {

					$details = maybe_unserialize($table->feed_meta);
					//$this->show_table($key, $table);
					echo '<tr>';
					echo '<td>';
					echo "<input type='checkbox' name='select[]' id='select-" . $table->feed_id . "' value='" . $table->feed_id . "' class='selectfeed' />";
					echo '</td>';
					echo '<td>';
					echo '<a href="' . admin_url("wpmu-admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . '">';
					if(!empty($details)) {
						echo stripslashes($details['title']);
					} else {
						echo __('No title set', 'autoblogtext');
					}
					echo '</a>';

					echo '<div class="row-actions">';
					echo "<a href='" . admin_url("wpmu-admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . "' class='editfeed'>" . __('Edit', 'autoblogtext') . "</a> | ";
					echo "<a href='" . wp_nonce_url(admin_url("wpmu-admin.php?page=autoblog_admin&amp;delete=" . $table->feed_id), 'autoblogdelete') . "' class='deletefeed'>" . __('Delete', 'autoblogtext') . "</a> | ";
					echo "<a href='" . wp_nonce_url(admin_url("wpmu-admin.php?page=autoblog_admin&amp;process=" . $table->feed_id), 'autoblogprocess') . "' class='processfeed'>" . __('Process', 'autoblogtext') . "</a>";
					echo '</div>';

					echo '</td>';
					echo '<td style="text-align: right;">';

					if($table->lastupdated != 0) {
						echo date("j M Y : H:i", $table->lastupdated);
					} else {
						echo __('Never', 'autoblogtext');
					}


					echo '</td>';
					echo '<td style="text-align: right;">';

					if($table->nextcheck != 0) {
						echo date("j M Y : H:i", $table->nextcheck);
					} else {
						echo __('Never', 'autoblogtext');
					}


					echo '</td>';

					echo '</tr>';

				}

			} else {

				echo '<tr>';
				echo '<td>';
				echo '</td>';
				echo '<td colspan="3">';
				echo __('You do not have any feeds setup - please click Add New to get started','autoblogtext');
				echo '</td>';
				echo '</tr>';

			}

			echo '</tbody>';

			echo '</table>';

			echo '<div class="tablenav">';
			echo '<div class="alignleft">';
			echo '<input class="button-secondary delete save" type="submit" name="add" value="' . __('Add New', 'autoblogtext') . '" />';
			echo '<input class="button-secondary addnew process" type="submit" name="process" value="' . __('Process selected', 'autoblogtext') . '" />';
			echo '</div>';

			echo '<div class="alignright">';
			echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'autoblogtext') . '" />';
			echo '</div>';

			echo '</div>';

			echo "</form>";

			//$this->show_table_template();

			echo "</div>";	// wrap
		}

	}

	function get_blogs_of_site($siteid = false, $all = false) {
		global $current_site, $wpdb;

		if ( !$siteid && !empty($current_site) ) {
			$siteid = $current_site->id;
		}

		$match = array();
		$blogs = array();

		$results = $wpdb->get_results( $wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d", $siteid) );

		if($results) {
			foreach ($results as $result) {
				$blog = get_blog_details( $result->blog_id );
				if ( !empty($blog) && isset( $blog->domain ) ) {
					$blogs[$result->blog_id]->id		  = $blog->blog_id;
					$blogs[$result->blog_id]->blogname    = $blog->blogname;
					$blogs[$result->blog_id]->domain      = $blog->domain;
					$blogs[$result->blog_id]->path        = $blog->path;
					$blogs[$result->blog_id]->site_id     = $blog->site_id;
					$blogs[$result->blog_id]->siteurl     = $blog->siteurl;
				}
			}
		}

		return $blogs;
	}

}

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

// Load them up

if(is_admin()) {
	$abp =& new autoblogpremium();
}

$abc =& new autoblogcron();

?>
