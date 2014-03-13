<?php
/*
Addon Name: Clean Face
Description: Cleans non-validating feeds like Facebook. Fixes Facebook spoofed relative links.
Author: Arnold Bailey (Incsub)
Author URI: http://premium.wpmudev.org
Since version: 4.0.8
*/

if(!class_exists('Autoblog_Clean_Face') ):

class Autoblog_Clean_Face{

	function __construct(){
		add_filter( 'autoblog_pre_post_update', array( &$this, 'clean_non_validating_feeds'), 12, 3 );
		add_filter( 'autoblog_pre_post_insert', array( &$this, 'clean_non_validating_feeds'), 12, 3 );
	}

	function clean_non_validating_feeds(array $data, array $details, SimplePie_Item $item) {

		// post title decode entities
		$data['post_title'] = html_entity_decode($data['post_title'],ENT_QUOTES );
		//post title remove carriage returns and linefeeds
		$data['post_title'] = strtr( $data['post_title'], "\r\n", "  ");

		$base_url = $item->get_base();

		//post_content expand urls
		if(preg_match_all('#(href="/l.php\?u=)([^&"]*)([^"]*)#mi', $data['post_content'], $matches)) {

			$hrefs = array_unique($matches[1]);
			foreach($hrefs as $href){
				$data['post_content'] = str_replace( $href, 'href="', $data['post_content'], $count);
			}

			$ends = array_unique($matches[3]);
			foreach($ends as $end){
				$data['post_content'] = str_replace( $end, '', $data['post_content'], $count);
			}

			$urls = array_unique($matches[2]);
			foreach($urls as $url){
				$data['post_content'] = str_replace( $url, esc_attr(urldecode($url) ), $data['post_content'], $count);
			}
		}

		return $data;
	}

	function write_to_log($error, $log = 'feeds') {

		//create filename for each month
		$filename = AUTOBLOG_ABSPATH . "{$log}_" . date('Y_m') . '.log';

		//add timestamp to error
		$message = gmdate("[Y-m-d H:i:s]\n") . $error;

		//write to file
		file_put_contents($filename, $message . "\n", FILE_APPEND);
	}

}

new Autoblog_Clean_Face;

endif;

if( !function_exists('write_to_log') ):
	function write_to_log($error, $log = 'feeds') {

		//create filename for each month
		$filename = AUTOBLOG_ABSPATH . "{$log}_" . date('Y_m') . '.log';

		//add timestamp to error
		$message = gmdate("[Y-m-d H:i:s]\n") . $error;

		//write to file
		file_put_contents($filename, $message . "\n", FILE_APPEND);
	}

endif;