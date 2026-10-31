<?php
/**
 * Offline coverage using unmodified responses from public oEmbed providers.
 *
 * @group oembed
 * @coversDefaultClass WP_oEmbed
 */
class Tests_oEmbed_ProviderFixtures extends WP_UnitTestCase {
	/**
	 * Loads the class without registering duplicate data filters.
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-oembed.php';
	}

	/**
	 * Returns captured provider cases. Unavailable responses are not passing cases.
	 *
	 * @return array[]
	 */
	public static function data_provider_fixtures() {
		$manifest = json_decode( file_get_contents( DIR_TESTDATA . '/oembed/manifest.json' ), true );
		$cases    = array();
		foreach ( $manifest['providers'] as $name => $entry ) {
			if ( isset( $entry['response']['file'] ) ) {
				$cases[ $name ] = array( $entry );
			}
		}
		return $cases;
	}

	/**
	 * @return array[]
	 */
	public static function data_discovery_fixtures() {
		$manifest = json_decode( file_get_contents( DIR_TESTDATA . '/oembed/manifest.json' ), true );
		$cases    = array();
		foreach ( $manifest['providers'] as $name => $entry ) {
			if ( isset( $entry['discovery']['file'] ) ) {
				$cases[ $name ] = array( $entry );
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider data_provider_fixtures
	 * @covers ::_strip_newlines
	 * @covers ::data2html
	 *
	 * @param array $entry Captured response and provenance.
	 */
	public function test_provider_html( $entry ) {
		$raw = file_get_contents( DIR_TESTDATA . '/oembed/' . $entry['response']['file'] );
		$this->assertSame( $entry['response']['sha256'], hash( 'sha256', $raw ) );
		if ( 'xml' === $entry['format'] ) {
			$data = (object) array_map( 'strval', (array) simplexml_load_string( $raw ) );
		} else {
			$data = json_decode( $raw );
		}
		$this->assertIsObject( $data );
		$this->assertSame( $entry['type'], $data->type );
		$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
		if ( 'photo' === $data->type ) {
			$html = $oembed->data2html( $data, $entry['url'] );
			$tags = new WP_HTML_Tag_Processor( $html );
			$this->assertTrue( $tags->next_tag( 'IMG' ) );
			$this->assertSame( $data->url, $tags->get_attribute( 'src' ) );
			$this->assertSame( (string) $data->width, $tags->get_attribute( 'width' ) );
			$this->assertSame( (string) $data->height, $tags->get_attribute( 'height' ) );
			$this->assertSame( $html, $oembed->_strip_newlines( $html, $data, $entry['url'] ) );
		} else {
			$this->assertSame(
				file_get_contents( DIR_TESTDATA . '/oembed/' . $entry['expected_html'] ),
				$oembed->_strip_newlines( $data->html, $data, $entry['url'] )
			);
		}
	}

	/**
	 * @dataProvider data_discovery_fixtures
	 * @covers ::discover
	 *
	 * @param array $entry Captured page and provenance.
	 */
	public function test_discovery( $entry ) {
		$body = file_get_contents( DIR_TESTDATA . '/oembed/' . $entry['discovery']['file'] );
		$this->assertSame( $entry['discovery']['sha256'], hash( 'sha256', $body ) );
		$this->assertLessThanOrEqual( 153600, strlen( $body ) );
		$requests = 0;
		$callback = function ( $response, $args, $url ) use ( $entry, $body, &$requests ) {
			++$requests;
			$this->assertSame( $entry['url'], $url );
			$this->assertSame( 153600, $args['limit_response_size'] );
			return array(
				'headers'  => array(),
				'body'     => $body,
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
			);
		};
		add_filter( 'pre_http_request', $callback, PHP_INT_MAX, 3 );
		try {
			$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
			$this->assertSame( $entry['discovery']['expected_endpoint'], $oembed->discover( $entry['url'] ) );
			$this->assertSame( 1, $requests );
		} finally {
			remove_filter( 'pre_http_request', $callback, PHP_INT_MAX );
		}
	}

	/**
	 * Every captured file is accounted for, including unavailable providers.
	 */
	public function test_fixture_inventory() {
		$root       = DIR_TESTDATA . '/oembed/';
		$manifest   = json_decode( file_get_contents( $root . 'manifest.json' ), true );
		$referenced = array();
		foreach ( $manifest['providers'] as $entry ) {
			foreach ( array( 'response', 'discovery' ) as $kind ) {
				$capture = $entry[ $kind ];
				if ( isset( $capture['file'] ) ) {
					$this->assertFileExists( $root . $capture['file'] );
					$this->assertSame( $capture['sha256'], hash_file( 'sha256', $root . $capture['file'] ) );
					$referenced[] = $root . $capture['file'];
				} else {
					$this->assertNotEmpty( $capture['error'] );
				}
			}
			if ( isset( $entry['expected_html'] ) ) {
				$this->assertFileExists( $root . $entry['expected_html'] );
				$referenced[] = $root . $entry['expected_html'];
			}
		}
		$files = array_merge( glob( $root . 'responses/*' ), glob( $root . 'discovery/*' ), glob( $root . 'expected/*' ) );
		sort( $files );
		sort( $referenced );
		$this->assertSame( $files, $referenced );
	}

	/**
	 * @covers ::_strip_newlines
	 */
	public function test_reddit_does_not_duplicate_links_or_widget_scripts() {
		$data   = json_decode( file_get_contents( DIR_TESTDATA . '/oembed/responses/reddit.json' ) );
		$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
		$tags   = new WP_HTML_Tag_Processor( $oembed->_strip_newlines( $data->html, $data, '' ) );
		$counts = array( 'A' => 0, 'SCRIPT' => 0 );
		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();
			if ( isset( $counts[ $tag ] ) ) {
				++$counts[ $tag ];
			}
		}
		$this->assertSame( array( 'A' => 4, 'SCRIPT' => 1 ), $counts );
	}
}
