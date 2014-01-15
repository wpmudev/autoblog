<?php
/*
Addon Name: Youtube Feed Import
Description: YouTube feeds importer. Adds YouTube video to the beginning of a post.
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

class Autoblog_Addon_Youtube extends Autoblog_Addon {

	/**
	 * Constructor.
	 *
	 * @since 4.0.2
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		$this->_add_filter( 'autoblog_pre_post_insert', 'process_video', 11, 3 );
		$this->_add_filter( 'autoblog_pre_post_update', 'process_video', 11, 3 );
	}

	/**
	 * Finds Youtube link and adds to post content.
	 *
	 * @since 4.0.2
	 * @filter autoblog_pre_post_insert 11 3
	 * @filter autoblog_pre_post_update 11 3
	 *
	 * @access public
	 * @param array $data The post data.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 * @return array The post data.
	 */
	public function process_video( array $data, array $details, SimplePie_Item $item ) {
		$permalink = htmlspecialchars_decode( $item->get_permalink() );
		if ( stripos( $permalink, 'http://www.youtube.com/watch' ) !== false ) {
			$data['post_content'] = $permalink . PHP_EOL . PHP_EOL . $data['post_content'];
		}

		return $data;
	}

}

$ayoutubeaddon = new Autoblog_Addon_Youtube();