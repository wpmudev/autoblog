<?php
class autoblogpremium {

	var $build = 5;

	var $db;

	var $mylocation = "";
	var $plugindir = "";
	var $base_uri = '';

	var $tables = array('autoblog');
	var $autoblog;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		// Installation functions
		register_activation_hook(__FILE__, array(&$this, 'install'));

		add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

		add_action('init', array(&$this, 'initialise_plugin'));

		add_action('load-toplevel_page_autoblog', array(&$this, 'add_admin_header_autoblog'));
		add_action('load-autoblog_page_autoblog_admin', array(&$this, 'add_admin_header_autoblog_admin'));
		add_action('load-autoblog_page_autoblog_options', array(&$this, 'add_admin_header_autoblog_options'));

		add_action('admin_menu', array(&$this,'add_adminmenu'));

		foreach($this->tables as $table) {
			$this->$table = autoblog_db_prefix($this->db, $table);
		}

		// check for installation
		if(get_autoblog_option('autoblog_installed', 0) < $this->build) {
			// create the database table
			$this->install();
		}

	}

	function autoblogpremium() {
		$this->__construct();
	}

	function load_textdomain() {

		$locale = apply_filters( 'autoblog_locale', get_locale() );
		$mofile = autoblog_dir( "autoblogincludes/autoblog-$locale.mo" );

		if ( file_exists( $mofile ) )
			load_textdomain( 'autoblogtext', $mofile );

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

			update_autoblog_option('autoblog_installed', $this->build);

	}

	function initialise_plugin() {

		if(get_site_option('autoblog_installed', 1) < $this->build) {
			$this->install();
		}

		add_action( 'wp_ajax__getblogcategorylist', array(&$this,'ajax__getblogcategorylist') );
		add_action( 'wp_ajax__getblogauthorlist', array(&$this,'ajax__getblogauthorlist') );
		add_action( 'wp_ajax__getblogtaglist', array(&$this,'ajax__getblogtaglist') );

	}

	function add_admin_header_autoblog() {

		wp_enqueue_script('flot_js', autoblog_url('autoblogincludes/js/jquery.flot.min.js'), array('jquery'));

		add_action ('admin_head', array(&$this, 'dashboard_iehead'));
		add_action ('admin_head', array(&$this, 'dashboard_chartdata'));

		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );
		wp_enqueue_script( 'autoblogdashjs', autoblog_url('autoblogincludes/js/autoblogdash.js'), array('jquery'), $this->build );

		wp_localize_script( 'autoblogdashjs', 'autoblog', array( 'imports' => __('Posts imported','autoblog') ) );

		// actions
		add_action( 'autoblog_dashboard_left', array(&$this, 'dashboard_news') );

		$debug = get_site_option('autoblog_debug', false);
		if($debug) {
			add_action( 'autoblog_dashboard_left', array(&$this, 'dashboard_debug') );
		}

		add_action( 'autoblog_dashboard_right', array(&$this, 'dashboard_stats') );

	}

	function add_admin_header_autoblog_admin() {
		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );
		wp_enqueue_script( 'qtip', autoblog_url('autoblogincludes/js/jquery.qtip-1.0.0-rc3.min.js'), array('jquery'), $this->build );
		wp_enqueue_script( 'autoblogadminjs', autoblog_url('autoblogincludes/js/autoblogadmin.js'), array('jquery'), $this->build );

		wp_localize_script( 'autoblogadminjs', 'autoblog', array( 	'deletefeed' => __('Are you sure you want to delete this feed?','autoblogtext'),
																	'processfeed' => __('Are you sure you want to process this feed?','autoblogtext')
																) );
	}

	function add_admin_header_autoblog_options() {

		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );

		if(isset($_POST['action']) && $_POST['action'] == 'updateoptions') {

			check_admin_referer('update-autoblog-options');

			if($_POST['debugmode'] == 'yes') {
				update_autoblog_option('autoblog_debug', true);
			} else {
				delete_autoblog_option('autoblog_debug');
			}

			wp_safe_redirect( add_query_arg('msg', 1, wp_get_referer()) );
		}

	}

	function dashboard_iehead() {
		echo '<!--[if IE]><script language="javascript" type="text/javascript" src="' . autoblog_url('autoblogincludes/js/excanvas.min.js') . '"></script><![endif]-->';
	}

	function get_data($results, $str = false) {

		$data = array();

		foreach( (array) $results as $key => $res) {
			if($str) {
				$data[] = "[ " . $key . ", '" . $res . "' ]";
			} else {
				$data[] = "[ " . $key . ", " . $res . " ]";
			}
		}

		return "[ " . implode(", ", $data) . " ]";

	}

	function dashboard_chartdata() {

		$autos = $this->get_autoblogentries();
		$results = array();
		$fres = array();
		$ticks = array();
		$data = array();

		foreach($autos as $key => $auto) {
			$feed = unserialize($auto->feed_meta);

			if($feed['blog'] != $this->db->blogid && function_exists('switch_to_blog')) {
				switch_to_blog( $feed['blog'] );

			}

			$sql = $this->db->prepare( "SELECT DATE(p.post_date) AS thedate, count(*) AS thecount FROM {$this->db->posts} AS p, {$this->db->postmeta} AS pm WHERE p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %s AND p.post_date >= %s GROUP BY DATE(p.post_date) ORDER BY p.post_date DESC", 'original_feed', $feed['url'], date("Y-m-d", strtotime('-20 days')) );
			$results[$auto->feed_id] = $this->db->get_results( $sql );

			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		}


		for($n=0; $n < 15; $n++) {
			$ticks[14 - $n] = date("j/n", strtotime('-' . $n . ' days'));
		}

		foreach($results as $key => $res) {
			$fres[$key] = array();

			foreach($res as $ikey => $ires) {
				$fres[$key][$ires->thedate] = $ires->thecount;
			}

			for($n=0; $n < 15; $n++) {
				$thedate = date("Y-m-d", strtotime('-' . $n . ' days'));
				if(!array_key_exists($thedate, $fres[$key])) {
					$fres[$key][$thedate] = 0;
				}
			}

			foreach($fres[$key] as $fkey => $fval) {
				$newdate = date("j/n", strtotime($fkey));
				unset($fres[$key][$fkey]);
				if(array_search($newdate, $ticks) !== false) {
					$fres[$key][array_search($newdate, $ticks)] = $fval;
				}

			}
		}

		if(!empty($results)) {
			echo "\n" . '<script type="text/javascript">';
			echo "\n" . '/* <![CDATA[ */ ' . "\n";

			echo "var autoblogdata = {\n";

			echo "feeds : [";

			foreach($fres as $key => $data) {
				echo "[ " . $key . ", ";
				echo $this->get_data($data) . "";
				echo " ], \n";
			}

			echo "],\n";

			echo "ticks : " . $this->get_data($ticks, true) . "\n";

			echo "};\n";

			echo "\n" . '/* ]]> */ ';
			echo '</script>';
		}


	}

	function ajax__getblogcategorylist() {
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

		exit; // or bad things happen
	}

	function ajax__getblogauthorlist() {
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

		exit;
	}

	function ajax__getblogtaglist() {
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

		exit;
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

		global $menu, $admin_page_hooks;

		add_menu_page(__('Auto Blog','autoblog'), __('Auto Blog','autoblog'), 'manage_options',  'autoblog', array(&$this,'handle_dash_page'), autoblog_url('autoblogincludes/images/menu.png'));

		// Fix WP translation hook issue
		if(isset($admin_page_hooks['autoblog'])) {
			$admin_page_hooks['autoblog'] = 'autoblog';
		}

		// Add the sub menu
		add_submenu_page('autoblog', __('Edit feeds','autoblog'), __('Edit feeds','autoblog'), 'manage_options', "autoblog_admin", array(&$this,'handle_admin_page'));
		add_submenu_page('autoblog', __('Edit Options','autoblog'), __('Edit Options','autoblog'), 'manage_options', "autoblog_options", array(&$this,'handle_options_page'));

	}

	function dashboard_news() {
		global $page, $action;

		$plugin = get_plugin_data(autoblog_dir('autoblog.php'));

		$debug = get_autoblog_option('autoblog_debug', false);

		?>
		<div class="postbox " id="dashboard_right_now">
			<h3 class="hndle"><span><?php _e('Autoblog','autoblog'); ?></span></h3>
			<div class="inside">
				<?php
				echo "<p>";
				echo __('You are running Autoblog version ','autoblog') . "<strong>" . $plugin['Version'] . '</strong>';
				echo "</p>";

				echo "<p>";
				echo __('Debug mode is ','autoblog') . "<strong>";
				if($debug) {
					echo __('Enabled','autoblog');
				} else {
					echo __('Disabled','autoblog');
				}
				echo '</strong>';
				echo "</p>";
				?>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	function dashboard_debug() {

		$sql = $this->db->prepare( "SELECT * FROM {$this->db->sitemeta} WHERE site_id = %d AND meta_key LIKE %s ORDER BY meta_id DESC LIMIT 0, 25", $this->db->siteid, "autoblog_log_%");

		$logs = $this->db->get_results( $sql );

		?>
		<div class="postbox " id="dashboard_right_now">
			<h3 class="hndle"><span><?php _e('Debug report','autoblog'); ?></span></h3>
			<div class="inside">
				<?php
				if(!empty($logs)) {
					foreach($logs as $log) {
						$val = unserialize($log->meta_value);
						echo "<p>";
						echo "<strong>" . date('Y-m-d \a\t H:i', (int) $val['timestamp']) . "</strong><br/>";
						if(!empty($val['log'])) {
							foreach($val['log'] as $key => $l) {
								echo "&#8226; " . $l . "<br/>";
							}
						}
						echo "</p>";
					}
				} else {
					echo "<p>";
					echo __('No debug logs are available, either you have not processed a feed or everything is running smoothly.','autoblog');
					echo "</p>";
				}

				?>
				<br class="clear">
			</div>
		</div>
		<?php

		// Get some columns to remove - as a tidy up
		$sql2 = $this->db->prepare( "SELECT meta_id FROM {$this->db->sitemeta} WHERE site_id = %d AND meta_key LIKE %s ORDER BY meta_id DESC LIMIT 25, 100", $this->db->siteid, "autoblog_log_%");
		$ids = $this->db->get_col( $sql2 );

		if(!empty($ids)) {
			$sql3 = $this->db->prepare( "DELETE FROM {$this->db->sitemeta} WHERE site_id = %d AND meta_id IN (" . implode(',', $ids) . ")", $this->db->siteid);
			$this->db->query( $sql3 );
		}


	}



	function dashboard_stats() {

		$autos = $this->get_autoblogentries();

		?>
		<div class="postbox " id="dashboard_right_now">
			<h3 class="hndle"><span><?php _e('Statistics - posts per day','autoblog'); ?></span></h3>
			<div class="inside">
				<?php
					if(empty($autos)) {
						echo "<p>";
						echo __('You need to set up some feeds before we can produce statistics.','autoblog');
						echo "</p>";
					} else {
						foreach($autos as $key => $a) {
							$feed = unserialize($a->feed_meta);
							echo "<p><strong>" . $feed['title'] . " - " . substr($feed['url'], 0, 30) . "</strong></p>";
							echo "<div id='feedchart-" . $a->feed_id . "' class='dashchart'></div>";

						}
					}
				?>
				<br class="clear">
			</div>
		</div>
		<?php

	}

	function handle_dash_page() {
		?>
		<div class='wrap nosubsub'>
			<div class="icon32" id="icon-index"><br></div>
			<h2><?php _e('Autoblog dashboard','autoblog'); ?></h2>

			<div id="dashboard-widgets-wrap">

			<div class="metabox-holder" id="dashboard-widgets">
				<div style="width: 49%;" class="postbox-container">
					<div class="meta-box-sortables ui-sortable" id="normal-sortables">
						<?php
						do_action( 'autoblog_dashboard_left' );
						?>
					</div>
				</div>

				<div style="width: 49%;" class="postbox-container">
					<div class="meta-box-sortables ui-sortable" id="side-sortables">
						<?php
						do_action( 'autoblog_dashboard_right' );
						?>
					</div>
				</div>

				<div style="display: none; width: 49%;" class="postbox-container">
					<div class="meta-box-sortables ui-sortable" id="column3-sortables" style="">
					</div>
				</div>

				<div style="display: none; width: 49%;" class="postbox-container">
					<div class="meta-box-sortables ui-sortable" id="column4-sortables" style="">
					</div>
				</div>
			</div>

			<div class="clear"></div>
			</div>

		</div> <!-- wrap -->
		<?php
	}

	function show_table($key, $details) {

		global $blog_id;

		$table = maybe_unserialize($details->feed_meta);

		echo '<div class="postbox" id="ab-' . $details->feed_id . '">';

		echo '<h3 class="hndle"><span>' . __('Feed : ','autoblogtext') . esc_html(stripslashes($table['title'])) . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		// Title
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Your Title','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[title]' value='" . esc_attr(stripslashes($table['title'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('Enter a memorable title.','autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='" . esc_attr(stripslashes($table['url'])) . "' class='long url field' />" . "<a href='#' class='info' title='" . __('Enter the feed URL.','autoblogtext') . "'></a>";
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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select a blog to add the post to.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the status the imported posts will have in the blog.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the date imported posts will have.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the author you want to use for the posts, or attempt to use the original feed author.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('If the feed author does not exist in your blog then use this author.', 'autoblogtext') . "'></a>";

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

		echo "<a href='#' class='info' title='" . __('Assign this category to the imported posts.', 'autoblogtext') . "'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='" . esc_attr(stripslashes($table['tag'])) . "' class='long tag field' />" . "<a href='#' class='info' title='" . __('Enter a comma separated list of tags to add.', 'autoblogtext') . "'></a>";
		echo "<br/><input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
		if($table['originalcategories'] == '1') echo " checked='checked'";
		echo "/>&nbsp;<span>" . __('Use original feeds tags as well (adding if necessary).','autoblogtext') . "</span>" . "<a href='#' class='info' title='" . __('Imported and use the tags originally associated with the post.', 'autoblogtext') . "'></a>";


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
		echo "<input type='text' name='abtble[allwords]' value='" . esc_attr(stripslashes($table['allwords'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have ALL of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='" . esc_attr(stripslashes($table['anywords'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have ANY of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='" . esc_attr(stripslashes($table['phrase'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have this exact phrase in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='" . esc_attr(stripslashes($table['nonewords'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must NOT have any of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='" . esc_attr(stripslashes($table['anytags'])) . "' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must be marked with any of these categories or tags.', 'autoblogtext') . "'></a>";
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";


		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'"; echo ($table['useexcerpt'] == '1') ? " selected='selected'" : ""; echo ">" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'"; echo ($table['useexcerpt'] == '2') ? " selected='selected'" : ""; echo ">" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='" . __('Use the full post (if available) or create an excerpt.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('For excerpts use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[excerptnumber]' value='" . esc_attr(stripslashes($table['excerptnumber'])) . "' class='narrow field' style='width: 3em;' />";
		echo "&nbsp;<select name='abtble[excerptnumberof]' class='field'>";
		echo "<option value='words'"; echo ($table['excerptnumberof'] == 'words') ? " selected='selected'" : ""; echo ">" . __('Words','autoblogtext') . "</option>";
		echo "<option value='sentences'"; echo ($table['excerptnumberof'] == 'sentences') ? " selected='selected'" : ""; echo ">" . __('Sentences','autoblogtext') . "</option>";
		echo "<option value='paragraphs'"; echo ($table['excerptnumberof'] == 'paragraphs') ? " selected='selected'" : ""; echo ">" . __('Paragraphs','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='" . __('Specify the size of the excerpt to create (if selected)', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='" . esc_attr(stripslashes($table['source'])) . "' class='long source field' />" . "<a href='#' class='info' title='" . __('If you want to link back to original source, enter a phrase to use here.', 'autoblogtext') . "'></a>";
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
		echo "</select>" . "<a href='#' class='info' title='" . __('Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		// New fields

		$startfrom = $table['startfrom'];

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Starting from','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[startfromday]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=31; $n++) {
			echo "<option value='$n'";
			if(!empty($startfrom) && $n == date('j', $startfrom)) {
				echo " selected='selected'";
			}
			echo ">" . $n . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[startfrommonth]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=12; $n++) {
			echo "<option value='$n'";
			if(!empty($startfrom) && $n == date('n', $startfrom)) {
				echo " selected='selected'";
			}
			echo ">" . date('M', strtotime(date('Y-' . $n . '-1'))) . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[startfromyear]' class='field'>";
		echo "<option value=''></option>";
		for($n=date("Y") - 1; $n<=date("Y") + 9; $n++) {
			echo "<option value='$n'";
			if(!empty($startfrom) && $n == date('Y', $startfrom)) {
				echo " selected='selected'";
			}
			echo ">" . $n . "</option>";
		}
		echo "</select>";
		echo "<a href='#' class='info' title='" . __('Set the date you want to start processing posts from.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		$endon = $table['endon'];

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Ending on','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[endonday]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=31; $n++) {
			echo "<option value='$n'";
			if(!empty($endon) && $n == date('j', $endon)) {
				echo " selected='selected'";
			}
			echo ">" . $n . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[endonmonth]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=12; $n++) {
			echo "<option value='$n'";
			if(!empty($endon) && $n == date('n', $endon)) {
				echo " selected='selected'";
			}
			echo ">" . date('M', strtotime(date('Y-' . $n . '-1'))) . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[endonyear]' class='field'>";
		echo "<option value=''></option>";
		for($n=date("Y") - 1; $n<=date("Y") + 9; $n++) {
			echo "<option value='$n'";
			if(!empty($endon) && $n == date('Y', $endon)) {
				echo " selected='selected'";
			}
			echo ">" . $n . "</option>";
		}
		echo "</select>";
		echo "<a href='#' class='info' title='" . __('Set the date you want to stop processing posts from this feed.', 'autoblogtext') . "'></a>";
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
		echo "<input type='text' name='abtble[title]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('Enter a memorable title.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='' class='long url field' />" . "<a href='#' class='info' title='" . __('Enter the feed URL', 'autoblogtext') . "'></a>";
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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select a blog to add the post to.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the status the imported posts will have in the blog.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the date imported posts will have.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('Select the author you want to use for the posts, or attempt to use the original feed author.', 'autoblogtext') . "'></a>";

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
		echo "</select>" . "<a href='#' class='info' title='" . __('If the feed author does not exist in your blog then use this author.', 'autoblogtext') . "'></a>";

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
		echo "<a href='#' class='info' title='" . __('Assign this category to the imported posts.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='' class='long tag field' />" . "<a href='#' class='info' title='" . __('Enter a comma separated list of tags to add.', 'autoblogtext') . "'></a>";
		echo "<br/><input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
		echo "/>&nbsp;<span>" . __('Use original feeds tags as well (adding if necessary).','autoblogtext') . "</span>" . "<a href='#' class='info' title='" . __('Imported and use the tags originally associated with the post.', 'autoblogtext') . "'></a>";

		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post Filtering','autoblogtext') . "</span></td></tr>";
		echo "<tr><td colspan='2'><p>" . __('Include posts that contain (separate words with commas)','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('All of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[allwords]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have ALL of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have ANY of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must have this exact phrase in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must NOT have any of these words in the title or content.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='' class='long title field' />" . "<a href='#' class='info' title='" . __('A post to be imported must be marked with any of these categories or tags.', 'autoblogtext') . "'></a>";
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'>" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'>" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . "<a href='#' class='info' title='" . __('Use the full post (if available) or create an excerpt.', 'autoblogtext') . "'></a>";
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
		echo "</select>" . "<a href='#' class='info' title='" . __('Specify the size of the excerpt to create (if selected)', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='' class='long source field' />" . "<a href='#' class='info' title='" . __('If you want to link back to original source, enter a phrase to use here.', 'autoblogtext') . "'></a>";
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
		echo "</select>" . "<a href='#' class='info' title='" . __('Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Starting from','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[startfromday]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=31; $n++) {
			echo "<option value='$n'>" . $n . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[startfrommonth]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=12; $n++) {
			echo "<option value='$n'>" . date('M', strtotime(date('Y-' . $n . '-1'))) . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[startfromyear]' class='field'>";
		echo "<option value=''></option>";
		for($n=date("Y") - 1; $n<=date("Y") + 9; $n++) {
			echo "<option value='$n'>" . $n . "</option>";
		}
		echo "</select>";
		echo "<a href='#' class='info' title='" . __('Set the date you want to start processing posts from.', 'autoblogtext') . "'></a>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Ending on','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[endonday]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=31; $n++) {
			echo "<option value='$n'>" . $n . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[endonmonth]' class='field'>";
		echo "<option value=''></option>";
		for($n=1; $n<=12; $n++) {
			echo "<option value='$n'>" . date('M', strtotime(date('Y-' . $n . '-1'))) . "</option>";
		}
		echo "</select>&nbsp;";
		echo "<select name='abtble[endonyear]' class='field'>";
		echo "<option value=''></option>";
		for($n=date("Y") - 1; $n<=date("Y") + 9; $n++) {
			echo "<option value='$n'>" . $n . "</option>";
		}
		echo "</select>";
		echo "<a href='#' class='info' title='" . __('Set the date you want to stop processing posts from this feed.', 'autoblogtext') . "'></a>";
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
		echo "<a href='admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		$this->show_table_template($stamp);

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="savenew" value="' . __('Add New', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='admin.php?page=autoblog_admin'>";
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
		echo "<a href='admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		$this->show_table($id, $feed);

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="save" value="' . __('Update feed', 'autoblogtext') . '" />';
		echo "&nbsp;";
		echo "<a href='admin.php?page=autoblog_admin'>";
		echo __('&lt; cancel and return', 'autoblogtext');
		echo "</a>";
		echo '</div>';

		echo '</div>';

		echo '</div>';

		echo "</div>";

	}

	function update_admin_page() {

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
					$feed['lastupdated'] = 0;

					if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
						$feed['nextcheck'] = time() + (intval($_POST['abtble']['processfeed']) * 60);
					} else {
						$feed['nextcheck'] = 0;
					}

					$feed['site_id'] = $this->db->siteid;
					$feed['blog_id'] = $this->db->blogid;

					if(!empty($_POST['abtble']['startfromday']) && !empty($_POST['abtble']['startfrommonth']) && !empty($_POST['abtble']['startfromyear'])) {
						$_POST['abtble']['startfrom'] = strtotime($_POST['abtble']['startfromyear'] . '-' . $_POST['abtble']['startfrommonth'] . '-' . $_POST['abtble']['startfromday']);
					}

					if(!empty($_POST['abtble']['endonday']) && !empty($_POST['abtble']['endonmonth']) && !empty($_POST['abtble']['endonyear'])) {
						$_POST['abtble']['endon'] = strtotime($_POST['abtble']['endonyear'] . '-' . $_POST['abtble']['endonmonth'] . '-' . $_POST['abtble']['endonday']);
					}
					$feed['feed_meta'] = serialize($_POST['abtble']);

					$id = $this->db->insert($this->autoblog, $feed);
					if(!is_wp_error($id)) {
						echo '<div id="message" class="updated fade"><p>' . sprintf(__("Your feed has been added.", 'autoblogtext')) . '</p></div>';
					} else {
						echo '<div id="message" class="error fade"><p>' . $id->get_error_mssage() . '</p></div>';
					}

				}

				if(!empty($_POST['save'])) {
					// Saving a feed
					$feed = array();
					if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
						$feed['nextcheck'] = time() + (intval($_POST['abtble']['processfeed']) * 60);
					} else {
						$feed['nextcheck'] = 0;
					}

					if(!empty($_POST['abtble']['startfromday']) && !empty($_POST['abtble']['startfrommonth']) && !empty($_POST['abtble']['startfromyear'])) {
						$_POST['abtble']['startfrom'] = strtotime($_POST['abtble']['startfromyear'] . '-' . $_POST['abtble']['startfrommonth'] . '-' . $_POST['abtble']['startfromday']);
					}

					if(!empty($_POST['abtble']['endonday']) && !empty($_POST['abtble']['endonmonth']) && !empty($_POST['abtble']['endonyear'])) {
						$_POST['abtble']['endon'] = strtotime($_POST['abtble']['endonyear'] . '-' . $_POST['abtble']['endonmonth'] . '-' . $_POST['abtble']['endonday']);
					}
					$feed['feed_meta'] = serialize($_POST['abtble']);

					$id = $this->db->update($this->autoblog, $feed, array( "feed_id" => mysql_real_escape_string($_POST['feed_id'])) );
					if( !is_wp_error($id) ) {
						echo '<div id="message" class="updated fade"><p>' . sprintf(__("Your feed has been updated.", 'autoblogtext')) . '</p></div>';
					} else {
						echo '<div id="message" class="error fade"><p>' . $id->get_error_mssage() . '</p></div>';
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
					echo '<a href="' . admin_url("ms-admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . '">';
					if(!empty($details)) {
						echo esc_html(stripslashes($details['title']));
					} else {
						echo __('No title set', 'autoblogtext');
					}
					echo '</a>';

					echo '<div class="row-actions">';
					echo "<a href='" . admin_url("admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . "' class='editfeed'>" . __('Edit', 'autoblogtext') . "</a> | ";
					echo "<a href='" . wp_nonce_url(admin_url("admin.php?page=autoblog_admin&amp;delete=" . $table->feed_id), 'autoblogdelete') . "' class='deletefeed'>" . __('Delete', 'autoblogtext') . "</a> | ";
					echo "<a href='" . wp_nonce_url(admin_url("admin.php?page=autoblog_admin&amp;process=" . $table->feed_id), 'autoblogprocess') . "' class='processfeed'>" . __('Process', 'autoblogtext') . "</a>";
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

			echo "</div>";	// wrap
		}

	}

	function handle_options_page() {

		global $action, $page;

		$messages = array();
		$messages[1] = __('Your options have been updated.','membership');

		?>
		<div class='wrap nosubsub'>
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e('Edit Options','membership'); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}
			?>

			<form action='?page=<?php echo $page; ?>' method='post'>

				<input type='hidden' name='page' value='<?php echo $page; ?>' />
				<input type='hidden' name='action' value='updateoptions' />

				<?php
					wp_nonce_field('update-autoblog-options');
				?>

				<h3><?php _e('Debug mode','autoblog'); ?></h3>
				<p><?php _e('Switch on debug mode and reporting.','autoblog'); ?></p>

				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e('Debug mode is','autoblog'); ?></th>
						<td>
							<?php
								$debug = get_site_option('autoblog_debug', false);
							?>
							<select name='debugmode' id='debugmode'>
								<option value="no" <?php if($debug == false) echo "selected='selected'"; ?>><?php _e('Disabled','autoblog'); ?></option>
								<option value="yes" <?php if($debug == true) echo "selected='selected'"; ?>><?php _e('Enabled','autoblog'); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>

			</form>

		</div> <!-- wrap -->
		<?php
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
?>