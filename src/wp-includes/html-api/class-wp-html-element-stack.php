<?php

class WP_HTML_Element_Stack {
	/**
	 * Stack holding HTML tokens, tag openers, tag closers, or plain bookmarks.
	 *
	 * @var WP_HTML_Element_Stack_Item[]
	 */
	public $stack = array();

	/**
	 * Returns a copy of the ith element on the stack for inspection.
	 *
	 * @param int $nth_from_top Which item on the stack to return.
	 * @return WP_HTML_Element_Stack_Item|null
	 */
	public function peek( $nth_from_top ) {
		if ( $nth_from_top < 0 || $nth_from_top >= $this->count() ) {
			return null;
		}

		return $this->stack[ $this->count() - $nth_from_top - 1 ];
	}

	/**
	 * Add an item to the top of the stack.
	 *
	 * @TODO: Do we need to insertion-sort these?
	 *
	 * @param WP_HTML_Element_Stack_Item $stack_item
	 * @return void
	 */
	public function push( $stack_item ) {
		$this->stack[] = $stack_item;
	}

	public function count() {
		return count( $this->stack );
	}

	/**
	 * Returns the bottom-most node on the stack.
	 *
	 * @return WP_HTML_Element_Stack_Item|null
	 */
	public function current_node() {
		$count = $this->count();

		return $this->count() > 0
			? $this->stack[ $count - 1 ]
			: null;
	}

	/**
	 * Returns whether the given element is on the stack.
	 *
	 * @param string $element the ::class name of the element to check for.
	 * @return boolean whether the given element is on the stack.
	 */
	public function has_element( $element ) {
		for ( $i = 0; $i < $this->count(); $i++ ) {
			if ( $this->peek( $i )->element === $element ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether an element is in a specific scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-the-specific-scope
	 *
	 * @param string   $target_node      The target node.
	 * @param string[] $termination_list List of elements that terminate the search.
	 * @return bool
	 */
	public function has_element_in_specific_scope( $target_node, $termination_list ) {
		$i = $this->count();
		if ( $i === 0 ) {
			return false;
		}

		$node = $this->stack[ --$i ];

		if ( $node->element === $target_node ) {
			return true;
		}

		if ( in_array( $target_node, $termination_list, true ) ) {
			return false;
		}

		while ( $i > 0 && null !== ( $node = $this->stack[ --$i ] ) ) {
			if ( $node->element === $target_node ) {
				return true;
			}

			if ( in_array( $target_node, $termination_list, true ) ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Returns whether a given element is in a particular scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-scope
	 *
	 * @param string $element
	 * @return bool
	 */
	public function has_element_in_particular_scope( $element ) {
		return $this->has_element_in_specific_scope( $element, array(
			WP_HTMLAppletElement::class,
			WP_HTMLCaptionElement::class,
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLThElement::class,
			WP_HTMLMarqueeElement::class,
			WP_HTMLObjectElement::class,
			WP_HTMLTemplateElement::class,
			WP_MathML_Mi_Element::class,
			WP_MathML_Mo_Element::class,
			WP_MathML_Mn_Element::class,
			WP_MathML_Ms_Element::class,
			WP_MathML_Mtext_Element::class,
			WP_MathML_Annotation_Xml_Element::class,
			WP_SVG_ForeignObject_Element::class,
			WP_SVG_Description_Element::class,
			WP_SVG_Title_Element::class,
		) );
	}

	/**
	 * Returns whether a given element is in list item scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-list-item-scope
	 *
	 * @param $element
	 * @return void
	 */
	public function has_element_in_list_item_scope( $element ) {
		return $this->has_element_in_specific_scope( $element, array(
			WP_HTMLAppletElement::class,
			WP_HTMLCaptionElement::class,
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLThElement::class,
			WP_HTMLMarqueeElement::class,
			WP_HTMLObjectElement::class,
			WP_HTMLTemplateElement::class,
			WP_MathML_Mi_Element::class,
			WP_MathML_Mo_Element::class,
			WP_MathML_Mn_Element::class,
			WP_MathML_Ms_Element::class,
			WP_MathML_Mtext_Element::class,
			WP_MathML_Annotation_Xml_Element::class,
			WP_SVG_ForeignObject_Element::class,
			WP_SVG_Description_Element::class,
			WP_SVG_Title_Element::class,

			// Additionally these elements.
			WP_HTMLOlElement::class,
			WP_HTMLUlElement::class,
		) );
	}

	/**
	 * Returns whether a given element is in button scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-button-scope
	 *
	 * @param string $element
	 * @return boolean
	 */
	public function has_element_in_button_scope( $element ) {
		return $this->has_element_in_specific_scope( $element, array(
			WP_HTMLAppletElement::class,
			WP_HTMLCaptionElement::class,
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLThElement::class,
			WP_HTMLMarqueeElement::class,
			WP_HTMLObjectElement::class,
			WP_HTMLTemplateElement::class,
			WP_MathML_Mi_Element::class,
			WP_MathML_Mo_Element::class,
			WP_MathML_Mn_Element::class,
			WP_MathML_Ms_Element::class,
			WP_MathML_Mtext_Element::class,
			WP_MathML_Annotation_Xml_Element::class,
			WP_SVG_ForeignObject_Element::class,
			WP_SVG_Description_Element::class,
			WP_SVG_Title_Element::class,

			// Additionally these elements.
			WP_HTMLButtonElement::class,
		) );
	}

	/**
	 * Returns whether the given element is in table scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-table-scope
	 *
	 * @param string $element
	 * @return bool
	 */
	public function has_element_in_table_scope( $element ) {
		return $this->has_element_in_specific_scope( $element, array(
			WP_HTMLAppletElement::class,
			WP_HTMLCaptionElement::class,
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLThElement::class,
			WP_HTMLMarqueeElement::class,
			WP_HTMLObjectElement::class,
			WP_HTMLTemplateElement::class,
			WP_MathML_Mi_Element::class,
			WP_MathML_Mo_Element::class,
			WP_MathML_Mn_Element::class,
			WP_MathML_Ms_Element::class,
			WP_MathML_Mtext_Element::class,
			WP_MathML_Annotation_Xml_Element::class,
			WP_SVG_ForeignObject_Element::class,
			WP_SVG_Description_Element::class,
			WP_SVG_Title_Element::class,

			// Additionally these elements.
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTemplateElement::class,
		) );
	}

	/**
	 * Returns whether a given element is in select scope.
	 *
	 * @see https://html.spec.whatwg.org/#has-an-element-in-select-scope
	 *
	 * @param string $element
	 * @return bool
	 */
	public function has_element_in_select_scope( $element ) {
		return $this->has_element_in_specific_scope( $element, array(
			WP_HTMLAppletElement::class,
			WP_HTMLCaptionElement::class,
			WP_HTMLHtmlElement::class,
			WP_HTMLTableElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLThElement::class,
			WP_HTMLMarqueeElement::class,
			WP_HTMLObjectElement::class,
			WP_HTMLTemplateElement::class,
			WP_MathML_Mi_Element::class,
			WP_MathML_Mo_Element::class,
			WP_MathML_Mn_Element::class,
			WP_MathML_Ms_Element::class,
			WP_MathML_Mtext_Element::class,
			WP_MathML_Annotation_Xml_Element::class,
			WP_SVG_ForeignObject_Element::class,
			WP_SVG_Description_Element::class,
			WP_SVG_Title_Element::class,

			// Additionally these elements.
			WP_HTMLOptgroupElement::class,
			WP_HTMLOptionElement::class,
		) );
	}
}
