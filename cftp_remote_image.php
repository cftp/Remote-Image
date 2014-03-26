<?php

/**
 * Class cftp_remote_image
 *
 * Pass in an image URL and it will download and save it locally if it isn't already present.
 */
class cftp_remote_image {

	public $ID = 0;
	public $error = false;
	public $justsaved = false;
	public $url = '';

	public function __construct( $url, $parent = 0, $description = '', $att_data = array() ) {
		$this->url = $url;
		if ( !$this->exists( $url ) ) {
			if ( $this->download( $url, $parent, $description, $att_data ) == 0 ) {
				$this->download( $url, $parent, $description, $att_data );
			}
		}
	}

	public function is_valid(){
		return ( $this->ID != 0 );
	}

	protected function exists( $url ) {
		$exists = wp_cache_get( 'cftp-remote-image-exists-'.$url, 'cftp-remote-image' );
		// if false was returned, there must be nothing in the cache, so find out from scratch
		if ( $exists != 'yes' ) {
			$exists = 'no';
			global $wpdb;
			$existing = $wpdb->get_results(
				"SELECT post_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$url."'", ARRAY_A
			);
			if ( !empty( $existing ) ) {
				foreach( $existing as $index => $value ) {
					$this->ID = reset( $value );
					$exists = 'yes';
				}
			}
		}
		if ( $exists == 'yes' ) {
			$exists = true;
		} else {
			$exists = false;
		}
		return $exists;
	}

	public function getURL( $size = '' ) {
		if ( empty( $size ) ) {
			return wp_get_attachment_url( $this->ID );
		}
	}

	public function getID() {
		return $this->ID;
	}

	/**
	 * Download an image from the specified URL and attach it to a post.
	 *
	 * @param string $file The URL of the image to download
	 * @param int $post_id The post ID the media is to be associated with
	 * @param string $desc Optional. Description of the image
	 *
	 * @param array $att_data
	 * @return string|WP_Error Populated HTML img tag on success
	 */
	protected function download( $file, $post_id, $desc = null, $att_data = array() ) {

		if ( empty( $file ) ) {
			return 0;
		}
		$original_file = $file;

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Download file to temp location
		$file = str_replace( ' ', '%20', $file );
		$tmp = download_url( $file );
		$file_array = array();

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {

			$tmp->add( 9993, 'Problem: with image at '. $file.' originally '.$original_file, array( $file, $original_file ) );

			$this->error = $tmp;

			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
			return 0;
		}

		// fix file filename
		$file = preg_replace( array( '/[^a-zA-Z0-9\.-_]/', '/_+/' ), '_', $file );
		preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $file, $matches );
		if ( empty( $matches ) ) {
			$file_array['name'] = md5($file).'.jpg';
		} else {
			$file_array['name'] = basename( $matches[0] );
		}
		$file_array['tmp_name'] = $tmp;

		// do the validation and storage stuff
		$id = media_handle_sideload( $file_array, $post_id, $desc, $att_data );

		// If error storing permanently, unlink
		if ( is_wp_error( $id ) ) {
			$this->ID = 0;
			$id->add( 9993, 'Problem: with image at '.$file.' originally '.$original_file, array( $file, $original_file ) );
			$this->error = $id;

			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		add_post_meta( $id, 'original-source', $original_file );

		$this->ID = $id;
		$this->justsaved = true;
		wp_cache_set( 'cftp-remote-image-exists-'.$file, 'yes', 'cftp-remote-image', 8000 );
		return $id;

	}
}