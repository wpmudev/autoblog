<?php

/*
Addon Name: Open links in popup
Description: When your reader clicks on links from the source, they will open in a popup
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
 */

class A_SourceLinkPopup extends Autoblog_Addon {

	const BROWSER_NATIVE = 'native', WORDPRESS_THICKBOX = 'thickbox';
	public $feed;

	/**
	 * Constructor.
	 *
	 * @since  4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		$this->_add_filter( 'the_content', 'swap_content', PHP_INT_MAX );
		$this->_add_action( 'wp_enqueue_scripts', 'load_thick_box' );
		$this->_add_action( 'wp_footer', 'open_link', 20 );
		$this->_add_action( 'autoblog_feed_edit_form_end', 'add_footer_options', 10, 2 );
	}

	/**
	 * @param $content
	 *
	 * @return mixed
	 */
	public function swap_content( $content ) {
		//check does this post is an auto post or not
		if ( ( $feed_id = get_post_meta( get_the_ID(), 'original_feed_id', true ) ) > 0 ) {
			$feed = $this->get_feed( $feed_id );
			if ( @$feed['olp_disable'] != 'on' ) {
				$swap_content = $this->generate_source_link_index( $content, $feed_id );
				return $swap_content;
			}
		}

		return $content;
	}

	/**
	 * This function will clone the original content, update all the links belong to the source domain and add a css class,
	 * which use for mark to load the link in popup
	 *
	 * @param $post_id
	 * @param $details
	 * @param $item
	 */
	public function generate_source_link_index( $content, $feed_id ) {
		$regex = '#<\s*?a\b[^>]*>(.*?)</a\b[^>]*>#s';
		if ( preg_match_all( "$regex", $content, $matches ) > 0 ) {
			//we will get all the link from the feed domain and add class
			foreach ( $matches[0] as $anchor ) {
				//add class
				$dom = new DOMDocument();
				@$dom->loadHTML( mb_convert_encoding($anchor, 'HTML-ENTITIES', 'UTF-8') );
				$new_dom = new DOMDocument();
				foreach ( $dom->getElementsByTagName( 'a' ) as $node ) {
					//check does this url domain is blog domain
					$blog_url = strtolower( parse_url( get_site_url( get_current_blog_id() ), PHP_URL_HOST ) );
					$href     = strtolower( parse_url( $node->getAttribute( 'href' ), PHP_URL_HOST ) );
					//check does this domain exclude
					$feed           = $this->get_feed( $feed_id );
					$domain_exclude = explode( PHP_EOL, $feed['olp_domain_exclude'] );
					$domain_exclude = array_map( 'trim', $domain_exclude );

					if ( in_array( $href, $domain_exclude )){
						break;
					}

					if ( $blog_url != $href ) {
						$node->setAttribute( 'class', $node->getAttribute( 'class' ) . ' autoblog-load-by-popup thickbox' );
						//$node->setAttribute( 'href', $node->getAttribute( 'href' ) . '?TB_iframe=true&width=800&height=600' );
						$new_dom->appendChild( $new_dom->importNode( $node, true ) );
						//replace the old tag with the new
						$content = str_replace( $anchor, $new_dom->saveHTML(), $content );
					}
				}
			}
			//update the clone content
			update_post_meta( get_the_ID(), 'autoblog_open_source_popup', $content );

			return $content;
		}
		return $content;
	}

	public function load_thick_box() {
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

	public function add_footer_options( $key, $details ) {
		$data = ! empty( $details ) ? maybe_unserialize( $details->feed_meta ) : array();

		$label = sprintf( '<p>%s</p><p>%s</p>',
			__( 'Do you want to turn off this feature for this feed', 'autoblogtext' ),
			__( 'Domain you want to exclude, seperate by line(without "http(s)://")', 'autoblogtext' ));

		$content = sprintf( '<p>%s</p><p>%s</p>',
			'<label><input ' . checked( @$data['olp_disable'], 'on', false ) . ' name="abtble[olp_disable]" type="checkbox">' . __( 'Yes','autoblogtext' ) . '</label>',
			'<textarea name="abtble[olp_domain_exclude]" rows="3" class="long field">' . @$data['olp_domain_exclude'] . '</textarea>'
		);
		$this->_render_block_header( __( 'Open Link In Popup', 'autoblogtext' ) );
		// render block elements
		$this->_render_block_element( $label, $content );
	}

	public function get_feed( $feed_id ) {
		if ( empty( $this->feed ) ) {
			$feed = $feed = $this->_wpdb->get_row( sprintf(
				is_network_admin()
					? 'SELECT * FROM %s WHERE feed_id = %d LIMIT 1'
					: 'SELECT * FROM %s WHERE feed_id = %d AND blog_id = %d LIMIT 1',
				AUTOBLOG_TABLE_FEEDS,
				$feed_id,
				get_current_blog_id()
			), ARRAY_A );

			$this->feed = maybe_unserialize( $feed['feed_meta'] );
		}

		return $this->feed;
	}

}

$aoriginallinkinpopup = new A_SourceLinkPopup();