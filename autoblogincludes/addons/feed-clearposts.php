<?php

/*
Addon Name: Feed Clear Posts
Description: Clear all posts imported by a feed
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

class AutoBlog_Feed_ClearPosts extends Autoblog_Addon
{
    public function __construct()
    {
        parent::__construct();
        $this->_add_action('autoblog_feed_edit_form_end', 'feed_options', 10, 2);
        $this->_add_action('admin_footer', 'footer_script');
        $this->_add_action('wp_ajax_ab_clean_posts', 'clear_posts');
    }

    function clear_posts()
    {
        if (!current_user_can('manage_options')) {
            return '';
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'ab_clean_posts')) {
            return '';
        }

        $feed_id = $_POST['feed_id'];
        //load all posts
        $posts = get_posts(array(
            'meta_key' => 'original_feed_id',
            'meta_value' => $feed_id,
            'post_status' => array('publish', 'draft', 'pending'),
            'nopaging' => true
        ));
        $count = count($posts);
        foreach ($posts as $post) {
            wp_delete_post($post->ID);
        }

        wp_send_json(array(
            'message' => sprintf(__("%d posts has been deleted", "autoblogtext"), $count)
        ));
    }

    function footer_script()
    {
        ?>
        <script type="text/javascript">
            jQuery(function ($) {
                $('#autoblog_clear_posts').click(function () {
                    var feed_id = $(this).data('id');
                    var that = $(this);
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'ab_clean_posts',
                            feed_id: feed_id,
                            _wpnonce: '<?php echo wp_create_nonce('ab_clean_posts') ?>'
                        },
                        beforeSend: function () {
                            that.attr('disabled', 'disabled').text('Submitting...');
                        },
                        success: function (data) {
                            that.removeAttr('disabled').text('Clean up').after(' <strong>' + data.message + '</strong>');
                        }
                    })
                })
            })
        </script>
    <?php
    }

    function feed_options($key, $details)
    {
        $table = !empty($details->feed_meta) ? maybe_unserialize($details->feed_meta) : array();
        $this->_render_block_header(esc_html__('Clear Posts', 'autoblogtext'));

        $this->_render_block_element(
            esc_html__('Clear all posts imported by this feed', 'autoblogtext'),
            '<button data-id="' . $details->feed_id . '" type="button" id="autoblog_clear_posts" class="button">' . __("Clean up", 'autoblogtext') . '</button>'
        );
    }
}

$autoblog_feed_clearposts = new AutoBlog_Feed_ClearPosts();