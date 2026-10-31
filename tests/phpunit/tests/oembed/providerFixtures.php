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
	 * Returns usable captured provider responses.
	 *
	 * @return array[]
	 */
	public static function data_provider_fixtures() {
		return array(
			'youtube' => array(
				'youtube.json',
				'https://www.youtube.com/watch?v=72xdCU__XCk',
				'video',
			),
			'vimeo' => array(
				'vimeo.json',
				'https://vimeo.com/76979871',
				'video',
			),
			'dailymotion' => array(
				'dailymotion.json',
				'https://www.dailymotion.com/video/x7tgad0',
				'video',
			),
			'flickr' => array(
				'flickr.json',
				'https://www.flickr.com/photos/bees/2341623661/',
				'photo',
			),
			'smugmug' => array(
				'smugmug.json',
				'https://gilmarphotography.smugmug.com/Galleries/Hamburger-Hat/n-fzXnQ/i-TtMhZ3v',
				'photo',
			),
			'scribd' => array(
				'scribd.json',
				'https://www.scribd.com/document/505551003/Tales-from-the-Evil-Empire-The-mysteries-of-Orchard-s-OEmbedPart-or-how-to-integrate-YouTube-videos',
				'rich',
			),
			'wordpress-tv' => array(
				'wordpress-tv.json',
				'https://wordpress.tv/2019/11/03/2019-state-of-the-word/',
				'video',
			),
			'crowdsignal' => array(
				'crowdsignal.json',
				'https://poll.fm/12463328',
				'rich',
			),
			'twitter' => array(
				'twitter.json',
				'https://twitter.com/Interior/status/463440424141459456',
				'rich',
			),
			'soundcloud' => array(
				'soundcloud.json',
				'https://soundcloud.com/matas/hobnotropic',
				'rich',
			),
			'spotify' => array(
				'spotify.json',
				'https://open.spotify.com/episode/7makk4oTQel546B0PZlDM5',
				'rich',
			),
			'issuu' => array(
				'issuu.json',
				'https://issuu.com/zestmedialondon/docs/absolutely_kensington_chelsea_may_2026',
				'rich',
			),
			'mixcloud' => array(
				'mixcloud.json',
				'https://www.mixcloud.com/spartacus/party-time/',
				'rich',
			),
			'ted' => array(
				'ted.json',
				'https://www.ted.com/talks/sir_ken_robinson_do_schools_kill_creativity',
				'video',
			),
			'animoto' => array(
				'animoto.json',
				'https://video214.com/play/eljukF5O1S13GEnPNBz6Tg/s/dark',
				'video',
			),
			'tumblr' => array(
				'tumblr.json',
				'https://staff.tumblr.com/post/5845153911',
				'rich',
			),
			'kickstarter' => array(
				'kickstarter.json',
				'https://www.kickstarter.com/projects/ankermake/eufymake-e1-the-first-personal-3d-textured-uv-printer',
				'rich',
			),
			'cloudup' => array(
				'cloudup.json',
				'https://cloudup.com/c64lcxc-u5Y',
				'photo',
			),
			'reverbnation' => array(
				'reverbnation.json',
				'https://www.reverbnation.com/andredrayyungnsprings/song/9295486-keep-it-g',
				'rich',
			),
			'videopress' => array(
				'videopress.json',
				'https://videopress.com/v/8cQHyy5N',
				'video',
			),
			'reddit' => array(
				'reddit.json',
				'https://www.reddit.com/r/Showerthoughts/comments/2safxv/we_should_start_keeping_giraffes_a_secret_from/cno7zic',
				'rich',
			),
			'speakerdeck' => array(
				'speakerdeck.json',
				'https://speakerdeck.com/jnunemaker/atom',
				'rich',
			),
			'amazon' => array(
				'amazon.json',
				'https://www.amazon.com/dp/B00Y7WCHQW',
				'rich',
			),
			'tiktok' => array(
				'tiktok.json',
				'https://www.tiktok.com/@scout2015/video/6718335390845095173',
				'video',
			),
			'pinterest' => array(
				'pinterest.json',
				'https://www.pinterest.com/pin/99360735500167749/',
				'rich',
			),
			'wolframcloud' => array(
				'wolframcloud.json',
				'https://www.wolframcloud.com/obj/0ae489a1-ba7d-47df-a8c6-516b42afa59a',
				'rich',
			),
			'pocketcasts' => array(
				'pocketcasts.json',
				'https://pca.st/podnews',
				'rich',
			),
			'anghami' => array(
				'anghami.json',
				'https://play.anghami.com/playlist/22304669',
				'rich',
			),
			'bluesky' => array(
				'bluesky.json',
				'https://bsky.app/profile/wordpress.org/post/3md4f4s5dyi2k',
				'rich',
			),
			'canva' => array(
				'canva.json',
				'https://www.canva.com/design/DACSWFLr08k/view',
				'rich',
			),
			'wordpress' => array(
				'wordpress.json',
				'https://make.wordpress.org/core/2016/03/11/embeds-changes-in-wordpress-4-5/',
				'rich',
			),
			'wordpress-tv-discovered' => array(
				'wordpress-tv-discovered.json',
				'https://wordpress.tv/2019/11/03/2019-state-of-the-word/',
				'rich',
			),
			'amazon-uk' => array(
				'amazon-uk.json',
				'https://www.amazon.co.uk/dp/B00Y7WCHQW',
				'rich',
			),
			'amazon-au' => array(
				'amazon-au.json',
				'https://www.amazon.com.au/dp/B00Y7WCHQW',
				'rich',
			),
			'amazon-in' => array(
				'amazon-in.json',
				'https://www.amazon.in/dp/B00Y7WCHQW',
				'rich',
			),
			'canva-xml' => array(
				'canva-xml.xml',
				'https://www.canva.com/design/DACSWFLr08k/view',
				'rich',
			),
		);
	}

	/**
	 * Returns discovery pages and their expected endpoints.
	 *
	 * @return array[]
	 */
	public static function data_discovery_fixtures() {
		return array(
			'youtube' => array(
				'youtube',
				'https://www.youtube.com/watch?v=72xdCU__XCk',
				false,
			),
			'vimeo' => array(
				'vimeo',
				'https://vimeo.com/76979871',
				'https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F76979871%3Fh%3D8272103f6e',
			),
			'dailymotion' => array(
				'dailymotion',
				'https://www.dailymotion.com/video/x7tgad0',
				false,
			),
			'flickr' => array(
				'flickr',
				'https://www.flickr.com/photos/bees/2341623661/',
				'https://www.flickr.com/services/oembed?url=https://www.flickr.com/photos/bees/2341623661&format=json',
			),
			'smugmug' => array(
				'smugmug',
				'https://gilmarphotography.smugmug.com/Galleries/Hamburger-Hat/n-fzXnQ/i-TtMhZ3v',
				false,
			),
			'scribd' => array(
				'scribd',
				'https://www.scribd.com/document/505551003/Tales-from-the-Evil-Empire-The-mysteries-of-Orchard-s-OEmbedPart-or-how-to-integrate-YouTube-videos',
				false,
			),
			'wordpress-tv' => array(
				'wordpress-tv',
				'https://wordpress.tv/2019/11/03/2019-state-of-the-word/',
				'https://public-api.wordpress.com/oembed/?format=json&url=https%3A%2F%2Fwordpress.tv%2F2019%2F11%2F03%2F2019-state-of-the-word%2F&for=wpcom-auto-discovery',
			),
			'crowdsignal' => array(
				'crowdsignal',
				'https://poll.fm/12463328',
				false,
			),
			'twitter' => array(
				'twitter',
				'https://twitter.com/Interior/status/463440424141459456',
				'https://publish.x.com/oembed?url=https%3A%2F%2Fx.com%2FInterior%2Fstatus%2F463440424141459456',
			),
			'soundcloud' => array(
				'soundcloud',
				'https://soundcloud.com/matas/hobnotropic',
				'https://soundcloud.com/oembed?url=https%3A%2F%2Fsoundcloud.com%2Fmatas%2Fhobnotropic&format=xml',
			),
			'spotify' => array(
				'spotify',
				'https://open.spotify.com/episode/7makk4oTQel546B0PZlDM5',
				'https://open.spotify.com/oembed?url=https%3A%2F%2Fopen.spotify.com%2Fepisode%2F7makk4oTQel546B0PZlDM5',
			),
			'issuu' => array(
				'issuu',
				'https://issuu.com/zestmedialondon/docs/absolutely_kensington_chelsea_may_2026',
				false,
			),
			'mixcloud' => array(
				'mixcloud',
				'https://www.mixcloud.com/spartacus/party-time/',
				'https://app.mixcloud.com/oembed/?url=https%3A%2F%2Fwww.mixcloud.com%2Fspartacus%2Fparty-time%2F',
			),
			'ted' => array(
				'ted',
				'https://www.ted.com/talks/sir_ken_robinson_do_schools_kill_creativity',
				'https://www.ted.com/services/v1/oembed.json?url=https%3A%2F%2Fwww.ted.com%2Ftalks%2Fsir_ken_robinson_do_schools_kill_creativity',
			),
			'animoto' => array(
				'animoto',
				'https://video214.com/play/eljukF5O1S13GEnPNBz6Tg/s/dark',
				'https://video214.com/oembeds/create.json?automated=true&options=start_hq&url=https%3A%2F%2Fvideo214.com%2Fplay%2FeljukF5O1S13GEnPNBz6Tg',
			),
			'tumblr' => array(
				'tumblr',
				'https://staff.tumblr.com/post/5845153911',
				'https://www.tumblr.com/oembed/1.0?url=https://staff.tumblr.com/post/5845153911/our-very-own-topherchris-is-featured-in-time-out',
			),
			'kickstarter' => array(
				'kickstarter',
				'https://www.kickstarter.com/projects/ankermake/eufymake-e1-the-first-personal-3d-textured-uv-printer',
				'https://www.kickstarter.com/services/oembed?url=https%3A%2F%2Fwww.kickstarter.com%2Fprojects%2Fankermake%2Feufymake-e1-the-first-personal-3d-textured-uv-printer',
			),
			'cloudup' => array(
				'cloudup',
				'https://cloudup.com/c64lcxc-u5Y',
				'https://cloudup.com/oembed?url=https%3A%2F%2Fcloudup.com%2Fc64lcxc-u5Y',
			),
			'reverbnation' => array(
				'reverbnation',
				'https://www.reverbnation.com/andredrayyungnsprings/song/9295486-keep-it-g',
				false,
			),
			'videopress' => array(
				'videopress',
				'https://videopress.com/v/8cQHyy5N',
				'https://public-api.wordpress.com/oembed/?for=wpcom-auto-discovery&url=https%3A%2F%2Fvideopress.com%2Fv%2F8cQHyy5N&format=json',
			),
			'reddit' => array(
				'reddit',
				'https://www.reddit.com/r/Showerthoughts/comments/2safxv/we_should_start_keeping_giraffes_a_secret_from/cno7zic',
				false,
			),
			'speakerdeck' => array(
				'speakerdeck',
				'https://speakerdeck.com/jnunemaker/atom',
				'https://speakerdeck.com/oembed.json?url=https%3A%2F%2Fspeakerdeck.com%2Fjnunemaker%2Fatom',
			),
			'amazon' => array(
				'amazon',
				'https://www.amazon.com/dp/B00Y7WCHQW',
				false,
			),
			'tiktok' => array(
				'tiktok',
				'https://www.tiktok.com/@scout2015/video/6718335390845095173',
				false,
			),
			'pinterest' => array(
				'pinterest',
				'https://www.pinterest.com/pin/99360735500167749/',
				false,
			),
			'wolframcloud' => array(
				'wolframcloud',
				'https://www.wolframcloud.com/obj/0ae489a1-ba7d-47df-a8c6-516b42afa59a',
				false,
			),
			'pocketcasts' => array(
				'pocketcasts',
				'https://pca.st/podnews',
				'https://pca.st/oembed.json?url=https%3A%2F%2Fpocketcasts.com%2Fpodcast%2Fpodnews-daily-podcast-industry-and-podcasting-news%2Fafc41610-c22f-0135-9e60-5bb073f92b78',
			),
			'anghami' => array(
				'anghami',
				'https://play.anghami.com/playlist/22304669',
				'https://coussa.anghami.com/rest/v1/oembed.view?url=https://play.anghami.com/playlist/22304669&format=json',
			),
			'bluesky' => array(
				'bluesky',
				'https://bsky.app/profile/wordpress.org/post/3md4f4s5dyi2k',
				'https://embed.bsky.app/oembed?format=json&url=at%3A%2F%2Fdid%3Aplc%3Ar2no5dzwjjkp4cysp43tqzxa%2Fapp.bsky.feed.post%2F3md4f4s5dyi2k',
			),
			'wordpress' => array(
				'wordpress',
				'https://make.wordpress.org/core/2016/03/11/embeds-changes-in-wordpress-4-5/',
				'https://make.wordpress.org/core/wp-json/oembed/1.0/embed?url=https%3A%2F%2Fmake.wordpress.org%2Fcore%2F2016%2F03%2F11%2Fembeds-changes-in-wordpress-4-5%2F',
			),
			'wordpress-tv-discovered' => array(
				'wordpress-tv-discovered',
				'https://wordpress.tv/2019/11/03/2019-state-of-the-word/',
				'https://public-api.wordpress.com/oembed/?format=json&url=https%3A%2F%2Fwordpress.tv%2F2019%2F11%2F03%2F2019-state-of-the-word%2F&for=wpcom-auto-discovery',
			),
			'amazon-uk' => array(
				'amazon-uk',
				'https://www.amazon.co.uk/dp/B00Y7WCHQW',
				false,
			),
			'amazon-au' => array(
				'amazon-au',
				'https://www.amazon.com.au/dp/B00Y7WCHQW',
				false,
			),
			'amazon-in' => array(
				'amazon-in',
				'https://www.amazon.in/dp/B00Y7WCHQW',
				false,
			),
			'soundcloud-xml' => array(
				'soundcloud-xml',
				'https://soundcloud.com/forss/flickermood',
				'https://soundcloud.com/oembed?url=https%3A%2F%2Fsoundcloud.com%2Fforss%2Fflickermood&format=xml',
			),
		);
	}

	/**
	 * Parses a captured response without changing its embedded HTML.
	 *
	 * @param string $filename Response fixture filename.
	 * @return object Parsed oEmbed response.
	 */
	private function get_fixture_data( $filename ) {
		$raw = file_get_contents( DIR_TESTDATA . '/oembed/responses/' . $filename );
		if ( 'xml' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$xml = simplexml_load_string( $raw );
			$this->assertNotFalse( $xml );
			$data = (object) array_map( 'strval', (array) $xml );
		} else {
			$data = json_decode( $raw );
		}
		$this->assertIsObject( $data );
		return $data;
	}

	/**
	 * @dataProvider data_provider_fixtures
	 * @covers ::_strip_newlines
	 * @covers ::data2html
	 *
	 * @param string $filename      Response fixture filename.
	 * @param string $source_url    URL of the embedded content.
	 * @param string $expected_type Expected oEmbed response type.
	 */
	public function test_provider_html( $filename, $source_url, $expected_type ) {
		$data = $this->get_fixture_data( $filename );
		$this->assertSame( $expected_type, $data->type );
		$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
		if ( 'photo' === $data->type ) {
			$html = $oembed->data2html( $data, $source_url );
			$tags = new WP_HTML_Tag_Processor( $html );
			$this->assertTrue( $tags->next_tag( 'IMG' ) );
			$this->assertSame( $data->url, $tags->get_attribute( 'src' ) );
			$this->assertSame( (string) $data->width, $tags->get_attribute( 'width' ) );
			$this->assertSame( (string) $data->height, $tags->get_attribute( 'height' ) );
			$this->assertSame( $html, $oembed->_strip_newlines( $html, $data, $source_url ) );
		} else {
			$expected_file = pathinfo( $filename, PATHINFO_FILENAME ) . '.html';
			$this->assertSame(
				file_get_contents( DIR_TESTDATA . '/oembed/expected/' . $expected_file ),
				$oembed->_strip_newlines( $data->html, $data, $source_url )
			);
		}
	}

	/**
	 * @dataProvider data_discovery_fixtures
	 * @covers ::discover
	 *
	 * @param string       $basename          Discovery fixture basename.
	 * @param string       $source_url        URL of the embedded content.
	 * @param string|false $expected_endpoint Discovered provider endpoint, or false.
	 */
	public function test_discovery( $basename, $source_url, $expected_endpoint ) {
		$body = file_get_contents( DIR_TESTDATA . '/oembed/discovery/' . $basename . '.html' );
		$this->assertLessThanOrEqual( 153600, strlen( $body ) );
		$requests = 0;
		$callback = function ( $response, $args, $url ) use ( $source_url, $body, &$requests ) {
			++$requests;
			$this->assertSame( $source_url, $url );
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
			$this->assertSame( $expected_endpoint, $oembed->discover( $source_url ) );
			$this->assertSame( 1, $requests );
		} finally {
			remove_filter( 'pre_http_request', $callback, PHP_INT_MAX );
		}
	}

	/**
	 * A genuine provider response can contain unusable embedded HTML.
	 *
	 * @covers ::_strip_newlines
	 */
	public function test_malformed_soundcloud_xml_preserves_literal_cdata_without_an_iframe() {
		$data   = $this->get_fixture_data( 'soundcloud-xml.xml' );
		$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
		$html   = $oembed->_strip_newlines( $data->html, $data, 'https://soundcloud.com/forss/flickermood' );

		$this->assertStringStartsWith( '<![CDATA[', $data->html );
		$this->assertStringEndsWith( ']]>', $data->html );
		$this->assertSame( $data->html, $html );
		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertFalse( $tags->next_tag( 'IFRAME' ) );
	}

	/**
	 * @return array[]
	 */
	public static function data_iframe_fixtures() {
		return array(
			'SoundCloud JSON' => array(
				'soundcloud.json',
				'https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F49931&show_artwork=true',
			),
			'Canva XML'       => array(
				'canva-xml.xml',
				'https://www.canva.com/design/DACSWFLr08k/view?embed&meta',
			),
		);
	}

	/**
	 * @dataProvider data_iframe_fixtures
	 * @covers ::_strip_newlines
	 *
	 * @param string $filename     Response fixture filename.
	 * @param string $expected_src Expected iframe source URL.
	 */
	public function test_provider_iframe_is_preserved( $filename, $expected_src ) {
		$data   = $this->get_fixture_data( $filename );
		$oembed = ( new ReflectionClass( 'WP_oEmbed' ) )->newInstanceWithoutConstructor();
		$tags   = new WP_HTML_Tag_Processor( $oembed->_strip_newlines( $data->html, $data, '' ) );

		$this->assertTrue( $tags->next_tag( 'IFRAME' ) );
		$this->assertSame( $expected_src, $tags->get_attribute( 'src' ) );
		$this->assertFalse( $tags->next_tag( 'IFRAME' ) );
	}

	/**
	 * @covers ::_strip_newlines
	 */
	public function test_reddit_does_not_duplicate_links_or_widget_scripts() {
		$data   = $this->get_fixture_data( 'reddit.json' );
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
