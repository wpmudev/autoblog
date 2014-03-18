<?php
/*
Addon Name: WPML Languages
Description: Fixes language insertion problem on WPML multilingual sites.
Author: Arnold Bailey (Incsub)
Author URI: http://premium.wpmudev.org
Since version: 4.0.8
*/


if(! class_exists('Autoblog_WPML_languages') ):
class Autoblog_WPML_languages{

	function __construct(){
		add_action( 'init', array(&$this,'on_init'), 10, 3 );
	}

	function on_init(){
		if( ! defined('ICL_SITEPRESS_VERSION')) return; //no wpml
		
		add_action( 'autoblog_post_post_update', array(&$this,'notify_wpml'), 10, 3 );
		add_action( 'autoblog_post_post_insert', array(&$this,'notify_wpml'), 10, 3 );

	}

	function get_feed_language( SimplePie_Item $item ){
		global $wpdb;
		
		if( !defined('ICL_SITEPRESS_VERSION')) return; //no wpml

		if($feed = $item->get_feed()){
			$language_code = $feed->get_language();
			$language_code = empty($language_code) ? get_locale() : $language_code;
		}
		
		if($language_code){
			$language_code = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE tag=%s", $language_code));
		}

		return $language_code;
	}

	function notify_wpml( $post_id, array $details, SimplePie_Item $item) {
		global $wpdb;

		if( !defined('ICL_SITEPRESS_VERSION')) return; //no wpml
		if(empty($post_id) ) return $post_id; //No post id

		$language_code = $this->get_feed_language($item);
		
		global $sitepress;
		
		global $sitepress;

		$trid = (int)$wpdb->get_var(("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations"));
		$sitepress->set_element_language_details($post_id, $el_type='post_post', null , $language_code, null);

		//update the cache
		$languages = icl_cache_get('posts_per_language');
		if($languages && array_key_exists($language_code, $languages)){
			$languages[$language_code] += 1; 
			$languages['all'] += 1; 
			icl_cache_set('posts_per_language', $languages);
		}

	}

new Autoblog_WPML_languages;

endif;


