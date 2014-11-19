<?php

/*
Addon Name: Youtube Feed Import
Description: YouTube feeds importer. Adds YouTube video to the beginning of a post.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class Autoblog_Addon_Youtube extends Autoblog_Addon {

	/**
	 * Constructor.
	 *
	 * @since  4.0.2
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		$this->_add_filter( 'autoblog_pre_post_insert', 'process_video', 11, 3 );
		$this->_add_filter( 'autoblog_pre_post_update', 'process_video', 11, 3 );
		$this->_add_filter( 'autoblog_post_content_before_import', 'process_content', 10, 3 );
	}


	function process_content( $old_content, $details, SimplePie_Item $item ) {
		//we need to check does the disable santinitize add-on activated
		if ( isset( $details['disablesanitization'] ) && $details['disablesanitization'] == 1 ) {
			return $old_content;
		}
		global $allowedposttags;
		$allowedposttags['iframe'] = array(
			"src"    => array(),
			"height" => array(),
			"width"  => array()
		);
		$content                   = $item->get_content();
		$doc                       = new DOMDocument();
		$can_use_dom               = @$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		$doc->preserveWhiteSpace   = false;

		if ( $can_use_dom ) {
			//now only allow iframe from youtube
			$iframes = $doc->getElementsByTagName( 'iframe' );
			foreach ( $iframes as $iframe ) {
				$url = $iframe->getAttribute( 'src' );
				if ( strpos( $url, '//' ) == 0 ) {
					$url = 'http:' . $url;
				}
				if ( ! stristr( parse_url( $url, PHP_URL_HOST ), 'youtube.com' ) ) {
					$iframe->parentNode->removeChild( $iframe );
				}
			}
			$new_content = preg_replace( '~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $doc->saveHTML() );

			return $new_content;
		}

		return $old_content;
	}

	/**
	 * Finds Youtube link and adds to post content.
	 *
	 * @since  4.0.2
	 * @filter autoblog_pre_post_insert 11 3
	 * @filter autoblog_pre_post_update 11 3
	 *
	 * @access public
	 *
	 * @param array $data The post data.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 *
	 * @return array The post data.
	 */
	public function process_video( array $data, array $details, SimplePie_Item $item ) {
		$permalink = htmlspecialchars_decode( $item->get_permalink() );

		if ( preg_match( '#^https?://(www\.)?youtube\.com/watch#i', $permalink ) ) {
			$data['post_content'] = $permalink . PHP_EOL . PHP_EOL . $data['post_content'];
		}

		return $data;
	}

}

$ayoutubeaddon = new Autoblog_Addon_Youtube();