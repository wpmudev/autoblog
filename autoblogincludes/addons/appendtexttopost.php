<?php
/*
Addon Name: Append text to post
Description: Allows some text to be appended to each post with variable placeholders
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_appendtexttopost {

	var $build = 1;

	var $db;

	var $tables = array('autoblog');
	var $autoblog;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		foreach($this->tables as $table) {
			$this->$table = autoblog_db_prefix($this->db, $table);
		}

		add_action( 'autoblog_feed_edit_form_end', array(&$this, 'add_footer_options'), 10, 2 );

		add_filter( 'the_content', array(&$this, 'append_footer_content'), 11, 1 );

	}

	function A_appendtexttopost() {
		$this->__construct();
	}

	function get_feed_details( $id ) {

		$sql = $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE feed_id = %d", $id );

		$results = $this->db->get_row($sql);

		return $results;

	}

	function add_footer_options( $key, $details ) {

		if(!empty($details)) {
			$table = maybe_unserialize($details->feed_meta);
		} else {
			$table = array();
		}

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __('Append text to post content','autoblogtext') . "</span></td></tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Post footer text','autoblogtext');
		echo "<br/><br/>";
		echo "<em>";

		echo __('You can use the following placeholders in your footer:','autoblogtext');
		echo "<br/><br/>";

		echo "%ORIGINALPOSTURL%<br/>";
		echo "%FEEDURL%<br/>";
		echo "%FEEDTITLE%<br/>";
		echo "%POSTIMPORTEDTIME%<br/>";
		echo "%FEEDID%<br/>";
		echo "%ORIGINALPOSTGUID%<br/>";
		echo "%ORIGINALAUTHORNAME%<br/>";
		echo "%ORIGINALAUTHORLINK%<br/>";
		echo "%ORIGINALAUTHOREMAIL%<br/>";

		echo "</em>";
		echo "</td>";
		echo "<td valign='top' class=''>";
		if(isset($table['footertext'])) {
			$footertext = $table['footertext'];
		} else {
			$footertext = '';
		}
		$args = array("textarea_name" => "abtble[footertext]", "textarea_rows" => 10);
		wp_editor( stripslashes($footertext), "abtble[footertext]", $args );

		echo "</td>";
		echo "</tr>\n";

	}

	function append_footer_content( $content ) {

		global $post;

		if( is_object($post) && !empty($post->ID) ) {

			// Get the feed id so we can get hold of the footer content
			$feed_id = get_post_meta( $post->ID, 'original_feed_id', true );

			if( !empty($feed_id) ) {
				// We have a feed id so the post was imported, so grab the footer content
				$feed = $this->get_feed_details( $feed_id );

				// Unserialise the feed_meta details
				if(!empty($feed)) {
					$table = maybe_unserialize($feed->feed_meta);
				} else {
					$table = array();
				}

				// If the footer isn't empty then we will use it
				if(!empty($table['footertext'])) {
					$footertext = $table['footertext'];

					// Do the search and replace of variables
					$footertext = str_replace( '%ORIGINALPOSTURL%', get_post_meta( $post->ID, 'original_source', true ), $footertext );
					$footertext = str_replace( '%FEEDURL%', get_post_meta( $post->ID, 'original_feed', true ), $footertext );
					$footertext = str_replace( '%FEEDTITLE%', get_post_meta( $post->ID, 'original_feed_title', true ), $footertext );
					$footertext = str_replace( '%POSTIMPORTEDTIME%', get_post_meta( $post->ID, 'original_imported_time', true ), $footertext );
					$footertext = str_replace( '%FEEDID%', get_post_meta( $post->ID, 'original_feed_id', true ), $footertext );
					$footertext = str_replace( '%ORIGINALPOSTGUID%', get_post_meta( $post->ID, 'original_guid', true ), $footertext );
					$footertext = str_replace( '%ORIGINALAUTHORNAME%', get_post_meta( $post->ID, 'original_author_name', true ), $footertext );
					$footertext = str_replace( '%ORIGINALAUTHOREMAIL%', get_post_meta( $post->ID, 'original_author_email', true ), $footertext );
					$footertext = str_replace( '%ORIGINALAUTHORLINK%', get_post_meta( $post->ID, 'original_author_link', true ), $footertext );

					// Add the footer to the bottom of the content
					$content .= $footertext;

				}

			}


		}

		return $content;
	}



}

$aappendtexttopost = new A_appendtexttopost();