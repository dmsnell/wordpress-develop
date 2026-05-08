#!/usr/bin/env php
<?php
/**
 * Groups HTML API fuzzer failures into a compact report.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

require_once __DIR__ . '/memory-limit.php';
require_once __DIR__ . '/fuzzer.php';
require_once __DIR__ . '/triage-lib.php';

$args = html_api_fuzzer_parse_args(
	$argv,
	array(
		'php-memory-limit' => null,
		'_'                => array(),
	)
);

$run_root = $args['_'][0] ?? null;
if ( null === $run_root ) {
	fwrite( STDERR, "Usage: php tools/html-api-fuzzer/triage.php <run-root>\n" );
	exit( 1 );
}

if ( ! html_api_fuzzer_is_absolute_path( $run_root ) ) {
	$repo_root = realpath( __DIR__ . '/../..' );
	$run_root  = $repo_root . '/' . $run_root;
}

$groups = html_api_fuzzer_triage_run_root( $run_root );

echo 'Distinct signatures: ' . count( $groups ) . "\n";
echo "Report: {$run_root}/TRIAGE.md\n";