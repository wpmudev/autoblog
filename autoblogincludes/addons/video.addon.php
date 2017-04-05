<?php

/*
Addon Name: Video Feed Import
Description: Video feeds importer. Adds video embed to a post.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class Autoblog_Addon_Video extends Autoblog_Addon {
	/**
	* list of hostnames of allowed video services
	*
	* @access private
	* @var string
	*/
	private $video_services = "www.youtube.com,player.vimeo.com";
	
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
		
		$this->_add_action( 'autoblog_global_menu', 'video_settings_menu' );
		
		$this->set_video_services();
		
	}
	
	function set_video_services(){
		if ( is_network_admin() ) {
			$get_video_services = get_site_option( 'autoblog_video_service_providers');
			
			if ( $get_video_services == "" ){
				update_site_option('autoblog_video_service_providers', 'www.youtube.com,player.vimeo.com' );
			}			
		}else{
			$get_video_services = get_option( 'autoblog_video_service_providers');
			
			if ( $get_video_services == "" ){
				update_option( 'autoblog_video_service_providers', 'www.youtube.com,player.vimeo.com' );
			}
		}
		$this->video_services = ( $get_video_services != "" ) ? $get_video_services : 'www.youtube.com,player.vimeo.com';
	}
	
	function get_video_services(){		
		if ( $this->video_services == "" ){
			if ( is_network_admin() ) {
				$get_video_services = get_site_option( 'autoblog_video_service_providers');				
				if ( $get_video_services == "" ){
					$this->set_video_services();
					$get_video_services = 'www.youtube.com,player.vimeo.com';					
				}			
			}else{
				$get_video_services = get_option( 'autoblog_video_service_providers');				
				if ( $get_video_services == "" ){
					$this->set_video_services();
					$get_video_services = 'www.youtube.com,player.vimeo.com';
				}
			}
			
			return $get_video_services;		
		}
		return $this->video_services;
	}
	
	function video_settings_menu(){
		$is_network_admin = is_network_admin();
		$capability = $is_network_admin ? 'manage_network_options' : 'manage_options';
		
		$page_title = __( 'Video Services', 'autoblogtext' );
		$menu_title = __( 'Video Services', 'autoblogtext' );
		
		if ( $is_network_admin ) {
			$active_addons = get_site_option( 'autoblog_networkactivated_addons');
			if ( in_array( 'video.addon.php', $active_addons) )
				add_submenu_page( 'autoblog', $page_title, $menu_title, $capability, 'autoblog_videos', array( $this, 'handle_video_settings_page' ) );//
		} else {/*//*/
			$active_addons = get_option( 'autoblog_activated_addons');
			if ( in_array( 'video.addon.php', $active_addons) )
				add_submenu_page( 'autoblog', $page_title, $menu_title, $capability, 'autoblog_videos', array( $this, 'handle_video_settings_page' ) );
		}
	}
	
	function handle_video_settings_page(){
		?>
        <div class="wrap">
            <h2>Video Service Providers</h2>
            <?php
			
			if ( isset ( $_POST['save_providers'] ) ) {
				if ( ! isset( $_POST['save_video_providers'] ) || ! wp_verify_nonce( $_POST['save_video_providers'], 'do_save_video_providers' ) ) {
					echo '<div class="update-message notice inline notice-warning notice-alt"><p>Are you cheatin? We cannot allow you to do that.</p></div>';
				}else{
					$is_network_admin = is_network_admin();
					if ( $is_network_admin ) {						
						$updated = update_site_option('autoblog_video_service_providers', esc_attr( $_POST['videourls'] ) );						
					} else {						
						$updated = update_option( 'autoblog_video_service_providers', esc_attr( $_POST['videourls'] ) );
					}
					
					if ( $updated ) {
						echo '<div class="update-message notice inline notice-alt updated-message notice-success"><p>Updated</p></div>';	
					}else{
						echo '<div class="update-message notice inline notice-warning notice-alt"><p>Not updated.</p></div>';	
					}					
				}
				$this->video_services = esc_attr($_POST['videourls']);
			}
			
			if ( $this->video_services == "" )
				$this->set_video_services();
			?>
            <form name="videoservices" action="" method="post">            	
				<?php wp_nonce_field( 'do_save_video_providers', 'save_video_providers', true, true ); ?>
                <textarea name="videourls" style="width: 95%;" rows="3" wrap="soft"><?php echo $this->video_services ?></textarea>
                <p><em>Seperate domains with commas like www.youtube.com,player.vimeo.com,example.com. You may seperate with a comma. You should get this domain from the embeded <code>iframe</code> tag.</em></p>
                <p>
                    <input type="submit" name="save_providers" class="button button-primary" value="Save" />
                </p>
            </form>
           
        </div>
        <?php
	}


	function process_content( $old_content, $details, SimplePie_Item $item ) {
		//we need to check does the disable sanitize add-on activated
		if ( isset( $details['disablesanitization'] ) && $details['disablesanitization'] == 1 ) {
			return $old_content;
		}
		global $allowedposttags;
		$allowedposttags['iframe'] = array(
			"src"    => array(),
			"height" => array(),
			"width"  => array()
		);
		$allowedposttags['video'] = array(
			"src"    => array(),
			"height" => array(),
			"width"  => array()
		);
		
		$content                   = $item->get_content();
		$doc                       = new DOMDocument();
		$can_use_dom               = @$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		$doc->preserveWhiteSpace   = false;

		if ( $can_use_dom ) {
			//now only allow iframe from specified video services
			$iframes = $doc->getElementsByTagName( 'iframe' );
			$removed = array();
			if ( $this->video_services == "" ){
				$this->set_video_services();
			}				
			$video_services = $this->video_services;
			
			$video_services = str_replace(" ", "", $video_services ); //remove all spaces
			$video_services = explode(",",$video_services);//convert string to array
			
				
			foreach ( $iframes as $iframe ) {
				$url = $iframe->getAttribute( 'src' );
				if ( strpos( $url, '//' ) == 0 ) {
					$url = 'http:' . $url;
				}				
				$host = parse_url( $url, PHP_URL_HOST );				
				if ( ! in_array( $host, $video_services) && ! in_array( $host, $removed )) {//if host in video_services then we skip
					$iframe->parentNode->removeChild( $iframe );
					$removed[] = $host;
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

$avideoaddon = new Autoblog_Addon_Video();