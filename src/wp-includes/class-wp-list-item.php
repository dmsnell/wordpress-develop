<?php

/**
 * Item inside WP_List singly-linked list.
 *
 * @template T of mixed Contains the data inside a WP_List.
 */
class WP_List_Item {
	/**
	 * Stores the data contained in this item.
	 *
	 * @var T
	 */
	public $data;

	/**
	 * Points to the next item in the list, if any.
	 *
	 * @var WP_List_Item<T>|null
	 */
	public $next;

	/**
	 * Constructor function.
	 *
	 * @param T $data item stored in item.
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}
}
