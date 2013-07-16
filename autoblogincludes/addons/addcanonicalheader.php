<?php
/*
Addon Name: Canonical link in header
Description: Adds a rel='canonical' link in a posts header to point to the original post
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_canonicalheader {

	var $build = 1;

	function __construct() {

		add_action( 'wp_head', array( &$this, 'output_canonical_header' ), 9 );

	}

	function A_canonicalheader() {
		$this->__construct();
	}

	function output_canonical_header() {

		global $post;

		// Check if we are on a single item page
		if( !is_singular() ) {
			return;
		}

		if( is_object($post) ) {
			// We have a post - so we can look for the information we want
			$original_link = get_post_meta( $post->ID, 'original_source', true );

			if( !empty($original_link) ) {
				// We have a link - so output it as the canonical
				echo "<link rel='canonical' href='" . esc_attr($original_link) . "' />\n";

				// Check and remove any standard actions so we don't duplicate.
				if( has_action( 'wp_head', 'rel_canonical' ) ) {
					remove_action( 'wp_head', 'rel_canonical' );
				}
			}

		}

	}

}

$acanonicalheader = new A_canonicalheader();