<?php
/**
 * Server-side rendering of the `core/tag-cloud` block.
 *
 * @package WordPress
 */

/**
 * Renders the `core/tag-cloud` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the tag cloud for selected taxonomy.
 */
function render_block_core_tag_cloud( $attributes ) {
	$smallest_font_size = $attributes['smallestFontSize'];
	$unit               = ( preg_match( '/^[0-9.]+(?P<unit>[a-z%]+)$/i', $smallest_font_size, $m ) ? $m['unit'] : 'pt' );

	$args      = array(
		'echo'       => false,
		'unit'       => $unit,
		'taxonomy'   => $attributes['taxonomy'],
		'show_count' => $attributes['showTagCounts'],
		'number'     => $attributes['numberOfTags'],
		'smallest'   => floatVal( $attributes['smallestFontSize'] ),
		'largest'    => floatVal( $attributes['largestFontSize'] ),
	);
	$tag_cloud = wp_tag_cloud( $args );

	if ( ! $tag_cloud ) {
		$tag_cloud = __( 'There&#8217;s no content to show here yet.' );
	}

	$wrapper_attributes = get_block_wrapper_attributes();

	return sprintf(
		'<p %1$s>%2$s</p>',
		$wrapper_attributes,
		$tag_cloud
	);
}
