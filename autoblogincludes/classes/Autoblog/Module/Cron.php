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
 * Cron module.
 *
 * @since 4.0.0
 *
 * @category Autoblog
 * @package Module
 */
class Autoblog_Module_Cron extends Autoblog_Module {

	const NAME = __CLASS__;

	/**
	 * Cron processing timestamp.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var int
	 */
	private $_cron_timestamp = 0;

	/**
	 * The id of currently processing feed.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @var int
	 */
	private $_feed_id = 0;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param Autoblog_Plugin $plugin The instance of Autoblog plugin class.
	 */
	public function __construct( Autoblog_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_action( Autoblog_Plugin::SCHEDULE_PROCESS, 'process_feeds' );

		$this->_add_action( 'autoblog_pre_process_feed', 'update_feed_check_timestamps', 10, 2 );
		$this->_add_action( 'autoblog_pre_process_feed', 'switch_to_feed_blog', 10, 2 );

		$this->_add_filter( 'autoblog_pre_post_insert', 'get_post_content_and_statuses', 10, 3 );
		$this->_add_filter( 'autoblog_pre_post_insert', 'get_post_author_id', 10, 3 );
		$this->_add_filter( 'autoblog_pre_post_insert', 'get_post_dates', 10, 3 );

		$this->_add_action( 'autoblog_post_post_insert', 'add_post_meta', 1, 3 );
		$this->_add_action( 'autoblog_post_post_insert', 'add_post_taxonomies', 1, 3 );

		$this->_add_action( 'autoblog_post_process_feed', 'restore_switch_blog', 10, 2 );
	}

	/**
	 * Updates feed check timestamps.
	 *
	 * @since 4.0.0
	 * @action autoblog_pre_process_feed 10 2
	 *
	 * @access public
	 * @param int $feed_id The feed ID.
	 * @param array $details The feed details.
	 */
	public function update_feed_check_timestamps( $feed_id, array $details ) {
		$time = current_time( 'timestamp' );
		$this->_wpdb->update( AUTOBLOG_TABLE_FEEDS, array(
			'lastupdated' => $time,
			'nextcheck'   => $time + absint( $details['processfeed'] ) * MINUTE_IN_SECONDS,
		), array(
			'feed_id' => $feed_id,
		), array( '%d', '%d' ), array( '%d' ) );
	}

	/**
	 * Switches current blog to feed's blog if need be.
	 *
	 * @since 4.0.0
	 * @action autoblog_pre_process_feed 10 2
	 *
	 * @access public
	 * @param int $feed_id The feed ID.
	 * @param array $details The feed details.
	 */
	public function switch_to_feed_blog( $feed_id, array $details ) {
		$blogid = !empty( $details['blog'] ) ? absint( $details['blog'] ) : 0;
		if ( $blogid && function_exists( 'switch_to_blog' ) ) {
			switch_to_blog( $blogid );
		}
	}

	/**
	 * Restore previously switched current blog.
	 *
	 * @since 4.0.0
	 * @action autoblog_post_process_feed 10 2
	 *
	 * @access public
	 * @param int $feed_id The feed ID.
	 * @param array $details The feed details.
	 */
	public function restore_switch_blog( $feed_id, array $details ) {
		$blogid = !empty( $details['blog'] ) ? absint( $details['blog'] ) : 0;
		if ( $blogid && function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}
	}

	/**
	 * Processes feeds by cron job.
	 *
	 * @since 4.0.0
	 * @action autoblog_process_feeds
	 *
	 * @access public
	 * @param array $feed_ids The array of feed IDs to process.
	 */
	public function process_feeds( $feed_ids = array() ) {
		// prepare ids
		$feed_ids = array_filter( array_map( 'intval', $feed_ids ) );
		$force = !empty( $feed_ids );
		if ( empty( $feed_ids ) ) {
			$feed_ids = $this->_wpdb->get_col( sprintf(
				'SELECT feed_id FROM %s WHERE site_id = %d AND nextcheck BETWEEN 0 AND %d ORDER BY nextcheck',
				AUTOBLOG_TABLE_FEEDS,
				!empty( $this->_wpdb->siteid ) ? $this->_wpdb->siteid : 1,
				current_time( 'timestamp' )
			) );
		}

		// return if nothing to process
		if ( empty( $feed_ids ) ) {
			return;
		}

		$this->_cron_timestamp = current_time( 'timestamp' );

		ignore_user_abort( true );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		// setup simplepie stuff
		$this->_add_action( 'wp_feed_options', 'setup_simplepie_options' );
		$this->_add_filter( 'wp_feed_cache_transient_lifetime', 'get_feed_cahce_lifetime', PHP_INT_MAX );

		do_action( 'autoblog_pre_process_feeds' );

		// process feeds
		foreach ( $feed_ids as $feed_id ) {
			$feed = $this->_wpdb->get_row( sprintf( 'SELECT feed_meta, nextcheck FROM %s WHERE feed_id = %d', AUTOBLOG_TABLE_FEEDS, $feed_id ) );

			// skip already processed feeds
			if ( !$force && current_time( 'timestamp' ) < $feed->nextcheck ) {
				continue;
			}

			$this->_feed_id = $feed_id;
			$time = current_time( 'timestamp' );
			$details = unserialize( $feed->feed_meta );

			// do not process the feed if we are not in the requested period to process
			if ( !$force ) {
				if ( !empty( $details['startfrom'] ) && $time < $details['startfrom'] ) {
					$this->_log_message( Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_EARLY, $details['startfrom'] );
					continue;
				}

				if ( !empty( $details['endon'] ) && $details['endon'] < $time ) {
					$this->_log_message( Autoblog_Plugin::LOG_FEED_SKIPPED_TOO_LATE, $details['endon'] );
					continue;
				}
			}

			// process the feed
			if ( isset( $details['processfeed'] ) && $details['processfeed'] > 0 ) {
				do_action( 'autoblog_pre_process_feed', $feed_id, $details );

				$simplepie = $this->_fetch_feed( $details );
				if ( is_a( $simplepie, 'SimplePie' ) ) {
					$amount = $this->_process_feed( $simplepie, $details );
					if ( $amount ) {
						$this->_log_message( Autoblog_Plugin::LOG_FEED_PROCESSED, $amount );
					} else {
						$this->_log_message( Autoblog_Plugin::LOG_FEED_PROCESSED_NO_RESULTS );
					}
				}

				do_action( 'autoblog_post_process_feed', $feed_id, $details );
			}
		}

		do_action( 'autoblog_post_process_feeds' );
	}

	/**
	 * Returns lifetime limit for SimplePie cache.
	 *
	 * @since 4.0.0
	 * @filter wp_feed_cache_transient_lifetime PHP_INT_MAX
	 *
	 * @access public
	 */
	public function get_feed_cahce_lifetime() {
		return absint( AUTOBLOG_SIMPLEPIE_CACHE_TIMELIMIT );
	}

	/**
	 * Setups SimplePie options.
	 *
	 * @since 4.0.0
	 * @action wp_feed_options
	 *
	 * @access public
	 * @param SimplePie $feed The actual instance of SimplePie class.
	 */
	public function setup_simplepie_options( SimplePie $feed ) {
		$timeout = absint( AUTOBLOG_FEED_FETCHING_TIMEOUT );
		$feed->set_timeout( $timeout ? $timeout : 10 );
	}

	/**
	 * Fetches feed content.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param array $details The array of feed details.
	 * @return SimplePie|boolean SimplePie object on success, otherwise FALSE.
	 */
	private function _fetch_feed( $details ) {
		require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

		if ( empty( $details['url'] ) || !filter_var( $details['url'], FILTER_VALIDATE_URL ) ) {
			$this->_log_message( Autoblog_Plugin::LOG_INVALID_FEED_URL );
			return false;
		}

		// add filter to disable ssl verification if need be
		$added_filter = false;
		if ( !empty( $details['forcessl'] ) && !filter_var( $details['forcessl'], FILTER_VALIDATE_BOOLEAN ) ) {
			$added_filter = true;
			$this->_add_filter( 'http_request_args', 'disable_ssl_verification' );
		}

		$feed = fetch_feed( $details['url'] );
		if ( is_wp_error( $feed ) ) {
			$this->_log_message( Autoblog_Plugin::LOG_FETCHING_ERRORS, $feed->get_error_messages() );
			$feed = false;
		} elseif ( $feed->get_item_quantity() == 0 ) {
			$this->_log_message( Autoblog_Plugin::LOG_FETCHING_ERRORS, array( __( 'No entries found in the feed.', 'autoblogtext' ) ) );
			$feed = false;
		}

		// remove filter to disable ssl verification
		if ( $added_filter ) {
			remove_filter( 'http_request_args', array( $this, 'disable_ssl_verification' ) );
		}

		return $feed;
	}

	/**
	 * Processes fetched feed.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param SimplePie $feed The fetched feed object.
	 * @param array $details The array of feed details.
	 * @return int The amount of importent feed items.
	 */
	private function _process_feed( SimplePie $feed, $details ) {
		$max = isset( $details['poststoimport'] ) && (int)$details['poststoimport'] != 0
			? (int) $details['poststoimport']
			: $feed->get_item_quantity();

		$processed_count = 0;
		for ( $x = 0; $x < $max; $x++ ) {
			$item = $feed->get_item( $x );
			if ( is_a( $item, 'SimplePie_Item' ) ) {
				if ( $this->_check_item_duplicate( $item ) && $this->_check_item_content( $item, $details ) ) {
					if ( $this->_process_item( $item, $details ) ) {
						$processed_count++;
					}
				}
			}
		}

		return $processed_count;
	}

	/**
	 * Checks whether feed item has already been imported or not.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param SimplePie_Item $item The feed item object.
	 * @return boolean TRUE if duplicate was not found, otherwise FALSE.
	 */
	private function _check_item_duplicate( SimplePie_Item $item ) {
		// try to find whether we already imported this item or not
		$meta_key = 'original_source';
		$meta_value = $item->get_permalink();

		$check = defined( 'AUTOBLOG_POST_DUPLICATE_CHECK' ) && AUTOBLOG_POST_DUPLICATE_CHECK == 'guid' ? 'guid' : 'link';
		if ( $check == 'guid' ) {
			$meta_key = 'original_guid';
			$meta_value = $item->get_id();
		}

		$post_id = $this->_wpdb->get_var( $this->_wpdb->prepare( "SELECT post_id FROM {$this->_wpdb->postmeta} WHERE meta_key = '{$meta_key}' AND meta_value = %s LIMIT 1", $meta_value ) );
		if ( $post_id ) {
			$this->_log_message( Autoblog_Plugin::LOG_DUPLICATE_POST, array(
				'post_id' => $post_id,
				'title'   => trim( $item->get_title() ),
				'checked' => $check,
				$check    => $meta_value,
			) );
			return false;
		}

		return true;
	}

	/**
	 * Checks whether feed item contains required phrases/keywords or not.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param SimplePie_Item $item The feed item object.
	 * @param array $details The array of feed details.
	 * @return boolean TRUE if feed item content matched, otherwise FALSE.
	 */
	private function _check_item_content( SimplePie_Item $item, $details ) {
		$pos_func = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';
		$item_content = trim( $item->get_title() ) . ' ' . trim( $item->get_content() );

		$matchall = $matchany = $matchphrase = $matchnone = $matchtags = true;

		if ( !empty( $details['allwords'] ) ) {
			$matchall = true;
			$words = array_filter( array_map( 'trim', explode( ',', $details['allwords'] ) ) );
			foreach ( $words as $word ) {
				if ( $pos_func( $item_content, $word ) === false ) {
					$matchall = false;
					break;
				}
			}
		}

		if ( !empty( $details['anywords'] ) ) {
			$matchany = false;
			$words = array_filter( array_map( 'trim', explode( ',', $details['anywords'] ) ) );
			foreach ( $words as $word ) {
				if ( $pos_func( $item_content, $word ) !== false ) {
					$matchany = true;
					break;
				}
			}
		}

		if ( !empty( $details['phrase'] ) ) {
			$word = trim( $details['phrase'] );
			if ( !empty( $word ) ) {
				$matchphrase = $pos_func( $item_content, $word ) !== false;
			}
		}

		if ( !empty( $details['nonewords'] ) ) {
			$matchnone = true;
			$words = array_filter( array_map( 'trim', explode( ',', $details['nonewords'] ) ) );
			foreach ( $words as $word ) {
				if ( $pos_func( $item_content, $word ) !== false ) {
					$matchnone = false;
					break;
				}
			}
		}

		if ( !empty( $details['anytags'] ) ) {
			$matchtags = true;

			$words = array_filter( array_map( 'trim', explode( ',', $details['anytags'] ) ) );
			if ( !empty( $words ) ) {
				$thecats = $item->get_categories();
				if ( !empty( $thecats ) ) {
					$posttags = array();
					foreach ( $thecats as $category ) {
						$posttags[] = trim( $category->get_label() );
					}

					foreach ( $words as $word ) {
						if ( !in_array( $word, $posttags ) ) {
							$matchtags = false;
							break;
						}
					}
				} else {
					$matchtags = false;
				}
			}
		}

		$matched = $matchall && $matchany && $matchphrase && $matchnone && $matchtags;
		if ( !$matched ) {
			$this->_log_message( Autoblog_Plugin::LOG_POST_DOESNT_MATCH );
		}

		return $matched;
	}

	/**
	 * Processes feed item.
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 * @param SimplePie_Item $item The feed item object.
	 * @param array $details The array of feed details.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	private function _process_item( SimplePie_Item $item, $details ) {
		$post_id = wp_insert_post( apply_filters( 'autoblog_pre_post_insert', array(), $details, $item ) );
		if ( is_wp_error( $post_id ) ) {
			$this->_log_message( Autoblog_Plugin::LOG_POST_INSERT_FAILED, $post_id->get_error_messages() );
			return false;
		}

		do_action( 'autoblog_post_post_insert', $post_id, $details, $item );

		$this->_log_message( Autoblog_Plugin::LOG_POST_INSERT_SUCCESS, array(
			'post_id' => $post_id,
			'title'   => trim( $item->get_title() ),
			'link'    => $item->get_permalink(),
		) );

		return true;
	}

	/**
	 * Saves post meta information.
	 *
	 * @since 4.0.0
	 * @action autoblog_post_post_insert 1 3
	 *
	 * @access public
	 * @param int $post_id The post id to save meta information for.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 */
	public function add_post_meta ($post_id, $details, SimplePie_Item $item ) {
		$feed_url = trim( $details['url'] );
		$link = trim( $item->get_permalink() );

		// add general information
		update_post_meta( $post_id, 'original_feed_id', $this->_feed_id );
		update_post_meta( $post_id, 'original_feed', $feed_url );
		update_post_meta( $post_id, 'original_feed_title', trim( $details['title'] ) );
		update_post_meta( $post_id, 'original_guid', trim( $item->get_id() ) );
		update_post_meta( $post_id, 'original_source', $link );
		update_post_meta( $post_id, 'original_imported_time', time() );

		// add original author information
		$theauthor = $item->get_author();
		if ( !empty( $theauthor ) ) {
			$authorname = $theauthor->get_name();
			if ( !empty( $authorname ) ) {
				update_post_meta( $post_id, 'original_author_name', $authorname );
			}

			$authoremail = $theauthor->get_email();
			if ( !empty( $authoremail ) ) {
				update_post_meta( $post_id, 'original_author_email', $authoremail );
			}

			$authorlink = $theauthor->get_link();
			if ( !empty( $authorlink ) ) {
				update_post_meta( $post_id, 'original_author_link', $authorlink );
			}
		}

		// add the original source to the bottom of the post
		if ( !empty( $details['source'] ) ) {
			update_post_meta( $post_id, 'original_source_link_html',
				apply_filters( 'autoblog_source_link', sprintf(
					'<a href="%s"%s%s>%s</a>',
					$link,
					!empty( $details['nofollow'] ) && $details['nofollow'] == 1 ? ' rel="nofollow"' : '',
					!empty( $details['newwindow'] ) && $details['newwindow'] == 1 ? ' target="_blank"' : '',
					str_replace( array( '%POSTURL%', '%FEEDURL%' ), array( $link, $feed_url ), $details['source'] )
				), $details )
			);
		}
	}

	/**
	 * Saves log record into database.
	 *
	 * @sicne 4.0.0
	 *
	 * @access private
	 * @param int $type The log type.
	 * @param string|array $info The log information.
	 */
	private function _log_message( $type, $info = '' ) {
		$inerts = array(
			Autoblog_Plugin::LOG_DUPLICATE_POST,
			Autoblog_Plugin::LOG_POST_DOESNT_MATCH,
			Autoblog_Plugin::LOG_POST_INSERT_FAILED,
			Autoblog_Plugin::LOG_POST_INSERT_SUCCESS,
		);

		// if verbose processing is disabled then do not log inert messages
		if ( !AUTOBLOG_VERBOSE_PROCESSING && in_array( $type, $inerts ) ) {
			return;
		}

		// insert log message
		$this->_wpdb->insert( AUTOBLOG_TABLE_LOGS, array(
			'feed_id'  => $this->_feed_id,
			'cron_id'  => $this->_cron_timestamp,
			'log_at'   => current_time( 'timestamp' ),
			'log_type' => $type,
			'log_info' => is_array( $info ) ? serialize( $info ) : $info,
		), array( '%d', '%d', '%d', '%d', '%s' ) );
	}

	/**
	 * Disables SSL verification for feed fetch request.
	 *
	 * @sicne 4.0.0
	 * @filter http_request_args
	 *
	 * @access public
	 * @param array $args The array of HTTP request arguments.
	 * @return array Modified array of arguments.
	 */
	public function disable_ssl_verification( $args ) {
		$args['sslverify'] = false;
		return $args;
	}

	/**
	 * Finds post content and statuses.
	 *
	 * @since 4.0.0
	 * @filter autoblog_pre_post_insert 10 3
	 *
	 * @access public
	 * @param array $data The post data.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 * @return array The post data.
	 */
	public function get_post_content_and_statuses( array $data, array $details, SimplePie_Item $item ) {
		// post title
		if ( empty( $data['post_title'] ) ) {
			$data['post_title'] = trim( $item->get_title() );
		}

		// post content
		if ( empty( $data['post_content'] ) ) {
			$content = trim( html_entity_decode( $item->get_content(), ENT_QUOTES, 'UTF-8' ) );

			$length = absint( $details['excerptnumber'] );
			if ( $details['useexcerpt'] != '1' && $length > 0 ) {
				$delimiter = ' ';
				switch ( $details['excerptnumberof'] ) {
					case 'sentences':
						$delimiter = '.';
						break;
					case 'paragraphs':
						$delimiter = "\n\n";
						break;
				}

				$content = explode( $delimiter, strip_tags( $content ), $length + 1 );
				$content = implode( $delimiter, array_splice( $content, 0, $length ) );
			}

			$data['post_content'] = $content;
		}

		// post status
		if ( empty( $data['post_status'] ) ) {
			$data['post_status'] = $details['poststatus'];
		}

		// post type
		if ( empty( $data['post_type'] ) ) {
			$data['post_type'] = $details['posttype'];
		}

		return $data;
	}

	/**
	 * Finds post author id.
	 *
	 * @since 4.0.0
	 * @filter autoblog_pre_post_insert 10 3
	 *
	 * @access public
	 * @param array $data The post data.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 * @return array The post data.
	 */
	public function get_post_author_id( array $data, array $details, SimplePie_Item $item ) {
		// do not override author id if it has been already found
		if ( !empty( $data['post_author'] ) ) {
			return $data;
		}

		// find author id
		if ( empty( $details['author'] ) ) {
			$author = $item->get_author();
			if ( $author ) {
				// we look at the email address first
				$author_email = filter_var( $author->get_email(), FILTER_VALIDATE_EMAIL );
				if ( $author_email && ( $user = get_user_by( 'email', $author_email ) ) ) {
					$data['post_author'] = $user->ID;
				} else {
					// no email, then try to find by login or first/last name
					$author_name = $author->get_name();

					// first try to find by login
					if ( ( $user = get_user_by( 'login', $author_name ) ) ) {
						$data['post_author'] = $user->ID;
					} else {
						// can't find by login, then try to find by first/last name
						$author_name = explode( ' ', $author_name, 2 );
						if ( count( $author_name ) >= 2 ) {
							$author_query = new WP_User_Query( array(
								'number'     => 1,
								'meta_query' => array(
									'relation' => 'AND',
									array( 'key' => 'first_name', 'value' => $author_name[0], 'compare' => '=' ),
									array( 'key' => 'last_name',  'value' => $author_name[1], 'compare' => '=' ),
								),
							) );

							if ( !empty( $author_query->results ) ) {
								$data['post_author'] = $author_query->results[0]->ID;
							}
						}
					}
				}
			}

			// an author has not been found, then set default
			if ( empty( $data['post_author'] ) ) {
				$data['post_author'] = $details['altauthor'];
			}
		} else {
			$data['post_author'] = $details['author'];
		}

		return $data;
	}

	/**
	 * Finds post import dates.
	 *
	 * @since 4.0.0
	 * @filter autoblog_pre_post_insert 10 3
	 *
	 * @access public
	 * @param array $data The post data.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 * @return array The post data.
	 */
	public function get_post_dates( array $data, array $details, SimplePie_Item $item ) {
		// do not override dates if it has been already found
		if ( !empty( $data['post_date'] ) && !empty( $data['post_date_gmt'] ) ) {
			return $data;
		}

		$thedate = strtotime( $item->get_date() );
		if ( $details['postdate'] != 'existing' || !$thedate ) {
			if ( empty( $data['post_date'] ) ) {
				$data['post_date'] = current_time( 'mysql' );
			}

			if ( empty( $data['post_date_gmt'] ) ) {
				$data['post_date_gmt'] = current_time( 'mysql', 1 );
			}
		} else {
			if ( empty( $data['post_date'] ) ) {
				$data['post_date'] = date( 'Y-m-d H:i:s', $thedate );
			}

			if ( empty( $data['post_date_gmt'] ) ) {
				$data['post_date_gmt'] = date( 'Y-m-d H:i:s', $thedate + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Adds post taxonomies.
	 *
	 * @since 4.0.0
	 * @filter autoblog_post_post_insert 1 3
	 *
	 * @access public
	 * @param int $post_id The post id.
	 * @param array $details The array of feed details.
	 * @param SimplePie_Item $item The feed item object.
	 */
	public function add_post_taxonomies( $post_id, array $details, SimplePie_Item $item ) {
		$tags = !empty( $details['tag'] ) ? array_filter( array_map( 'trim', explode( ',', $details['tag'] ) ) ) : array();
		$post_category = (int)$details['category'] >= 0 ? array( (int)$details['category'] ) : array();

		if ( $details['feedcatsare'] == 'categories' ) {
			foreach ( $item->get_categories() as $category ) {
				$cat_name = trim( $category->get_label() );
				$term_id = term_exists( $cat_name, 'category' );
				if ( !empty( $term_id ) ) {
					$post_category[] = is_array( $term_id ) ? $term_id['term_id'] : $term_id;
				} else {
					if ( $details['originalcategories'] == '1' ) {
						$term_id = wp_create_category( $cat_name );
						if ( !empty( $term_id ) && !is_wp_error( $term_id ) ) {
							$post_category[] = $term_id;
						}
					}
				}
			}
		} else {
			$thecats = array();
			if ( isset( $details['originalcategories'] ) && $details['originalcategories'] == '1' ) {
				$thecats = $item->get_categories();
				foreach ( $thecats as $category ) {
					$tags[] = trim( $category->get_label() );
				}
			}
		}

		wp_set_post_terms( $post_id, $post_category, 'category', true );
		wp_set_post_terms( $post_id, $tags, 'post_tag', true );
	}

}