<?php
/**
 * Applies a requested PHP memory limit before the fuzzer bootstrap loads.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

if ( defined( 'HTML_API_FUZZER_MEMORY_LIMIT_APPLIED' ) ) {
	return;
}

define( 'HTML_API_FUZZER_MEMORY_LIMIT_APPLIED', true );

/**
 * Finds the requested memory limit in CLI args or environment.
 *
 * @param string[] $argv Raw CLI args.
 * @return string|null Requested memory limit, or null if none was requested.
 */
function html_api_fuzzer_requested_memory_limit( array $argv ): ?string {
	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( 0 === strpos( $arg, '--php-memory-limit=' ) ) {
			return substr( $arg, strlen( '--php-memory-limit=' ) );
		}
	}

	$env_limit = getenv( 'PHP_MEMORY_LIMIT' );
	return false === $env_limit || '' === $env_limit ? null : $env_limit;
}

/**
 * Normalizes and validates memory limit syntax.
 *
 * @param string $memory_limit Requested memory limit.
 * @return string|null Normalized memory limit, or null if invalid.
 */
function html_api_fuzzer_normalize_memory_limit( string $memory_limit ): ?string {
	$memory_limit = trim( $memory_limit );

	if ( '-1' === $memory_limit ) {
		return '-1';
	}

	if ( 1 !== preg_match( '/^[1-9][0-9]*[KMG]?$/i', $memory_limit ) ) {
		return null;
	}

	$suffix = substr( $memory_limit, -1 );
	if ( ctype_alpha( $suffix ) ) {
		return substr( $memory_limit, 0, -1 ) . strtoupper( $suffix );
	}

	return $memory_limit;
}

$html_api_fuzzer_memory_limit = html_api_fuzzer_requested_memory_limit( $argv ?? array() );

if ( null !== $html_api_fuzzer_memory_limit ) {
	$html_api_fuzzer_normalized_memory_limit = html_api_fuzzer_normalize_memory_limit( $html_api_fuzzer_memory_limit );

	if ( null === $html_api_fuzzer_normalized_memory_limit ) {
		fwrite(
			STDERR,
			"Invalid PHP memory limit '{$html_api_fuzzer_memory_limit}'. Use -1 or a PHP shorthand value like 512M, 2G, or 12G.\n"
		);
		exit( 1 );
	}

	$old_memory_limit = ini_set( 'memory_limit', $html_api_fuzzer_normalized_memory_limit );
	$actual_limit     = ini_get( 'memory_limit' );

	if ( false === $old_memory_limit || 0 !== strcasecmp( $actual_limit, $html_api_fuzzer_normalized_memory_limit ) ) {
		fwrite(
			STDERR,
			"Unable to apply PHP memory limit '{$html_api_fuzzer_normalized_memory_limit}'. Current value is '{$actual_limit}'.\n"
		);
		exit( 1 );
	}
}

unset(
	$html_api_fuzzer_memory_limit,
	$html_api_fuzzer_normalized_memory_limit,
	$old_memory_limit,
	$actual_limit
);
