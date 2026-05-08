<?php
/**
 * Shared HTML API fuzzer library.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Small deterministic PRNG, independent of PHP mt_rand() implementation details.
 */
class HTML_API_Fuzzer_Random {
	/**
	 * Internal 31-bit state.
	 *
	 * @var int
	 */
	private $state;

	/**
	 * Constructor.
	 *
	 * @param int $seed Initial seed.
	 */
	public function __construct( int $seed ) {
		$this->state = $seed & 0x7fffffff;
		if ( 0 === $this->state ) {
			$this->state = 1;
		}
	}

	/**
	 * Returns the next pseudo-random integer.
	 *
	 * @return int Pseudo-random integer.
	 */
	public function next(): int {
		$this->state = (int) ( ( ( 1103515245 * $this->state ) + 12345 ) % 2147483648 );
		return $this->state;
	}

	/**
	 * Returns an integer in a closed range.
	 *
	 * @param int $min Minimum.
	 * @param int $max Maximum.
	 * @return int Random integer.
	 */
	public function int( int $min, int $max ): int {
		if ( $max <= $min ) {
			return $min;
		}

		return $min + ( $this->next() % ( $max - $min + 1 ) );
	}

	/**
	 * Returns whether an event with a numerator/denominator probability occurs.
	 *
	 * @param int $numerator   Numerator.
	 * @param int $denominator Denominator.
	 * @return bool Whether the event occurred.
	 */
	public function chance( int $numerator, int $denominator ): bool {
		return $this->int( 1, $denominator ) <= $numerator;
	}

	/**
	 * Picks a value from a list.
	 *
	 * @param array $values Values.
	 * @return mixed Picked value.
	 */
	public function pick( array $values ) {
		return $values[ $this->int( 0, count( $values ) - 1 ) ];
	}
}

/**
 * Parses simple --key=value CLI arguments.
 *
 * @param string[] $argv     Raw argv.
 * @param array    $defaults Default values.
 * @return array Parsed arguments.
 */
function html_api_fuzzer_parse_args( array $argv, array $defaults ): array {
	$args = $defaults;

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( 0 !== strpos( $arg, '--' ) ) {
			$args['_'][] = $arg;
			continue;
		}

		$arg = substr( $arg, 2 );
		if ( false === strpos( $arg, '=' ) ) {
			$args[ $arg ] = true;
			continue;
		}

		list( $key, $value ) = explode( '=', $arg, 2 );
		$args[ $key ]       = $value;
	}

	return $args;
}

/**
 * Returns whether a path is absolute on common Unix/Windows path forms.
 *
 * @param string $path Path.
 * @return bool Whether path is absolute.
 */
function html_api_fuzzer_is_absolute_path( string $path ): bool {
	return (
		'' !== $path &&
		(
			'/' === $path[0] ||
			'\\' === $path[0] ||
			1 === preg_match( '/^[A-Za-z]:[\/\\\\]/', $path )
		)
	);
}

/**
 * Returns the current Git commit when available.
 *
 * @return string Git HEAD or empty string.
 */
function html_api_fuzzer_git_head(): string {
	if ( ! function_exists( 'shell_exec' ) ) {
		return '';
	}

	$head = shell_exec( 'git rev-parse HEAD 2>/dev/null' );
	return is_string( $head ) ? trim( $head ) : '';
}

/**
 * Builds a replay command safe to paste into a shell.
 *
 * @param string $failure_path Failure artifact path.
 * @return string Replay command.
 */
function html_api_fuzzer_replay_command( string $failure_path ): string {
	return 'php ' . escapeshellarg( 'tools/html-api-fuzzer/replay.php' ) . ' ' . escapeshellarg( $failure_path );
}

/**
 * JSON-encodes data for artifact files.
 *
 * @param mixed $data Data to encode.
 * @param bool  $pretty Whether to pretty-print.
 * @return string JSON.
 */
function html_api_fuzzer_json_encode( $data, bool $pretty = false ): string {
	$flags = JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
	if ( $pretty ) {
		$flags |= JSON_PRETTY_PRINT;
	}

	return json_encode( $data, $flags ) . "\n";
}

/**
 * Appends an NDJSON event.
 *
 * @param string $path  File path.
 * @param array  $event Event.
 */
function html_api_fuzzer_append_json_line( string $path, array $event ): void {
	file_put_contents( $path, html_api_fuzzer_json_encode( $event ), FILE_APPEND );
}

/**
 * Captures PHP warnings/notices and throwables from an oracle.
 *
 * @param callable $callback        Callback.
 * @param int      $timeout_seconds Per-oracle timeout in seconds.
 * @return array Capture result.
 */
function html_api_fuzzer_capture( callable $callback, int $timeout_seconds = 2 ): array {
	$errors    = array();
	$throwable = null;
	$value     = null;

	$previous = set_error_handler(
		function ( $errno, $errstr, $errfile, $errline ) use ( &$errors ) {
			$errors[] = array(
				'errno'   => $errno,
				'message' => $errstr,
				'file'    => $errfile,
				'line'    => $errline,
			);
			return true;
		}
	);

	$alarm_enabled = function_exists( 'pcntl_alarm' ) && function_exists( 'pcntl_signal' );
	if ( $alarm_enabled ) {
		pcntl_async_signals( true );
		pcntl_signal(
			SIGALRM,
			function () {
				throw new RuntimeException( 'HTML API fuzzer oracle timed out.' );
			}
		);
		pcntl_alarm( $timeout_seconds );
	}

	try {
		$value = $callback();
	} catch ( Throwable $e ) {
		$throwable = array(
			'class'   => get_class( $e ),
			'message' => $e->getMessage(),
			'file'    => $e->getFile(),
			'line'    => $e->getLine(),
			'trace'   => array_slice( explode( "\n", $e->getTraceAsString() ), 0, 12 ),
		);
	} finally {
		if ( $alarm_enabled ) {
			pcntl_alarm( 0 );
		}
		restore_error_handler();

		if ( null !== $previous ) {
			set_error_handler( $previous );
			restore_error_handler();
		}
	}

	return array(
		'value'     => $value,
		'errors'    => $errors,
		'throwable' => $throwable,
	);
}

/**
 * Returns non-benign captured PHP errors.
 *
 * @param array $errors Captured errors.
 * @return array Non-benign errors.
 */
function html_api_fuzzer_non_benign_errors( array $errors ): array {
	$non_benign = array();

	foreach ( $errors as $error ) {
		$message = $error['message'];
		if ( false !== strpos( $message, 'Cannot serialize HTML Processor with parsing error: unsupported.' ) ) {
			continue;
		}

		$non_benign[] = $error;
	}

	return $non_benign;
}

/**
 * Builds a fuzzer issue record.
 *
 * @param string $invariant Invariant name.
 * @param string $message   Message.
 * @param array  $details   Details.
 * @return array Issue.
 */
function html_api_fuzzer_issue( string $invariant, string $message, array $details = array() ): array {
	return array(
		'invariant' => $invariant,
		'message'   => $message,
		'details'   => $details,
	);
}

/**
 * Produces a stable issue signature.
 *
 * @param array $issue Issue.
 * @return string Signature.
 */
function html_api_fuzzer_issue_signature( array $issue ): string {
	if (
		'Oracle emitted PHP warning or notice.' === $issue['message'] &&
		isset( $issue['details']['errors'][0] )
	) {
		$error = $issue['details']['errors'][0];
		$error_message = preg_replace( '/\b\d+\b/', '#', $error['message'] ?? '' );
		return substr(
			sha1(
				"php-error\n" .
				$error_message . "\n" .
				basename( $error['file'] ?? '' ) . ':' . ( $error['line'] ?? '' )
			),
			0,
			16
		);
	}

	$message = preg_replace( '/\b\d+\b/', '#', $issue['message'] );
	return substr( sha1( $issue['invariant'] . "\n" . $message ), 0, 16 );
}

/**
 * Returns whether exact normalization comparisons are low-noise for this case.
 *
 * @param string $html       Original HTML.
 * @param string $normalized Normalized HTML.
 * @return bool Whether exact comparison should be used.
 */
function html_api_fuzzer_can_compare_normalized( string $html, string $normalized ): bool {
	return 0 === preg_match( '/<(pre|listing|textarea)\b/i', $html . "\n" . $normalized );
}

/**
 * Loads checked-in seed corpus files.
 *
 * @return string[] Seed HTML fragments.
 */
function html_api_fuzzer_load_seed_corpus(): array {
	$seed_dir = dirname( __DIR__, 2 ) . '/tests/phpunit/data/html-api/fuzzer/seeds';
	$seeds    = array(
		'<p>One<p>Two',
		'<div></p>fun<table><td>cell</div>',
		'<figure><img src="one.jpg"><figcaption>A <em>caption</em></figcaption></figure>',
		'<!-- leading --><div class="wp-block-group"><p>Text</p></div>',
		'<ul><li>one<li><b>two</b><li><a href="/x">three</a></ul>',
		'<h1>Title<h2>Subtitle</h1><p>Body',
		'<button><span>One<button>Two</button></span>',
		'<a href=#anchor v=5 href="/" enabled>One</a another v=5><!--',
		"apples > or\x00anges",
	);

	if ( is_dir( $seed_dir ) ) {
		foreach ( glob( $seed_dir . '/*.html' ) as $path ) {
			$contents = file_get_contents( $path );
			if ( false !== $contents && '' !== $contents ) {
				$seeds[] = $contents;
			}
		}
	}

	return $seeds;
}

/**
 * Generates one HTML input.
 *
 * @param HTML_API_Fuzzer_Random $rng   Random generator.
 * @param string[]               $seeds Seed corpus.
 * @return string HTML.
 */
function html_api_fuzzer_generate_case( HTML_API_Fuzzer_Random $rng, array $seeds ): string {
	if ( $rng->chance( 1, 3 ) ) {
		return html_api_fuzzer_mutate( $rng->pick( $seeds ), $rng );
	}

	$html  = '';
	$parts = $rng->int( 1, 8 );
	for ( $i = 0; $i < $parts; $i++ ) {
		$html .= html_api_fuzzer_generate_node( $rng, $rng->int( 1, 5 ) );
	}

	if ( $rng->chance( 1, 5 ) ) {
		$html = $rng->pick( array( '<!-- wp:paragraph -->', "\n", 'Text before ' ) ) . $html;
	}

	if ( $rng->chance( 1, 6 ) ) {
		$html .= $rng->pick( array( '</p>', '</span>', '<!--', '<', '</>' ) );
	}

	return $html;
}

/**
 * Mutates a seed input.
 *
 * @param string                 $html Seed HTML.
 * @param HTML_API_Fuzzer_Random $rng  Random generator.
 * @return string Mutated HTML.
 */
function html_api_fuzzer_mutate( string $html, HTML_API_Fuzzer_Random $rng ): string {
	$mutations = $rng->int( 1, 8 );

	for ( $i = 0; $i < $mutations; $i++ ) {
		$length = strlen( $html );
		$at     = $rng->int( 0, max( 0, $length ) );

		switch ( $rng->int( 0, 6 ) ) {
			case 0:
				$html = substr( $html, 0, $at ) . html_api_fuzzer_generate_piece( $rng ) . substr( $html, $at );
				break;

			case 1:
				if ( $length > 0 ) {
					$remove = $rng->int( 1, min( 16, $length - $at ) );
					$html   = substr( $html, 0, $at ) . substr( $html, $at + $remove );
				}
				break;

			case 2:
				if ( $length > 0 ) {
					$take = $rng->int( 1, min( 24, $length - $at ) );
					$html = substr( $html, 0, $at ) . substr( $html, $at, $take ) . substr( $html, $at );
				}
				break;

			case 3:
				$html .= $rng->pick( array( '</p>', '</li>', '</div>', '<br/>', '<img src=x />', "\x00" ) );
				break;

			case 4:
				$html = '<div data-fuzz="' . $rng->int( 0, 999 ) . '">' . $html . ( $rng->chance( 3, 4 ) ? '</div>' : '' );
				break;

			case 5:
				$html = strtoupper( substr( $html, 0, $at ) ) . substr( $html, $at );
				break;

			default:
				$html .= html_api_fuzzer_generate_node( $rng, 2 );
				break;
		}

		if ( strlen( $html ) > 4000 ) {
			$html = substr( $html, 0, 4000 );
		}
	}

	return $html;
}

/**
 * Generates a recursive node.
 *
 * @param HTML_API_Fuzzer_Random $rng   Random generator.
 * @param int                    $depth Remaining depth.
 * @return string HTML.
 */
function html_api_fuzzer_generate_node( HTML_API_Fuzzer_Random $rng, int $depth ): string {
	if ( $depth <= 0 || $rng->chance( 1, 4 ) ) {
		return html_api_fuzzer_generate_piece( $rng );
	}

	$supported_tags = array( 'div', 'p', 'span', 'b', 'i', 'em', 'strong', 'a', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'h1', 'h2', 'h3', 'section', 'article', 'figure', 'figcaption', 'button', 'form', 'label', 'select', 'option', 'template', 'custom-element' );
	$edge_tags      = array( 'table', 'tbody', 'tr', 'td', 'caption', 'svg', 'math', 'title', 'textarea', 'script', 'style', 'plaintext' );
	$void_tags      = array( 'img', 'br', 'hr', 'input', 'source', 'meta', 'link' );

	if ( $rng->chance( 1, 7 ) ) {
		$tag = $rng->pick( $edge_tags );
	} elseif ( $rng->chance( 1, 5 ) ) {
		$tag = $rng->pick( $void_tags );
	} else {
		$tag = $rng->pick( $supported_tags );
	}

	$attrs = html_api_fuzzer_generate_attrs( $rng );

	if ( in_array( $tag, $void_tags, true ) ) {
		return "<{$tag}{$attrs}" . ( $rng->chance( 1, 3 ) ? ' /' : '' ) . '>';
	}

	$children = '';
	$count    = $rng->int( 0, 4 );
	for ( $i = 0; $i < $count; $i++ ) {
		$children .= html_api_fuzzer_generate_node( $rng, $depth - 1 );
	}

	if ( $rng->chance( 1, 8 ) ) {
		return "<{$tag}{$attrs}/>{$children}";
	}

	if ( $rng->chance( 1, 5 ) ) {
		return "<{$tag}{$attrs}>{$children}";
	}

	if ( $rng->chance( 1, 8 ) ) {
		return "<{$tag}{$attrs}>{$children}</" . $rng->pick( $supported_tags ) . '>';
	}

	return "<{$tag}{$attrs}>{$children}</{$tag}>";
}

/**
 * Generates an HTML piece.
 *
 * @param HTML_API_Fuzzer_Random $rng Random generator.
 * @return string HTML.
 */
function html_api_fuzzer_generate_piece( HTML_API_Fuzzer_Random $rng ): string {
	$texts = array(
		'text',
		'one & two',
		'5 < 7 > 3',
		'"quoted"',
		"null\x00byte",
		'&amp;&notin;&bogus;',
		'<!-- comment -->',
		'<![CDATA[ cdata body ]]>',
		'<?pi instruction ?>',
		'</p>',
		'</br>',
		'<>',
		'<!--',
	);

	return $rng->pick( $texts );
}

/**
 * Generates attributes.
 *
 * @param HTML_API_Fuzzer_Random $rng Random generator.
 * @return string Attribute HTML.
 */
function html_api_fuzzer_generate_attrs( HTML_API_Fuzzer_Random $rng ): string {
	$names  = array( 'id', 'class', 'data-id', 'data-fuzz', 'href', 'src', 'alt', 'title', 'disabled', 'checked', 'aria-label' );
	$values = array( 'one', 'two words', 'a&b', '<tag>', '"quote"', "'apos'", '/relative', '#hash', 'https://example.test/?a=1&b=2', 'javascript:alert(1)', "null\x00byte" );

	$out   = '';
	$count = $rng->int( 0, 4 );
	for ( $i = 0; $i < $count; $i++ ) {
		$name = $rng->pick( $names );
		if ( $rng->chance( 1, 8 ) ) {
			$name = strtoupper( $name );
		}

		if ( $rng->chance( 1, 5 ) ) {
			$out .= " {$name}";
			continue;
		}

		$value = $rng->pick( $values );
		$quote = $rng->pick( array( '"', "'", '' ) );
		if ( '' === $quote ) {
			$value = preg_replace( '/\s+/', '', $value );
			$out  .= " {$name}={$value}";
		} else {
			$out .= " {$name}={$quote}{$value}{$quote}";
		}

		if ( $rng->chance( 1, 7 ) ) {
			$out .= " {$name}=\"duplicate\"";
		}
	}

	return $out;
}

/**
 * Runs all fuzzer oracles for an input.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_find_issues( string $html ): array {
	$issues = array();

	foreach ( array(
		'processor-fragment-traversal' => 'html_api_fuzzer_oracle_processor_fragment_traversal',
		'processor-full-traversal'     => 'html_api_fuzzer_oracle_processor_full_traversal',
		'processor-normalize'          => 'html_api_fuzzer_oracle_processor_normalize',
		'tag-processor-application'    => 'html_api_fuzzer_oracle_tag_processor_application',
		'processor-img-application'    => 'html_api_fuzzer_oracle_processor_img_application',
	) as $name => $callback ) {
		$capture = html_api_fuzzer_capture(
			function () use ( $callback, $html ) {
				return call_user_func( $callback, $html );
			}
		);

		if ( null !== $capture['throwable'] ) {
			$issues[] = html_api_fuzzer_issue(
				$name,
				'Oracle threw.',
				array( 'throwable' => $capture['throwable'] )
			);
			continue;
		}

		$errors = html_api_fuzzer_non_benign_errors( $capture['errors'] );
		if ( ! empty( $errors ) ) {
			$issues[] = html_api_fuzzer_issue(
				$name,
				'Oracle emitted PHP warning or notice.',
				array( 'errors' => $errors )
			);
			continue;
		}

		foreach ( (array) $capture['value'] as $issue ) {
			$issues[] = $issue;
		}
	}

	return $issues;
}

/**
 * Dedupe multiple oracle reports for the same root cause on one input.
 *
 * @param array[] $issues Issues.
 * @return array[] Unique issues by signature.
 */
function html_api_fuzzer_dedupe_issues( array $issues ): array {
	$unique = array();

	foreach ( $issues as $issue ) {
		$signature = html_api_fuzzer_issue_signature( $issue );
		if ( isset( $unique[ $signature ] ) ) {
			++$unique[ $signature ]['occurrences'];
			continue;
		}

		$issue['occurrences'] = 1;
		$unique[ $signature ] = $issue;
	}

	return array_values( $unique );
}

/**
 * Traverses a fragment parser and checks fail-closed behavior.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_oracle_processor_fragment_traversal( string $html ): array {
	$processor = WP_HTML_Processor::create_fragment( $html );
	return html_api_fuzzer_check_processor_traversal( $processor, $html, 'fragment' );
}

/**
 * Traverses a full parser and checks fail-closed behavior.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_oracle_processor_full_traversal( string $html ): array {
	$processor = WP_HTML_Processor::create_full_parser( $html );
	return html_api_fuzzer_check_processor_traversal( $processor, $html, 'full' );
}

/**
 * Checks a processor traversal.
 *
 * @param WP_HTML_Processor|null $processor Processor.
 * @param string                 $html      Input HTML.
 * @param string                 $mode      Parser mode.
 * @return array[] Issues.
 */
function html_api_fuzzer_check_processor_traversal( ?WP_HTML_Processor $processor, string $html, string $mode ): array {
	if ( null === $processor ) {
		return array(
			html_api_fuzzer_issue(
				"processor-{$mode}-traversal",
				'Processor creator returned null for supported arguments.'
			),
		);
	}

	$issues  = array();
	$budget  = max( 200, strlen( $html ) * 12 + 80 );
	$tokens  = 0;
	$allowed = array( '#tag', '#text', '#cdata-section', '#comment', '#doctype', '#presumptuous-tag', '#funky-comment' );

	while ( $processor->next_token() ) {
		++$tokens;
		if ( $tokens > $budget ) {
			return array(
				html_api_fuzzer_issue(
					"processor-{$mode}-traversal",
					'Token traversal exceeded budget.',
					array(
						'budget' => $budget,
						'tokens' => $tokens,
					)
				),
			);
		}

		if ( null !== $processor->get_last_error() ) {
			break;
		}

		$breadcrumbs = $processor->get_breadcrumbs();
		if ( $processor->get_current_depth() !== count( $breadcrumbs ) ) {
			$issues[] = html_api_fuzzer_issue(
				"processor-{$mode}-traversal",
				'Current depth differs from breadcrumb count.',
				array(
					'current_depth' => $processor->get_current_depth(),
					'breadcrumbs'   => $breadcrumbs,
					'token_name'    => $processor->get_token_name(),
					'token_type'    => $processor->get_token_type(),
				)
			);
		}

		$token_type = $processor->get_token_type();
		if ( null !== $token_type && ! in_array( $token_type, $allowed, true ) ) {
			$issues[] = html_api_fuzzer_issue(
				"processor-{$mode}-traversal",
				'Processor returned an unknown token type.',
				array(
					'token_type' => $token_type,
					'token_name' => $processor->get_token_name(),
				)
			);
		}

		if ( '#tag' === $token_type && null === $processor->get_tag() ) {
			$issues[] = html_api_fuzzer_issue(
				"processor-{$mode}-traversal",
				'Tag token has no tag name.',
				array(
					'token_name'  => $processor->get_token_name(),
					'breadcrumbs' => $breadcrumbs,
				)
			);
		}
	}

	$error = $processor->get_last_error();
	if ( null !== $error && WP_HTML_Processor::ERROR_UNSUPPORTED !== $error ) {
		$issues[] = html_api_fuzzer_issue(
			"processor-{$mode}-traversal",
			'Processor failed with a non-unsupported error.',
			array( 'last_error' => $error )
		);
	}

	if ( WP_HTML_Processor::ERROR_UNSUPPORTED === $error && null === $processor->get_unsupported_exception() ) {
		$issues[] = html_api_fuzzer_issue(
			"processor-{$mode}-traversal",
			'Unsupported parse did not provide unsupported exception context.'
		);
	}

	return $issues;
}

/**
 * Checks normalization idempotence and token serialization consistency.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_oracle_processor_normalize( string $html ): array {
	$issues = array();

	$first = WP_HTML_Processor::normalize( $html );
	if ( null === $first ) {
		return $issues;
	}

	if ( ! html_api_fuzzer_can_compare_normalized( $html, $first ) ) {
		return $issues;
	}

	$second = WP_HTML_Processor::normalize( $first );
	if ( null === $second ) {
		$issues[] = html_api_fuzzer_issue(
			'processor-normalize',
			'Normalized HTML could not be normalized again.',
			array( 'normalized' => $first )
		);
	} elseif ( $first !== $second ) {
		$issues[] = html_api_fuzzer_issue(
			'processor-normalize',
			'Normalization is not idempotent.',
			array(
				'first'  => $first,
				'second' => $second,
			)
		);
	}

	$processor = WP_HTML_Processor::create_fragment( $html );
	$tokens    = '';
	while ( $processor->next_token() ) {
		$tokens .= $processor->serialize_token();
	}

	if ( null === $processor->get_last_error() && $tokens !== $first ) {
		$issues[] = html_api_fuzzer_issue(
			'processor-normalize',
			'Token-by-token serialization differs from normalize().',
			array(
				'normalize' => $first,
				'tokens'    => $tokens,
			)
		);
	}

	return $issues;
}

/**
 * Exercises application-style first-wrapper mutation with WP_HTML_Tag_Processor.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_oracle_tag_processor_application( string $html ): array {
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( ! $processor->next_tag() ) {
		return array();
	}

	$tag_name = $processor->get_tag();
	$processor->add_class( 'html-api-fuzz-class' );
	$processor->set_attribute( 'data-html-api-fuzz', '<value & "quoted">' );

	$updated_once  = $processor->get_updated_html();
	$updated_twice = $processor->get_updated_html();

	if ( $updated_once !== $updated_twice ) {
		return array(
			html_api_fuzzer_issue(
				'tag-processor-application',
				'Repeated get_updated_html() changed output.',
				array(
					'tag_name'      => $tag_name,
					'updated_once'  => $updated_once,
					'updated_twice' => $updated_twice,
				)
			),
		);
	}

	$check = new WP_HTML_Tag_Processor( $updated_once );
	if ( ! $check->next_tag( array( 'class_name' => 'html-api-fuzz-class' ) ) ) {
		return array(
			html_api_fuzzer_issue(
				'tag-processor-application',
				'Application-style class mutation was not observable after update.',
				array(
					'tag_name' => $tag_name,
					'updated'  => $updated_once,
				)
			),
		);
	}

	return array();
}

/**
 * Exercises application-style IMG mutation through WP_HTML_Processor.
 *
 * @param string $html Input HTML.
 * @return array[] Issues.
 */
function html_api_fuzzer_oracle_processor_img_application( string $html ): array {
	$processor = WP_HTML_Processor::create_fragment( $html );
	if ( null === $processor || ! $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
		return array();
	}

	if ( ! $processor->set_attribute( 'fetchpriority', 'high' ) ) {
		return array(
			html_api_fuzzer_issue(
				'processor-img-application',
				'Processor matched IMG but could not set an attribute.',
				array(
					'token_name'  => $processor->get_token_name(),
					'breadcrumbs' => $processor->get_breadcrumbs(),
				)
			),
		);
	}

	$updated_once  = $processor->get_updated_html();
	$updated_twice = $processor->get_updated_html();
	if ( $updated_once !== $updated_twice ) {
		return array(
			html_api_fuzzer_issue(
				'processor-img-application',
				'Repeated processor get_updated_html() changed output.',
				array(
					'updated_once'  => $updated_once,
					'updated_twice' => $updated_twice,
				)
			),
		);
	}

	if ( false === strpos( $updated_once, 'fetchpriority="high"' ) ) {
		return array(
			html_api_fuzzer_issue(
				'processor-img-application',
				'Processor IMG mutation was not observable after update.',
				array( 'updated' => $updated_once )
			),
		);
	}

	return array();
}

/**
 * Minimizes an input while preserving a specific issue signature.
 *
 * @param string $html      Input HTML.
 * @param string $signature Issue signature.
 * @param int    $max_tries Maximum attempts.
 * @return array Minimized input and stats.
 */
function html_api_fuzzer_minimize_for_signature( string $html, string $signature, int $max_tries = 400 ): array {
	$tries = 0;

	$still_fails = function ( string $candidate ) use ( $signature, &$tries, $max_tries ): bool {
		if ( $tries >= $max_tries ) {
			return false;
		}
		++$tries;

		foreach ( html_api_fuzzer_find_issues( $candidate ) as $issue ) {
			if ( html_api_fuzzer_issue_signature( $issue ) === $signature ) {
				return true;
			}
		}

		return false;
	};

	$changed = true;
	while ( $changed && $tries < $max_tries ) {
		$changed = false;
		preg_match_all( '/<[^>]*>|&[#A-Za-z0-9]+;|[^<&]+|./s', $html, $matches );
		$chunks = $matches[0];

		for ( $i = 0; $i < count( $chunks ) && $tries < $max_tries; $i++ ) {
			$candidate_chunks = $chunks;
			array_splice( $candidate_chunks, $i, 1 );
			$candidate = implode( '', $candidate_chunks );
			if ( $candidate !== $html && $still_fails( $candidate ) ) {
				$html    = $candidate;
				$changed = true;
				break;
			}
		}
	}

	$chunk_size = (int) max( 1, floor( strlen( $html ) / 2 ) );
	while ( $chunk_size >= 1 && $tries < $max_tries ) {
		$changed = false;
		for ( $at = 0; $at < strlen( $html ) && $tries < $max_tries; $at += $chunk_size ) {
			$candidate = substr( $html, 0, $at ) . substr( $html, $at + $chunk_size );
			if ( $candidate !== $html && $still_fails( $candidate ) ) {
				$html    = $candidate;
				$changed = true;
				break;
			}
		}

		if ( ! $changed ) {
			$chunk_size = (int) floor( $chunk_size / 2 );
		}
	}

	return array(
		'html'  => $html,
		'tries' => $tries,
	);
}

/**
 * Creates a failure artifact.
 *
 * @param string $path           Artifact path.
 * @param array  $metadata       Metadata.
 * @param string $html           Original HTML.
 * @param array  $issue          Issue.
 * @param bool   $should_minimize Whether to minimize.
 */
function html_api_fuzzer_write_failure( string $path, array $metadata, string $html, array $issue, bool $should_minimize ): void {
	$signature = html_api_fuzzer_issue_signature( $issue );
	$minimized = array(
		'html'  => $html,
		'tries' => 0,
	);

	if ( $should_minimize ) {
		$minimized = html_api_fuzzer_minimize_for_signature( $html, $signature );
	}

	$artifact = array(
		'kind'                  => 'wp-html-api-fuzzer-failure',
		'signature'             => $signature,
		'metadata'              => $metadata,
		'issue'                 => $issue,
		'php_memory_limit'      => ini_get( 'memory_limit' ),
		'html_sha1'             => sha1( $html ),
		'html_base64'           => base64_encode( $html ),
		'html'                  => $html,
		'minimized_html_sha1'   => sha1( $minimized['html'] ),
		'minimized_html_base64' => base64_encode( $minimized['html'] ),
		'minimized_html'        => $minimized['html'],
		'minimize_tries'        => $minimized['tries'],
		'replay_command'        => html_api_fuzzer_replay_command( $path ),
	);

	file_put_contents( $path, html_api_fuzzer_json_encode( $artifact, true ) );
}
