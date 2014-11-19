<?php

/*
Addon Name: Strip Images
Description: Removes all image tags from the post content.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class A_StripImagesAddon extends Autoblog_Addon
{

    /**
     * Constructor.
     *
     * @since 4.0.0
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();

        $this->_add_filter('autoblog_pre_post_insert', 'filter_post', 11, 2);
        $this->_add_action('autoblog_pre_post_update', 'filter_post', 11, 2);

        $this->_add_action('autoblog_feed_edit_form_end', 'add_feed_option', 12, 2);
    }

    /**
     * Filters post content to strip images.
     *
     * @since 4.0.0
     * @filter autoblog_pre_post_insert 11 2
     *
     * @access public
     * @param array $data The post data.
     * @param array $details The array of feed details.
     * @return array The post data.
     */
    public function filter_post($data, array $details)
    {
        if (!is_array($data)) {
            return $data;
        }
        if (!empty($details['stripimgtags']) && addslashes($details['stripimgtags']) == '1') {
            $placeholder = isset($details['stripimgtagsreplace']) ? $details['stripimgtagsreplace'] : '';
            $data['post_content'] = preg_replace("/<img[^>]+\>/", $placeholder, $data['post_content']);
        }

        return $data;
    }

    /**
     * Renders addon options.
     *
     * @since 4.0.0
     * @action autoblog_feed_edit_form_end 12 2
     *
     * @param type $key
     * @param type $details
     */
    public function add_feed_option($key, $details)
    {
        $table = !empty($details->feed_meta) ? maybe_unserialize($details->feed_meta) : array();

        if (!isset($table['stripimgtagsreplace'])) {
            $table['stripimgtagsreplace'] = '';
        }

        // render block header
        $this->_render_block_header(esc_html__('Strip Images', 'autoblogtext'));

        // render block elements
        $this->_render_block_element(esc_html__('Strip Image Tags', 'autoblogtext'), sprintf(
            '<input type="checkbox" name="abtble[stripimgtags]" value="1"%s>',
            checked(isset($table['stripimgtags']) && $table['stripimgtags'] == '1', true, false)
        ));

        $this->_render_block_element(esc_html__('Replace With', 'autoblogtext'), sprintf(
            '<input type="text" class="long field" name="abtble[stripimgtagsreplace]" value="%s">',
            esc_attr(stripslashes($table['stripimgtagsreplace']))
        ));
    }

}

$a_stripimagesaddon = new A_StripImagesAddon();