<?php
/**
 * Unit tests covering WP_HTML_Processor support functionality.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

/**
 * @group html-api
 *
 * @coversDefaultClass WP_HTML_Processor
 */
class Tests_HtmlApi_wpHtmlProcessor_Support extends WP_UnitTestCase {
	/**
	 * @dataProvider data_fully_balanced_html
	 */
	public function test_detects_fully_balanced_html( $html ) {
		$p = new WP_HTML_Processor( $html );

		$this->assertTrue( $p->ensure_support(), "Detected that supported HTML input isn't supported." );
	}

	/**
	 * @return array[]
	 */
	public function data_fully_balanced_html() {
		return array(
			'Fully-nested balanced tags'                => array( '<div><p><strong>Test</strong></p></div>' ),
			'Sibling nested tags'                       => array( '<ul><li>One</li><li>Two</li><li>Three</li></ul>' ),
			'Top-level siblings'                        => array( '<li>One</li><li>Two</li><li>Three</li>' ),
			'Void tags'                                 => array( '<img><br><hr>' ),
			'Void tags with invalid self-closing flags' => array( '<img /><br/><hr />' ),
			'Invalid self-closing non-void'              => array( 'This <div/> is (not) empty.</div>' ),
			'Nested with void tags'                     => array( '<div><p><img>Text<br>More Text</p></div>'),
			'HTML foreign elements'                     => array( '<svg><circle /></svg>'),
		);
	}

	/**
	 * @dataProvider data_not_fully_balanced_html
	 */
	public function test_detects_not_fully_balanced_html( $html ) {
		$p = new WP_HTML_Processor( $html );

		$this->assertFalse( $p->ensure_support(), 'Detected that unsupported HTML input is supported.' );
	}

	/**
	 * @return array[]
	 */
	public function data_not_fully_balanced_html() {
		return array(
			'Unclosed tag'                               => array( '<p>Unclosed paragraph' ),
			'Unclosed nested tag'                        => array( '<div><p>Unclosed paragraph</div>' ),
			'Overlapping tags'                           => array( '<strong><p>Important</strong></p>' ),
			'Overlapping nested tags'                    => array( '<div><strong><p>Important</strong></p></div>' ),
			'Invalid self-closing non-void'              => array( 'This <div/> is (not) empty.' ),
			'Un-closed HTML foreign self-closer'         => array( '<svg><circle></svg>'),
			'Improperly-closed HTML foreign self-closer' => array( '<svg><circle /></circle></svg>'),
		);
	}

	/**
	 * @dataProvider data_not_fully_balanced_html
	 */
	public function test_does_not_call_next_tag_for_unsupported_html( $html ) {
		$p = new WP_HTML_Processor( $html );

		$this->assertFalse( $p->next_tag(), "Advanced to '{$p->get_tag()}' even though input HTML isn't supported." );
	}
}
