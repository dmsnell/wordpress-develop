<?php
/**
 * HTML API: WP_HTML_Active_Formatting_Elements class
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since 6.4.0
 */

/**
 * Core class used by the HTML processor during HTML parsing
 * for managing the stack of active formatting elements.
 *
 * This class is designed for internal use by the HTML processor.
 *
 * > Initially, the list of active formatting elements is empty.
 * > It is used to handle mis-nested formatting element tags.
 * >
 * > The list contains elements in the formatting category, and markers.
 * > The markers are inserted when entering applet, object, marquee,
 * > template, td, th, and caption elements, and are used to prevent
 * > formatting from "leaking" into applet, object, marquee, template,
 * > td, th, and caption elements.
 * >
 * > In addition, each element in the list of active formatting elements
 * > is associated with the token for which it was created, so that
 * > further elements can be created for that token if necessary.
 *
 * @since 6.4.0
 *
 * @access private
 *
 * @see https://html.spec.whatwg.org/#list-of-active-formatting-elements
 * @see WP_HTML_Processor
 */
class WP_HTML_Active_Formatting_Elements {
	/**
	 * Holds the stack of active formatting element references.
	 *
	 * @since 6.4.0
	 *
	 * @var WP_HTML_Token[]
	 */
	private $stack = array();

	/**
	 * Holds a stack of hashes representing uniquely representing the active formatting element.
	 *
	 * This is important to efficiently track and remove duplicate elements when pushing.
	 *
	 * @since 7.0.0
	 *
	 * @var string[]
	 */
	private $hash_stack = array();

	/**
	 * Returns the node at the given 1-offset index in the list of active formatting elements.
	 *
	 * @since 7.0.0
	 *
	 * @param int $index Number of nodes from the top node to return.
	 * @return WP_HTML_Token|null Node at the given index in the stack, if one exists, otherwise null.
	 */
	public function at( $nth ) {
		return $this->stack[ $nth - 1 ];
	}

	/**
	 * Reports if a specific node is in the stack of active formatting elements.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $token Look for this node in the stack.
	 * @return bool Whether the referenced node is in the stack of active formatting elements.
	 */
	public function contains_node( WP_HTML_Token $token ) {
		foreach ( $this->walk_up() as $item ) {
			if ( $token->bookmark_name === $item->bookmark_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns how many nodes are currently in the stack of active formatting elements.
	 *
	 * @since 6.4.0
	 *
	 * @return int How many node are in the stack of active formatting elements.
	 */
	public function count() {
		return count( $this->stack );
	}

	/**
	 * Returns the node at the end of the stack of active formatting elements,
	 * if one exists. If the stack is empty, returns null.
	 *
	 * @since 6.4.0
	 *
	 * @return WP_HTML_Token|null Last node in the stack of active formatting elements, if one exists, otherwise null.
	 */
	public function current_node() {
		$current_node = end( $this->stack );

		return $current_node ? $current_node : null;
	}

	/**
	 * Inserts a "marker" at the end of the list of active formatting elements.
	 *
	 * > The markers are inserted when entering applet, object, marquee,
	 * > template, td, th, and caption elements, and are used to prevent
	 * > formatting from "leaking" into applet, object, marquee, template,
	 * > td, th, and caption elements.
	 *
	 * @see https://html.spec.whatwg.org/#concept-parser-marker
	 *
	 * @since 6.7.0
	 */
	public function insert_marker(): void {
		$this->stack[]      = new WP_HTML_Token( null, 'marker', false );
		$this->hash_stack[] = 'marker';
	}

	/**
	 * Generates a hash string for a given token, based on its
	 * tag name, namespace, and attributes.
	 *
	 * @since 7.0.0
	 *
	 * @param WP_HTML_Token $token      Token to generate a hash for.
	 * @param string        $token_html The original HTML of the token.
	 * @return string Generated hash string.
	 */
	private function get_token_hash( WP_HTML_Token $token, string $token_html ): string {
		$processor   = new WP_HTML_Tag_Processor( $token_html );
		$processor->change_parsing_namespace( $token->namespace );
		$processor->next_tag();

		$node_name   = $processor->get_qualified_tag_name();
		$hash_string = "{$token->namespace}::<{$node_name}";

		$attribute_names = $processor->get_attribute_names_with_prefix( '' );
		if ( ! empty( $attribute_names ) ) {
			$attr_parts = [];
			sort( $attribute_names, SORT_STRING );
			foreach ( $attribute_names as $attribute_name ) {
				$display_name = $processor->get_qualified_attribute_name( $attribute_name );
				$val = $processor->get_attribute( $attribute_name );

				/*
				 * Attributes with no value are `true` with the HTML API,
				 * We map use the empty string value in the tree structure.
				 */
				if ( true === $val ) {
					$val = '';
				}
				$val = strtr( $val, '"', '&quot;' );

				$attr_parts[] = "{$display_name}=\"{$val}\"";
			}
			$hash_string .= ' ' . implode( ' ', $attr_parts );
		}
		$hash_string .= '>';

		return dechex( crc32( $hash_string ) );
	}

	/**
	 * Pushes a node onto the stack of active formatting elements.
	 *
	 * @since 6.4.0
	 *
	 * @see https://html.spec.whatwg.org/#push-onto-the-list-of-active-formatting-elements
	 *
	 * @param WP_HTML_Token $token Push this node onto the stack.
	 * @return bool Whether a node was pushed onto the stack of active formatting elements.
	 */
	public function push( WP_HTML_Token $token, string $token_html ): bool {
		/*
		 * > If there are already three elements in the list of active formatting elements after the last marker,
		 * > if any, or anywhere in the list if there are no markers, that have the same tag name, namespace, and
		 * > attributes as element, then remove the earliest such element from the list of active formatting
		 * > elements. For these purposes, the attributes must be compared as they were when the elements were
		 * > created by the parser; two elements have the same attributes if all their parsed attributes can be
		 * > paired such that the two attributes in each pair have identical names, namespaces, and values
		 * > (the order of the attributes does not matter).
		 */

		if ( 'marker' === $token->node_name ) {
			_doing_it_wrong(
				__METHOD__,
				'Markers must be added using the WP_HTML_Active_Formatting_Elements::insert_marker() method.',
				'7.0.0'
			);
			return false;
		}

		$token_hash     = $this->get_token_hash( $token, $token_html );
		$existing_count = 0;
		for ( $i = count( $this->hash_stack ) - 1; $i >= 0; $i-- ) {
			$item_hash = $this->hash_stack[ $i ];

			if ( 'marker' === $item_hash ) {
				break;
			}

			if ( $item_hash === $token_hash ) {
				if ( ++$existing_count >= 3 ) {
					$this->remove_node( $this->stack[ $i ] );
					break;
				}
			}
		}

		// > Add element to the list of active formatting elements.
		$this->stack[]      = $token;
		$this->hash_stack[] = $token_hash;
		return true;
	}

	/**
	 * Removes a node from the stack of active formatting elements.
	 *
	 * @since 6.4.0
	 *
	 * @param WP_HTML_Token $token Remove this node from the stack, if it's there already.
	 * @return bool Whether the node was found and removed from the stack of active formatting elements.
	 */
	public function remove_node( WP_HTML_Token $token ) {
		foreach ( $this->walk_up() as $position_from_end => $item ) {
			if ( $token->bookmark_name !== $item->bookmark_name ) {
				continue;
			}

			$position_from_start = $this->count() - $position_from_end - 1;
			array_splice( $this->stack, $position_from_start, 1 );
			array_splice( $this->hash_stack, $position_from_start, 1 );
			return true;
		}

		return false;
	}

	/**
	 * Steps through the stack of active formatting elements, starting with the
	 * top element (added first) and walking downwards to the one added last.
	 *
	 * This generator function is designed to be used inside a "foreach" loop.
	 *
	 * Example:
	 *
	 *     $html = '<em><strong><a>We are here';
	 *     foreach ( $stack->walk_down() as $node ) {
	 *         echo "{$node->node_name} -> ";
	 *     }
	 *     > EM -> STRONG -> A ->
	 *
	 * To start with the most-recently added element and walk towards the top,
	 * see WP_HTML_Active_Formatting_Elements::walk_up().
	 *
	 * @since 6.4.0
	 */
	public function walk_down() {
		$count = count( $this->stack );

		for ( $i = 0; $i < $count; $i++ ) {
			yield $this->stack[ $i ];
		}
	}

	/**
	 * Steps through the stack of active formatting elements, starting with the
	 * bottom element (added last) and walking upwards to the one added first.
	 *
	 * This generator function is designed to be used inside a "foreach" loop.
	 *
	 * Example:
	 *
	 *     $html = '<em><strong><a>We are here';
	 *     foreach ( $stack->walk_up() as $node ) {
	 *         echo "{$node->node_name} -> ";
	 *     }
	 *     > A -> STRONG -> EM ->
	 *
	 * To start with the first added element and walk towards the bottom,
	 * see WP_HTML_Active_Formatting_Elements::walk_down().
	 *
	 * @since 6.4.0
	 */
	public function walk_up() {
		for ( $i = count( $this->stack ) - 1; $i >= 0; $i-- ) {
			yield $this->stack[ $i ];
		}
	}

	/**
	 * Clears the list of active formatting elements up to the last marker.
	 *
	 * > When the steps below require the UA to clear the list of active formatting elements up to
	 * > the last marker, the UA must perform the following steps:
	 * >
	 * > 1. Let entry be the last (most recently added) entry in the list of active
	 * >    formatting elements.
	 * > 2. Remove entry from the list of active formatting elements.
	 * > 3. If entry was a marker, then stop the algorithm at this point.
	 * >    The list has been cleared up to the last marker.
	 * > 4. Go to step 1.
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#clear-the-list-of-active-formatting-elements-up-to-the-last-marker
	 *
	 * @since 6.7.0
	 */
	public function clear_up_to_last_marker(): void {
		foreach ( $this->walk_up() as $item ) {
			array_pop( $this->stack );
			array_pop( $this->hash_stack );
			if ( 'marker' === $item->node_name ) {
				break;
			}
		}
	}
}
