<?php
/*
Addon Name: CDN Mime filter
Description: Identifies mimetypes from CDN image downloads that do not have file extentions.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
Since version: 4.0.8
*/

if(!class_exists('Autoblog_CDN_Mime') ):

class Autoblog_CDN_Mime{

	function __construct(){
		add_filter( 'wp_check_filetype_and_ext', array( &$this, 'mime_filter'), 10, 4 );
	}

	function mime_filter( $wp_filetype, $file, $filename, $mimes) {

		extract($wp_filetype);
		if( $type && $ext) return $wp_filetype; //Already knows.

		if ( !file_exists( $file)
		|| !function_exists('getimagesize') ) return $wp_filetype; // no file to check

		$proper_filename = false;
		// Attempt to figure out what type of image it actually is
		$image_type = @exif_imagetype( $file );

		if ( $image_type ) {

			$ext = image_type_to_extension($image_type, true);
			$type = image_type_to_mime_type($image_type);
			$proper_filename = $filename . $ext;

			// Redefine the extension / MIME
			$wp_filetype = wp_check_filetype( $proper_filename, $mimes );
			extract( $wp_filetype );
		}
		$result = compact( 'ext', 'type', 'proper_filename' );
		return $result;
		
	}

}

new Autoblog_CDN_Mime;
endif;

