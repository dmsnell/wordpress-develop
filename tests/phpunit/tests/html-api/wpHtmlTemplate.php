<?php
/**
 * Unit tests covering WP_HTML_Template functionality.
 *
 * @package WordPress
 * @subpackage HTML-API
 *
 * @since 6.5.0
 *
 * @group html-api
 *
 * @coversDefaultClass WP_HTML_Template
 */

class Tests_HtmlApi_WpHtmlTemplate extends WP_UnitTestCase {
	/**
	 * Demonstrates how to pass values into an HTML template.
	 *
	 * @ticket 60229
	 */
	public function test_basic_render() {
		$html = WP_HTML_Template::render(
			'<div class="is-test </%class>" ...div-args inert="</%is_inert>">Just a </%count> test</div>',
			array(
				'count'    => '<strong>Hi <3</strong>',
				'class'    => '5>4',
				'is_inert' => 'inert',
				'div-args' => array(
					'class'    => 'hoover',
					'disabled' => true,
				),
			)
		);

		$this->assertSame(
			'<div disabled class="hoover"  inert="inert">Just a &lt;strong&gt;Hi &lt;3&lt;/strong&gt; test</div>',
			$html,
			'Failed to properly render template.'
		);
	}
}
