<?php
/*
Addon Name: Allow Force Feed
Description: Allows you to override feed validation, and force a feed to process even if it has an incorrect MIME type. This can help with compatibility for unusual feeds. Use with caution.
Author: Alexander Rohmann (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_ForceFeedAddon {

	private $_force_feed = false;

    public function __construct() {
		add_filter( 'autoblog_pre_process_feed', array( $this, 'check_force_feed' ), 10, 3 );
		add_filter( 'autoblog_pre_test_feed', array( $this, 'check_force_feed' ), 10, 3 );

		add_action( 'autoblog_feed_edit_form_end', array( $this, 'add_feed_option' ), 12, 2 );
		add_action( 'wp_feed_options', array( $this, 'enable_force_feed' ), 10, 2 );
	}

	public function check_force_feed( $feed_id, $ablog ) {
		$this->_force_feed = !empty( $ablog['enableforcefeed'] ) && $ablog['enableforcefeed'] == 1;
	}

	public function enable_force_feed( $feed, $url ) {
		if ( $this->_force_feed ) {
			$feed->force_feed( true );
		}

		return $feed;
	}

	public function add_feed_option( $key, $details ) {
        $table = !empty( $details->feed_meta ) ? maybe_unserialize( $details->feed_meta ) : array();

        ?><tr class="spacer">
			<td colspan="2" class="spacer">
				<span><?php esc_html_e( 'Force Feed', 'autoblogtext' ) ?></span>
			</td>
		</tr>
        <tr>
			<td valign="top" class="heading">
				<?php esc_html_e( 'Enable Force Feed', 'autoblogtext' ) ?>
			</td>
			<td valign="top">
				<input type="checkbox" name="abtble[enableforcefeed]" value="1"<?php checked( isset( $table['enableforcefeed'] ) && $table['enableforcefeed'] == '1' ) ?>>
			</td>
        <tr><?php
    }

}

new A_ForceFeedAddon();