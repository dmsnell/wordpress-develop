<?php
/**
 * Minimal WordPress bootstrap for the HTML API fuzzer.
 *
 * This intentionally loads only the pieces needed by the HTML API so the fuzzer
 * can run as a fast PHP CLI tool without a database or full WordPress install.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

if ( defined( 'HTML_API_FUZZER_BOOTSTRAPPED' ) ) {
	return;
}

define( 'HTML_API_FUZZER_BOOTSTRAPPED', true );

$html_api_fuzzer_root = realpath( __DIR__ . '/../..' );

if ( false === $html_api_fuzzer_root ) {
	fwrite( STDERR, "Unable to determine repository root.\n" );
	exit( 1 );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $html_api_fuzzer_root . '/src/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.textFound
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.textFound
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook_name ) {
		return null;
	}
}

if ( ! function_exists( '_deprecated_argument' ) ) {
	function _deprecated_argument( $function_name, $version, $message = '' ) {
		return null;
	}
}

if ( ! function_exists( '_doing_it_wrong' ) ) {
	function _doing_it_wrong( $function_name, $message, $version ) {
		trigger_error( "{$function_name}: {$message}", E_USER_NOTICE );
	}
}

if ( ! function_exists( 'wp_trigger_error' ) ) {
	function wp_trigger_error( $function_name, $message, $error_level = E_USER_NOTICE ) {
		$prefix = '' === $function_name ? '' : "{$function_name}: ";
		trigger_error( "{$prefix}{$message}", $error_level );
	}
}

if ( ! function_exists( 'wp_allowed_protocols' ) ) {
	function wp_allowed_protocols() {
		return array(
			'http',
			'https',
			'ftp',
			'ftps',
			'mailto',
			'news',
			'irc',
			'irc6',
			'ircs',
			'gopher',
			'nntp',
			'feed',
			'telnet',
			'mms',
			'rtsp',
			'sms',
			'svn',
			'tel',
			'fax',
			'xmpp',
			'webcal',
			'urn',
		);
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return -1 === $component ? parse_url( $url ) : parse_url( $url, $component );
	}
}

require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/compat-utf8.php';
require_once ABSPATH . WPINC . '/utf8.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/kses.php';
require_once ABSPATH . WPINC . '/class-wp-token-map.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-attribute-token.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-span.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-doctype-info.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-text-replacement.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-decoder.php';
require_once ABSPATH . WPINC . '/html-api/html5-named-character-references.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-tag-processor.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-unsupported-exception.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-active-formatting-elements.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-open-elements.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-token.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-stack-event.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-processor-state.php';
require_once ABSPATH . WPINC . '/html-api/class-wp-html-processor.php';

unset( $html_api_fuzzer_root );
