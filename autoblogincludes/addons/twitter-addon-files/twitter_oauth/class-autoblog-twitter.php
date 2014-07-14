<?php
require_once( 'OAuth.php' );

class Autoblog_Twitter {
	public $token_url = '';
	public $authenticate_url = '';
	public $authorize_url = '';
	public $request_token_url = '';

	public $sha1_method;
	public $consumer;
	public $this_token;

	public $host;
	public $ssl_verifypeer;
	public $format = 'json';


	public function __construct( $consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL ) {
		//init urls
		$this->token_url         = 'https://api.twitter.com/oauth/access_token';
		$this->authenticate_url  = 'https://api.twitter.com/oauth/authenticate';
		$this->authorize_url     = 'https://api.twitter.com/oauth/authorize';
		$this->request_token_url = 'https://api.twitter.com/oauth/request_token';

		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer    = new OAuthConsumer( $consumer_key, $consumer_secret );
		if ( ! empty( $oauth_token ) && ! empty( $oauth_token_secret ) ) {
			$this->token = new OAuthConsumer( $oauth_token, $oauth_token_secret );
		} else {
			$this->token = NULL;
		}
	}

	function get( $url, $parameters = array() ) {
		$response = $this->oAuthRequest( $url, 'GET', $parameters );
		if ( $this->format === 'json' ) {
			return json_decode( $response );
		}

		return $response;
	}

	function oAuthRequest( $url, $method, $parameters ) {
		if ( strrpos( $url, 'https://' ) !== 0 && strrpos( $url, 'http://' ) !== 0 ) {
			$url = "{$this->host}{$url}.{$this->format}";
		}
		$request = OAuthRequest::from_consumer_and_token( $this->consumer, $this->token, $method, $url, $parameters );
		$request->sign_request( $this->sha1_method, $this->consumer, $this->token );
		switch ( $method ) {
			case 'GET':
				$result = wp_remote_get( $request->to_url() );
				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					return $result['body'];
				}
				return false;
				break;
			default:
				$result = wp_remote_post( $request->get_normalized_http_url(), $request->to_postdata() );
				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					return $result['body'];
				}

				return false;
				break;
		}
	}
}