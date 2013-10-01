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
	var $tables = array( 'autoblog' );
	var $autoblog;

	public function __construct() {
		global $wpdb;

		$this->db = $wpdb;
		foreach ( $this->tables as $table ) {
			$this->$table = autoblog_db_prefix( $this->db, $table );
		}

		add_action( 'autoblog_feed_edit_form_end', array( $this, 'add_footer_options' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'append_footer_content' ), 11, 1 );
	}

	public function get_feed_details( $id ) {
		return $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->autoblog} WHERE feed_id = %d", $id ) );
	}

	public function add_footer_options( $key, $details ) {
		$table = !empty( $details ) ? maybe_unserialize( $details->feed_meta ) : array();

		echo "<tr class='spacer'><td colspan='2' class='spacer'><span>" . __( 'Append text to post content', 'autoblogtext' ) . "</span></td></tr>";
		echo "<tr>";
			echo "<td valign='top' class='heading'>";
				echo __( 'Post footer text', 'autoblogtext' );
				echo "<br><br>";

				echo __( 'You can use the following placeholders in your footer:', 'autoblogtext' );
				echo "<br><br>";

				echo "<em>";
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
				wp_editor( isset( $table['footertext'] ) ? stripcslashes( $table['footertext'] ) : '', "abtble[footertext]", array(
					"textarea_name" => "abtble[footertext]",
					"textarea_rows" => 10,
				) );
			echo "</td>";
		echo "</tr>";
	}

	public function append_footer_content( $content ) {
		global $post;
		if ( !is_object( $post ) || empty( $post->ID ) ) {
			return $content;
		}

		// Get the feed id so we can get hold of the footer content
		$feed_id = get_post_meta( $post->ID, 'original_feed_id', true );
		if ( !empty( $feed_id ) ) {
			// We have a feed id so the post was imported, so grab the footer content
			$feed = $this->get_feed_details( $feed_id );
			$table = !empty( $feed ) ? maybe_unserialize( $feed->feed_meta ) : array();

			// If the footer isn't empty then we will use it
			if ( !empty( $table['footertext'] ) ) {
				$footertext = stripcslashes( $table['footertext'] );
				$keywords = array(
					'%ORIGINALPOSTURL%'     => 'original_source',
					'%FEEDURL%'             => 'original_feed',
					'%FEEDTITLE%'           => 'original_feed_title',
					'%POSTIMPORTEDTIME%'    => 'original_imported_time',
					'%FEEDID%'              => 'original_feed_id',
					'%ORIGINALPOSTGUID%'    => 'original_guid',
					'%ORIGINALAUTHORNAME%'  => 'original_author_name',
					'%ORIGINALAUTHOREMAIL%' => 'original_author_email',
					'%ORIGINALAUTHORLINK%'  => 'original_author_link',
				);

				// Do the search and replace of variables
				foreach ( $keywords as $key => $meta_name ) {
					if ( mb_stripos( $footertext, $key ) !== false ) {
						$footertext = str_replace( $key, get_post_meta( $post->ID, $meta_name, true ), $footertext );
					}
				}

				// Add the footer to the bottom of the content
				$content .= $footertext;
			}
		}

		return $content;
	}

}

$aappendtexttopost = new A_appendtexttopost();