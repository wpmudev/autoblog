<?php
/*
Addon Name: Replace Author Information
Description: Replaces the author details shown on a post with the imported sites ones
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class A_importedauthor extends Autoblog_Addon {

	/**
	 * Constructor
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_filter( 'get_the_author_url', 'return_the_author_url' );
		$this->_add_filter( 'get_the_author_email', 'return_the_author_email' );
		$this->_add_filter( 'the_author', 'return_the_author' );
	}

	/**
	 * Returns author meta value if a meta key exists, otherwise returns default value.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @global WP_Post $post The current post object.
	 * @param string $meta_key The meta key to look for.
	 * @param mixed $default The default value to return if meta key is not found.
	 * @return mixed The meta value on successs, otherwise default value.
	 */
	private function _get_option( $meta_key, $default ) {
		global $post;

		if ( is_object( $post ) ) {
			$author_link = get_post_meta( $post->ID, $meta_key, true );
			if ( !empty( $author_link ) ) {
				return $author_link;
			}
		}

		return $default;
	}

	/**
	 * Returns the original author URL.
	 *
	 * @since 4.0.0
	 * @filter get_the_author_url
	 *
	 * @access public
	 * @param string $author_url The author URl in the system.
	 * @return string The original author URL on success, otherwise incomming author URL.
	 */
	public function return_the_author_url( $author_url ) {
		return $this->_get_option( 'original_author_link', $author_url );
	}

	/**
	 * Returns the original author email.
	 *
	 * @since 4.0.0
	 * @filter get_the_author_email
	 *
	 * @access public
	 * @param string $author_url The author email in the system.
	 * @return string The original author email on success, otherwise incomming author email.
	 */
	public function return_the_author_email( $author_email ) {
		return $this->_get_option( 'original_author_email', $author_email );
	}

	/**
	 * Returns the original author name.
	 *
	 * @since 4.0.0
	 * @filter the_author
	 *
	 * @access public
	 * @param string $author_url The author name in the system.
	 * @return string The original author name on success, otherwise incomming author name.
	 */
	public function return_the_author( $author_name ) {
		return $this->_get_option( 'original_author_name', $author_name );
	}

}

$aimportedauthor = new A_importedauthor();