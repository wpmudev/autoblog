<?php

/*
Addon Name: Twitter Add-on
Description: Adds a Twitter post type and processes tweets to have correct links.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/


class A_twitter_addon extends Autoblog_Addon {

	/**
	 * Construcotr.
	 *
	 * @since  4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'init', 'initialise_addon' );
		$this->_add_action( 'widgets_init', 'register_widgets' );

		$this->_add_filter( 'autoblog_pre_post_insert', 'process_tweet', 10, 3 );
		$this->_add_action( 'autoblog_feed_edit_form_end', 'add_footer_options', 10, 2 );

		$this->_add_action( 'autoblog_feed_created', 'update_feed_url', 20 );
		$this->_add_action( 'autoblog_feed_updated', 'update_feed_url', 20 );
	}

	/**
	 * Initializes custom post type.
	 *
	 * @since  4.0.0
	 * @action init
	 *
	 * @access public
	 */
	public function initialise_addon() {
		register_post_type( 'tweet', array(
			'public'              => true,
			'show_ui'             => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'page-attributes' ),
			'rewrite'             => array( 'slug' => __( 'tweet', 'autoblogtext' ), 'with_front' => false ),
			'labels'              => array(
				'name'               => __( 'Tweets', 'autoblogtext' ),
				'singular_name'      => __( 'Tweet', 'autoblogtext' ),
				'add_new'            => __( 'Add New', 'autoblogtext' ),
				'add_new_item'       => __( 'Add New Tweet', 'autoblogtext' ),
				'edit'               => __( 'Edit', 'autoblogtext' ),
				'edit_item'          => __( 'Edit Tweet', 'autoblogtext' ),
				'new_item'           => __( 'New Tweet', 'autoblogtext' ),
				'view'               => __( 'View Tweet', 'autoblogtext' ),
				'view_item'          => __( 'View Tweet', 'autoblogtext' ),
				'search_items'       => __( 'Search Tweets', 'autoblogtext' ),
				'not_found'          => __( 'No Tweets found', 'autoblogtext' ),
				'not_found_in_trash' => __( 'No Tweets found in Trash', 'autoblogtext' ),
				'parent'             => __( 'Parent Tweet', 'autoblogtext' ),
			),
		) );
	}

	/**
	 * Registers widgets.
	 *
	 * @since  4.0.0
	 * @action widgets_init
	 *
	 * @access public
	 */
	public function register_widgets() {
		register_widget( 'A_Widget_Recent_Tweets' );
	}

	/**
	 * Processes tweet item.
	 *
	 * @since  4.0.0
	 * @filter autoblog_pre_post_insert 10 3
	 *
	 * @param type $post_data
	 * @param type $details
	 * @param type $item
	 *
	 * @return type
	 */
	public function process_tweet( $post_data, $details, $item ) {
		if ( $post_data['post_type'] == 'tweet' || stripos( $item->get_permalink(), 'twitter.com' ) !== false ) {
			$post_data['post_title']   = preg_replace( "^(\w+): (.*)^", "\\2", $post_data['post_title'] );
			$post_data['post_content'] = $this->_twitterify( $post_data['post_content'] );
		}

		return $post_data;
	}

	/**
	 * Generates twitter based links.
	 *
	 * @since  4.0.0
	 * @link   http://www.snipe.net/2009/09/php-twitter-clickable-links/
	 *
	 * @access private
	 *
	 * @param type $ret
	 *
	 * @return type
	 */
	private function _twitterify( $ret ) {
		$ret = preg_replace( "^(\w+): (.*)^", "\\2", $ret );
		$ret = preg_replace( "#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret );
		$ret = preg_replace( "#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret );
		$ret = preg_replace( "/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $ret );
		$ret = preg_replace( "/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $ret );

		return $ret;
	}

	public function add_footer_options( $key, $details ) {
		$data = ! empty( $details ) ? maybe_unserialize( $details->feed_meta ) : array();

		$label = sprintf( '<p>%s</p><p style="height:43px">%s</p><p style="height: 30px">%s</p><p style="height:30px">%s</p><p>%s</p>',
			__( 'Enable Twitter to this feed', 'autoblogtext' ),
			'',
			__( 'Consumer Key', 'autoblogtext' ),
			__( 'Consumer Secret', 'autoblogtext' ),
			__( 'User Screen Name', 'autoblogtext' ) );

		$content = sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p><p>%s</p>',
			'<input type="checkbox" name="abtble[twitter_status]" ' . checked( 'on', @$data['twitter_status'], false ) . ' >',
			__( 'For getting Twitter Consumer Key & Secret Key, please visit this url <a target="_blank" href="https://apps.twitter.com/">https://apps.twitter.com/</a>, creating new app. <br/>After the app created, please visit the tab API Keys, you will see the information', 'autoblogtext' ),
			'<input type="text" name="abtble[twitter_consumer_key]" value="' . esc_html( @$data['twitter_consumer_key'] ) . '" class="long field"/>',
			'<input type="text" name="abtble[twitter_secret_key]" value="' . esc_html( @$data['twitter_secret_key'] ) . '" class="long field"/>',
			'<input type="text" name="abtble[twitter_user_name]" value="' . esc_html( @$data['twitter_user_name'] ) . '" class="long field"/>'
		);
		$this->_render_block_header( __( 'Twitter Timeline', 'autoblogtext' ) );
		// render block elements
		$this->_render_block_element( $label, $content );
		$this->footer_scripts( $data );
	}

	public function footer_scripts( $data ) {
		if ( @$data['twitter_status'] == 'on' ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					if ($('input[name="abtble[url]"]').size() > 0) {
						$('input[name="abtble[url]"]').attr({
							'placeholder': 'If you using twitter add-on, url will override and auto generate',
							'disabled'   : 'disabled'
						});
					}
				})
			</script>
		<?php
		}
	}

	public function update_feed_url( $feed ) {
		$meta = maybe_unserialize( $feed['feed_meta'] );
		if ( isset( $meta['twitter_status'] ) && $meta['twitter_status'] == 'on' ) {

			$meta['url']       = AUTOBLOG_ABSURL . 'addons/twitter-addon-files/twitter_json_rss.php?username=' . $meta['twitter_user_name'] . '&feed_id=' . $feed['feed_id'];
			$feed['feed_meta'] = serialize( $meta );
			//save the virtual url
			global $wpdb;
			$wpdb->update( AUTOBLOG_TABLE_FEEDS, $feed, array( 'feed_id' => $feed['feed_id'] ) );
		}
	}
}

// Based on the default WP_Widget_Recent_Posts default WP widget
class A_Widget_Recent_Tweets extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_recent_tweets', 'description' => __( 'The most recent tweets on your site', 'autoblogtext' ) );
		$this->WP_Widget( 'recent-tweets', __( 'Recent Tweets', 'autoblogtext' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_tweets';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		$cache = wp_cache_get( 'widget_recent_tweets', 'widget' );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];

			return;
		}

		ob_start();
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Posts', 'autoblogtext' ) : $instance['title'], $instance, $this->id_base );
		if ( ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}

		$r = new WP_Query( array( 'posts_per_page' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'ignore_sticky_posts' => true, 'post_type' => 'tweet' ) );
		if ( $r->have_posts() ) :
			?>
			<?php echo $before_widget; ?>
			<?php if ( $title ) {
			echo $before_title . $title . $after_title;
		} ?>
			<ul class='tweets'>
				<?php while ( $r->have_posts() ) : $r->the_post(); ?>
					<li class='tweet'><?php the_content(); ?>
						<a href='<?php echo get_post_meta( get_the_id(), 'original_source', true ); ?>'>#</a> - <?php echo get_the_date() . __( ' at ', 'autoblogtext' ) . get_the_time(); ?>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php echo $after_widget; ?>
			<?php
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set( 'widget_recent_posts', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_recent_tweets'] ) ) {
			delete_option( 'widget_recent_tweets' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'widget_recent_tweets', 'widget' );
	}

	function form( $instance ) {
		$title  = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of tweets to show:', 'autoblogtext' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		</p>
	<?php
	}
}

$atwitteraddon = new A_twitter_addon();