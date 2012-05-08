<?php
/*
Addon Name: Cache images locally
Description: Imports and caches any images in imported posts locally.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_ImageCacheAddon {

	var $build = 1;

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

	}

	function A_ImageCacheAddon() {
		$this->__construct();
	}

	/**
	 * Find all external images in provided content
	 * Regex by BDuelz on StackOverflow
	 * @link http://stackoverflow.com/questions/3371902/php-regex-get-image-from-url/3372785#3372785
	 */
	/**
	 * Find hotlinked external images in provided content
	 */
	function get_images_in_content( $content ) {

		preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $content, $matches);

		foreach ($matches[1] as $url) {

			$url = parse_url($url);

			$domains[$url['host']]++;
		}

		return $domains;
	}

	function grab_image_from_url( $url, $post_id ) {

		$orig_url = $url;

		set_time_limit( 300 );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$upload = media_sideload_image($url, $postid);

		if ( !is_wp_error($upload) ) {
			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $upload, $locals);
			foreach ( $locals[1] as $newurl ) :
				$this->db->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_url', '$newurl');");
			endforeach;
		}

		return $url;
	}

	/**
	 * Cache images on post's saving
	 */
	function check_post_for_images( $post_ID, $post ) {

		$domains = cache_images_find_images($post->post_content, $domains);
		if ( !$domains )
			return $post_ID;

		$local_domain = parse_url( get_option( 'siteurl' ) );

		foreach ($domains as $domain => $num) :
			if ( strstr( $domain,  $local_domain['host'] ) )
				continue; // Already local

			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches);
			foreach ( $matches[1] as $url ) :
				if ( strstr( $url, get_option('siteurl') . '/' . get_option('upload_path') ) || !strstr( $url, $domain) || (($res) && in_multi_array($url, $res)))
					continue; // Already local
				cache_images_cache_image($url, $post_ID);
			endforeach;
		endforeach;

		return $post_ID;

	}

}

$aimagecacheaddon = new A_ImageCacheAddon();

?>