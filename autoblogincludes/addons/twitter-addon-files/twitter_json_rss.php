<?php
/**
 * Author: Hoang Ngo
 */
if ( ! isset( $_GET['feed_id'] ) || ! filter_var( $_GET['feed_id'], FILTER_VALIDATE_INT ) ) {
	die( 'Hey, wrong way' );
}
//load libs
$docroot = dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) );
require_once $docroot . DIRECTORY_SEPARATOR . 'wp-load.php';
require_once 'twitter_oauth/class-autoblog-twitter.php';

//get autoblog config
global $wpdb;
//get feed
$sql = 'SELECT * FROM ' . AUTOBLOG_TABLE_FEEDS . ' WHERE feed_id = %d';
$feed = $wpdb->get_row( $wpdb->prepare( $sql, array( $_GET['feed_id'] ) ), ARRAY_A );

if ( empty( $feed ) ) {
	die( 'Feed not found!!' );
}
// all goods
$meta = maybe_unserialize( $feed['feed_meta'] );
if ( ! is_array( $meta ) || empty( $meta ) ) {
	die( 'Some error happen, blank data!' );
}

$twitter_api = $meta['twitter_consumer_key'];
$twitter_api_secret = $meta['twitter_secret_key'];
$twitter_user_name = $meta['twitter_user_name'];

///inition & connect to twitter
$connection                 = new Autoblog_Twitter( $twitter_api, $twitter_api_secret );
$connection->host           = 'https://api.twitter.com/1.1/';
$connection->ssl_verifypeer = true;
$timeline                   = $connection->get( 'statuses/user_timeline.json?screen_name=' . $twitter_user_name . '&count=100&include_rts=1' );
$now = date("D, d M Y H:i:s T");
$now = rfc822Date($now);
$link = htmlspecialchars( 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'] );
$description = $twitter_user_name;
//output rss
ob_clean();
ob_end_clean();
header( "Content-Type: application/xml; charset=UTF-8" );
?>
	<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
		<channel>
			<title><?php echo $twitter_user_name; ?></title>
			<link><?php echo $link; ?></link>
			<atom:link href="<?php echo $link; ?>" rel="self" type="application/rss+xml" />
			<description><?php echo $description; ?></description>
			<pubDate><?php echo $now; ?></pubDate>
			<lastBuildDate><?php echo $now; ?></lastBuildDate>
			<?php
			$tweets = $timeline;
			foreach ( $tweets as $line ) {
/*				echo '<pre>';
				print_r($line);
				echo '</pre>';*/
				$title       = htmlspecialchars( htmlspecialchars_decode( $line->user->name . ": " . strip_tags( $line->text ) ) );
				$description = htmlspecialchars( htmlspecialchars_decode( strip_tags( $line->text ) ) );
				$url         = htmlspecialchars( "https://twitter.com/" . $line->user->screen_name . "/statuses/" . $line->id_str );;
				@$image      = ( strlen( $line->entities->media[0]->media_url ) > 0 ) ? htmlspecialchars( $line->entities->media[0]->media_url ) : null;
				$created_at = rfc822Date( $line->created_at );

				?>
				<item>
					<title><?php echo $title; ?></title>
					<description>
						<![CDATA[
						<?php
						echo $description;
						if ( strlen( @$line->entities->media[0]->media_url ) > 0 ) {
							?>
							<img src="<?php echo $image; ?>">
						<?php
						}
						?>    ]]>
					</description>
					<pubDate><?php echo $created_at ?></pubDate>
					<guid><?php echo $url; ?></guid>
					<link><?php echo $url; ?></link>
				</item>
			<?php
			}
			?>
		</channel>
	</rss>
<?php
function rfc822Date( $str ) {
	$timestamp = strtotime( $str );

	return date( DATE_RSS, $timestamp );
}

?>