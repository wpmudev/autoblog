<?php
/*
Addon Name: Replace Author Information
Description: Replaces the author details shown on a post with the imported sites ones
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_importedauthor {

	var $build = 1;

	function __construct() {

		// Override the author link
		add_filter( 'get_the_author_url', array( &$this, 'return_the_author_url' ), 10, 2 );

		// Override the author email
		add_filter( 'get_the_author_email', array( &$this, 'return_the_author_email' ), 10, 2 );

		// Override the author display name
		add_filter( 'the_author', array( &$this, 'return_the_author' ) );

	}

	function A_importedauthor() {
		$this->__construct();
	}

	function return_the_author_url( $value, $user_id ) {

		global $post;

		if( is_object( $post ) ) {

			$author_link = get_post_meta( $post->ID, 'original_author_link', true );

			if( !empty($author_link) ) {
				return $author_link;
			}

		}

		return $value;
	}

	function return_the_author_email( $value, $user_id ) {

		global $post;

		if( is_object( $post ) ) {

			$author_email = get_post_meta( $post->ID, 'original_author_email', true );

			if( !empty($author_email) ) {
				return $author_email;
			}

		}

		return $value;
	}

	function return_the_author( $displayname ) {

		global $post;

		if( is_object( $post ) ) {

			$author_name = get_post_meta( $post->ID, 'original_author_name', true );

			if( !empty($author_name) ) {
				return $author_name;
			}

		}


		return $displayname;
	}



}

$aimportedauthor = new A_importedauthor();