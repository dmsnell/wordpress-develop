<?php
/**
 * Unit tests covering WP_HTML_Processor support functionality.
 *
 * @package WordPress
 * @subpackage HTML-API
 */

require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-attribute-token.php';
require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-span.php';
require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-spec.php';
require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-text-replacement.php';
require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-tag-processor.php';
require_once '/Users/dmsnell/code/WordPress-develop/src/wp-includes/html-api/class-wp-html-processor.php';

class WP_UnitTestCase extends PHPUnit\Framework\TestCase {}

function esc_attr( $s ) { return str_replace( [ '<', '>', '"' ], [ '&lt;', '&gt;', '&quot;' ], $s ); }

/**
 * @group html-api
 *
 * @coversDefaultClass WP_HTML_Processor
 */
class Tests_HtmlApi_wpHtmlProcessor_Support extends WP_UnitTestCase {
	private function html_processor_at_start( $html ) {
		$p = new WP_HTML_Processor( $html );

		while ( true !== $p->get_attribute( 'start' ) && $p->next_tag() ) {
			continue;
		}

		return $p;
	}

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

	/**
	 * @dataProvider data_next_sibling
	 */
	public function test_finds_next_sibling( $html ) {
		$p = $this->html_processor_at_start( $html );
		$p->next_sibling();

		$this->assertTrue( $p->get_attribute( 'end' ), 'Did not finding sibling tag.' );
	}

	/**
	 * @return array[]
	 */
	public function data_next_sibling() {
		return array(
			'Leading markup'     => array( 'before<img start><img end><img>' ),
			'Top-level siblings' => array( '<img start><img end><img>' ),
			'Nested siblings'    => array( '<ul><li>One</li><li start>Two</li><li end>Three</li><li>Four</li></ul>' ),
			'Nesting avalanche'  => array( '<img start><ul end><li><div><p><strong><a><img></a></strong></p></div></li></ul><div></div><footer></footer>'),
		);
	}

	/**
	 * @dataProvider data_no_next_sibling
	 */
	public function test_finds_no_next_sibling_when_none_exists( $html ) {
		$p = $this->html_processor_at_start( $html );
		$this->assertFalse( $p->next_sibling(), 'Found a sibling when none exists.' );
	}

	public function data_no_next_sibling() {
		return array(
			'Leading markup'        => array( 'before<div><img start></div><img end><img>' ),
			'No more siblings'      => array( '<ul><li></li><li start></li></ul><ul><li end></li></ul>' ),
			'Tag-closing avalanche' => array( '<ul><li><div><p><strong><a><img start></a></strong></p></div></li></ul><div end></div><footer></footer>'),
		);
	}

	/**
	 * @dataProvider data_next_child
	 */
	public function test_finds_next_child( $html ) {
		$p = $this->html_processor_at_start( $html );

		$p->first_child();
		$this->assertTrue( $p->get_attribute( 'end' ), 'Did not find child tag.' );
	}

	public function data_next_child() {
		return array(
			'Leading markup' => array( 'this is not tag content<div start><img end></div>afterwards' ),
			'Normal nesting' => array( '<ul><li></li><li><p start>text<img end></p></li><li></li></ul>' ),
		);
	}

	/**
	 * @dataProvider data_no_next_child
	 */
	public function test_finds_no_next_child( $html ) {
		$p = $this->html_processor_at_start( $html );

		$this->assertFalse( $p->first_child(), 'Did not find child tag.' );
	}

	public function data_no_next_child() {
		return array(
			'Leading markup' => array( 'this is not tag content<div start></div><img end>afterwards' ),
			'Already nested' => array( '<li><li></li><li><p start>text</p><img end></li><li></li></ul>', ),
			'Void element'   => array( '<img start>' ),
		);
	}

	public function test_finds_chain_of_elements() {
		$p = new WP_HTML_Processor( <<<HTML
<main>
	<h2>Things I could be eating right now</h2>
	<ul>
		<li>Apples</li>
		<li>Pears</li>
		<li><em>Prickly</em> pears</li>
		<li>
			<img src="yum.avif">
			<details>
				<summary>Scwarzwälder Kirschtorte</summary>
				<ul>
					<li>Flour</li>
					<li>Eggs</li>
					<li this-one>Sugar</li>
					<li>Cream</li>
					<li>Cherries</li>
					<li>Chocolate</li>
				</ul>
			</details>
		</li>
	</ul>
</main>
HTML
		);

		$p->next_tag();
		$p->first_child();
		$p->next_sibling();
		$p->first_child();
		$p->next_sibling();
		$p->next_sibling();
		$p->next_sibling();
		$p->first_child();
		$p->next_sibling();
		$p->first_child();
		$p->next_sibling();
		$p->first_child();
		$p->next_sibling();
		$p->next_sibling();

		$this->assertTrue( $p->get_attribute( 'this-one' ) );
	}

	/**
	 * @dataProvider data_inner_content
	 */
	public function test_get_inner_content( $before, $inner, $after ) {
		$p = $this->html_processor_at_start( $before . $inner . $after );

		$this->assertSame( $inner, $p->get_inner_content(), 'Found the wrong inner content.' );
	}

	public function data_inner_content() {
		return array(
			'Leading text' => array( '<!-- when will this start? --><div start>', 'text', '</div>' ),
			'Single tag'   => array( '<div start>', 'text', '</div>' ),
			'Nested tags'  => array( '<div start>', '<ul><li>One</li><li><strong>Two<img></strong></li></ul>', '</div>' ),
			'Complex HTML' => array(
				<<<HTML
<main>
	<h2>Things I could be eating right now</h2>
	<ul>
		<li>Apples</li>
		<li>Pears</li>
		<li><em>Prickly</em> pears</li>
		<li>
			<img src="yum.avif">
			<details start>
HTML,
				<<<HTML
				<summary>Scwarzwälder Kirschtorte</summary>
				<ul>
					<li>Flour</li>
					<li>Eggs</li>
					<li>Sugar</li>
					<li>Cream</li>
					<li>Cherries</li>
					<li>Chocolate</li>
				</ul>

HTML,
			<<<HTML
</details>
		</li>
	</ul>
</main>
HTML
			),
		);
	}

	/**
	 * @dataProvider data_set_inner_content
	 */
	public function test_set_inner_content( $before, $old, $new, $after ) {
		$p = $this->html_processor_at_start( $before . $old . $after );

		$p->set_inner_content( $new );

		$this->assertSame( $before . $new . $after, $p->get_updated_html(), 'Did not properly swap out inner content.' );
	}

	public function data_set_inner_content() {
		return array(
			'Single tag'  => array( '<div start>', 'boring text', 'exciting text', '</div>' ),
			'Nested tags' => array( '<div><ul><li start>', '<p><img>This is <strong>neat</strong></p>', 'this<br>is<br>not', '</li></ul></div>' ),
		);
	}

	/**
	 * @dataProvider data_outer_content
	 */
	public function test_get_outer_content( $before, $outer, $after ) {
		$p = $this->html_processor_at_start( $before . $outer . $after );

		$this->assertSame( $outer, $p->get_outer_content(), 'Found the wrong outer content.' );
	}

	public function data_outer_content() {
		return array(
			'Leading text' => array( '<!-- when will this start? -->', '<div start>text</div>', 'when will it end?' ),
			'Single tag'   => array( '', '<div start>text</div>', '' ),
			'Nested tags'  => array( '<div>', '<ul start><li>One</li><li><strong>Two<img></strong></li></ul>', '</div>' ),
			'Complex HTML' => array(
				<<<HTML
<main>
	<h2>Things I could be eating right now</h2>
	<ul>
		<li>Apples</li>
		<li>Pears</li>
		<li><em>Prickly</em> pears</li>
		<li>
			<img src="yum.avif">

HTML,
				<<<HTML
<details start>
				<summary>Scwarzwälder Kirschtorte</summary>
				<ul>
					<li>Flour</li>
					<li>Eggs</li>
					<li>Sugar</li>
					<li>Cream</li>
					<li>Cherries</li>
					<li>Chocolate</li>
				</ul>
			</details>
HTML,
			<<<HTML
		</li>
	</ul>
</main>
HTML
			),
		);
	}

	/**
	 * @dataProvider data_set_outer_content
	 */
	public function test_set_outer_content( $before, $old, $new, $after ) {
		$p = $this->html_processor_at_start( $before . $old . $after );

		$p->set_outer_content( $new );

		$this->assertSame( $before . $new . $after, $p->get_updated_html(), 'Did not properly swap out outer content.' );
	}

	public function data_set_outer_content() {
		return array(
			'Single tag'  => array( '', '<div start>boring text</div>', 'exciting text', '' ),
			'Nested tags' => array( '<div><ul>', '<li start><p><img>This is <strong>neat</strong></p></li>', '<li>this is<br>not</li>', '<li></li></ul></div>' ),
		);
	}
}
