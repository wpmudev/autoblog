<?php
/*
Addon Name: Post formats addon
Description: Allows a post format to be selected for a feed.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/
//get_post_format_strings

function A_show_post_formats( $key, $details ) {

	$table = maybe_unserialize($details->feed_meta);

	echo "<tr>";
	echo "<td valign='top' class='heading'>";
	echo __('Post format for new posts','autoblogtext');
	echo "</td>";
	echo "<td valign='top' class=''>";

	$formats = get_post_format_strings();

	echo "<select name='abtble[postformat]' class='field'>";
	foreach ($formats as $key => $format ) {
		echo "<option value='" . $key . "'";
		echo $table['postformat'] == $key ? " selected='selected'" : "";
		echo ">" . $format . "</option>";
	}
	echo "</select>" . "<a href='#' class='info' title='" . __('Select the post format the imported posts will have in the blog.', 'autoblogtext') . "'></a>";

	echo "</td>";
	echo "</tr>\n";


}

add_action( 'autoblog_feed_edit_form_details_end', 'A_show_post_formats', 10, 2 );

function A_insert_post_format( $post_ID, $ablog, $item ) {

	if(!empty($ablog['postformat'])) {
		set_post_format( $post_ID, $ablog['postformat'] );
	} else {
		set_post_format( $post_ID, 'standard' );
	}

}

add_action( 'autoblog_post_post_insert', 'A_insert_post_format', 10, 3 );

?>