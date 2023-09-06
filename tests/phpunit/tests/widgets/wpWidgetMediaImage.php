<?php
/**
 * Unit tests covering WP_Widget_Media_Image functionality.
 *
 * @package    WordPress
 * @subpackage widgets
 */

/**
 * Test wp-includes/widgets/class-wp-widget-media-image.php
 *
 * @group widgets
 */
class Tests_Widgets_wpWidgetMediaImage extends WP_UnitTestCase {

	/**
	 * Clean up global scope.
	 *
	 * @global WP_Scripts $wp_scripts
	 * @global WP_Styles $wp_styles
	 */
	public function clean_up_global_scope() {
		global $wp_scripts, $wp_styles;
		parent::clean_up_global_scope();
		$wp_scripts = null;
		$wp_styles  = null;
	}

	/**
	 * Test get_instance_schema method.
	 *
	 * @covers WP_Widget_Media_Image::get_instance_schema
	 */
	public function test_get_instance_schema() {
		$widget = new WP_Widget_Media_Image();
		$schema = $widget->get_instance_schema();

		$this->assertSameSets(
			array(
				'alt',
				'attachment_id',
				'caption',
				'height',
				'image_classes',
				'image_title',
				'link_classes',
				'link_rel',
				'link_target_blank',
				'link_type',
				'link_url',
				'size',
				'title',
				'url',
				'width',
			),
			array_keys( $schema )
		);
	}

	/**
	 * Test schema filtering.
	 *
	 * @covers WP_Widget_Media_Image::get_instance_schema
	 *
	 * @ticket 45029
	 */
	public function test_get_instance_schema_filtering() {
		$widget = new WP_Widget_Media_Image();
		$schema = $widget->get_instance_schema();

		add_filter( 'widget_media_image_instance_schema', array( $this, 'filter_instance_schema' ), 10, 2 );
		$schema = $widget->get_instance_schema();

		$this->assertSame( 'large', $schema['size']['default'] );
	}

	/**
	 * Filters instance schema.
	 *
	 * @since 5.2.0
	 *
	 * @param array                 $schema Schema.
	 * @param WP_Widget_Media_Image $widget Widget.
	 * @return array
	 */
	public function filter_instance_schema( $schema, $widget ) {
		// Override the default size value ('medium').
		$schema['size']['default'] = 'large';
		return $schema;
	}

	/**
	 * Test constructor.
	 *
	 * @covers WP_Widget_Media_Image::__construct
	 */
	public function test_constructor() {
		$widget = new WP_Widget_Media_Image();

		$this->assertArrayHasKey( 'mime_type', $widget->widget_options );
		$this->assertArrayHasKey( 'customize_selective_refresh', $widget->widget_options );
		$this->assertArrayHasKey( 'description', $widget->widget_options );
		$this->assertTrue( $widget->widget_options['customize_selective_refresh'] );
		$this->assertSame( 'image', $widget->widget_options['mime_type'] );
		$this->assertSameSets(
			array(
				'add_to_widget',
				'replace_media',
				'edit_media',
				'media_library_state_multi',
				'media_library_state_single',
				'missing_attachment',
				'no_media_selected',
				'add_media',
				'unsupported_file_type',
			),
			array_keys( $widget->l10n )
		);
	}

	/**
	 * Test get_instance_schema method.
	 *
	 * @covers WP_Widget_Media_Image::update
	 */
	public function test_update() {
		$widget   = new WP_Widget_Media_Image();
		$instance = array();

		// Should return valid attachment ID.
		$expected = array(
			'attachment_id' => 1,
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid attachment ID.
		$result = $widget->update(
			array(
				'attachment_id' => 'media',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid attachment url.
		$expected = array(
			'url' => 'https://example.org',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid attachment url.
		$result = $widget->update(
			array(
				'url' => 'not_a_url',
			),
			$instance
		);
		$this->assertNotSame( $result, $instance );
		$this->assertStringStartsWith( 'http://', $result['url'] );

		// Should return valid attachment title.
		$expected = array(
			'title' => 'What a title',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid attachment title.
		$result = $widget->update(
			array(
				'title' => '<h1>W00t!</h1>',
			),
			$instance
		);
		$this->assertNotSame( $result, $instance );

		// Should return valid image size.
		$expected = array(
			'size' => 'thumbnail',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image size.
		$result = $widget->update(
			array(
				'size' => 'big league',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid image width.
		$expected = array(
			'width' => 300,
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image width.
		$result = $widget->update(
			array(
				'width' => 'wide',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid image height.
		$expected = array(
			'height' => 200,
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image height.
		$result = $widget->update(
			array(
				'height' => 'high',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid image caption.
		$expected = array(
			'caption' => 'A caption with <a href="#">link</a>',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image caption.
		$result = $widget->update(
			array(
				'caption' => '"><i onload="alert(\'hello\')" />',
			),
			$instance
		);
		$this->assertSame(
			$result,
			array(
				'caption' => '"&gt;<i />',
			)
		);

		// Should return valid alt text.
		$expected = array(
			'alt' => 'A water tower',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid alt text.
		$result = $widget->update(
			array(
				'alt' => '"><i onload="alert(\'hello\')" />',
			),
			$instance
		);
		$this->assertSame(
			$result,
			array(
				'alt' => '">',
			)
		);

		// Should return valid link type.
		$expected = array(
			'link_type' => 'file',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid link type.
		$result = $widget->update(
			array(
				'link_type' => 'interesting',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid link url.
		$expected = array(
			'link_url' => 'https://example.org',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid link url.
		$result = $widget->update(
			array(
				'link_url' => 'not_a_url',
			),
			$instance
		);
		$this->assertNotSame( $result, $instance );
		$this->assertStringStartsWith( 'http://', $result['link_url'] );

		// Should return valid image classes.
		$expected = array(
			'image_classes' => 'A water tower',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image classes.
		$result = $widget->update(
			array(
				'image_classes' => '"><i onload="alert(\'hello\')" />',
			),
			$instance
		);
		$this->assertSame(
			$result,
			array(
				'image_classes' => 'i onloadalerthello',
			)
		);

		// Should return valid link classes.
		$expected = array(
			'link_classes' => 'A water tower',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid link classes.
		$result = $widget->update(
			array(
				'link_classes' => '"><i onload="alert(\'hello\')" />',
			),
			$instance
		);
		$this->assertSame(
			$result,
			array(
				'link_classes' => 'i onloadalerthello',
			)
		);

		// Should return valid rel text.
		$expected = array(
			'link_rel' => 'previous',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid rel text.
		$result = $widget->update(
			array(
				'link_rel' => '"><i onload="alert(\'hello\')" />',
			),
			$instance
		);
		$this->assertSame(
			$result,
			array(
				'link_rel' => 'i onloadalerthello',
			)
		);

		// Should return valid link target.
		$expected = array(
			'link_target_blank' => false,
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid  link target.
		$result = $widget->update(
			array(
				'link_target_blank' => 'top',
			),
			$instance
		);
		$this->assertSame( $result, $instance );

		// Should return valid image title.
		$expected = array(
			'image_title' => 'What a title',
		);
		$result   = $widget->update( $expected, $instance );
		$this->assertSame( $expected, $result );

		// Should filter invalid image title.
		$result = $widget->update(
			array(
				'image_title' => '<h1>W00t!</h1>',
			),
			$instance
		);
		$this->assertNotSame( $result, $instance );

		// Should filter invalid key.
		$result = $widget->update(
			array(
				'imaginary_key' => 'value',
			),
			$instance
		);
		$this->assertSame( $result, $instance );
	}

	/**
	 * Test render_media method.
	 *
	 * @covers WP_Widget_Media_Image::render_media
	 * @requires function imagejpeg
	 */
	public function test_render_media() {
		$widget = new WP_Widget_Media_Image();

		$test_image = get_temp_dir() . 'canola.jpg';
		copy( DIR_TESTDATA . '/images/canola.jpg', $test_image );
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => $test_image,
				'post_parent'    => 0,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Canola',
			)
		);
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $test_image ) );

		// Should be empty when there is no attachment_id.
		ob_start();
		$widget->render_media( array() );
		$output = ob_get_clean();
		$this->assertEmpty( $output );

		// Should be empty when there is an invalid attachment_id.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => 666,
			)
		);
		$output = ob_get_clean();
		$this->assertEmpty( $output );

		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		// No default title.
		$this->assertTrue( $p->next_tag( 'a' ), 'Failed to find wrapping A tag.' );
		$this->assertNull( $p->get_attribute( 'title' ), "Should not have returned title but found: {$p->get_attribute( 'title' )}." );

		// Default image classes.
		$this->assertTrue( $p->next_tag( 'img' ), 'Failed to find IMG tag.' );
		$expected_classes = array( 'image', "wp-image-{$attachment_id}" );
		$this->assertSame(
			$expected_classes,
			array_intersect( $expected_classes, preg_split( '~[ \t\f\r\n]~', $p->get_attribute( 'class' ), -1, PREG_SPLIT_NO_EMPTY ) ),
			"Expected 'image' and 'wp-image-{$attachment_id}' classes but instead found: {$p->get_attribute( 'class' )}"
		);

		$this->assertEquals( 'max-width: 100%; height: auto;', $p->get_attribute( 'style' ), "Found unexpected style values." );
		$this->assertEquals( '', $p->get_attribute( 'alt' ), 'Expected to find empty alt attribute.' );

		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
				'image_title'   => 'Custom Title',
				'image_classes' => 'custom-class',
				'alt'           => 'A flower',
				'size'          => 'custom',
				'width'         => 100,
				'height'        => 100,
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		// Custom image title.
		$this->assertTrue( $p->next_tag( 'a' ), 'Failed to find wrapping A tag.' );
		$this->assertEquals( 'Custom Title', $p->get_attribute( 'title' ), 'Did not return proper title attribute.' );

		// Custom image class.
		$this->assertTrue( $p->next_tag( 'img' ), 'Failed to find IMG tag.' );
		$expected_classes = array( 'image', "wp-image-{$attachment_id}", 'custom-class' );
		$this->assertSame(
			$expected_classes,
			array_intersect( $expected_classes, preg_split( '~[ \t\f\r\n]~', $p->get_attribute( 'class' ), -1, PREG_SPLIT_NO_EMPTY ) ),
			"Expected 'image', 'wp-image-{$attachment_id}', and 'custom-class' classes but instead found: {$p->get_attribute( 'class' )}"
		);

		$this->assertEquals( '100', $p->get_attribute( 'height' ), 'Returned wrong height attribute value.' );
		$this->assertEquals( '100', $p->get_attribute( 'width' ), 'Returned wrong width attribute value.' );
		$this->assertEquals( 'A flower', $p->get_attribute( 'alt' ), 'Returned wrong alt attribute value.' );

		// Embeded images.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => null,
				'caption'       => 'With caption',
				'height'        => 100,
				'link_type'     => 'file',
				'url'           => 'http://example.org/url/to/image.jpg',
				'width'         => 100,
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		// Custom image class.
		$this->assertTrue( $p->next_tag( 'img' ), 'Failed to find IMG tag.' );
		$this->assertEquals( 'http://example.org/url/to/image.jpg', $p->get_attribute( 'src' ), 'Returned wrong src attribute value.' );
		$this->assertEquals( 'async', $p->get_attribute( 'decoding' ), 'Returned wrong decoding attribute value.' );
		$this->assertEquals( 'lazy', $p->get_attribute( 'loading' ), 'Returned wrong loading attribute value.' );

		// Link settings.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
				'link_type'     => 'file',
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		$this->assertTrue( $p->next_tag( 'a' ), 'Failed to find A tag.' );
		$this->assertEquals( wp_get_attachment_url( $attachment_id ), $p->get_attribute( 'href' ), 'Returned wrong href attribute value.' );
		$this->assertNull( $p->get_attribute( 'class' ), "Should not have found class attribute but instead found: {$p->get_attribute( 'class' )}." );
		$this->assertNull( $p->get_attribute( 'rel' ), "Should not have found rel attribute but instead found: {$p->get_attribute( 'rel' )}." );
		$this->assertNull( $p->get_attribute( 'target' ), "Should not have found target attribute but instead found: {$p->get_attribute( 'target' )}." );

		ob_start();
		$widget->render_media(
			array(
				'attachment_id'     => $attachment_id,
				'link_type'         => 'post',
				'link_classes'      => 'custom-link-class',
				'link_rel'          => 'attachment',
				'link_target_blank' => false,
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		$this->assertTrue( $p->next_tag( 'a' ), 'Failed to find A tag.' );
		$this->assertEquals( wp_get_attachment_link( $attachment_id ), $p->get_attribute( 'href' ), 'Returned wrong href attribute value.' );
		$this->assertEquals( 'custom-link-class', $p->get_attribute( 'class' ), 'Returned wrong class attribute value.' );
		$this->assertEquals( 'attachment', $p->get_attribute( 'rel' ), 'Returned wrong rel attribute value.' );
		$this->assertNull( $p->get_attribute( 'target' ), "Should not have found target attribute but instead found: {$p->get_attribute( 'target' )}." );

		ob_start();
		$widget->render_media(
			array(
				'attachment_id'     => $attachment_id,
				'link_type'         => 'custom',
				'link_url'          => 'https://example.org',
				'link_target_blank' => true,
			)
		);
		$output = ob_get_clean();

		$p = new WP_HTML_Tag_Processor( $output );

		$this->assertTrue( $p->next_tag( 'a' ), 'Failed to find A tag.' );
		$this->assertEquals( 'https://example.org', $p->get_attribute( 'href' ), 'Returned wrong href attribute value.' );
		$this->assertEquals( 'noopener', $p->get_attribute( 'rel' ), 'Returned wrong rel attribute value.' );
		$this->assertEquals( '_blank', $p->get_attribute( 'target' ), 'Returned wrong target attribute value.' );

		// Populate caption in attachment.
		wp_update_post(
			array(
				'ID'           => $attachment_id,
				'post_excerpt' => 'Default caption',
			)
		);

		// If no caption is supplied, then the default is '', and so the caption will not be displayed.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
			)
		);
		$output = ob_get_clean();
		// @TODO: Update this test once $p->has_class() is available.
		$this->assertStringNotContainsString( 'wp-caption', $output );
		$this->assertStringNotContainsString( '<p class="wp-caption-text">', $output );

		// If the caption is explicitly null, then the caption of the underlying attachment will be displayed.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
				'caption'       => null,
			)
		);
		$output = ob_get_clean();
		// @TODO: Update this test once $p->has_class() is available.
		$this->assertStringContainsString( 'class="wp-caption alignnone"', $output );
		$this->assertStringContainsString( '<p class="wp-caption-text">Default caption</p>', $output );

		// If caption is provided, then it will be displayed.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
				'caption'       => 'Custom caption',
			)
		);
		$output = ob_get_clean();
		// @TODO: Update this test once $p->has_class() is available.
		$this->assertStringContainsString( 'class="wp-caption alignnone"', $output );
		$this->assertStringContainsString( '<p class="wp-caption-text">Custom caption</p>', $output );

		// Attachments with custom sizes can render captions.
		ob_start();
		$widget->render_media(
			array(
				'attachment_id' => $attachment_id,
				'size'          => 'custom',
				'width'         => '300',
				'height'        => '200',
				'caption'       => 'Caption for an image with custom size',
			)
		);
		$output = ob_get_clean();
		// @TODO: Update this test once $p->has_class() is available.
		$this->assertStringContainsString( 'style="width: 310px"', $output );
		$this->assertStringContainsString( '<p class="wp-caption-text">Caption for an image with custom size</p>', $output );
	}

	/**
	 * Test enqueue_admin_scripts method.
	 *
	 * @covers WP_Widget_Media_Image::enqueue_admin_scripts
	 */
	public function test_enqueue_admin_scripts() {
		set_current_screen( 'widgets.php' );
		$widget = new WP_Widget_Media_Image();
		$widget->enqueue_admin_scripts();

		$this->assertTrue( wp_script_is( 'media-image-widget' ) );
	}

	/**
	 * Test render_control_template_scripts method.
	 *
	 * @covers WP_Widget_Media_Image::render_control_template_scripts
	 */
	public function test_render_control_template_scripts() {
		$widget = new WP_Widget_Media_Image();

		ob_start();
		$widget->render_control_template_scripts();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script type="text/html" id="tmpl-wp-media-widget-image-preview">', $output );
	}
}
