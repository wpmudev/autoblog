<?php

/*
Addon Name: Open original link in popup
Description: When your reader click on the original link, it will open in popup
Author: Hoang (Incsub)
Author URI: http://premium.wpmudev.org
 */

class A_SourceLinkPopup extends Autoblog_Addon {

	const BROWSER_NATIVE = 'native', WORDPRESS_THICKBOX = 'thickbox';

	/**
	 * Constructor.
	 *
	 * @since  4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		//do it in very first in case the other adding something
		$this->_add_filter( 'the_content', 'swap_content', 9 );
		$this->_add_action( 'wp_enqueue_scripts', 'load_thick_box' );
		$this->_add_action( 'wp_footer', 'open_link', 20 );
		$this->_add_action( 'autoblog_post_post_insert', 'generate_source_link_index', 99, 3 );
		$this->_add_action( 'autoblog_post_post_update', 'generate_source_link_index', 99, 3 );
	}

	/**
	 * @param $content
	 *
	 * @return mixed
	 */
	public function swap_content( $content ) {
		$swap_content = get_post_meta( get_the_ID(), 'autoblog_open_source_popup', true );

		return ! empty( $swap_content ) ? $swap_content : $content;
	}

	/**
	 * This function will clone the original content, update all the links belong to the source domain and add a css class,
	 * which use for mark to load the link in popup
	 *
	 * @param $post_id
	 * @param $details
	 * @param $item
	 */
	function generate_source_link_index( $post_id, $details, $item ) {
		$domain = parse_url( $item->get_permalink(), PHP_URL_SCHEME ) . '://' . parse_url( $item->get_permalink(), PHP_URL_HOST );
		$post   = get_post( $post_id );
		//we will add a custom class to all the links inside this post
		//find all the anchor tag
		$content = $post->post_content;
		$regex   = '#<\s*?a\b[^>]*>(.*?)</a\b[^>]*>#s';
		if ( preg_match_all( "$regex", $post->post_content, $matches ) > 0 ) {
			//we will get all the link from the feed domain and add class
			foreach ( $matches[0] as $anchor ) {
				//append the " or ' before the domain,to make sure it in position
				if ( stristr( $anchor, '"' . $domain ) || stristr( $anchor, "'" . $domain ) ) {
					//add class
					$dom = new DOMDocument();
					$dom->loadHTML( $anchor );
					$new_dom = new DOMDocument();
					foreach ( $dom->getElementsByTagName( 'a' ) as $node ) {
						$node->setAttribute( 'class', $node->getAttribute( 'class' ) . ' autoblog-load-by-popup thickbox' );
						//$node->setAttribute( 'href', $node->getAttribute( 'href' ) . '?TB_iframe=true&width=800&height=600' );
						$new_dom->appendChild( $new_dom->importNode( $node, true ) );
						//replace the old tag with the new
						$content = str_replace( $anchor, $new_dom->saveHTML(), $content );
					}
					//update the clone content
					update_post_meta( $post_id, 'autoblog_open_source_popup', $content );
				}
			}

		}
	}

	public function load_thick_box(){
		add_thickbox();
	}

	/**
	 * Render a javascript code to open the original link in right way
	 */
	public function open_link() {

		$script = <<<EOP
	<script type="text/javascript">
		jQuery(document).ready(function(){
			var width = (jQuery(window).width() * 90)/100;
			var height =(jQuery(window).height() * 90)/100;
			jQuery(".autoblog-load-by-popup").each(function(){
				jQuery(this).attr("href",jQuery(this).attr("href")+"?TB_iframe=true&width="+width+"&height="+height)
			})
		})
	</script>
EOP;
		echo $script;
	}

	/**
	 * Check if this plugin load the firs time, generate the links
	 */
	public function first_load() {
		$func = is_multisite() ? 'get_site_option' : 'get_option';
		if ( $func( 'autoblog_source_index' ) == false ) {
			$this->generate_original_links_index();
		}
	}

	/**
	 * Generate orignial links index
	 */
	public function generate_original_links_index() {
		global $wpdb;
		$sql = "SELECT meta_value FROM ";
		if ( is_multisite() ) {
			$tbl_name = $wpdb->prefix . ( get_current_blog_id() == 1 ? null : get_current_blog_id() . '_' ) . 'postmeta';
		} else {
			$tbl_name = $wpdb->prefix . '_postmeta';
		}
		$sql .= $tbl_name . " WHERE meta_key='original_source'";
		$original_urls = $wpdb->get_col( $sql );
		//we only cache domain


		if ( is_multisite() ) {
			update_site_option( 'autoblog_source_index', $original_urls );
		} else {
			update_option( 'autoblog_source_index', $original_urls );
		}
	}

	/**
	 * Renders addon options.
	 *
	 * @since  4.0.0
	 * @action autoblog_feed_edit_form_end 10 2
	 *
	 * @access public
	 *
	 * @param type $key
	 * @param type $details
	 */
	public function add_footer_options( $key, $details ) {
		$data = ! empty( $details ) ? maybe_unserialize( $details->feed_meta ) : array();
		// render block header
		$this->_render_block_header( __( 'Open source link in popup', 'autoblogtext' ) );

		$options = array(
			''                       => __( 'No change at all', 'autoblogtext' ),
			self::BROWSER_NATIVE     => __( 'Browser Native', 'autoblogtext' ),
			self::WORDPRESS_THICKBOX => __( 'Wordpress Thickbox', 'autoblogtext' )
		);

		$selected = apply_filters( 'autoblog_open_source_link_type', isset( $data['open_source_link_way'] ) ? $data['open_source_link_way'] : '' );

		$radios = '';

		foreach ( $options as $key => $label ) {
			$radios .= sprintf(
				'<div><label><input type="radio" name="abtble[open_source_link_way]" value="%s"%s> %s</label></div>',
				esc_attr( $key ),
				checked( $key, $selected, false ),
				esc_html( $label )
			);
			$radios .= '<br/>';
		}

		// render block elements
		$this->_render_block_element( __( 'Please specific the way source link open:', 'autoblogtext' ), $radios );
	}

}

$aoriginallinkinpopup = new A_SourceLinkPopup();