#!/usr/bin/env php
<?php
/**
 * Replays one HTML API fuzzer failure artifact.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

require_once __DIR__ . '/memory-limit.php';
require_once __DIR__ . '/fuzzer.php';

$args = html_api_fuzzer_parse_args(
	$argv,
	array(
		'html'             => null,
		'html-base64'      => null,
		'minimized'        => '1',
		'minimize'         => '0',
		'php-memory-limit' => null,
		'_'                => array(),
	)
);

$artifact = null;
$path     = $args['_'][0] ?? null;
$matched  = null;

if ( null !== $path ) {
	$artifact = json_decode( file_get_contents( $path ), true );
	if ( ! is_array( $artifact ) ) {
		fwrite( STDERR, "Unable to read failure artifact: {$path}\n" );
		exit( 1 );
	}

	$html = '1' === (string) $args['minimized']
		? base64_decode( $artifact['minimized_html_base64'] )
		: base64_decode( $artifact['html_base64'] );
} elseif ( null !== $args['html-base64'] ) {
	$html = base64_decode( $args['html-base64'] );
} elseif ( null !== $args['html'] ) {
	$html = (string) $args['html'];
} else {
	fwrite( STDERR, "Usage: php tools/html-api-fuzzer/replay.php <failure.json>\n" );
	exit( 1 );
}

$issues         = html_api_fuzzer_find_issues( $html );
$display_issues = html_api_fuzzer_dedupe_issues( $issues );

echo 'Input bytes: ' . strlen( $html ) . "\n";
echo 'Input SHA1: ' . sha1( $html ) . "\n";
echo 'Distinct issues: ' . count( $display_issues ) . "\n";
echo 'Oracle occurrences: ' . count( $issues ) . "\n";

foreach ( $display_issues as $issue ) {
	$signature = html_api_fuzzer_issue_signature( $issue );
	echo "\n";
	echo "Signature: {$signature}\n";
	echo 'Invariant: ' . $issue['invariant'] . "\n";
	echo 'Message: ' . $issue['message'] . "\n";
	echo 'Occurrences: ' . ( $issue['occurrences'] ?? 1 ) . "\n";
	echo html_api_fuzzer_json_encode( $issue['details'], true );
}

if ( null !== $artifact ) {
	$expected = $artifact['signature'];
	$matched  = false;
	foreach ( $display_issues as $issue ) {
		if ( html_api_fuzzer_issue_signature( $issue ) === $expected ) {
			$matched = true;
			break;
		}
	}

	echo "\nExpected signature {$expected}: " . ( $matched ? 'reproduced' : 'not reproduced' ) . "\n";
}

if ( '1' === (string) $args['minimize'] && ! empty( $display_issues ) ) {
	$signature = null !== $artifact ? $artifact['signature'] : html_api_fuzzer_issue_signature( $display_issues[0] );
	$result    = html_api_fuzzer_minimize_for_signature( $html, $signature, 1000 );
	echo "\nMinimized bytes: " . strlen( $result['html'] ) . "\n";
	echo "Minimize tries: {$result['tries']}\n";
	echo "Minimized HTML:\n";
	echo $result['html'] . "\n";
}

if ( null !== $matched ) {
	exit( $matched ? 0 : 1 );
}

exit( empty( $display_issues ) ? 1 : 0 );
