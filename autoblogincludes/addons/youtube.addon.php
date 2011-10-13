<?php
/*
Addon Name: Youtube Add-on
Description: Experimental YouTube rss feeds importer - changes content to embedded video.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

class A_youtube_addon {

	function __construct() {

		add_action('init', array(&$this, 'initialise_addon'));
		add_action( 'widgets_init', array(&$this, 'register_widgets') );

		add_filter( 'autoblog_pre_post_insert', array(&$this, 'process_video'), 10, 3 );
	}

	function A_youtube_addon() {
		$this->__construct();
	}

	function initialise_addon() {

	}

	function register_widgets() {

	}

	function process_video( $post_data, $ablog, $item ) {

		extract($post_data);

		if(strpos($item->get_permalink(), 'http://www.youtube.com/watch') !== false ) {
			$post_content = $item->get_permalink();
		}

		return compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'post_type', 'tax_input');

	}

}

$ayoutubeaddon = new A_youtube_addon();

?>