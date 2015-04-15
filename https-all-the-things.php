<?php

/**
 * Plugin Name: HTTPS All the Things!
 * Plugin URI: http://github.com/ryanmarkel/https-all-the-things/
 * Description: Simple, zeroconf plugin that forces URLs to the current space in siteurl.
 * Version: 1.0
 * Author: Ryan Markel, John Blackbourn
 * Author URI: http://ryanmarkel.com/
 * License: GPL2
 */

class makeitsecure_ssl {

	# Notable SSL tickets:
	# https://core.trac.wordpress.org/ticket/15928
	# https://core.trac.wordpress.org/ticket/18017
	# https://core.trac.wordpress.org/ticket/20253
	# https://core.trac.wordpress.org/ticket/20750

	public function __construct() {

		add_action( 'init',                         array( $this, 'action_init' ), 99 );

		if ( !is_admin() ) {

			# Front-end Filters:
			add_filter( 'the_content',              array( $this, 'force_current_scheme' ), 99 );
			add_filter( 'get_comment_author_link',  array( $this, 'force_current_scheme' ), 99 );
			add_filter( 'the_excerpt',              array( $this, 'force_current_scheme' ), 99 );
			add_filter( 'comment_text',             array( $this, 'force_current_scheme' ), 99 );

		}

		# Filters:
		add_filter( 'home_url',              array( $this, 'enforce_home_scheme' ) );
		add_filter( 'option_home',           array( $this, 'enforce_admin_scheme' ) );
		add_filter( 'option_siteurl',        array( $this, 'enforce_admin_scheme' ) );
		add_filter( 'option_wpurl',          array( $this, 'enforce_admin_scheme' ) );
		add_filter( 'wp_get_attachment_url', 'set_url_scheme', 1 );
		add_filter( 'wp_insert_post_data',   array( $this, 'wp_insert_post_data_guid' ) );

	}

	/**
	 * Enforce the home URL scheme on a URL. Used for forcing public blogs to use HTTP and private blogs to use HTTPS.
	 * 
	 * @param  string $url The URL.
	 * @return string      The URL with our desired scheme.
	 */
	public function enforce_home_scheme( $url ) {

		return set_url_scheme( $url, 'https' );

	}

	/**
	 * Enforce the admin URL scheme on a URL. Used mainly for forcing URLs which use `get_option('siteurl')` to use HTTPS if FORCE_SSL_ADMIN is true.
	 * 
	 * @param  string $url The URL.
	 * @return string      The URL with our desired scheme.
	 */
	public function enforce_admin_scheme( $url ) {

		return set_url_scheme( $url, 'https' );

	}

	/**
	 * Filter text to replace incorrect scheme with the current scheme. This is used to match the scheme of home URLs in content
	 * with the current scheme, in order to avoid insecure warnings on HTTPS sites and avoid HTTPS links on HTTP sites.
	 *
	 * @param string $text Any textual content. Usually post content.
	 * @param string       The updated content.
	 */
	public function force_current_scheme( $text ) {

		static $current_url, $incorrect_url;

		if ( !isset( $current_url ) ) {
			$current_url   = set_url_scheme( get_option( 'home' ), is_ssl() ? 'https' : 'http' );
			$incorrect_url = set_url_scheme( get_option( 'home' ), is_ssl() ? 'http' : 'https' );
		}

		$text = str_replace( $incorrect_url, $current_url, $text );

		return $text;

	}

	/**
	 * When inserting a post into the DB, match the GUID's scheme to the home scheme
	 *
	 * @param  array $data The post data
	 * @return array       The updated post data
	 */
	public function wp_insert_post_data_guid( array $data ) {
		if ( !empty( $data['guid']) ) {
			$data['guid'] = $this->enforce_home_scheme( $data['guid'] );
		}
		return $data;
	}

	/**
	 * Get the current URL.
	 *
	 * @return string The current URL.
	 */
	public static function current_url() {	
		$protocol = is_ssl() ? 'https' : 'http';
		return $protocol . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];
	}

	/**
	 * Singleton stuff.
	 * 
	 * @return makeitsecure_ssl
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new makeitsecure_ssl;
		}

		return $instance;

	}

}

makeitsecure_ssl::init();
