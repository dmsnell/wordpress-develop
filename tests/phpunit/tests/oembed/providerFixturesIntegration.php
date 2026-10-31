<?php
/**
 * Exercises the oEmbed pipeline with captured provider responses and default filters.
 *
 * @group oembed
 * @coversDefaultClass WP_oEmbed
 */
class Tests_oEmbed_ProviderFixtures_Integration extends WP_UnitTestCase {

	/**
	 * @covers ::get_html
	 */
	public function test_soundcloud_json_preserves_the_player_and_adds_a_title() {
		$html = $this->get_html_with_responses(
			'https://soundcloud.com/matas/hobnotropic',
			array( 'width' => 600, 'height' => 400 ),
			array(
				array(
					'endpoint' => 'https://soundcloud.com/oembed',
					'format'   => 'json',
					'file'     => 'responses/soundcloud.json',
				),
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'IFRAME' ) );
		$this->assertSame( 'https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F49931&show_artwork=true', $tags->get_attribute( 'src' ) );
		$this->assertSame( 'Hobnotropic by matas petrikas', $tags->get_attribute( 'title' ) );
		$this->assertSame( '100%', $tags->get_attribute( 'width' ) );
		$this->assertSame( '400', $tags->get_attribute( 'height' ) );
		$this->assertFalse( $tags->next_tag( 'IFRAME' ) );
	}

	/**
	 * @covers ::get_html
	 */
	public function test_reddit_json_preserves_trusted_links_and_widget_script() {
		$html = $this->get_html_with_responses(
			'https://www.reddit.com/r/Showerthoughts/comments/2safxv/we_should_start_keeping_giraffes_a_secret_from/cno7zic',
			array( 'width' => 600, 'height' => 400 ),
			array(
				array(
					'endpoint' => 'https://www.reddit.com/oembed',
					'format'   => 'json',
					'file'     => 'responses/reddit.json',
				),
			)
		);

		$this->assertSame( file_get_contents( DIR_TESTDATA . '/oembed/expected/reddit.html' ), $html );
		$tags   = new WP_HTML_Tag_Processor( $html );
		$counts = array( 'A' => 0, 'SCRIPT' => 0 );
		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();
			if ( isset( $counts[ $tag ] ) ) {
				++$counts[ $tag ];
			}
			if ( 'SCRIPT' === $tag ) {
				$this->assertSame( 'https://embed.reddit.com/widgets.js', $tags->get_attribute( 'src' ) );
			}
		}
		$this->assertSame( array( 'A' => 4, 'SCRIPT' => 1 ), $counts );
	}

	/**
	 * The HTTP 501 is simulated to exercise WordPress's XML fallback. The XML body
	 * was captured from Canva's discovered API endpoint; Canva did not return 501.
	 *
	 * @covers ::get_html
	 * @covers ::fetch
	 */
	public function test_canva_xml_fallback_preserves_the_player_and_adds_a_title() {
		$html = $this->get_html_with_responses(
			'https://www.canva.com/design/DACSWFLr08k/view',
			array( 'width' => 940, 'height' => 788 ),
			array(
				array(
					'endpoint' => 'https://canva.com/_oembed',
					'format'   => 'json',
					'code'     => 501,
				),
				array(
					'endpoint' => 'https://canva.com/_oembed',
					'format'   => 'xml',
					'file'     => 'responses/canva-xml.xml',
				),
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'IFRAME' ) );
		$this->assertSame( 'https://www.canva.com/design/DACSWFLr08k/view?embed&meta', $tags->get_attribute( 'src' ) );
		$this->assertSame( 'how canva makes embeds', $tags->get_attribute( 'title' ) );
		$this->assertSame( '940', $tags->get_attribute( 'width' ) );
		$this->assertSame( '788', $tags->get_attribute( 'height' ) );
		$this->assertFalse( $tags->next_tag( 'IFRAME' ) );
	}

	/**
	 * @covers ::get_html
	 * @covers ::discover
	 */
	public function test_wordpress_discovery_sanitizes_untrusted_embed_html() {
		$source_url = 'https://make.wordpress.org/core/2016/03/11/embeds-changes-in-wordpress-4-5/';
		$html       = $this->get_html_with_responses(
			$source_url,
			array( 'width' => 600, 'height' => 338 ),
			array(
				array(
					'endpoint' => $source_url,
					'format'   => null,
					'file'     => 'discovery/wordpress.html',
				),
				array(
					'endpoint' => 'https://make.wordpress.org/core/wp-json/oembed/1.0/embed',
					'format'   => 'json',
					'file'     => 'responses/wordpress.json',
				),
			)
		);

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'BLOCKQUOTE' ) );
		$secret = $tags->get_attribute( 'data-secret' );
		$this->assertIsString( $secret );
		$this->assertNotSame( '', $secret );
		$this->assertFalse( $tags->next_tag( 'BLOCKQUOTE' ) );

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( 'IFRAME' ) );
		$src = $tags->get_attribute( 'src' );
		$this->assertIsString( $src );
		$this->assertSame( $source_url . 'embed/', explode( '#', $src, 2 )[0] );
		// The captured URL already has a secret fragment; filtering appends a new one.
		$this->assertStringEndsWith( '#?secret=' . $secret, $src );
		$this->assertSame( $secret, $tags->get_attribute( 'data-secret' ) );
		$this->assertSame( 'allow-scripts', $tags->get_attribute( 'sandbox' ) );
		$this->assertSame( 'restricted', $tags->get_attribute( 'security' ) );
		$this->assertSame( '600', $tags->get_attribute( 'width' ) );
		$this->assertSame( '338', $tags->get_attribute( 'height' ) );
		$this->assertFalse( $tags->next_tag( 'IFRAME' ) );

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertFalse( $tags->next_tag( 'SCRIPT' ) );
	}

	/**
	 * Replays an exact HTTP request sequence through the normal oEmbed pipeline.
	 *
	 * @param string  $source_url URL of the embedded content.
	 * @param int[]   $args       Explicit width and height for the oEmbed request.
	 * @param array[] $responses Expected endpoints, formats, fixture files, and optional status codes.
	 * @return string Filtered embed HTML.
	 */
	private function get_html_with_responses( $source_url, $args, $responses ) {
		$oembed         = _wp_oembed_get_object();
		$newline_filter = array( $oembed, '_strip_newlines' );
		$had_filter     = has_filter( 'oembed_dataparse', $newline_filter );
		$requests       = 0;

		// PHPUnit restores hooks between cases, but the oEmbed singleton survives.
		if ( false === $had_filter ) {
			add_filter( 'oembed_dataparse', $newline_filter, 10, 3 );
		}

		$callback = function ( $response, $request_args, $request_url ) use ( $source_url, $args, $responses, &$requests ) {
			$this->assertArrayHasKey( $requests, $responses, 'Unexpected HTTP request: ' . $request_url );
			$expected = $responses[ $requests ];
			++$requests;

			$parts = explode( '?', $request_url, 2 );
			$this->assertSame( $expected['endpoint'], $parts[0] );
			parse_str( $parts[1] ?? '', $query );
			$expected_query = array();
			if ( null === $expected['format'] ) {
				$this->assertSame( 153600, $request_args['limit_response_size'] );
			} else {
				$expected_query = array(
					'url'       => $source_url,
					'format'    => $expected['format'],
					'maxwidth'  => (string) $args['width'],
					'maxheight' => (string) $args['height'],
					'dnt'       => '1',
				);
			}
			ksort( $query );
			ksort( $expected_query );
			$this->assertSame( $expected_query, $query );

			$code = $expected['code'] ?? 200;
			return array(
				'headers'  => array(),
				'body'     => isset( $expected['file'] ) ? file_get_contents( DIR_TESTDATA . '/oembed/' . $expected['file'] ) : '',
				'response' => array( 'code' => $code, 'message' => 501 === $code ? 'Not Implemented' : 'OK' ),
				'cookies'  => array(),
			);
		};

		add_filter( 'pre_http_request', $callback, PHP_INT_MAX, 3 );
		try {
			$this->assertSame( 5, has_filter( 'oembed_dataparse', 'wp_filter_oembed_iframe_title_attribute' ) );
			$this->assertSame( 10, has_filter( 'oembed_dataparse', 'wp_filter_oembed_result' ) );
			$this->assertSame( 10, has_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result' ) );
			$this->assertSame( 10, has_filter( 'oembed_dataparse', $newline_filter ) );

			$html = $oembed->get_html( $source_url, $args );
			$this->assertSame( count( $responses ), $requests );
			$this->assertIsString( $html );
			$this->assertStringNotContainsString( "\r", $html );
			$this->assertStringNotContainsString( "\n", $html );
			return $html;
		} finally {
			remove_filter( 'pre_http_request', $callback, PHP_INT_MAX );
			if ( false === $had_filter ) {
				remove_filter( 'oembed_dataparse', $newline_filter, 10 );
			}
		}
	}
}
