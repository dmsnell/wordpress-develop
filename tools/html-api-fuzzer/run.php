#!/usr/bin/env php
<?php
/**
 * Deterministic long-running fuzzer for the WordPress HTML API.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

require_once __DIR__ . '/memory-limit.php';
require_once __DIR__ . '/fuzzer.php';

$args = html_api_fuzzer_parse_args(
	$argv,
	array(
		'iterations'          => '1000',
		'seed'                => '1',
		'artifacts'           => 'artifacts/html-api-fuzzer',
		'run-root'            => '',
		'lanes'               => '1',
		'lane'                => '',
		'stop-after-failures' => '20',
		'minimize'            => '1',
		'time-limit-seconds'  => '0',
		'allow-failures'      => '0',
		'php-memory-limit'    => null,
		'_'                   => array(),
	)
);

$repo_root = realpath( __DIR__ . '/../..' );
if ( false === $repo_root ) {
	fwrite( STDERR, "Unable to determine repository root.\n" );
	exit( 1 );
}

$iterations          = max( 1, (int) $args['iterations'] );
$seed                = (int) $args['seed'];
$lanes               = max( 1, (int) $args['lanes'] );
$stop_after_failures = max( 1, (int) $args['stop-after-failures'] );
$should_minimize     = '0' !== (string) $args['minimize'];
$time_limit_seconds  = max( 0, (int) $args['time-limit-seconds'] );
$allow_failures      = '1' === (string) $args['allow-failures'];
$artifacts_root      = $args['artifacts'];

if ( '' !== $artifacts_root && ! html_api_fuzzer_is_absolute_path( $artifacts_root ) ) {
	$artifacts_root = $repo_root . '/' . $artifacts_root;
}

$run_root = (string) $args['run-root'];
if ( '' === $run_root ) {
	$run_root = $artifacts_root . '/run-' . gmdate( 'Ymd-His' ) . '-seed-' . $seed;
} elseif ( ! html_api_fuzzer_is_absolute_path( $run_root ) ) {
	$run_root = $repo_root . '/' . $run_root;
}

html_api_fuzzer_write_run_metadata(
	$run_root,
	array(
		'kind'                => 'wp-html-api-fuzzer-run',
		'started_at'          => gmdate( DATE_ATOM ),
		'seed'                => $seed,
		'lanes'               => $lanes,
		'iterations_per_lane' => $iterations,
		'head'                => html_api_fuzzer_git_head(),
		'php'                 => PHP_VERSION,
		'php_memory_limit'    => ini_get( 'memory_limit' ),
		'allow_failures'      => $allow_failures,
	)
);

if ( '' === (string) $args['lane'] && $lanes > 1 ) {
	$run_stats = html_api_fuzzer_run_supervisor( $run_root, $lanes, $iterations, $seed, $stop_after_failures, $should_minimize, $time_limit_seconds );
	exit( html_api_fuzzer_exit_code( $run_stats, $allow_failures ) );
}

$lane = '' === (string) $args['lane'] ? 0 : (int) $args['lane'];
$lane_stats = html_api_fuzzer_run_lane( $run_root, $lane, $iterations, $seed, $stop_after_failures, $should_minimize, $time_limit_seconds );
$groups     = html_api_fuzzer_write_triage_report( $run_root );
echo "Run root: {$run_root}\n";
echo "Triage: {$run_root}/TRIAGE.md\n";
exit(
	html_api_fuzzer_exit_code(
		array(
			'distinct_signatures' => count( $groups ),
			'child_errors'        => 0,
			'failures'            => $lane_stats['failures'],
		),
		$allow_failures
	)
);

/**
 * Writes run-level provenance.
 *
 * @param string $run_root Run root.
 * @param array  $metadata Metadata.
 */
function html_api_fuzzer_write_run_metadata( string $run_root, array $metadata ): void {
	if ( ! is_dir( $run_root ) ) {
		mkdir( $run_root, 0777, true );
	}

	file_put_contents( $run_root . '/run.json', html_api_fuzzer_json_encode( $metadata, true ) );
}

/**
 * Determines process exit status.
 *
 * @param array $run_stats      Run stats.
 * @param bool  $allow_failures Whether to exit zero when findings exist.
 * @return int Exit code.
 */
function html_api_fuzzer_exit_code( array $run_stats, bool $allow_failures ): int {
	if ( ! empty( $run_stats['child_errors'] ) ) {
		return 2;
	}

	if ( ! $allow_failures && ! empty( $run_stats['distinct_signatures'] ) ) {
		return 1;
	}

	return 0;
}

/**
 * Runs multiple fuzzer lanes.
 *
 * @param string $run_root            Run root.
 * @param int    $lanes               Lane count.
 * @param int    $iterations          Iterations per lane.
 * @param int    $seed                Base seed.
 * @param int    $stop_after_failures Failure cap per lane.
 * @param bool   $should_minimize     Whether to minimize.
 * @param int    $time_limit_seconds  Wall-clock limit.
 * @return array Run stats.
 */
function html_api_fuzzer_run_supervisor( string $run_root, int $lanes, int $iterations, int $seed, int $stop_after_failures, bool $should_minimize, int $time_limit_seconds ): array {
	$pids         = array();
	$child_errors = 0;
	for ( $lane = 0; $lane < $lanes; $lane++ ) {
		if ( function_exists( 'pcntl_fork' ) ) {
			$pid = pcntl_fork();
			if ( -1 === $pid ) {
				++$child_errors;
				continue;
			}
			if ( 0 === $pid ) {
				try {
					html_api_fuzzer_run_lane( $run_root, $lane, $iterations, $seed + ( $lane * 1000003 ), $stop_after_failures, $should_minimize, $time_limit_seconds );
					exit( 0 );
				} catch ( Throwable $e ) {
					fwrite( STDERR, "lane {$lane} failed: {$e->getMessage()}\n" );
					exit( 2 );
				}
			}
			$pids[ $pid ] = $lane;
		} else {
			html_api_fuzzer_run_lane( $run_root, $lane, $iterations, $seed + ( $lane * 1000003 ), $stop_after_failures, $should_minimize, $time_limit_seconds );
		}
	}

	foreach ( array_keys( $pids ) as $pid ) {
		pcntl_waitpid( $pid, $status );
		if (
			( function_exists( 'pcntl_wifexited' ) && ! pcntl_wifexited( $status ) ) ||
			( function_exists( 'pcntl_wexitstatus' ) && 0 !== pcntl_wexitstatus( $status ) )
		) {
			++$child_errors;
		}
	}

	$groups = html_api_fuzzer_write_triage_report( $run_root );
	echo "Run root: {$run_root}\n";
	echo "Triage: {$run_root}/TRIAGE.md\n";

	return array(
		'distinct_signatures' => count( $groups ),
		'child_errors'        => $child_errors,
	);
}

/**
 * Runs a fuzzer lane.
 *
 * @param string $run_root            Run root.
 * @param int    $lane                Lane number.
 * @param int    $iterations          Iterations.
 * @param int    $seed                Lane seed.
 * @param int    $stop_after_failures Failure cap.
 * @param bool   $should_minimize     Whether to minimize.
 * @param int    $time_limit_seconds  Wall-clock limit.
 * @return array Lane stats.
 */
function html_api_fuzzer_run_lane( string $run_root, int $lane, int $iterations, int $seed, int $stop_after_failures, bool $should_minimize, int $time_limit_seconds ): array {
	$lane_dir    = $run_root . '/lane-' . $lane;
	$failure_dir = $lane_dir . '/failures';

	if ( ! is_dir( $failure_dir ) ) {
		mkdir( $failure_dir, 0777, true );
	}

	$summary_path = $lane_dir . '/summary.ndjson';
	$events_path  = $lane_dir . '/events.ndjson';
	$state_path   = $lane_dir . '/state.json';
	$seeds        = html_api_fuzzer_load_seed_corpus();
	$rng          = new HTML_API_Fuzzer_Random( $seed );
	$started_at   = time();
	$failures     = 0;
	$successes    = 0;
	$occurrences  = 0;

	html_api_fuzzer_append_json_line(
		$events_path,
		array(
			'type'       => 'lane-start',
			'time'       => gmdate( DATE_ATOM ),
			'lane'       => $lane,
			'seed'       => $seed,
			'iterations' => $iterations,
		)
	);

	for ( $iteration = 0; $iteration < $iterations; $iteration++ ) {
		if ( $time_limit_seconds > 0 && time() - $started_at >= $time_limit_seconds ) {
			break;
		}

		$html        = html_api_fuzzer_generate_case( $rng, $seeds );
		$all_issues  = html_api_fuzzer_find_issues( $html );
		$issues      = html_api_fuzzer_dedupe_issues( $all_issues );
		$occurrences += count( $all_issues );

		if ( empty( $issues ) ) {
			++$successes;
		} else {
			foreach ( $issues as $issue_index => $issue ) {
				++$failures;
				$signature    = html_api_fuzzer_issue_signature( $issue );
				$failure_path = $failure_dir . '/' . $signature . '-iter-' . str_pad( (string) $iteration, 6, '0', STR_PAD_LEFT ) . '-' . $issue_index . '.json';
				$metadata     = array(
					'lane'             => $lane,
					'seed'             => $seed,
					'iteration'        => $iteration,
					'php'              => PHP_VERSION,
					'php_memory_limit' => ini_get( 'memory_limit' ),
					'head'             => html_api_fuzzer_git_head(),
					'time'             => gmdate( DATE_ATOM ),
				);

				html_api_fuzzer_write_failure( $failure_path, $metadata, $html, $issue, $should_minimize );
				html_api_fuzzer_append_json_line(
					$summary_path,
					array(
						'status'       => 'failure',
						'time'         => gmdate( DATE_ATOM ),
						'lane'         => $lane,
						'seed'         => $seed,
						'iteration'    => $iteration,
						'signature'    => $signature,
						'invariant'    => $issue['invariant'],
						'message'      => $issue['message'],
						'occurrences'  => $issue['occurrences'] ?? 1,
						'failure_path' => $failure_path,
					)
				);
			}
		}

		if ( 0 === $iteration % 100 || ! empty( $issues ) ) {
			file_put_contents(
				$state_path,
				html_api_fuzzer_json_encode(
					array(
						'lane'             => $lane,
						'seed'             => $seed,
						'iteration'        => $iteration,
						'successes'        => $successes,
						'failures'         => $failures,
						'occurrences'      => $occurrences,
						'php_memory_limit' => ini_get( 'memory_limit' ),
						'updated_at'       => gmdate( DATE_ATOM ),
					),
					true
				)
			);
		}

		if ( $failures >= $stop_after_failures ) {
			break;
		}
	}

	file_put_contents(
		$state_path,
		html_api_fuzzer_json_encode(
			array(
				'lane'        => $lane,
				'seed'        => $seed,
				'iterations'  => $iterations,
				'successes'   => $successes,
				'failures'    => $failures,
				'occurrences' => $occurrences,
				'php_memory_limit' => ini_get( 'memory_limit' ),
				'finished_at' => gmdate( DATE_ATOM ),
			),
			true
		)
	);

	html_api_fuzzer_append_json_line(
		$events_path,
		array(
			'type'        => 'lane-finish',
			'time'        => gmdate( DATE_ATOM ),
			'lane'        => $lane,
			'successes'   => $successes,
			'failures'    => $failures,
			'elapsed_sec' => time() - $started_at,
		)
	);

	echo "lane {$lane}: {$successes} successes, {$failures} failures\n";

	return array(
		'successes'   => $successes,
		'failures'    => $failures,
		'occurrences' => $occurrences,
	);
}

/**
 * Writes a triage report. Kept here so supervisor can summarize immediately.
 *
 * @param string $run_root Run root.
 * @return array Triage groups.
 */
function html_api_fuzzer_write_triage_report( string $run_root ): array {
	require_once __DIR__ . '/triage-lib.php';
	return html_api_fuzzer_triage_run_root( $run_root );
}
