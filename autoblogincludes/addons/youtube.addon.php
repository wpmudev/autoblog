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
        //$this->_add_filter('autoblog_pre_post_insert', 'process_video', 11, 3);
        //$this->_add_filter('autoblog_pre_post_update', 'process_video', 11, 3);
        $this->_add_action('autoblog_feed_edit_form_end', 'render_utube_options', 10, 2);
        //by default, wp will strip iframe, we will need to use the filter autoblog_post_content_before_import
        $this->_add_filter('autoblog_post_content_before_import', 'process_content', 10, 3);
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
        //we need to check does the disable santinitize add-on activated
        if (isset($details['disablesanitization']) && $details['disablesanitization'] == 1) {
            return $old_content;
        }
        $method = trim(isset($details['utubeimport']) ? $details['utubeimport'] : AUTOBLOG_IMAGE_CHECK_ORDER);
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
            $regex = "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/";
            if (preg_match($regex, $link, $uid)) {
                $uid = $uid[1];
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
            $iframe = isset($table['utubeimport_iframe']) ? $table['utubeimport_iframe'] : '<iframe width="560" height="349" src="SRC_LINK" frameborder="0" allowfullscreen="0"></iframe>';
            $iframe = str_replace('SRC_LINK', esc_url($link), $iframe);
            return $iframe . $old_content;
        }
        return $old_content;
    }

    function process_content1($old_content, $details, SimplePie_Item $item)
    {
        //we need to check does the disable santinitize add-on activated
        if (isset($details['disablesanitization']) && $details['disablesanitization'] == 1) {
            return $old_content;
        }
        global $allowedposttags;
        $allowedposttags['iframe'] = array(
            "src" => array(),
            "height" => array(),
            "width" => array()
        );
        $content = $item->get_content();
        $doc = new DOMDocument();
        $can_use_dom = @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $doc->preserveWhiteSpace = false;

        if ($can_use_dom) {
            //now only allow iframe from youtube
            $iframes = $doc->getElementsByTagName('iframe');
            foreach ($iframes as $iframe) {
                $url = $iframe->getAttribute('src');
                if (strpos($url, '//') == 0) {
                    $url = 'http:' . $url;
                }
                if (!stristr(parse_url($url, PHP_URL_HOST), 'youtube.com')) {
                    $iframe->parentNode->removeChild($iframe);
                }
            }
            $new_content = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $doc->saveHTML());

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
    public function process_video(array $data, array $details, SimplePie_Item $item)
    {
        $permalink = htmlspecialchars_decode($item->get_permalink());

        if (preg_match('#^https?://(www\.)?youtube\.com/watch#i', $permalink)) {
            $data['post_content'] = $permalink . PHP_EOL . PHP_EOL . $data['post_content'];
        }

        return $data;
    }


    private function linkifyYouTubeURLs($text)
    {
        $text = preg_replace('~
        # Match non-linked youtube URL in the wild. (Rev:20130823)
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube         # or youtube.com or
          (?:-nocookie)?  # youtube-nocookie.com
          \.com           # followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\s-]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w.-]*    # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w.-]*        # Consume any URL (query) remainder.
        ~ix',
            '<a href="http://www.youtube.com/watch?v=$1">YouTube link: $1</a>',
            $text);
        return $text;
    }

}

$ayoutubeaddon = new Autoblog_Addon_Youtube();