<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Feed form template class.
 *
 * @category Autoblog
 * @package Render
 * @subpackage Feeds
 *
 * @since 4.0.0
 */
class Autoblog_Render_Feeds_Form extends Autoblog_Render
{

    /**
     * Tips rendering object.
     *
     * @since 4.0.0
     *
     * @access private
     * @var WPMUDEV_Help_Tooltips
     */
    private $_tips;

    /**
     * Constructor.
     *
     * @since 4.0.0
     *
     * @access public
     * @param array $data The data what has to be associated with this render.
     */
    public function __construct($data = array())
    {
        parent::__construct($data);

        $this->_tips = new WPMUDEV_Help_Tooltips();
        $this->_tips->set_icon_url(AUTOBLOG_ABSURL . 'images/information.png');
    }

    /**
     * Renders template.
     *
     * @since 4.0.0
     *
     * @access protected
     */
    protected function _to_html()
    {
        $title = !empty($this->feed_id)
            ? esc_html__('Edit Auto Blog Feed', 'autoblogtext')
            : esc_html__('Create Auto Blog Feed', 'autoblogtext');

        ?>
        <div class="wrap">
        <div class="icon32" id="icon-edit"><br></div>
        <h2><?php echo $title ?></h2>

        <form id="autoblog-feeds-table" action="<?php echo add_query_arg('noheader', 'true') ?>" method="post">
            <?php wp_nonce_field('autoblog_feeds') ?>
            <?php $this->_render_form() ?>
        </form>
        </div><?php
    }

    /**
     * Renders form template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form()
    {
        ?>
    <div class="postbox autoblogeditbox" id="ab-<?php echo esc_attr($this->feed_id) ?>">

        <h3 class="hndle"><span><?php esc_html_e('Feed', 'autoblogtext') ?>
                :  <?php echo esc_html(stripslashes($this->title)) ?></span></h3>

        <div class="inside">
            <table width="100%" class="feedtable">
                <?php $this->_render_form_general_section() ?>
                <?php $this->_render_form_author_section() ?>
                <?php $this->_render_form_taxonomies_section() ?>
                <?php $this->_render_form_post_filters_section() ?>
                <?php $this->_render_form_post_excerpt_section() ?>
                <?php $this->_render_form_feed_processing_section() ?>
                <?php do_action('autoblog_feed_edit_form_end', null, $this) ?>
            </table>

            <div class="tablenav">
                <div class="alignright">
                    <a class="button"
                       href='admin.php?page=autoblog_admin'><?php esc_html_e('Cancel', 'autoblogtext') ?></a>
                    &nbsp;&nbsp;&nbsp;
                    <input class="button-primary delete save" type="submit"
                           value="<?php echo !empty($this->feed_id) ? esc_attr__('Update feed', 'autoblogtext') : esc_attr__('Create feed', 'autoblogtext') ?>"
                           style="margin-right: 10px;">
                </div>
            </div>
        </div>
        </div><?php
    }

    /**
     * Renders form general section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_general_section()
    {
        $post_types = get_post_types(array('public' => true), 'objects');
        $post_statuses = get_post_stati(array('public' => true, 'protected' => true, 'private' => true), 'objects', 'or');

        ?>
        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Your Title', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[title]" value="<?php echo esc_attr(stripslashes($this->title)) ?>"
                       class="long title field">
                <?php echo $this->_tips->add_tip(__('Enter a memorable title.', 'autoblogtext')); ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Feed URL', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[url]" value="<?php echo esc_attr(stripslashes($this->url)) ?>"
                       class="long url field">
                <?php echo $this->_tips->add_tip(__('Enter the feed URL.', 'autoblogtext')); ?>
            </td>
        </tr>

        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Add posts to', 'autoblogtext') ?></td>
            <td valign="top">
                <?php if (is_multisite() && is_network_admin()) : ?>
                    <select name="abtble[blog]" class="field blog">
                        <?php foreach ($this->_get_blogs_of_site() as $bkey => $blog) : ?>
                            <option value="<?php echo esc_attr($bkey) ?>"<?php selected($blog->id, $this->blog) ?>>
                                <?php echo esc_html($blog->domain . $blog->path) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo $this->_tips->add_tip(__('Select a blog to add the post to.', 'autoblogtext')) ?>
                <?php else : ?>
                    <strong>
                        <?php echo esc_html(function_exists('get_blog_option') ? get_blog_option((int)$this->blog, 'blogname') : get_option('blogname')) ?>
                    </strong>
                    <input type="hidden" name="abtble[blog]" value="<?php echo esc_attr($this->blog) ?>">
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Post type for new posts', 'autoblogtext') ?></td>
            <td valign="top">
                <select id="abtble_posttype" name="abtble[posttype]" class="field">
                    <?php foreach ($post_types as $key => $post_type) : ?>
                        <option value="<?php echo esc_attr($key) ?>"<?php selected($key, $this->posttype) ?>>
                            <?php echo esc_html($post_type->label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('Select the post type the imported posts will have in the blog.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Default status for new posts', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[poststatus]" class="field">
                    <?php foreach ($post_statuses as $key => $post_status) : ?>
                        <option value="<?php echo esc_attr($key) ?>"<?php selected($key, $this->poststatus) ?>>
                            <?php echo esc_html($post_status->label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('Select the status the imported posts will have in the blog.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
        <td valign="top" class="heading"><?php esc_html_e('Set the date for new posts', 'autoblogtext') ?></td>
        <td valign="top">
            <select name="abtble[postdate]" class="field">
                <option
                    value="current"<?php selected('current', $this->postdate) ?>><?php esc_html_e('Imported date', 'autoblogtext') ?></option>
                <option
                    value="existing"<?php selected('existing', $this->postdate) ?>><?php esc_html_e('Original posts date', 'autoblogtext') ?></option>
            </select>
            <?php echo $this->_tips->add_tip(__('Select the date imported posts will have.', 'autoblogtext')) ?>
        </td>
        </tr><?php

        do_action('autoblog_feed_edit_form_details_end', $this->feed_id, $this);

    }

    /**
     * Renders form author section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_author_section()
    {
        $blogusers = get_users('blog_id=' . $this->blog);

        ?>
        <tr class="spacer">
            <td colspan="2" class="spacer"><span><?php esc_html_e('Author details', 'autoblogtext') ?></span></td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Set author for new posts', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[author]" class="field author">
                    <option value="0"><?php esc_html_e('Use feed author', 'autoblogtext') ?></option>
                    <?php foreach ($blogusers as $bloguser) : ?>
                        <option
                            value="<?php echo esc_attr($bloguser->ID) ?>"<?php selected($bloguser->ID, $this->author) ?>>
                            <?php echo esc_html($bloguser->user_login) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('Select the author you want to use for the posts, or attempt to use the original feed author.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
        <td valign="top"
            class="heading"><?php esc_html_e('If author in feed does not exist locally use', 'autoblogtext') ?></td>
        <td valign="top">
            <select name="abtble[altauthor]" class="field altauthor">
                <?php foreach ($blogusers as $bloguser) : ?>
                    <option
                        value="<?php echo esc_attr($bloguser->ID) ?>"<?php selected($bloguser->ID, $this->altauthor) ?>>
                        <?php echo esc_html($bloguser->user_login) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php echo $this->_tips->add_tip(__('If the feed author does not exist in your blog then use this author.', 'autoblogtext')) ?>
        </td>
        </tr><?php
    }

    /**
     * Renders form taxonomies section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_taxonomies_section()
    {
        // backward compatibility
        switch ($this->feedcatsare) {
            case 'tags':
                $this->feedcatsare = 'post_tag';
                break;
            case 'categories':
                $this->feedcatsare = 'category';
                break;
        }

        // fetch all public taxonomies
        $taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');

        ?>
        <tr class="spacer">
            <td colspan="2" class="spacer"><span><?php esc_html_e('Taxonomies', 'autoblogtext') ?></span></td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Treat feed categories as', 'autoblogtext') ?></td>
            <td valign="top">
                <select id="abtble_feedcatsare" name="abtble[feedcatsare]">
                    <option></option>
                    <?php foreach ($taxonomies as $taxonomy_id => $taxonomy) : ?>
                        <option
                            value="<?php echo esc_attr($taxonomy_id) ?>"<?php selected($this->feedcatsare, $taxonomy_id) ?>
                            data-objects="<?php echo implode(',', $taxonomy->object_type) ?>">
                            <?php echo esc_html($taxonomy->label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>
                    <input type="checkbox" name="abtble[originalcategories]" class="case field"
                           value="1"<?php checked($this->originalcategories == 1) ?>>
                    <span><?php esc_html_e('Add any that do not exist.', 'autoblogtext') ?></span>
                </label>
                <?php echo $this->_tips->add_tip(__('Create any taxonomy terms that are needed.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Assign posts to this category', 'autoblogtext') ?></td>
            <td valign="top">
                <?php if (function_exists('switch_to_blog')) switch_to_blog($this->blog) ?>
                <?php wp_dropdown_categories(array(
                    'hide_empty' => 0,
                    'name' => 'abtble[category]',
                    'orderby' => 'name',
                    'selected' => $this->category,
                    'hierarchical' => true,
                    'show_option_none' => __('None', 'autoblogtext'),
                    'class' => 'field cat'
                )) ?>
                <?php if (function_exists('restore_current_blog')) restore_current_blog() ?>
                <?php echo $this->_tips->add_tip(__('Assign this category to the imported posts.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
        <td valign="top" class="heading"><?php esc_html_e('Add these tags to the posts', 'autoblogtext') ?></td>
        <td valign="top">
            <input type="text" name="abtble[tag]" value="<?php echo esc_attr(stripslashes($this->tag)) ?>"
                   class="long tag field">
            <?php echo $this->_tips->add_tip(__('Enter a comma separated list of tags to add.', 'autoblogtext')) ?>
        </td>
        </tr><?php
    }

    /**
     * Renders form post filters section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_post_filters_section()
    {
        ?>
        <tr class="spacer">
            <td colspan="2" class="spacer"><span><?php esc_html_e('Post Filtering', 'autoblogtext') ?></span></td>
        </tr>

        <tr>
            <td colspan="2"><?php esc_html_e('Include posts that contain (separate words with commas)', 'autoblogtext') ?></td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('All of these words', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[allwords]" value="<?php echo esc_attr(stripslashes($this->allwords)) ?>"
                       class="long title field">
                <?php echo $this->_tips->add_tip(__('A post to be imported must have ALL of these words in the title or content.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Any of these words', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[anywords]" value="<?php echo esc_attr(stripslashes($this->anywords)) ?>"
                       class="long title field">
                <?php echo $this->_tips->add_tip(__('A post to be imported must have ANY of these words in the title or content.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('The exact phrase', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[phrase]" value="<?php echo esc_attr(stripslashes($this->phrase)) ?>"
                       class="long title field">
                <?php echo $this->_tips->add_tip(__('A post to be imported must have this exact phrase in the title or content.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('None of these words', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[nonewords]"
                       value="<?php echo esc_attr(stripslashes($this->nonewords)) ?>" class="long title field">
                <?php echo $this->_tips->add_tip(__('A post to be imported must NOT have any of these words in the title or content.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td style="vertical-align: top"
                class="heading"><?php esc_html_e('Match Regular Expression', 'autoblogtext') ?></td>
            <td>
                <textarea name="abtble[regex]"
                          class="long title field"><?php echo esc_textarea(stripslashes($this->regex)) ?></textarea>
                <?php echo $this->_tips->add_tip(__('Use the "|" OR operator to combine multple expressions.', 'autoblogtext')) ?>
                <br>
                <span><?php _e('Use the <a href="http://www.php.net/manual/en/reference.pcre.pattern.syntax.php">PCRE pattern syntax</a> for your regular expression, including delimiters and escaping.', 'autoblogtext') ?></span>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Any of these tags', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[anytags]" value="<?php echo esc_attr(stripslashes($this->anytags)) ?>"
                       class="long title field">
                <?php echo $this->_tips->add_tip(__('A post to be imported must be marked with any of these categories or tags.', 'autoblogtext')) ?>
                <br>
                <span><?php esc_html_e('Tags should be comma separated', 'autoblogtext') ?></span>
            </td>
        </tr>
    <?php
    }

    /**
     * Renders form post_excerpt section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_post_excerpt_section()
    {
        ?>
        <tr class="spacer">
            <td colspan="2" class="spacer"><span><?php esc_html_e('Post excerpts', 'autoblogtext') ?></span></td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Use full post or an excerpt', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[useexcerpt]" class="field">
                    <option value="1"><?php esc_html_e('Use Full Post', 'autoblogtext') ?></option>
                    <option
                        value="2"<?php selected(2, $this->useexcerpt) ?>><?php esc_html_e('Use Excerpt', 'autoblogtext') ?></option>
                </select>
                <?php echo $this->_tips->add_tip(__('Use the full post (if available) or create an excerpt.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('For excerpts use', 'autoblogtext') ?></td>
            <td valign="top">
                <input type="text" name="abtble[excerptnumber]"
                       value="<?php echo esc_attr(stripslashes($this->excerptnumber)) ?>" class="narrow field"
                       style='width: 3em;'>
                <select name="abtble[excerptnumberof]" class="field">
                    <option
                        value="words"<?php selected('words', $this->excerptnumberof) ?>><?php esc_html_e('Words', 'autoblogtext') ?></option>
                    <option
                        value="sentences"<?php selected('sentences', $this->excerptnumberof) ?>><?php esc_html_e('Sentences', 'autoblogtext') ?></option>
                    <option
                        value="paragraphs"<?php selected('paragraphs', $this->excerptnumberof) ?>><?php esc_html_e('Paragraphs', 'autoblogtext') ?></option>
                </select>
                <?php echo $this->_tips->add_tip(__('Specify the size of the excerpt to create (if selected)', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
        <td valign="top" class="heading"><?php esc_html_e('Link to original source', 'autoblogtext') ?></td>
        <td valign="top">
            <input type="text" name="abtble[source]" value="<?php echo esc_attr(stripslashes($this->source)) ?>"
                   class="long source field">
            <?php echo $this->_tips->add_tip(__('If you want to link back to original source, enter a phrase to use here.', 'autoblogtext')) ?>
            <br>
            <label>
                <input type="checkbox" name="abtble[nofollow]" value="1"<?php checked($this->nofollow == 1) ?>>
                <?php esc_html_e('Ensure this link is a nofollow one', 'autoblogtext') ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="abtble[newwindow]" value="1"<?php checked($this->newwindow == 1) ?>>
                <?php esc_html_e('Open this link in a new window', 'autoblogtext') ?>
            </label>
        </td>
        </tr><?php
    }

    /**
     * Renders form feed processing section template.
     *
     * @since 4.0.0
     *
     * @access private
     */
    private function _render_form_feed_processing_section()
    {
        ?>
        <tr class="spacer">
            <td colspan="2" class="spacer"><span><?php esc_html_e('Feed Processing', 'autoblogtext') ?></span></td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Import the most recent', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[poststoimport]" class="field">
                    <option value="0"><?php esc_html_e('posts.', 'autoblogtext') ?></option>
                    <?php for ($n = 1; $n <= 100; $n++) : ?>
                        <option value="<?php echo $n ?>"<?php selected($n, $this->poststoimport) ?>>
                            <?php echo $n ?> <?php esc_html_e('added posts.', 'autoblogtext') ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('You can set this to only import a specific number of new posts rather than as many as the plugin can manage.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Process this feed', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[processfeed]" class="field">
                    <option
                        value="0"<?php selected(0, $this->processfeed) ?>><?php esc_html_e('Never (paused)', 'autoblogtext') ?></option>

                    <option
                        value="5"<?php selected(5, $this->processfeed) ?>><?php esc_html_e('every 5 minutes', 'autoblogtext') ?></option>
                    <option
                        value="10"<?php selected(10, $this->processfeed) ?>><?php esc_html_e('every 10 minutes', 'autoblogtext') ?></option>
                    <option
                        value="15"<?php selected(15, $this->processfeed) ?>><?php esc_html_e('every 15 minutes', 'autoblogtext') ?></option>
                    <option
                        value="20"<?php selected(20, $this->processfeed) ?>><?php esc_html_e('every 20 minutes', 'autoblogtext') ?></option>
                    <option
                        value="25"<?php selected(25, $this->processfeed) ?>><?php esc_html_e('every 25 minutes', 'autoblogtext') ?></option>

                    <option
                        value="30"<?php selected(30, $this->processfeed) ?>><?php esc_html_e('every 30 minutes', 'autoblogtext') ?></option>
                    <option
                        value="60"<?php selected(60, $this->processfeed) ?>><?php esc_html_e('every hour', 'autoblogtext') ?></option>
                    <option
                        value="90"<?php selected(90, $this->processfeed) ?>><?php esc_html_e('every 1 hour 30 minutes', 'autoblogtext') ?></option>
                    <option
                        value="120"<?php selected(120, $this->processfeed) ?>><?php esc_html_e('every 2 hours', 'autoblogtext') ?></option>
                    <option
                        value="150"<?php selected(150, $this->processfeed) ?>><?php esc_html_e('every 2 hours 30 minutes', 'autoblogtext') ?></option>
                    <option
                        value="300"<?php selected(300, $this->processfeed) ?>><?php esc_html_e('every 5 hours', 'autoblogtext') ?></option>
                    <option
                        value="1449"<?php selected(1449, $this->processfeed) ?>><?php esc_html_e('every day', 'autoblogtext') ?></option>
                </select>
                <?php echo $this->_tips->add_tip(__('Set the time delay for processing this feed, irregularly updated feeds do not need to be checked very often.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Starting from', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[startfromday]" class="field">
                    <option></option>
                    <?php for ($n = 1; $n <= 31; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->startfrom) && $n == date('j', $this->startfrom)) ?>><?php echo $n ?></option>
                    <?php endfor; ?>
                </select>
                <select name="abtble[startfrommonth]" class="field">
                    <option></option>
                    <?php for ($n = 1; $n <= 12; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->startfrom) && $n == date('n', $this->startfrom)) ?>><?php echo date('M', strtotime(date('Y-' . $n . '-1'))) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="abtble[startfromyear]" class="field">
                    <option></option>
                    <?php for ($n = date("Y") - 10; $n <= date("Y") + 9; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->startfrom) && $n == date('Y', $this->startfrom)) ?>><?php echo $n ?></option>
                    <?php endfor; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('Set the date you want to start processing posts from.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Ending on', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[endonday]" class="field">
                    <option></option>
                    <?php for ($n = 1; $n <= 31; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->endon) && $n == date('j', $this->endon)) ?>><?php echo $n ?></option>
                    <?php endfor; ?>
                </select>
                <select name="abtble[endonmonth]" class="field">
                    <option></option>
                    <?php for ($n = 1; $n <= 12; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->endon) && $n == date('n', $this->endon)) ?>><?php echo date('M', strtotime(date('Y-' . $n . '-1'))) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="abtble[endonyear]" class="field">
                    <option></option>
                    <?php for ($n = date("Y") - 10; $n <= date("Y") + 9; $n++) : ?>
                        <option
                            value="<?php echo $n ?>"<?php selected(!empty($this->endon) && $n == date('Y', $this->endon)) ?>><?php echo $n ?></option>
                    <?php endfor; ?>
                </select>
                <?php echo $this->_tips->add_tip(__('Set the date you want to stop processing posts from this feed.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
            <td valign="top" class="heading"><?php esc_html_e('Force SSL verification', 'autoblogtext') ?></td>
            <td valign="top">
                <select name="abtble[forcessl]" class="field">
                    <option value="yes"><?php esc_html_e('Yes', 'autoblogtext') ?></option>
                    <option
                        value="no"<?php selected('no', $this->forcessl) ?>><?php esc_html_e('No', 'autoblogtext') ?></option>
                </select>
                <?php echo $this->_tips->add_tip(__('If you are getting SSL errors, or your feed uses a self-signed SSL certificate then set this to <strong>No</strong>.', 'autoblogtext')) ?>
            </td>
        </tr>

        <tr>
        <td valign="top" class="heading"><?php esc_html_e('Override duplicates', 'autoblogtext') ?></td>
        <td valign="top">
            <select name="abtble[overridedups]" class="field">
                <option value="no"><?php esc_html_e('No', 'autoblogtext') ?></option>
                <option
                    value="yes"<?php selected('yes', $this->overridedups) ?>><?php esc_html_e('Yes', 'autoblogtext') ?></option>
            </select>
            <?php echo $this->_tips->add_tip(__('Select yes if you want to override previously imported items with the new content. Otherwise duplicates will be skipped.', 'autoblogtext')) ?>
        </td>
        </tr><?php
    }

    /**
     * Returns blogs of the site.
     *
     * @since 4.0.0
     *
     * @access private
     * @global type $current_site
     * @global type $wpdb
     * @param type $siteid
     * @param type $all
     * @return type
     */
    private function _get_blogs_of_site($siteid = false, $all = false)
    {
        global $current_site, $wpdb;
        if (!$siteid && !empty($current_site)) {
            $siteid = $current_site->id;
        }

        $blogs = array();
        $results = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d ORDER BY path ASC", $siteid));
        foreach ($results as $blog_id) {
            $blog = get_blog_details($blog_id);
            if (!empty($blog) && isset($blog->domain)) {
                $blogs[$blog_id] = $blog;
                $blogs[$blog_id]->id = $blog_id;
            }
        }
        //sort by alphebeta
        //get the main blog out of array
        //$main_blog = array_shift($blogs);
        //usort($blogs, array(&$this, '_sort_blogs_by_name'));
        //$blogs = array_merge(array($main_blog), $blogs);
        return $blogs;
    }

    function _sort_blogs_by_name($a, $b)
    {
        return strcmp($a->path, $b->path);
    }

}