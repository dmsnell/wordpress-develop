<?php
/**
 * Triage helpers for HTML API fuzzer artifacts.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

/**
 * Triage one run root.
 *
 * @param string $run_root Run root.
 * @return array Grouped failures.
 */
function html_api_fuzzer_triage_run_root( string $run_root ): array {
	$groups = array();

	foreach ( glob( $run_root . '/lane-*/failures/*.json' ) as $path ) {
		$failure = json_decode( file_get_contents( $path ), true );
		if ( ! is_array( $failure ) || empty( $failure['signature'] ) ) {
			continue;
		}

		$signature = $failure['signature'];
		if ( ! isset( $groups[ $signature ] ) ) {
			$groups[ $signature ] = array(
				'signature' => $signature,
				'count'     => 0,
				'occurrences' => 0,
				'canonical' => $failure + array( 'path' => $path ),
				'paths'     => array(),
			);
		}

		++$groups[ $signature ]['count'];
		$groups[ $signature ]['occurrences'] += $failure['issue']['occurrences'] ?? 1;
		$groups[ $signature ]['paths'][] = $path;

		if ( strlen( $failure['minimized_html'] ) < strlen( $groups[ $signature ]['canonical']['minimized_html'] ) ) {
			$groups[ $signature ]['canonical'] = $failure + array( 'path' => $path );
		}
	}

	uasort(
		$groups,
		function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);

	file_put_contents( $run_root . '/triage.json', html_api_fuzzer_json_encode( array_values( $groups ), true ) );
	file_put_contents( $run_root . '/TRIAGE.md', html_api_fuzzer_render_triage_markdown( $run_root, $groups ) );

	return $groups;
}

/**
 * Renders Markdown triage.
 *
 * @param string $run_root Run root.
 * @param array  $groups   Groups.
 * @return string Markdown.
 */
function html_api_fuzzer_render_triage_markdown( string $run_root, array $groups ): string {
	$run_metadata_path = $run_root . '/run.json';
	$run_metadata      = is_readable( $run_metadata_path )
		? json_decode( file_get_contents( $run_metadata_path ), true )
		: array();

	$out  = "# HTML API Fuzzer Triage\n\n";
	$out .= '- Run root: `' . $run_root . "`\n";
	$out .= '- Generated: `' . gmdate( DATE_ATOM ) . "`\n";
	$out .= '- Distinct signatures: `' . count( $groups ) . "`\n\n";
	if ( is_array( $run_metadata ) && ! empty( $run_metadata ) ) {
		$out .= '- Git HEAD: `' . ( $run_metadata['head'] ?? '' ) . "`\n";
		$out .= '- PHP: `' . ( $run_metadata['php'] ?? '' ) . "`\n";
		$out .= '- PHP memory limit: `' . ( $run_metadata['php_memory_limit'] ?? '' ) . "`\n";
		$out .= '- Seed: `' . ( $run_metadata['seed'] ?? '' ) . "`\n";
		$out .= '- Lanes: `' . ( $run_metadata['lanes'] ?? '' ) . "`\n";
		$out .= "\n";
	}

	if ( empty( $groups ) ) {
		$out .= "No failures were recorded.\n";
		return $out;
	}

	foreach ( $groups as $group ) {
		$failure = $group['canonical'];
		$issue   = $failure['issue'];
		$metadata = $failure['metadata'] ?? array();
		$out    .= "## {$group['signature']}\n\n";
		$out    .= '- Inputs: `' . $group['count'] . "`\n";
		$out    .= '- Oracle occurrences: `' . $group['occurrences'] . "`\n";
		$out    .= '- Invariant: `' . $issue['invariant'] . "`\n";
		$out    .= '- Message: ' . $issue['message'] . "\n";
		$out    .= '- Lane: `' . ( $metadata['lane'] ?? '' ) . "`\n";
		$out    .= '- Seed: `' . ( $metadata['seed'] ?? '' ) . "`\n";
		$out    .= '- Iteration: `' . ( $metadata['iteration'] ?? '' ) . "`\n";
		$out    .= '- PHP: `' . ( $metadata['php'] ?? '' ) . "`\n";
		$out    .= '- PHP memory limit: `' . ( $metadata['php_memory_limit'] ?? ( $failure['php_memory_limit'] ?? '' ) ) . "`\n";
		$out    .= '- Git HEAD: `' . ( $metadata['head'] ?? '' ) . "`\n";
		$out    .= '- Input SHA1: `' . ( $failure['html_sha1'] ?? '' ) . "`\n";
		$out    .= '- Minimized SHA1: `' . ( $failure['minimized_html_sha1'] ?? '' ) . "`\n";
		$out    .= '- Canonical artifact: `' . $failure['path'] . "`\n";
		$out    .= '- Replay: `' . html_api_fuzzer_replay_command( $failure['path'] ) . "`\n";
		$out    .= '- Minimized bytes: `' . strlen( $failure['minimized_html'] ) . "`\n\n";
		$out    .= "```html\n" . $failure['minimized_html'] . "\n```\n\n";
	}

	return $out;
}
