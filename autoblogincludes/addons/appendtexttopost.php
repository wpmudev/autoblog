<?php
/*
Addon Name: Append text to post
Description: Allows some text to be appended to each post with variable placeholders
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_appendtexttopost {

	var $build = 1;

	function __construct() {

		add_action( 'autoblog_feed_edit_form_end', array(&$this, 'add_footer_options'), 10, 2 );

	}

	function A_appendtexttopost() {
		$this->__construct();
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



}

$aappendtexttopost = new A_appendtexttopost();