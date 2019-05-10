# Autoblog


**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## Want to repost blog content from multiple sites to one place? Automate posting to WordPress and Multisite using RSS feeds with Autoblog.

No code required. No complicated instructions. Just copy and paste a feed URL and Autoblog will start importing content to your blog. 

![autoblog-add-feed-735x470](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-add-feed-735x470.jpg)

 Start a post feed to share content from a bunch of blogs in one place.

### Content From Anywhere

Schedule regular importing and keep your thread filled with fresh, relevant content from all over the web. If you manage multiple blogs Autoblog can save you a bunch of time. Click publish and Autoblog will import and publish your post to the rest of your sites.

### The Control You Need

Post the most relevant content to your blog every time. Use word, phrase, expression and tag filters to control what posts import. Set tags, categories and custom author information.

![Auto-Blog-Stats-735x470](https://premium.wpmudev.org/wp-content/uploads/2009/08/Auto-Blog-Stats-735x470.jpg)

 Track feed activity from your dashboard with the included stats module

 

![autoblog-linkback](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-linkback.jpg)

 Set Autoblog to link to original post.

### Give Credit Where Credit Is Due

Set Autoblog to only post an excerpt from imported posts. Link readers back to the original source to help build a bigger network of followers. Link backs and cross promotion is a great way to build your site.

### Tons of Add-ons

Extend function by activating any of the 17 included add-ons. Improve feed compatibility, import or strip post images, use original featured image, WPML support, auto tweet, embed video from a YouTube feed – It’s everything you need in one plugin.

![autoblog-addons](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-addons.jpg)

 17 included add-ons to make managing your post feed easier.

 Remember – with great power comes great responsibility. Sharing content should always be done with permission of the author.

## Usage

### To Get Started:

Start by reading [Installing plugins](https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/) section in our comprehensive [WordPress and WordPress Multisite Manual](https://premium.wpmudev.org/wpmu-manual/) if you are new to WordPress. _Please note that Autoblog pulls in content from RSS feeds. So if the content is not included in a feed, the plugin cannot fetch it. :)_ Login to your admin panel for WordPress or Multisite and activate the plugin:

*   On regular WordPress installs - visit **Plugins** and **Activate** the plugin.
*   For WordPress Multisite installs - Activate it blog-by-blog (say if you wanted to make it a Pro Sites premium plugin), or visit **Network Admin -> Plugins** and **Network Activate** the plugin.

Once installed and activated, you will see a new menu item in your admin: Autoblog. 

![Autoblog Menu](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-menu.png) Let's start this overview by getting a feel for the reporting features of this plugin.

### Reporting Features

Go ahead and click on the Dashboard link in the Autoblog menu now. If this is the first time you install Autoblog, there won't be any data to display on that page yet. Don't worry, we'll get that fixed up in a jiffy! 

![Autoblog Dashboard New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-dashboard-new.png) However, if you have just updated your Autoblog install to the latest version (4.0), you should see that page already populated with data from your current feeds. 

![Autoblog Dashboard Updated](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4040-dashboard-updated1.png) The graph at the top gives you a quick overview of Autoblog activity over the course of the last 7 days. Hover your mouse pointer over any day to pop up a few details about that day's imports.

*   How many feeds were processed
*   How many items were imported
*   How many errors were logged

Want all the juicy details about a particular day's import activity? No problem. Scroll down in the list of feeds beneath the graph until you find the date you want. 

![Autoblog Feed](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-feed.png) In the title bar of each feed, handy icons show you the day's activity for that particular one.

*   How many new items were imported during the day
*   How many times that feed was processed during the day
*   How many errors were logged for that feed during the day

![Autoblog Feed Titles](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-feed-titles.png) Now click on the title of the feed you want to view. 

![Autoblog Feed Info](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-feed-info.png) The first entry will tell you how many new items were imported that day. Each subsequent entry gives you all the details about each imported or updated item. Yes, you read that right, you can set your Autoblog feeds to re-import feed items and keep them updated on your site. How cool is that? The title of each entry links to the original source of the feed item, while the _(view post)_ link at the end goes to the post on your site. Awesome stuff, you say? We agree. :)

### Managing Feeds

Let's now take a look at what goes on under the All Feeds menu item. Click that now. This page displays all your feeds in a familiar format. 

![Autoblog All Feeds](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-all-feeds-main.png) Hover your mouse pointer over any feed to reveal the actions you can take for each one. 

![Autoblog All Feeds](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4040-all-feeds.png)

*   The _Edit_ link will open the feed editor so you can adjust things if you need to.
*   Click the _Clone_ link to create a duplicate feed that you can then adjust.
*   Click _Process_ to manually override the import schedule and process the feed immediately.
*   The _Validate_ link will automatically redirect to [w3.org](http://validator.w3.org/feed/ "W3C Feed Validation Service") with the correct URI appended so you be sure your feed is OK.
*   And of course, _Delete_ will delete the feed permanently.

### Creating a Feed

Before we get into all the addons you can activate for Autoblog, let's first take a look at the basic settings for creating or editing a feed. Click the Add New button at the top of the All Feeds page. 

![Autoblog Add New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-add-new.png) There are a lot of options on this page, so let's go over them in sections.

##### Feed

The first section is where you name & describe your feed. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-1.png)

*   The _Your Title_ field is where you should enter a memorable name for your feed.
*   The _Feed URL_ is, well, the URL for your feed. You can usually get that URL by clicking the RSS icon on the site you want the feed from. Some sites might use a service like Feedburner where the URL would look like this: [http://feeds.feedburner.com/ICanHasCheezburger](http://feeds.feedburner.com/ICanHasCheezburger) ...that's one of my favorites. :)
*   In the _Add posts to_ dropdown, select the site in your network where you want the feed to post the feed items. Note that if you are running a single site install, this will simply display the name of your site.
*   _Post type for new posts_ will display all the post types available on the site you just selected. All feed items imported by this feed will be added to your selected site as the post type you select here.
*   Select the _Default status for new posts_ imported by your feed.
*   Finally, to _Set the date for new posts_, you can choose either the date the post was imported to your site, or the original date of the post on the source site.

##### Author Details

Next up, the author details section. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-2.png)

*   _Set author for new posts_ enables you to select an existing user on your site, or attempt to use the original author from the source site.
*   If you have selected original feed author above, and _If author in feed does not exist locally use_ an existing author on your site.

##### Taxonomies

The Taxonomies section enables you to fine-tune how imported posts should be categorized on your site. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-3.png)

*   Select to _Treat feed categories as_ any taxonomy associated with the post type you chose above.
*   Check the _Add any that do not exist_ checkbox to automatically create any taxonomy terms that are needed and that don't already exist on your site. Watch out though. Checking this could potentially create hundreds of additional categories on your site if you are importing a feed from a public site where folks can tag their posts willy-nilly.
*   At _Assign posts to this category_, you can also select to assign existing categories on your site to all imported items.
*   Enter a comma-separated list of additional tags you want on all imported items in the _Add these tags to the posts_ field.

##### Post Filtering

The Post Filtering section it quite powerful. It enables you to identify exactly which feed items to import by specifying the content that should or should not be present in feed items. This way, you can filter out stuff that has no business being on your site. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-4.png)

*   At _All of these words_, you can specify all words that must be present in a feed item for it to be imported.
*   You can also specify that imported items must contain _Any of these words_. If any word you enter here is found in a feed item, it will be imported.
*   If you wish, you can specify _The exact phrase_ that must be present in the title or content of feed items.
*   You can also filter out feed items that should contain _None of these words_. Feed items with any words entered here will not be imported. Great for filtering out profanity on PG-13 sites. :)
*   Finally, you can specify that imported items must contain _Any of these tags_. Only feed items that have been categorized or tagged on the source site with terms you enter here will imported.

##### Post Excerpts

This section enables you to specify how much content should be imported in each feed item. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-5.png)

*   Select to _Use full post or an excerpt_ in imported items. Note that if the feed only contains excerpts anyway, Autoblog cannot fetch the full content from the source site.
*   If you have selected to import excerpts, then at _For excerpts use_, enter the number of Words, Sentences or Paragraphs your imported excerpts should contain.
*   The _Link to original source_ field is where you can specify the text that will appear after each imported item, and that will link to the original item on the source site.
*   Check Ensure this link is a nofollow one if you are concerned about a possible negative impact on your site's PageRank. See [this article at Google](https://support.google.com/webmasters/answer/96569?hl=en) for more.
*   Check _Open this link in a new window_ to, well, you know. :)

##### Feed Processing

Feed processing is where you schedule how much of the feed should be imported, how often, and how to import it. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-6.png)

*   First, select to _Import the most recent_ number of posts (feed items) that you want. You can set it to import up to 100 items each time the feed is processed.
*   Then select the frequency at which to _Process this feed_. You can also pause the feed by setting this to "Never".
*   _Starting from_ and _Ending on_ is where you can specify the date range for the imported items you want. If you don't want it to stop, leave the _Ending on_ fields blank.
*   Set _Force SSL verification_ to "No" if you are getting SSL errors, or if your feed uses a self-signed SSL certificate.
*   Set _Override duplicates_ to "Yes" if you want to update previously imported items with any new content that may be available. Set this to "No" to skip any duplicates in the feed.

If you've been setting up your first feed while following along, now's the time to click the _Create Feed_ button at the bottom right of your screen. If you do not want to keep the feed you just set up, simply click _Cancel_. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-7.png) Once you've created your feed, you'll be redirected to the All Feeds page where a nice little confirmation will appear at the top of the screen. 

![Autoblog Create New](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-create-8.png) You may not see details appear immediately on the _Autoblog Dashboard_ screen as importing the feed items and processing can take a bit of time. Patience, Grasshopper. :) Now it's time to have a play with all the cool addons that you can activate for Autoblog.

### Addons

![Autoblog Addons](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-main.png) As you can see, there are quite a few addons! Let's take at look at each one, shall we?

##### Allow Force Feed

This addon adds a new checkbox at the bottom of the feed settings page that allows you to override feed validation, and force a feed to process even if it has an incorrect MIME type. This can help with compatibility for unusual feeds. Use with caution. 

![Autoblog Addons Force Feed](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-force-feed.png)

##### Append text to post

This one enables you to append some custom content to the end of each imported feed item. You can also use any shortcodes you need to here. 

![Autoblog Addons Append Text](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-append-text.png) A variety of placeholders are available to dynamically insert the text too:

*   %ORIGINALPOSTURL% adds a link to the original post on the source site.
*   %FEEDURL% displays the URL of the feed used to fetch the post.
*   %FEEDTITLE% displays the title of the feed.
*   %POSTIMPORTEDTIME% displays the time at which the post was imported.
*   %FEEDID% will display the ID of the feed.
*   %ORIGINALPOSTGUID% the ID provided for the post through the feed.
*   %ORIGINALAUTHORNAME% will display the name of the author of the post from the source site.
*   %ORIGINALAUTHOREMAIL% will display the original author's email if available in the feed.
*   %ORIGINALAUTHORLINK% will display a link to the original author's profile on the source site if available.

##### Canonical link in header

This addon doesn't add any option to the feed settings page. What it does do is link the post title to the original source. This can be very handy if you are sourcing content from a variety of sites (like a news aggregation site) and using excerpts. Visitors to your site who wish to read the full article will be redirected to the source site.

##### Check Feed Entries

This one checks to make sure the blog id entries match in each feed entry.

##### Clean Face

This addon cleans non-validating feeds like Facebook, it fixes Facebook spoofed relative links. No further options are provided, it simply does its task once activated.

##### Disable Sanitization

Allows you to override feed content sanitization and force a feed to import bare content even if it has usually blocked tags. This can help with compatibility for unusual feeds. Use with caution. 

![Autoblog Addons Disable Sanitization](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-disable-sanitization.png)

##### Featured Image Import

This addon will import the featured image along with the feed item. It also adds the image to your media library, attaches it to the imported post, and marks it as featured image. 

![Autoblog Addons Featured Image](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4040-addons-featured-image.png) Once activated, you can select which type of image in the feed item to use on your site as the featured image.

*   Don't import featured image (turns the feature off for this feed without deactivating the addon).
*   Use media:thumbnail tag of a feed item.
*   Find the first image within content of a feed item.
*   Find the last image within content of a feed item.
*   You can also upload a default image to use if no image is found or imported.

##### Image Import

This addon imports any/all images in a feed item to your media library and attaches them to the imported post. It doesn't provide any additional options, simply activate and it takes care of the image imports!

##### Open links in popup

A very powerful and handy addon, this one lets you display imported posts within a popup dialog. Once activated, you'll see an _Open Link in Popup_ section in the feed settings. 

![Autoblog - Open link in popup add-on](https://premium.wpmudev.org/wp-content/uploads/2009/08/Autoblog-Open-link-in-popup-add-on.png) Tick the _Do you want to turn off this feature for this feed_ option to exclude this particular feed from using this feature. This lets you easily disable to feature on a per-feed basis. Enter any domains that you want to exclude, one per line, within the _Domain you want to exclude_ text area. Tick the _Always refresh content_ option to automatically re-index the link anytime you want to update the content. Any time the feed is processed, the content will be refreshed with any changes in the original articles.

##### Post formats addon

This addon lets you specify a post format on a per-feed basis. When activated, you'll see a new _Post format for new posts_ section when creating or editing a feed, where you'll be able to specify the post format of your choice. 

![Autoblog - Post Format add-on](https://premium.wpmudev.org/wp-content/uploads/2009/08/Autoblog-Post-Format-add-on.png)

##### Replace Author Information

This one has no settings either. It simply replaces the author details shown on a post with those of the original author from the source site.

##### Strip Images

This addon removes all image tags from the imported post content and allows you to replace those tags with your own custom text. 

![Autoblog Addons Strip Images](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-strip-images.png)

##### Twitter Add-on

This addon adds a Twitter post type and processes tweets to have correct links. 

![Autoblog Addons Twitter](https://premium.wpmudev.org/wp-content/uploads/2009/08/autoblog-4030-addons-twitter.png)

##### Use External Permalinks

This add-on lets you link to the original post using the post's permalink. When reader's click the post's link, they'll be taken to the original article.

##### WPML Languages

If you use [WPML](http://wpml.org/ "WPML"), you'll need a way to ensure the plugin is notified of post additions done through Autoblog. This addon takes care of that, ensuring WPML is notified so that posts can automatically get translated. Simply activate and it'll take care of the rest.

##### YouTube Feed Import

A very handy add-on for video content, this one lets you pull a YouTube feed, adding the video to the beginning of a post. There are no additional options for this add-on but once activated, you can link to a YouTube feed url and it will take care of parsing the feed and pulling video content as new posts.

### Known Issues

The plugin attempts to create a database table. If it isn't automatically created then you need to run the following SQL on your database: `CREATE TABLE wp_autoblog ( feed_id bigint(20) NOT NULL auto_increment, site_id bigint(20) default NULL, blog_id bigint(20) default NULL, feed_meta text, active int(11) default NULL, nextcheck bigint(20) default NULL, lastupdated bigint(20) default NULL, PRIMARY KEY ( feed_id), KEY site_id (site_id), KEY blog_id (blog_id), KEY nextcheck (nextcheck) )` If you are using the Multi-DB system, then you need to create the table in your global database and ensure you have added the "autoblog" table as a global table in your configuration file.
