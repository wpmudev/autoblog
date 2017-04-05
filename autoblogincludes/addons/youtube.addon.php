<?php

/*
Addon Name: Youtube Feed Import
Description: YouTube feeds importer. Adds YouTube video to the beginning of a post.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
*/

if (!defined('SIMPLEPIE_NAMESPACE_YOUTUBE')) {
    define('SIMPLEPIE_NAMESPACE_YOUTUBE', 'http://search.yahoo.com/mrss/');
}

class Autoblog_Addon_Youtube extends Autoblog_Addon
{

    const SOURCE_THE_FIRST_VIDEO = 'ASC';
    const SOURCE_THE_LAST_VIDEO = 'DESC';
    const SOURCE_ENCLOSURE = 'ENCLOSURE';

    /**
     * Constructor.
     *
     * @since  4.0.2
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->_add_action('autoblog_feed_edit_form_end', 'render_utube_options', 10, 2);
        //by default, wp will strip iframe, we will need to use the filter autoblog_post_content_before_import
        $this->_add_filter('autoblog_post_content_before_import', 'process_content', 10, 3);
        $this->_add_action('autoblog_post_post_update', 'enable_post_kses_filter');
        $this->_add_action('autoblog_post_post_insert', 'enable_post_kses_filter');
    }

    function enable_post_kses_filter()
    {
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    /**
     * @param $key
     * @param $details
     */
    public function render_utube_options($key, $details)
    {
        $table = !empty($details->feed_meta)
            ? maybe_unserialize($details->feed_meta)
            : array();

        $selected_option = apply_filters('autoblog_utube_from', isset($table['utubeimport']) ? $table['utubeimport'] : AUTOBLOG_IMAGE_CHECK_ORDER);
        $options = array(
            self::SOURCE_ENCLOSURE => __('Use enclosure tag of a feed item', 'autoblogtext'),
            self::SOURCE_THE_FIRST_VIDEO => __('Find the first youtube video within content of a feed item', 'autoblogtext'),
            self::SOURCE_THE_LAST_VIDEO => __('Find the last youtube video within content of a feed item', 'autoblogtext'),
        );

        $radio = '';
        foreach ($options as $key => $label) {
            $radio .= sprintf(
                '<div><label><input type="radio" name="abtble[utubeimport]" value="%s"%s> %s</label></div>',
                esc_attr($key),
                checked($key, $selected_option, false),
                esc_html($label)
            );
        }
        //$radio .= '<br>';

        //utube iframe config
        $iframe = isset($table['utubeimport_iframe']) ? $table['utubeimport_iframe'] : '<iframe width="560" height="349" src="SRC_LINK" frameborder="0" allowfullscreen="0"></iframe>';
        $iframe = stripslashes($iframe);
        $textarea = sprintf("<textarea class=\"long field\" name=\"abtble[utubeimport_iframe]\">%s</textarea>", $iframe);

        // render block header
        $this->_render_block_header(__('Youtube Video Importing', 'autoblogtext'));

        // render block elements
        $this->_render_block_element(__('Select a way to import Youtube Video', 'autoblogtext'), $radio);
        $this->_render_block_element(__('Youtube Iframe', 'autoblogtext'), $textarea);
    }

    function process_content($old_content, $details, SimplePie_Item $item)
    {
        //we will remove the post sanitize for cron
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        //we need to check does the disable santinitize add-on activated
        $method = trim(isset($details['utubeimport']) ? $details['utubeimport'] : self::SOURCE_THE_FIRST_VIDEO);
        if (empty($method)) {
            return $old_content;
        }
        $content = $old_content;

        if ($method == self::SOURCE_ENCLOSURE) {
            $content = $this->_find_in_enclosure($item, $old_content, $details);
        } else {
            $content = $this->_find_in_content($method, $item, $old_content, $details);
        }
        return $content;
    }

    function _find_in_content($method, SimplePie_Item $item, $old_content, $details)
    {
        //we need to check does the disable santinitize add-on activated
        //force autoblog not strip iframe
        global $allowedposttags;
        $allowedposttags['iframe'] = array(
            "src" => array(),
            "height" => array(),
            "width" => array()
        );
        //find every you tube links in the content
        //first we need to get raw content
        $content = $item->get_content();
        $regex = '/https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[?=&+%\w-]*/i';
        if (preg_match_all($regex, $content, $m)) {
            //found some link
            $links = $m[0];
            $method = trim(isset($details['utubeimport']) ? $details['utubeimport'] : AUTOBLOG_IMAGE_CHECK_ORDER);
            if ($method == self::SOURCE_THE_FIRST_VIDEO) {
                $link = array_shift($links);
            } else {
                $link = array_pop($links);
            }
            //getting youtube id
            $uid = $this->find_youtube_id($link);
            if ($uid != false) {
                $embed_link = "https://www.youtube.com/embed/$uid";
                $iframe = isset($table['utubeimport_iframe']) ? $table['utubeimport_iframe'] : '<iframe width="560" height="349" src="SRC_LINK" frameborder="0" allowfullscreen="0"></iframe>';
                $iframe = str_replace('SRC_LINK', esc_url($embed_link), $iframe);
                return $iframe . $old_content;
            }
        }
        return $old_content;
    }

    function _find_in_enclosure($item, $old_content, $details)
    {
        $enclosures = $item->get_enclosures();
        $link = null;
        foreach ($enclosures as $enclosure) {
            $utube_link = $enclosure->link;
            if (preg_match('#^https?://(www\.)?youtube\.com/#i', $utube_link)) {
                $link = $utube_link;
                break;
            }
        }

        if (!empty($link)) {
            //getting youtube id
            $uid = $this->find_youtube_id($link);
            if ($uid != false) {
                $embed_link = "https://www.youtube.com/embed/$uid";
                $iframe = isset($table['utubeimport_iframe']) ? $table['utubeimport_iframe'] : '<iframe width="560" height="349" src="SRC_LINK" frameborder="0" allowfullscreen="0"></iframe>';
                $iframe = str_replace('SRC_LINK', esc_url($embed_link), $iframe);
                return $iframe . $old_content;
            }
        }
        return $old_content;
    }

    function find_youtube_id($link)
    {
        $regex = "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/";
        if (preg_match($regex, $link, $uid)) {
            $uid = $uid[1];
            return $uid;
        }

        return false;
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
    public function process_video(array $data, array $details, SimplePie_Item $item)
    {
        $permalink = htmlspecialchars_decode($item->get_permalink());

        if (preg_match('#^https?://(www\.)?youtube\.com/watch#i', $permalink)) {
            $data['post_content'] = $permalink . PHP_EOL . PHP_EOL . $data['post_content'];
        }

        return $data;
    }

}

$ayoutubeaddon = new Autoblog_Addon_Youtube();