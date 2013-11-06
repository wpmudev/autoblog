<?php
/*
Addon Name: Strip Images
Description: Removes all image tags from the post content.
Author: Alexander Rohmann (Incsub)
Author URI: http://premium.wpmudev.org
*/

class A_StripImagesAddon {

    public function __construct() {
		add_filter( 'autoblog_pre_post_insert', array( $this, 'filter_post' ), 10, 3 );
		add_action( 'autoblog_feed_edit_form_end', array( $this, 'add_feed_option' ), 12, 2 );
	}

	public function filter_post( $post_data, $ablog, $item ) {
		if ( !empty( $ablog['stripimgtags'] ) && addslashes( $ablog['stripimgtags'] ) == '1' ) {
			$placeholder = isset( $ablog['stripimgtagsreplace'] ) ? $ablog['stripimgtagsreplace'] : '';
			$post_data['post_content'] = preg_replace( "/<img[^>]+\>/", $placeholder, $post_data['post_content'] );
		}

		return $post_data;
	}

	public function add_feed_option( $key, $details ) {
		$table = !empty( $details->feed_meta ) ? maybe_unserialize( $details->feed_meta ) : array();

		if ( !isset( $table['stripimgtagsreplace'] ) ) {
			$table['stripimgtagsreplace'] = '';
		}

		?><tr class="spacer">
			<td colspan="2" class="spacer">
				<span><?php esc_html_e( 'Strip Images', 'autoblogtext' ) ?></span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="heading">
				<?php esc_html_e( 'Strip image tags', 'autoblogtext' ) ?>
			</td>
			<td valign="top">
				<input type="checkbox" name="abtble[stripimgtags]" value="1" <?php checked( isset( $table['stripimgtags'] ) && $table['stripimgtags'] == '1' ) ?>>
			</td>
		</tr>
		<tr>
			<td valign="top" class="heading">
				<?php esc_html_e( 'Replace with', 'autoblogtext' ) ?>
			</td>
			<td valign="top">
				<input type="text" class="long field" name="abtble[stripimgtagsreplace]" value="<?php echo esc_attr( stripslashes( $table['stripimgtagsreplace'] ) ) ?>">
			</td>
		</tr><?php
	}

}

new A_StripImagesAddon();