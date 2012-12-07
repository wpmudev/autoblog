<?php
class autoblogpremium {

	var $build = 5;

	var $db;

	var $mylocation = "";
	var $plugindir = "";
	var $base_uri = '';

	var $tables = array('autoblog');
	var $autoblog;

	var $siteid = 1;
	var $blogid = 1;

	// Class variable to hold a link to the tooltips class
	var $_tips;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		// Installation functions
		register_activation_hook(__FILE__, array(&$this, 'install'));

		add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

		add_action('init', array(&$this, 'initialise_plugin'));

		add_action('load-toplevel_page_autoblog', array(&$this, 'add_admin_header_autoblog'));
		add_action('load-autoblog_page_autoblog_admin', array(&$this, 'add_admin_header_autoblog_admin'));
		add_action('load-autoblog_page_autoblog_settings', array(&$this, 'add_admin_header_autoblog_settings'));
		add_action('load-autoblog_page_autoblog_addons', array(&$this, 'add_admin_header_autoblog_addons'));

		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_network_admin') && is_network_admin()) {
				add_action('network_admin_menu', array(&$this,'add_adminmenu'));
			} else {
				add_action('admin_menu', array(&$this,'add_adminmenu'));
			}
		} else {
			add_action('admin_menu', array(&$this,'add_adminmenu'));
		}

		foreach($this->tables as $table) {
			$this->$table = autoblog_db_prefix($this->db, $table);
		}

		// check for installation
		if(get_autoblog_option('autoblog_installed', 0) < $this->build) {
			// create the database table
			$this->install();
		}

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

		// Instantiate the tooltips class and set the icon
		$this->_tips = new WpmuDev_HelpTooltips();
		$this->_tips->set_icon_url(autoblog_url('autoblogincludes/images/information.png'));

	}

	function autoblogpremium() {
		$this->__construct();
	}

	function load_textdomain() {

		$locale = apply_filters( 'autoblog_locale', get_locale() );
		$mofile = autoblog_dir( "autoblogincludes/languages/autoblog-$locale.mo" );

		if ( file_exists( $mofile ) )
			load_textdomain( 'autoblogtext', $mofile );

	}

	function install() {

			if($this->db->get_var( "SHOW TABLES LIKE '" . $this->autoblog . "' ") != $this->autoblog) {
				$sql = "CREATE TABLE `" . $this->autoblog . "` (
				  	  `feed_id` bigint(20) NOT NULL auto_increment,
					  `site_id` bigint(20) default '1',
					  `blog_id` bigint(20) default '1',
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

	function update_settings_page() {

		if(isset($_POST['action']) && $_POST['action'] == 'updatesettings') {

			check_admin_referer('update-autoblog-settings');

			if($_POST['debugmode'] == 'yes') {
				update_autoblog_option('autoblog_debug', true);
			} else {
				delete_autoblog_option('autoblog_debug');
			}

			wp_safe_redirect( add_query_arg('msg', 1, wp_get_referer()) );
		}

	}

	function add_admin_header_autoblog() {

		wp_enqueue_script('flot_js', autoblog_url('autoblogincludes/js/jquery.flot.min.js'), array('jquery'));

		add_action ('admin_head', array(&$this, 'dashboard_iehead'));
		add_action ('admin_head', array(&$this, 'dashboard_chartdata'));

		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );
		wp_enqueue_script( 'autoblogdashjs', autoblog_url('autoblogincludes/js/autoblogdash.js'), array('jquery'), $this->build );

		wp_localize_script( 'autoblogdashjs', 'autoblog', array( 'imports' => __('Posts imported','autoblogtext') ) );

		// actions
		add_action( 'autoblog_dashboard_left', array(&$this, 'dashboard_news') );


		add_action( 'autoblog_dashboard_left', array(&$this, 'dashboard_report') );

		add_action( 'autoblog_dashboard_right', array(&$this, 'dashboard_stats') );

	}

	function add_admin_header_autoblog_admin() {

		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );
		wp_enqueue_script( 'autoblogadminjs', autoblog_url('autoblogincludes/js/autoblogadmin.js'), array('jquery'), $this->build );

		wp_localize_script( 'autoblogadminjs', 'autoblog', array( 	'deletefeed' => __('Are you sure you want to delete this feed?','autoblogtext'),
																	'processfeed' => __('Are you sure you want to process this feed?','autoblogtext')
																) );

		$this->update_admin_page();
	}

	function add_admin_header_autoblog_settings() {

		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		wp_enqueue_style( 'autoblogadmincss', autoblog_url('autoblogincludes/styles/autoblog.css'), array(), $this->build );

		$this->update_settings_page();

	}

	function add_admin_header_autoblog_addons() {

		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		$this->handle_addons_panel_updates();

	}

	function dashboard_iehead() {
		echo '<!--[if lt IE 8]><script language="javascript" type="text/javascript" src="' . autoblog_url('autoblogincludes/js/excanvas.min.js') . '"></script><![endif]-->';
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
			$result = array('errorcode' => '500', 'message' => __('No blog.','autoblogtext') );
		}
		$this->return_json($result);

		exit; // or bad things happen
	}

	function ajax__getblogauthorlist() {
		$bid = addslashes($_GET['id']);
		if($bid != "") {
			$blogusers = get_users( 'blog_id=' . $bid ); //get_users_of_blog( $bid );
			$bu = array();
			foreach($blogusers as $key => $buser) {
				$bu[] = array('user_id' => $buser->user_id, 'user_login' => $buser->user_login);
			}
			$result = array('errorcode' => '200', 'data' => $bu);
		} else {
			$result = array('errorcode' => '500', 'message' => __('No blog.','autoblogtext'));
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
			$result = array('errorcode' => '500', 'message' => __('No blog.','autoblogtext'));
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

		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('autoblog/autoblog.php')) {
				if(function_exists('is_network_admin') && is_network_admin()) {
					add_menu_page(__('Auto Blog','autoblogtext'), __('Auto Blog','autoblogtext'), 'manage_options',  'autoblog', array(&$this,'handle_dash_page'), autoblog_url('autoblogincludes/images/menu.png'));
				}
			} else {
				add_menu_page(__('Auto Blog','autoblogtext'), __('Auto Blog','autoblogtext'), 'manage_options',  'autoblog', array(&$this,'handle_dash_page'), autoblog_url('autoblogincludes/images/menu.png'));
			}
		} else {
			add_menu_page(__('Auto Blog','autoblogtext'), __('Auto Blog','autoblogtext'), 'manage_options',  'autoblog', array(&$this,'handle_dash_page'), autoblog_url('autoblogincludes/images/menu.png'));
		}


		// Fix WP translation hook issue
		if(isset($admin_page_hooks['autoblog'])) {
			$admin_page_hooks['autoblog'] = 'autoblog';
		}

		// Add the sub menu
		add_submenu_page('autoblog', __('Edit feeds','autoblogtext'), __('All feeds','autoblogtext'), 'manage_options', "autoblog_admin", array(&$this,'handle_admin_page'));

		if(function_exists('is_multisite') && is_multisite()) {
			if(!function_exists('is_network_admin') || !is_network_admin()) {
				//add_submenu_page('autoblog', __('Edit Options','autoblogtext'), __('Options','autoblogtext'), 'manage_options', "autoblog_options", array(&$this,'handle_options_page'));
				add_submenu_page('autoblog', __('Autoblog Add-ons','autoblogtext'), __('Add-ons','autoblogtext'), 'manage_options', "autoblog_addons", array(&$this,'handle_addons_panel'));

				do_action('autoblog_site_menu');
			} else {

				add_submenu_page('autoblog', __('Autoblog Add-ons','autoblogtext'), __('Add-ons','autoblogtext'), 'manage_options', "autoblog_addons", array(&$this,'handle_networkaddons_panel'));
				do_action('autoblog_network_menu');
			}
		} else {
			//add_submenu_page('autoblog', __('Edit Options','autoblogtext'), __('Options','autoblogtext'), 'manage_options', "autoblog_options", array(&$this,'handle_options_page'));
			add_submenu_page('autoblog', __('Autoblog Add-ons','autoblogtext'), __('Add-ons','autoblogtext'), 'manage_options', "autoblog_addons", array(&$this,'handle_addons_panel'));

			do_action('autoblog_site_menu');
		}

		// Add the new options menu
		//add_submenu_page('autoblog', __('Settings','autoblogtext'), __('Settings','autoblogtext'), 'manage_options', "autoblog_settings", array(&$this,'handle_settings_page'));

		do_action('autoblog_global_menu');

	}

	function dashboard_news() {
		global $page, $action;

		$plugin = get_plugin_data(autoblog_dir('autoblogpremium.php'));

		$debug = get_autoblog_option('autoblog_debug', false);

		?>
		<div class="postbox ">
			<h3 class="hndle"><span><?php _e('Autoblog','autoblogtext'); ?></span></h3>
			<div class="inside">
				<?php
				echo "<p>";
				echo __('You are running Autoblog version ','autoblogtext') . "<strong>" . $plugin['Version'] . '</strong>';
				echo "</p>";
				?>
			</div>
		</div>
		<?php
	}

	function dashboard_report() {

		if(is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('autoblog/autoblogpremium.php') && defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
			$sql = $this->db->prepare( "SELECT * FROM {$this->db->sitemeta} WHERE site_id = %d AND meta_key LIKE %s ORDER BY meta_id DESC LIMIT 0, 25", $this->db->siteid, "autoblog_log_%");
		} else {
			$sql = $this->db->prepare( "SELECT * FROM {$this->db->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT 0, 25", "autoblog_log_%");
		}

		$logs = $this->db->get_results( $sql );

		?>
		<div class="postbox ">
			<h3 class="hndle"><span><?php _e('Processing Report','autoblogtext'); ?></span></h3>
			<div class="inside">
				<?php
				if(!empty($logs)) {
					foreach($logs as $log) {
						if(is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('autoblog/autoblogpremium.php') && defined( 'AUTOBLOG_GLOBAL' ) && AUTOBLOG_GLOBAL == true) {
							$val = unserialize($log->meta_value);
						} else {
							$val = unserialize($log->option_value);
						}
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
					echo __('No processing reports are available, either you have not processed a feed or everything is running smoothly.','autoblogtext');
					echo "</p>";
				}

				?>
			</div>
		</div>
		<?php

	}



	function dashboard_stats() {

		$autos = $this->get_autoblogentries();

		?>
		<div class="postbox ">
			<h3 class="hndle"><span><?php _e('Statistics - posts per day','autoblogtext'); ?></span></h3>
			<div class="inside">
				<?php
					if(empty($autos)) {
						echo "<p>";
						echo __('You need to set up some feeds before we can produce statistics.','autoblogtext');
						echo "</p>";
					} else {
						foreach($autos as $key => $a) {
							$feed = unserialize($a->feed_meta);
							echo "<p><strong>" . $feed['title'] . " - " . substr($feed['url'], 0, 30) . "</strong></p>";
							echo "<div id='feedchart-" . $a->feed_id . "' class='dashchart'></div>";

						}
					}
				?>
			</div>
		</div>
		<?php
	}

	function handle_dash_page() {
		?>
		<div class='wrap nosubsub'>
			<div class="icon32" id="icon-index"><br></div>
			<h2><?php _e('Autoblog dashboard','autoblogtext'); ?></h2>

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

		if(empty($table['blog'])) {
			$table['blog'] = $blog_id;
		}
		if(empty($table['posttype'])) {
			$table['posttype'] = 'post';
		}

		echo '<div class="postbox autoblogeditbox" id="ab-' . $details->feed_id . '">';

		echo '<h3 class="hndle"><span>' . __('Feed : ','autoblogtext') . esc_html(stripslashes($table['title'])) . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>\n";

		// Title
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Your Title','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[title]' value='" . esc_attr(stripslashes($table['title'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('Enter a memorable title.','autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='" . esc_attr(stripslashes($table['url'])) . "' class='long url field' />" . $this->_tips->add_tip(  __('Enter the feed URL.','autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";

		// Blogs
		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_network_admin') && is_network_admin()) {
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
				echo "</select>" . $this->_tips->add_tip(  __('Select a blog to add the post to.', 'autoblogtext') );

				echo "</td>";
				echo "</tr>\n";
			} else {
				echo "<tr>";
				echo "<td valign='top' class='heading'>";
				echo __('Add posts to','autoblogtext');
				echo "</td>";
				echo "<td valign='top' class=''>";
				if(function_exists('get_blog_option')) {
					echo "<strong>" . esc_html(get_blog_option( (int) $table['blog'], 'blogname' )) . "</strong>";
				} else {
					echo "<strong>" . esc_html(get_option( 'blogname' )) . "</strong>";
				}
				echo "<input type='hidden' name='abtble[blog]' value='" . $table['blog'] . "' />";
				echo "</td>";
				echo "</tr>";
			}
		} else {
			echo "<tr>";
			echo "<td valign='top' class='heading'>";
			echo __('Add posts to','autoblogtext');
			echo "</td>";
			echo "<td valign='top' class=''>";
			if(function_exists('get_blog_option')) {
				echo "<strong>" . esc_html(get_blog_option( (int) $table['blog'], 'blogname' )) . "</strong>";
			} else {
				echo "<strong>" . esc_html(get_option( 'blogname' )) . "</strong>";
			}
			echo "<input type='hidden' name='abtble[blog]' value='" . $table['blog'] . "' />";
			echo "</td>";
			echo "</tr>";
		}

		// Post type
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Post type for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		$output = 'objects'; // names or objects
		$post_types = get_post_types( '' , $output );

		echo "<select name='abtble[posttype]' class='field'>";
		foreach ($post_types as $key => $post_type ) {
			echo "<option value='" . $key . "'";
			echo $table['posttype'] == $key ? " selected='selected'" : "";
			echo ">" . $post_type->name . "</option>";
		}
		echo "</select>" . $this->_tips->add_tip(  __('Select the post type the imported posts will have in the blog.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";


		// Status
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Default status for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[poststatus]' class='field'>";
		echo "<option value='publish'"; echo $table['poststatus'] == 'publish' ? " selected='selected'" : "";  echo ">" . __('Published','autoblogtext') . "</option>";
		echo "<option value='pending'"; echo  $table['poststatus'] == 'pending' ? " selected='selected'" : "";  echo ">" . __('Pending Review','autoblogtext') . "</option>";
		echo "<option value='draft'"; echo  $table['poststatus'] == 'draft' ? " selected='selected'" : "";  echo ">" . __('Draft','autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip(  __('Select the status the imported posts will have in the blog.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";

		// Post dates
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set the date for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[postdate]' class='field'>";
		echo "<option value='current'"; echo $table['postdate'] == 'current' ? " selected='selected'" : "";  echo ">" . __('Imported date','autoblogtext') . "</option>";
		echo "<option value='existing'"; echo  $table['postdate'] == 'existing' ? " selected='selected'" : "";  echo ">" . __('Original posts date','autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip(  __('Select the date imported posts will have.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";

		do_action( 'autoblog_feed_edit_form_details_end', $key, $details );

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Author details','autoblogtext') . "</span></td></tr>\n";

		$blogusers = get_users( 'blog_id=' . $table['blog'] ); //get_users_of_blog( $table['blog'] );

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set author for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[author]' class='field author'>";
		echo "<option value='0'"; echo  (!isset($table['author']) || $table['author'] == '0') ? " selected='selected'" : ""; echo ">" . __('Use feed author','autoblogtext') . "</option>";

		if(!empty($blogusers)) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->ID . "'"; echo (isset($table['author']) && $table['author'] == $bloguser->ID) ? " selected='selected'" : ""; echo ">";
				if(method_exists( $bloguser, 'get' )) {
					echo $bloguser->get('user_login');
				} elseif(isset($bloguser->user_login)) {
					echo $bloguser->user_login;
				}
				echo "</option>";
			}
		}
		echo "</select>" . $this->_tips->add_tip(  __('Select the author you want to use for the posts, or attempt to use the original feed author.', 'autoblogtext') );

		//print_r($blogusers);

		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('If author in feed does not exist locally use','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[altauthor]' class='field altauthor'>";
		reset($blogusers);
		if(!empty($blogusers)) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->ID . "'"; echo (isset($table['author']) && $table['altauthor'] == $bloguser->ID) ? " selected='selected'" : ""; echo ">";
				if(method_exists( $bloguser, 'get' )) {
					echo $bloguser->get('user_login');
				} elseif(isset($bloguser->user_login)) {
					echo $bloguser->user_login;
				}
				echo "</option>";
			}
		}
		echo "</select>" . $this->_tips->add_tip(  __('If the feed author does not exist in your blog then use this author.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Categories and Tags','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Assign posts to this category','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		if(function_exists('switch_to_blog')) switch_to_blog($table['blog']);
		wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'abtble[category]', 'orderby' => 'name', 'selected' => $table['category'], 'hierarchical' => true, 'show_option_none' => __('None','autoblogtext'), 'class' => 'field cat'));
		if(function_exists('restore_current_blog')) restore_current_blog();

		echo $this->_tips->add_tip(  __('Assign this category to the imported posts.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
			echo "<td valign='top' class='heading'>";
			echo __('Treat feed categories as','autoblogtext');
			echo "</td>";

			echo "<td valign='top' class=''>";
			echo "<select name='abtble[feedcatsare]'>";
			echo "<option value='tags' " . selected(esc_attr(stripslashes($table['feedcatsare'])), 'tags') . ">" . __('tags', 'autoblogtext') . "</option>";
			echo "<option value='categories' " . selected(esc_attr(stripslashes($table['feedcatsare'])), 'categories') . ">" . __('categories', 'autoblogtext') . "</option>";
			echo "</select>";
			echo "&nbsp;<input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
			if(isset($table['originalcategories'])  && $table['originalcategories'] == '1') echo " checked='checked'";
			echo "/>&nbsp;<span>" . __('Add any that do not exist.','autoblogtext') . "</span>" . $this->_tips->add_tip(  __('Create any tags or categories that are needed.', 'autoblogtext') );

			echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='" . esc_attr(stripslashes($table['tag'])) . "' class='long tag field' />" . $this->_tips->add_tip(  __('Enter a comma separated list of tags to add.', 'autoblogtext') );

		echo "</td>";
		echo "</tr>\n";

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Post Filtering','autoblogtext') . "</span></td></tr>\n";
		echo "<tr><td colspan='2'><p>" . __('Include posts that contain (separate words with commas)','autoblogtext') . "</p></td></tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('All of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[allwords]' value='" . esc_attr(stripslashes($table['allwords'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('A post to be imported must have ALL of these words in the title or content.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='" . esc_attr(stripslashes($table['anywords'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('A post to be imported must have ANY of these words in the title or content.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='" . esc_attr(stripslashes($table['phrase'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('A post to be imported must have this exact phrase in the title or content.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='" . esc_attr(stripslashes($table['nonewords'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('A post to be imported must NOT have any of these words in the title or content.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='" . esc_attr(stripslashes($table['anytags'])) . "' class='long title field' />" . $this->_tips->add_tip(  __('A post to be imported must be marked with any of these categories or tags.', 'autoblogtext') );
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>\n";


		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'"; echo ($table['useexcerpt'] == '1') ? " selected='selected'" : ""; echo ">" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'"; echo ($table['useexcerpt'] == '2') ? " selected='selected'" : ""; echo ">" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip(  __('Use the full post (if available) or create an excerpt.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

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
		echo "</select>" . $this->_tips->add_tip(  __('Specify the size of the excerpt to create (if selected)', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='" . esc_attr(stripslashes($table['source'])) . "' class='long source field' />" . $this->_tips->add_tip(  __('If you want to link back to original source, enter a phrase to use here.', 'autoblogtext') );
		echo "<br/>";
		echo "<input type='checkbox' name='abtble[nofollow]' value='1' ";
		if(isset($table['nofollow']) && $table['nofollow'] == '1') echo "checked='checked' ";
		echo "/>&nbsp;<span>" . __('Ensure this link is a nofollow one','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>\n";


		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Feed Processing','autoblogtext') . "</span></td></tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Import the most recent','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[poststoimport]' class='field'>";
		echo "<option value='0' " . selected('0', $table['poststoimport']) . ">" . __('posts.','autoblogtext') . "</option>";
		for($n=1; $n <= 100; $n++) {
			echo "<option value='" . $n . "' " . selected($n, $table['poststoimport']) . ">" . $n . ' ' . __('added posts.','autoblogtext') . "</option>";
		}
		echo "</select>" . $this->_tips->add_tip( __('You can set this to only import a specific number of new posts rather than as many as the plugin can manage.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

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
		echo "</select>" . $this->_tips->add_tip(  __('Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		// New fields

		$startfrom = (isset($table['startfrom'])) ? $table['startfrom'] : '';

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
		echo $this->_tips->add_tip(  __('Set the date you want to start processing posts from.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		$endon = (isset($table['endon'])) ? $table['endon'] : '';

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
		echo $this->_tips->add_tip(  __('Set the date you want to stop processing posts from this feed.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Force SSL verification','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[forcessl]' class='field'>";
		echo "<option value='yes'";
		selected( 'yes', $table['forcessl']);
		echo ">" . __('Yes','autoblogtext') . "</option>";
		echo "<option value='no'";
		selected( 'no', $table['forcessl']);
		echo ">" . __('No','autoblogtext') . "</option>";
		echo "</select>&nbsp;";
		echo $this->_tips->add_tip(  __('If you are getting SSL errors, or your feed uses a self-signed SSL certificate then set this to <strong>No</strong>.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";



		do_action( 'autoblog_feed_edit_form_end', $key, $details );

		echo "</table>\n";

		echo '<div class="tablenav">';
		echo '<div class="alignright">';
		echo "<a href='admin.php?page=autoblog_admin'>";
		echo __('Cancel', 'autoblogtext');
		echo "</a>";
		echo "&nbsp;";
		echo "&nbsp;";
		echo "&nbsp;";
		echo '<input class="button-primary delete save" type="submit" name="save" value="' . __('Update feed', 'autoblogtext') . '" style="margin-right: 10px;" />';
		echo '</div>';
		echo '</div>';

		echo '</div> <!-- inside -->';

		echo '</div> <!-- postbox -->';

	}

	function show_table_template($key = '') {

		global $blog_id;

		if(empty($key)) {
			echo '<div class="postbox blanktable autoblogeditbox" id="blanktable" style="display: none;">';
		} else {
			echo '<div class="postbox autoblogeditbox" id="ab-' . $key . '">';
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
		echo "<input type='text' name='abtble[title]' value='' class='long title field' />" . $this->_tips->add_tip( __('Enter a memorable title.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		// URL
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Feed URL','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[url]' value='' class='long url field' />" . $this->_tips->add_tip( __('Enter the feed URL.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr><td colspan='2'>&nbsp;</td></tr>";


		// Blogs
		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_network_admin') && is_network_admin()) {
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
				echo "</select>" . $this->_tips->add_tip( __('Select a blog to add the post to.','autoblogtext') );

				echo "</td>";
				echo "</tr>";
			} else {
				echo "<tr>";
				echo "<td valign='top' class='heading'>";
				echo __('Add posts to','autoblogtext');
				echo "</td>";
				echo "<td valign='top' class=''>";
				if(function_exists('get_blog_option')) {
					echo "<strong>" . esc_html(get_blog_option( $blog_id, 'blogname' )) . "</strong>";
				} else {
					echo "<strong>" . esc_html(get_option( 'blogname' )) . "</strong>";
				}
				echo "<input type='hidden' name='abtble[blog]' value='" . $blog_id . "' />";
				echo "</td>";
				echo "</tr>";
			}
		} else {
			echo "<tr>";
			echo "<td valign='top' class='heading'>";
			echo __('Add posts to','autoblogtext');
			echo "</td>";
			echo "<td valign='top' class=''>";
			if(function_exists('get_blog_option')) {
				echo "<strong>" . esc_html(get_blog_option( $blog_id, 'blogname' )) . "</strong>";
			} else {
				echo "<strong>" . esc_html(get_option( 'blogname' )) . "</strong>";
			}
			echo "<input type='hidden' name='abtble[blog]' value='" . $blog_id . "' />";
			echo "</td>";
			echo "</tr>";

		}

		// Post type
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Post type for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		$output = 'objects'; // names or objects
		$post_types = get_post_types( '' , $output );

		echo "<select name='abtble[posttype]' class='field'>";
		foreach ($post_types as $key => $post_type ) {
			echo "<option value='" . $key . "'";
			if( isset($table['posttype']) && $table['posttype'] == $key ) {
				echo " selected='selected'";
			}
			echo ">" . $post_type->name . "</option>";
		}
		echo "</select>" . $this->_tips->add_tip( __('Select the post type the imported posts will have in the blog.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		// Status
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Default status for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[poststatus]' class='field'>";
		echo "<option value='publish'>" . __('Published', 'autoblogtext') . "</option>";
		echo "<option value='pending'>" . __('Pending Review', 'autoblogtext') . "</option>";
		echo "<option value='draft'>" . __('Draft', 'autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip( __('Select the status the imported posts will have in the blog.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		// Post dates
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set the date for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[postdate]' class='field'>";
		echo "<option value='current'>" . __('Imported date','autoblogtext') . "</option>";
		echo "<option value='existing'>" . __('Original posts date','autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip( __('Select the date imported posts will have.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		do_action( 'autoblog_feed_edit_form_details_end', $key, '' );

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Author details','autoblogtext') . "</span></td></tr>";

		$blogusers = get_users( 'blog_id=' . $blog_id ); //get_users_of_blog( $blog_id );

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Set author for new posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		echo "<select name='abtble[author]' class='field author'>";
		echo "<option value='0'>" . __('Use feed author','autoblogtext') . "</option>";

		if(!empty($blogusers)) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->ID . "'"; echo ">";
				if(method_exists( $bloguser, 'get' )) {
					echo $bloguser->get('user_login');
				} elseif(isset($bloguser->user_login)) {
					echo $bloguser->user_login;
				}
				echo "</option>";
			}
		}
		echo "</select>" . $this->_tips->add_tip( __('Select the author you want to use for the posts, or attempt to use the original feed author.','autoblogtext') );

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
		if(!empty($blogusers)) {
			foreach($blogusers as $bloguser) {
				echo "<option value='" . $bloguser->ID . "'"; echo ">";
				if(method_exists( $bloguser, 'get' )) {
					echo $bloguser->get('user_login');
				} elseif(isset($bloguser->user_login)) {
					echo $bloguser->user_login;
				}
				echo "</option>";
			}
		}
		echo "</select>" . $this->_tips->add_tip( __('If the feed author does not exist in your blog then use this author.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Categories and Tags','autoblogtext') . "</span></td></tr>";

		//
		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Assign posts to this category','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";

		wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'abtble[category]', 'orderby' => 'name', 'selected' => '', 'hierarchical' => true, 'show_option_none' => __('None'), 'class' => 'field cat'));
		echo "" . $this->_tips->add_tip( __('Assign this category to the imported posts.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Treat feed categories as','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[feedcatsare]'>";
		echo "<option value='tags'>" . __('tags', 'autoblogtext') . "</option>";
		echo "<option value='categories'>" . __('categories', 'autoblogtext') . "</option>";
		echo "</select>";
		echo "&nbsp;<input type='checkbox' name='abtble[originalcategories]' class='case field' value='1' ";
		echo "/>&nbsp;<span>" . __('Add any that do not exist.','autoblogtext') . "</span>" . $this->_tips->add_tip( __('Create any tags or categories that are needed.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Add these tags to the posts','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[tag]' value='' class='long tag field' />" . $this->_tips->add_tip( __('Enter a comma separated list of tags to add.','autoblogtext') );

		echo "</td>";
		echo "</tr>";

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Post Filtering','autoblogtext') . "</span></td></tr>";
		echo "<tr><td colspan='2'><p>" . __('Include posts that contain (separate words with commas)','autoblogtext') . "</p></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('All of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[allwords]' value='' class='long title field' />" . $this->_tips->add_tip( __('A post to be imported must have ALL of these words in the title or content.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anywords]' value='' class='long title field' />" . $this->_tips->add_tip( __('A post to be imported must have ANY of these words in the title or content.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('The exact phrase','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[phrase]' value='' class='long title field' />" . $this->_tips->add_tip( __('A post to be imported must have this exact phrase in the title or content.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('None of these words','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[nonewords]' value='' class='long title field' />" . $this->_tips->add_tip( __('A post to be imported must NOT have any of these words in the title or content.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Any of these tags','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[anytags]' value='' class='long title field' />" . $this->_tips->add_tip( __('A post to be imported must be marked with any of these categories or tags.','autoblogtext') );
		echo "<br/>";
		echo "<span>" . __('Tags should be comma separated','autoblogtext') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Post excerpts','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Use full post or an excerpt','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[useexcerpt]' class='field'>";
		echo "<option value='1'>" . __('Use Full Post','autoblogtext') . "</option>";
		echo "<option value='2'>" . __('Use Excerpt','autoblogtext') . "</option>";
		echo "</select>" . $this->_tips->add_tip( __('Use the full post (if available) or create an excerpt.','autoblogtext') );
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
		echo "</select>" . $this->_tips->add_tip( __('Specify the size of the excerpt to create (if selected)','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Link to original source','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='abtble[source]' value='' class='long source field' />" . $this->_tips->add_tip( __('If you want to link back to original source, enter a phrase to use here.','autoblogtext') );
		echo "<br/>";
		echo "<input type='checkbox' name='abtble[nofollow]' value='1' />&nbsp;<span>" . __('Ensure this link is a nofollow one','autoblogtext') . "</span>";

		echo "</td>";
		echo "</tr>";


		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Feed Processing','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Import the most recent','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[poststoimport]' class='field'>";
		echo "<option value='0'>" . __('posts.','autoblogtext') . "</option>";
		for($n=1; $n <= 100; $n++) {
			echo "<option value='" . $n . "'>" . $n . ' ' . __('added posts.','autoblogtext') . "</option>";
		}
		echo "</select>" . $this->_tips->add_tip( __('You can set this to only import a specific number of new posts rather than as many as the plugin can manage.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

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
		echo "</select>" . $this->_tips->add_tip( __('Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.','autoblogtext') );
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
		echo "" . $this->_tips->add_tip( __('Set the date you want to start processing posts from.','autoblogtext') );
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
		echo "" . $this->_tips->add_tip( __('Set the date you want to stop processing posts from this feed.','autoblogtext') );
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Force SSL verification','autoblogtext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<select name='abtble[forcessl]' class='field'>";
		echo "<option value='yes'";
		echo ">" . __('Yes','autoblogtext') . "</option>";
		echo "<option value='no'";
		echo ">" . __('No','autoblogtext') . "</option>";
		echo "</select>&nbsp;";
		echo $this->_tips->add_tip(  __('If you are getting SSL errors, or your feed uses a self-signed SSL certificate then set this to <strong>No</strong>.', 'autoblogtext') );
		echo "</td>";
		echo "</tr>\n";

		do_action( 'autoblog_feed_edit_form_end', $key, '' );

		echo "</table>";

		echo '<div class="tablenav">';
		echo '<div class="alignright">';
		echo "<a href='admin.php?page=autoblog_admin'>";
		echo __('Cancel', 'autoblogtext');
		echo "</a>";
		echo "&nbsp;";
		echo "&nbsp;";
		echo "&nbsp;";
		echo '<input class="button-primary delete save" type="submit" name="savenew" value="' . __('Add feed', 'autoblogtext') . '" />';
		echo '</div>';
		echo '</div>';

		echo '</div>';

		echo '</div>';

	}

	function get_autoblogentries() {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('autoblog/autoblogpremium.php') && is_network_admin()) {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") ORDER BY nextcheck ASC", '' );
			} else {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND blog_id IN (" . implode(',', $blogs) . ") ORDER BY nextcheck ASC", '' );
			}
		} else {
			$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND blog_id IN (" . implode(',', $blogs) . ") ORDER BY nextcheck ASC", '' );
		}

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

		if(function_exists('is_multisite') && is_multisite()) {
			if(function_exists('is_network_admin') && is_network_admin()) {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id = %d ORDER BY feed_id ASC", $id );
			} else {
				$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id = %d AND blog_id  IN (" . implode(',', $blogs) . ") ORDER BY feed_id ASC", $id );
			}
		} else {
			$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id = %d AND blog_id  IN (" . implode(',', $blogs) . ") ORDER BY feed_id ASC", $id );
		}

		$results = $this->db->get_row($sql);

		return $results;

	}

	function deletefeed($id) {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		$sql = $this->db->prepare( "DELETE FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id = %d", $id);

		return $this->db->query($sql);

	}

	function processfeed($id) {
		//echo $id . "-";
		return true;
	}

	function deletefeeds($ids) {

		if(defined('AUTOBLOG_LAZY_ID') && AUTOBLOG_LAZY_ID == true) {
			$sites = array( $this->siteid, 0 );
			$blogs = array( $this->blogid, 0 );
		} else {
			$sites = array( $this->siteid );
			$blogs = array( $this->blogid );
		}

		$sql = $this->db->prepare( "DELETE FROM {$this->autoblog} WHERE site_id IN (" . implode(',', $sites) . ") AND feed_id IN (0, " . implode(',', $ids) . ")");

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

		$this->show_table_template($stamp);

		echo '</form>';

		echo "</div>";

	}

	function handle_edit_page($id) {

		global $action, $page;

		$feed = $this->get_autoblogentry($id);

		echo "<div class='wrap'>";

		// Show the heading
		echo '<div class="icon32" id="icon-edit"><br/></div>';
		echo "<h2>" . __('Auto Blog Feeds','autoblogtext') . '<a class="add-new-h2" href="admin.php?page=' . $page . '&action=add">' . __('Add New','membership') . '</a></h2>';

		echo "<br/>";

		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='action' value='autoblog' />";
		echo "<input type='hidden' name='feed_id' value='" . $feed->feed_id . "' />";

		wp_nonce_field( 'autoblog' );

		$this->show_table($id, $feed);

		echo '</form>';

		echo "</div>";

	}

	function update_admin_page() {

		global $action, $page;

		wp_reset_vars( array('action', 'page'));

		if(!empty($action) && $action == 'autoblog') {

			check_admin_referer('autoblog');


			//save
			if(!empty($_POST['savenew'])) {
				// Adding a new feed

				$feed = array();
				$feed['lastupdated'] = 0;

				if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
					$feed['nextcheck'] = current_time('timestamp') + (intval($_POST['abtble']['processfeed']) * 60);
				} else {
					$feed['nextcheck'] = 0;
				}

				$feed['site_id'] = $this->siteid;
				$feed['blog_id'] = (int) $_POST['abtble']['blog'];


				if(!empty($_POST['abtble']['startfromday']) && !empty($_POST['abtble']['startfrommonth']) && !empty($_POST['abtble']['startfromyear'])) {
					$_POST['abtble']['startfrom'] = strtotime($_POST['abtble']['startfromyear'] . '-' . $_POST['abtble']['startfrommonth'] . '-' . $_POST['abtble']['startfromday']);
				}

				if(!empty($_POST['abtble']['endonday']) && !empty($_POST['abtble']['endonmonth']) && !empty($_POST['abtble']['endonyear'])) {
					$_POST['abtble']['endon'] = strtotime($_POST['abtble']['endonyear'] . '-' . $_POST['abtble']['endonmonth'] . '-' . $_POST['abtble']['endonday']);
				}
				$feed['feed_meta'] = serialize($_POST['abtble']);

				$id = $this->db->insert($this->autoblog, $feed);
				if(!is_wp_error($id)) {
					wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
				} else {
					wp_safe_redirect( add_query_arg( 'err', 1, 'admin.php?page=' . $page ) );
				}

			}

			if(!empty($_POST['save'])) {
				// Saving a feed
				$feed = array();
				if(isset($_POST['abtble']['processfeed']) && is_numeric($_POST['abtble']['processfeed']) && intval($_POST['abtble']['processfeed']) > 0) {
					$feed['nextcheck'] = current_time('timestamp') + (intval($_POST['abtble']['processfeed']) * 60);
				} else {
					$feed['nextcheck'] = 0;
				}

				$feed['site_id'] = $this->siteid;
				$feed['blog_id'] = (int) $_POST['abtble']['blog'];

				if(!empty($_POST['abtble']['startfromday']) && !empty($_POST['abtble']['startfrommonth']) && !empty($_POST['abtble']['startfromyear'])) {
					$_POST['abtble']['startfrom'] = strtotime($_POST['abtble']['startfromyear'] . '-' . $_POST['abtble']['startfrommonth'] . '-' . $_POST['abtble']['startfromday']);
				}

				if(!empty($_POST['abtble']['endonday']) && !empty($_POST['abtble']['endonmonth']) && !empty($_POST['abtble']['endonyear'])) {
					$_POST['abtble']['endon'] = strtotime($_POST['abtble']['endonyear'] . '-' . $_POST['abtble']['endonmonth'] . '-' . $_POST['abtble']['endonday']);
				}
				$feed['feed_meta'] = serialize($_POST['abtble']);

				$id = $this->db->update($this->autoblog, $feed, array( "feed_id" => mysql_real_escape_string($_POST['feed_id'])) );
				if( !is_wp_error($id) ) {
					wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
				} else {
					wp_safe_redirect( add_query_arg( 'err', 2, 'admin.php?page=' . $page ) );
				}

			}

			if(isset($_POST['doaction'])) {
				switch($_POST['bulkaction']) {
					case 'process':	check_admin_referer('autoblog');
									if(!empty($_POST['select'])) {
										$toprocess = array();
										foreach($_POST['select'] as $key => $value) {
											$toprocess[] = mysql_real_escape_string($value);
										}

										if(ab_process_feeds($toprocess)) {
											wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'err', 3, 'admin.php?page=' . $page ) );
										}

									} else {
										wp_safe_redirect( add_query_arg( 'err', 6, 'admin.php?page=' . $page ) );
									}
									break;

					case 'delete':	check_admin_referer('autoblog');
									if(!empty($_POST['select'])) {
										$todelete = array();
										foreach($_POST['select'] as $key => $value) {
											$todelete[] = mysql_real_escape_string($value);
										}
										if($this->deletefeeds($todelete)) {
											wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'err', 3, 'admin.php?page=' . $page ) );
										}

									} else {
										wp_safe_redirect( add_query_arg( 'err', 5, 'admin.php?page=' . $page ) );
									}
									break;
				}
			}

			if(isset($_POST['doaction2'])) {
				switch($_POST['bulkaction2']) {
					case 'process':	check_admin_referer('autoblog');
									if(!empty($_POST['select'])) {
										$toprocess = array();
										foreach($_POST['select'] as $key => $value) {
											$toprocess[] = mysql_real_escape_string($value);
										}

										if(ab_process_feeds($toprocess)) {
											wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'err', 3, 'admin.php?page=' . $page ) );
										}

									} else {
										wp_safe_redirect( add_query_arg( 'err', 6, 'admin.php?page=' . $page ) );
									}
									break;

					case 'delete':	check_admin_referer('autoblog');
									if(!empty($_POST['select'])) {
										$todelete = array();
										foreach($_POST['select'] as $key => $value) {
											$todelete[] = mysql_real_escape_string($value);
										}
										if($this->deletefeeds($todelete)) {
											wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'err', 3, 'admin.php?page=' . $page ) );
										}

									} else {
										wp_safe_redirect( add_query_arg( 'err', 5, 'admin.php?page=' . $page ) );
									}
									break;
				}
			}

		} else {
			// Edit a feed

			// Delete a feed
			if(isset($_GET['delete']) && is_numeric(addslashes($_GET['delete']))) {
				check_admin_referer('autoblogdelete');
				if($this->deletefeed(addslashes($_GET['delete']))) {
					wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
				} else {
					wp_safe_redirect( add_query_arg( 'err', 3, 'admin.php?page=' . $page ) );
				}
			}
			// Process a feed
			if(isset($_GET['process']) && is_numeric(addslashes($_GET['process']))) {
				check_admin_referer('autoblogprocess');

				$feed = $this->get_autoblogentry(addslashes($_GET['process']));

				if(!empty($feed->feed_meta)) {
					$details = unserialize($feed->feed_meta);
					if(ab_process_feed($feed->feed_id, $details)) {
						wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
					} else {
						wp_safe_redirect( add_query_arg( 'err', 8, 'admin.php?page=' . $page ) );
					}
				}

			}

			// Test feeds
			if(isset($_GET['test']) && is_numeric(addslashes($_GET['test']))) {
				check_admin_referer('autoblogtest');

				$feed = $this->get_autoblogentry(addslashes($_GET['test']));

				if(!empty($feed->feed_meta)) {
					$details = unserialize($feed->feed_meta);

					if(ab_test_feed($feed->feed_id, $details)) {
						wp_safe_redirect( add_query_arg( 'msg', 7, 'admin.php?page=' . $page ) );
					} else {
						wp_safe_redirect( add_query_arg( 'err', 7, 'admin.php?page=' . $page ) );
					}

				}
			}
		}

	}

	function handle_admin_page() {

		global $action, $page;

		// Handle the editing and adding pages
		if(isset($_GET['action']) && $_GET['action'] == 'add') {
			// We are adding a new feed
			$this->handle_addnew_page();
			return;
		}

		$showlist = true;

		$current_offset = get_option('gmt_offset');
		$timezone_format = _x('Y-m-d G:i:s', 'timezone date format');

		$messages = array();
		$messages[1] = __('Your feed has been added.','autoblogtext');
		$messages[2] = __('Your feed has been updated.','autoblogtext');
		$messages[3] = __('Your feed(s) have been deleted.','autoblogtext');
		$messages[4] = __('Your feed(s) has been processed.','autoblogtext');

		$messages[7] = __('Your feed has been tested.','autoblogtext');
		$messages[8] = __('Your feed(s) has been processed.','autoblogtext');

		$errors = array();
		$errors[1] = __('Your feed could not be added.','autoblogtext');
		$errors[2] = __('Your feed could not be updated.','autoblogtext');
		$errors[3] = __('Your feed(s) could not be deleted.','autoblogtext');
		$errors[4] = __('Your feed(s) could not be processed.','autoblogtext');

		$errors[5] = __('Please select a feed to delete.','autoblogtext');
		$errors[6] = __('Please select a feed to process.','autoblogtext');

		$errors[7] = __('Your feed could not be tested.','autoblogtext');
		$errors[8] = __('No new entries in your feed(s).','autoblogtext');


		if(isset($_GET['edit']) && is_numeric(addslashes($_GET['edit']))) {
			$this->handle_edit_page(addslashes($_GET['edit']));
			return;
		}

		// If we are still here then we are wanting to view the list
		echo "<div class='wrap'>";

		// Show the heading
		echo '<div class="icon32" id="icon-edit"><br/></div>';
		echo "<h2>" . __('Auto Blog Feeds','autoblogtext') . '<a class="add-new-h2" href="admin.php?page=' . $page . '&action=add">' . __('Add New','membership') . '</a></h2>';

		echo "<br/>";

		if ( isset($_GET['msg']) ) {
			echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}

		if ( isset($_GET['err']) ) {
			echo '<div id="message" class="error fade"><p>' . $errors[(int) $_GET['err']] . '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}

		$testlog = get_autoblog_option('autoblog_last_test_log', false);

		if(!empty($testlog) && $testlog !== false && isset($_GET['msg']) && (int) $_GET['msg'] == 7) {
			echo '<div id="testmessage" class="updated fade"><p>';
			echo implode( '<br/>', $testlog['log'] );
			echo '</p></div>';
		}

		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='action' value='autoblog' />";

		wp_nonce_field( 'autoblog' );

		echo '<div class="tablenav">';
		echo '<div class="alignleft actions">';
		?>
			<select name="bulkaction">
			<option selected="selected" value=""><?php _e('Bulk Actions', 'popover'); ?></option>
			<option value="process"><?php _e('Process', 'autoblogtext'); ?></option>
			<option value="delete"><?php _e('Delete', 'autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply', 'popover'); ?>">
		<?php
		echo '</div>';

		echo '<div class="alignright actions">';
		echo '</div>';

		echo '</div>';

		// New table based layout
		echo '<table width="100%" cellpadding="3" cellspacing="3" class="widefat" style="width: 100%;">';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column column-cb check-column">';
		echo "<input type='checkbox' />";
		echo '</th>';
		echo '<th scope="col">';
		echo __('Feed title','autoblogtext');
		echo '</th>';

		echo '<th scope="col">';
		echo __('Feed post type','autoblogtext');
		echo '</th>';

		if(function_exists('is_network_admin') && is_network_admin()) {
			echo '<th scope="col">';
			echo __('Feed target','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed *','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check *','autoblogtext');
			echo '</th>';
		} else {
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check','autoblogtext');
			echo '</th>';
		}

		do_action('autoblog_admin_columns');
		echo '</tr>';
		echo '</thead>';

		echo '<tfoot>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column column-cb check-column">';
		echo "<input type='checkbox'/>";
		echo '</th>';
		echo '<th scope="col">';
		echo __('Feed title','autoblogtext');
		echo '</th>';

		echo '<th scope="col">';
		echo __('Feed post type','autoblogtext');
		echo '</th>';

		if(function_exists('is_network_admin') && is_network_admin()) {
			echo '<th scope="col">';
			echo __('Feed target','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed *','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check *','autoblogtext');
			echo '</th>';
		} else {
			echo '<th scope="col" style="text-align: right;">';
			echo __('Last processed','autoblogtext');
			echo '</th>';
			echo '<th scope="col" style="text-align: right;">';
			echo __('Next check','autoblogtext');
			echo '</th>';
		}

		do_action('autoblog_admin_columns');
		echo '</tr>';
		echo '</tfoot>';

		echo '<tbody id="the-list">';

		$autoblogs = $this->get_autoblogentries();

		if(!empty($autoblogs)) {

			foreach($autoblogs as $key => $table) {

				$details = maybe_unserialize($table->feed_meta);
				//$this->show_table($key, $table);
				echo '<tr>';
				?>
				<td class="check-column" scope="row"><input type="checkbox" value="<?php echo $table->feed_id; ?>" name="select[]"></td>
				<?php
				echo '<td>';
				if(function_exists('is_network_admin') && is_network_admin() ) {
					echo '<a href="' . network_admin_url("admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . '">';
				} else {
					echo '<a href="' . admin_url("admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . '">';
				}

				if(!empty($details)) {
					echo esc_html(stripslashes($details['title']));
				} else {
					echo __('No title set', 'autoblogtext');
				}
				echo '</a>';

				//network_admin_url
				echo '<div class="row-actions">';
				$actions = array();
				if(function_exists('is_network_admin') && is_network_admin() ) {
					$actions[] = "<a href='" . network_admin_url("admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . "' class='editfeed'>" . __('Edit', 'autoblogtext') . "</a>";
					$actions[] = "<a href='" . wp_nonce_url(network_admin_url("admin.php?page=autoblog_admin&amp;delete=" . $table->feed_id), 'autoblogdelete') . "' class='deletefeed'>" . __('Delete', 'autoblogtext') . "</a>";
					$actions[] = "<a href='" . wp_nonce_url(network_admin_url("admin.php?page=autoblog_admin&amp;process=" . $table->feed_id), 'autoblogprocess') . "' class='processfeed'>" . __('Process', 'autoblogtext') . "</a>";

					$actions[] = "<a href='" . wp_nonce_url(network_admin_url("admin.php?page=autoblog_admin&amp;test=" . $table->feed_id), 'autoblogtest') . "' class='testfeed'>" . __('Test', 'autoblogtext') . "</a>";
					$actions = apply_filters( 'autoblog_networkadmin_actions', $actions, $table->feed_id );
				} else {
					$actions[] = "<a href='" . admin_url("admin.php?page=autoblog_admin&amp;edit=" . $table->feed_id) . "' class='editfeed'>" . __('Edit', 'autoblogtext') . "</a>";
					$actions[] = "<a href='" . wp_nonce_url(admin_url("admin.php?page=autoblog_admin&amp;delete=" . $table->feed_id), 'autoblogdelete') . "' class='deletefeed'>" . __('Delete', 'autoblogtext') . "</a>";
					$actions[] = "<a href='" . wp_nonce_url(admin_url("admin.php?page=autoblog_admin&amp;process=" . $table->feed_id), 'autoblogprocess') . "' class='processfeed'>" . __('Process', 'autoblogtext') . "</a>";

					$actions[] = "<a href='" . wp_nonce_url(admin_url("admin.php?page=autoblog_admin&amp;test=" . $table->feed_id), 'autoblogtest') . "' class='testfeed'>" . __('Test', 'autoblogtext') . "</a>";
					$actions = apply_filters( 'autoblog_networkadmin_actions', $actions, $table->feed_id );
				}

				echo implode(' | ', $actions);

				echo '</div>';

				echo '</td>';

				echo "<td>";
				echo $details['posttype'];
				echo "</td>";

				if(function_exists('is_network_admin') && is_network_admin()) {
					echo "<td>";
					echo esc_html(get_blog_option( $table->blog_id, 'blogname' ));
					echo "</td>";
				}


				echo '<td style="text-align: right;">';

				if($table->lastupdated != 0) {
					echo "<abbr title='" . date_i18n($timezone_format, $table->lastupdated) . "'>";
					echo autoblog_time2str( $table->lastupdated );
					echo "</abbr>";
					//echo date_i18n($timezone_format, $table->lastupdated);
					//echo date("j M Y : H:i", $table->lastupdated);
				} else {
					echo __('Never', 'autoblogtext');
				}

				echo '</td>';
				echo '<td style="text-align: right;">';

				if($table->nextcheck != 0) {

					echo "<abbr title='" . date_i18n($timezone_format, $table->nextcheck) . "'>";
					echo autoblog_time2str($table->nextcheck );
					echo "</abbr>";


				} else {
					echo __('Never', 'autoblogtext');
				}

				echo '</td>';

				do_action('autoblog_admin_columns_data', $table);

				echo '</tr>';

			}

		} else {

			echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td colspan="5">';
			echo __('You do not have any feeds setup - please click Add New to get started','autoblogtext');
			echo '</td>';
			echo '</tr>';

		}

		echo '</tbody>';

		echo '</table>';

		echo '<div class="tablenav">';
		echo '<div class="alignleft actions">';
		?>
			<select name="bulkaction2">
			<option selected="selected" value=""><?php _e('Bulk Actions', 'popover'); ?></option>
			<option value="process"><?php _e('Process', 'autoblogtext'); ?></option>
			<option value="delete"><?php _e('Delete', 'autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply', 'popover'); ?>">
		<?php
		echo '</div>';

		echo '<div class="alignright actions">';
		echo '</div>';

		echo '</div>';

		echo "</form>";

		if(function_exists('is_network_admin') && is_network_admin()) {
			echo "<p>" . __('* Times and dates are local to each site.', 'autoblogtext') . "</p>";

		}

		echo "</div>";	// wrap

	}

	function handle_settings_page() {

		global $action, $page;

		$messages = array();
		$messages[1] = __('Your options have been updated.','autoblogtext');

		?>
		<div class='wrap nosubsub'>

			<?php /*
			<h3 class="nav-tab-wrapper">
				<a href="admin.php?page=branding&amp;tab=dashboard" class="nav-tab nav-tab-active"><?php _e('General','autoblogtext'); ?></a>
				<a href="admin.php?page=branding&amp;tab=images" class="nav-tab"><?php _e('Time Limits','autoblogtext'); ?></a>
			</h3>
			*/
			?>

			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e('Autoblog Settings','autoblogtext'); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}
			?>
			<div id="poststuff" class="metabox-holder m-settings">
			<form action='?page=<?php echo $page; ?>' method='post'>

				<input type='hidden' name='page' value='<?php echo $page; ?>' />
				<input type='hidden' name='action' value='updatesettings' />

				<?php
					wp_nonce_field('update-autoblog-settings');
				?>

				<div class="postbox">
					<h3 class="hndle" style='cursor:auto;'><span><?php _e('Debug mode','autoblog'); ?></span></h3>
					<div class="inside">
						<p><?php _e('Switch on debug mode and reporting.','autoblogtext'); ?></p>

						<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row"><?php _e('Debug mode is','autoblogtext'); ?></th>
								<td>
									<?php
										$debug = get_site_option('autoblog_debug', false);
									?>
									<select name='debugmode' id='debugmode'>
										<option value="no" <?php if($debug == false) echo "selected='selected'"; ?>><?php _e('Disabled','autoblogtext'); ?></option>
										<option value="yes" <?php if($debug == true) echo "selected='selected'"; ?>><?php _e('Enabled','autoblogtext'); ?></option>
									</select>
								</td>
							</tr>
						</tbody>
						</table>
					</div>
				</div>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'autoblogtext') ?>" />
				</p>

			</form>
			</div>
		</div> <!-- wrap -->
		<?php
	}

	function handle_addons_panel_updates() {
		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		if(function_exists('is_network_admin') && is_network_admin()) {
			$this->handle_networkaddons_panel_updates();
		} else {
			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			$active = get_option('autoblog_activated_addons', array());

			switch(addslashes($action)) {

				case 'deactivate':	$key = addslashes($_GET['addon']);
									if(!empty($key)) {
										check_admin_referer('toggle-addon-' . $key);

										$found = array_search($key, $active);
										if($found !== false) {
											unset($active[$found]);
											update_option('autoblog_activated_addons', array_unique($active));
											wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
										}
									}
									break;

				case 'activate':	$key = addslashes($_GET['addon']);
									if(!empty($key)) {
										check_admin_referer('toggle-addon-' . $key);

										if(!in_array($key, $active)) {
											$active[] = $key;
											update_option('autoblog_activated_addons', array_unique($active));
											wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
										}
									}
									break;

				case 'bulk-toggle':
									check_admin_referer('bulk-addons');
									foreach($_GET['addoncheck'] AS $key) {
										$found = array_search($key, $active);
										if($found !== false) {
											unset($active[$found]);
										} else {
											$active[] = $key;
										}
									}
									update_option('autoblog_activated_addons', array_unique($active));
									wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									break;

			}
		}

	}

	function handle_networkaddons_panel_updates() {
		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
			if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
				$action = 'bulk-toggle';
			}
		}

		$active = get_blog_option(1, 'autoblog_networkactivated_addons', array());

		switch(addslashes($action)) {

			case 'deactivate':	$key = addslashes($_GET['addon']);
								if(!empty($key)) {
									check_admin_referer('toggle-addon-' . $key);

									$found = array_search($key, $active);
									if($found !== false) {
										unset($active[$found]);
										update_blog_option(1, 'autoblog_networkactivated_addons', array_unique($active));
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}
								}
								break;

			case 'activate':	$key = addslashes($_GET['addon']);
								if(!empty($key)) {
									check_admin_referer('toggle-addon-' . $key);

									if(!in_array($key, $active)) {
										$active[] = $key;
										update_blog_option(1, 'autoblog_networkactivated_addons', array_unique($active));
										wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
									}
								}
								break;

			case 'bulk-toggle':
								check_admin_referer('bulk-addons');
								foreach($_GET['addoncheck'] AS $key) {
									$found = array_search($key, $active);
									if($found !== false) {
										unset($active[$found]);
									} else {
										$active[] = $key;
									}
								}
								update_blog_option(1, 'autoblog_networkactivated_addons', array_unique($active));
								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

		}
	}

	function handle_addons_panel() {
		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		$messages = array();
		$messages[1] = __('Add-on updated.','autoblogtext');
		$messages[2] = __('Add-on not updated.','autoblogtext');

		$messages[3] = __('Add-on activated.','autoblogtext');
		$messages[4] = __('Add-on not activated.','autoblogtext');

		$messages[5] = __('Add-on deactivated.','autoblogtext');
		$messages[6] = __('Add-on not deactivated.','autoblogtext');

		$messages[7] = __('Add-on activation toggled.','autoblogtext');

		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php _e('Edit Add-ons','autoblogtext'); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}

			?>

			<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

			<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

			<div class="tablenav">

			<div class="alignleft actions">
			<select name="action">
			<option selected="selected" value=""><?php _e('Bulk Actions','autoblogtext'); ?></option>
			<option value="toggle"><?php _e('Toggle activation','autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply','autoblogtext'); ?>">

			</div>

			<div class="alignright actions"></div>

			<br class="clear">
			</div>

			<div class="clear"></div>

			<?php
				wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-addons');

				$columns = array(	"name"		=>	__('Add-on Name', 'autoblogtext'),
									"active"	=>	__('Active','autoblogtext')
								);

				$columns = apply_filters('autoblog_addoncolumns', $columns);

				$plugins = get_autoblog_addons();

				$active = get_option('autoblog_activated_addons', array());
				if(function_exists('get_blog_option')) {
					$networkactive = get_blog_option(1, 'autoblog_networkactivated_addons', array());
				} else {
					$networkactive = array();
				}


			?>

			<table cellspacing="0" class="widefat fixed">
				<thead>
				<tr>
				<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
				<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
				<?php
					reset($columns);
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</tfoot>

				<tbody>
					<?php
					if($plugins) {
						foreach($plugins as $key => $plugin) {
							$default_headers = array(
								                'Name' => 'Addon Name',
												'Author' => 'Author',
												'Description'	=>	'Description',
												'AuthorURI' => 'Author URI',
												'Network'	=>	'Network'
								        );

							$plugin_data = get_file_data( autoblog_dir('autoblogincludes/addons/' . $plugin), $default_headers, 'plugin' );

							if(empty($plugin_data['Name']) || (!empty($plugin_data['Name']) && $plugin_data['Network'] == 'True')) {
								continue;
							}

							if(in_array($plugin, $networkactive)) {
								continue;
							}

							?>
							<tr valign="middle" class="alternate" id="plugin-<?php echo $plugin; ?>">
								<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($plugin); ?>" name="addoncheck[]"></th>
								<td class="column-name">
									<strong><?php echo esc_html($plugin_data['Name']) . "</strong>"; ?>
									<?php if(!empty($plugin_data['Description'])) {
										?><br/><?php echo esc_html($plugin_data['Description']);
										}

										$actions = array();

										if(in_array($plugin, $active)) {
											$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=deactivate&amp;addon=" . $plugin . "", 'toggle-addon-' . $plugin) . "'>" . __('Deactivate','autoblogtext') . "</a></span>";
										} else {
											$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=activate&amp;addon=" . $plugin . "", 'toggle-addon-' . $plugin) . "'>" . __('Activate','autoblogtext') . "</a></span>";
										}
									?>
									<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
									</td>

								<td class="column-active">
									<?php
										if(in_array($plugin, $active)) {
											echo "<strong>" . __('Active', 'autoblogtext') . "</strong>";
										} else {
											echo __('Inactive', 'autoblogtext');
										}
									?>
								</td>
						    </tr>
							<?php
						}
					} else {
						$columncount = count($columns) + 1;
						?>
						<tr valign="middle" class="alternate" >
							<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Add-ons where found for this install.','autoblogtext'); ?></td>
					    </tr>
						<?php
					}
					?>

				</tbody>
			</table>


			<div class="tablenav">

			<div class="alignleft actions">
			<select name="action2">
				<option selected="selected" value=""><?php _e('Bulk Actions','autoblogtext'); ?></option>
				<option value="toggle"><?php _e('Toggle activation','autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
			</div>
			<div class="alignright actions"></div>
			<br class="clear">
			</div>

			</form>

		</div> <!-- wrap -->
		<?php
	}

	function handle_networkaddons_panel() {
		global $action, $page;

		wp_reset_vars( array('action', 'page') );

		$messages = array();
		$messages[1] = __('Add-on updated.','autoblogtext');
		$messages[2] = __('Add-on not updated.','autoblogtext');

		$messages[3] = __('Add-on activated.','autoblogtext');
		$messages[4] = __('Add-on not activated.','autoblogtext');

		$messages[5] = __('Add-on deactivated.','autoblogtext');
		$messages[6] = __('Add-on not deactivated.','autoblogtext');

		$messages[7] = __('Add-on activation toggled.','autoblogtext');

		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php _e('Edit Add-ons','autoblogtext'); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}

			?>

			<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

			<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

			<div class="tablenav">

			<div class="alignleft actions">
			<select name="action">
			<option selected="selected" value=""><?php _e('Bulk Actions','autoblogtext'); ?></option>
			<option value="toggle"><?php _e('Toggle activation','autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply','autoblogtext'); ?>">

			</div>

			<div class="alignright actions"></div>

			<br class="clear">
			</div>

			<div class="clear"></div>

			<?php
				wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-addons');

				$columns = array(	"name"		=>	__('Add-on Name', 'autoblogtext'),
									"active"	=>	__('Active','autoblogtext')
								);

				$columns = apply_filters('autoblog_addoncolumns', $columns);

				$plugins = get_autoblog_addons();

				$active = get_option('autoblog_networkactivated_addons', array());

			?>

			<table cellspacing="0" class="widefat fixed">
				<thead>
				<tr>
				<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
				<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
				<?php
					reset($columns);
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</tfoot>

				<tbody>
					<?php
					if($plugins) {
						foreach($plugins as $key => $plugin) {
							$default_headers = array(
								                'Name' => 'Addon Name',
												'Author' => 'Author',
												'Description'	=>	'Description',
												'AuthorURI' => 'Author URI',
												'Network'	=>	'Network'
								        );

							$plugin_data = get_file_data( autoblog_dir('autoblogincludes/addons/' . $plugin), $default_headers, 'plugin' );

							if(empty($plugin_data['Name'])) {
								continue;
							}

							?>
							<tr valign="middle" class="alternate" id="plugin-<?php echo $plugin; ?>">
								<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($plugin); ?>" name="addoncheck[]"></th>
								<td class="column-name">
									<strong><?php echo esc_html($plugin_data['Name']) . "</strong>"; ?>
									<?php if(!empty($plugin_data['Description'])) {
										?><br/><?php echo esc_html($plugin_data['Description']);
										}

										$actions = array();

										if(in_array($plugin, $active)) {
											$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=deactivate&amp;addon=" . $plugin . "", 'toggle-addon-' . $plugin) . "'>" . __('Network Deactivate','autoblogtext') . "</a></span>";
										} else {
											$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=activate&amp;addon=" . $plugin . "", 'toggle-addon-' . $plugin) . "'>" . __('Network Activate','autoblogtext') . "</a></span>";
										}
									?>
									<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
									</td>

								<td class="column-active">
									<?php
										if(in_array($plugin, $active)) {
											echo "<strong>" . __('Active', 'autoblogtext') . "</strong>";
										} else {
											echo __('Inactive', 'autoblogtext');
										}
									?>
								</td>
						    </tr>
							<?php
						}
					} else {
						$columncount = count($columns) + 1;
						?>
						<tr valign="middle" class="alternate" >
							<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Add-ons where found for this install.','autoblogtext'); ?></td>
					    </tr>
						<?php
					}
					?>

				</tbody>
			</table>


			<div class="tablenav">

			<div class="alignleft actions">
			<select name="action2">
				<option selected="selected" value=""><?php _e('Bulk Actions','autoblogtext'); ?></option>
				<option value="toggle"><?php _e('Toggle activation','autoblogtext'); ?></option>
			</select>
			<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
			</div>
			<div class="alignright actions"></div>
			<br class="clear">
			</div>

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