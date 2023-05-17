<?php

class WP_HTML_Element_Stack_Item {
	const IS_CLOSER = 1 << 0;
	const HAS_SELF_CLOSING_FLAG = 1 << 1;

	/**
	 * Stores the name of the bookmark pointing to the element at the position of the item.
	 *
	 * @var string|null
	 */
	public $bookmark_name = null;

	/**
	 * Stores the element class name for the element at the position of the item.
	 *
	 * This is the name of the PHP class representing the element.
	 * For example, `WP_HTMLDivElement` from calling `WP_HTMLDivElement::class`.
	 *
	 * @var string|null
	 */
	public $element = null;

	/**
	 * Properties about this item in the stack that are relevant for relating opening and closing tags.
	 *
	 * @var int
	 */
	public $flags = 0;

	/**
	 * Pointer to related item on the stack, if one exists.
	 * For example, a tag opener that opens the current tag closer.
	 *
	 * @var WP_HTML_Element_Stack_Item|null
	 */
	public $related_item = null;

	public function __construct( $bookmark_name, $element, $flags, $related_item = null ) {
		$this->bookmark_name = $bookmark_name;
		$this->element       = $element;
		$this->flags         = $flags;
		$this->related_item  = $related_item;
	}
}
