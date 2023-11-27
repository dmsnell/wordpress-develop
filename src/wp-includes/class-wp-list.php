<?php

/**
 * Singly-linked list implementation for vector-like arrays without
 * the overhead of PHP's associative arrays.
 *
 * This is slower for random access but should be faster for linear
 * scans through the array, and for appending.
 *
 * @template T
 */
class WP_List implements ArrayAccess, Countable, Iterator {
	/**
	 * First item in the list, if it exists, otherwise null.
	 *
	 * @var WP_List_Item<T>|null
	 */
	private $head;

	/**
	 * Last item in the list, if it exists, otherwise null.
	 *
	 * @var WP_List_Item<T>|null
	 */
	private $last;

	/**
	 * How many items are in the list.
	 */
	private $length = 0;

	/**
	 * Used for the Iterator implementation.
	 *
	 * @var int
	 */
	private $current_position = 0;

	/**
	 * Used for the Iterator implemenation.
	 *
	 * @var WP_List_Item<T> Points to the current item in the Iterable implementation.
	 */
	private $current_item;

	/**
	 * Append a new item to the end of the list.
	 *
	 * @param T $data Data to add at the end of the list.
	 * @return int How many items are in the list after appending the given new item.
	 */
	public function append( $data ) {
		$item = new WP_List_Item( $data );

		if ( null === $this->head ) {
			$this->head = $item;
			$this->last = $item;
		} else {
			$this->last->next = $item;
			$this->last       = $item;
		}

		return ++$this->length;
	}

	/**
	 * Generator function to iterate over list items.
	 *
	 * @return Generator
	 */
	public function items() {
		$item = $this->head;
		if ( null === $item ) {
			return null;
		}

		while ( $item ) {
			yield $item->data;
			$item = $item->next;
		}
	}

	/**
	 * Find nth item in list via linear scan.
	 *
	 * @param int $n 0-offset index into list.
	 * @return WP_List_Item<T> the item wrapper at the nth position in the list.
	 */
	private function nth( $n ) {
		$item = $this->head;
		while ( --$n > 0 ) {
			$item = $item->next;
		}

		return $item;
	}

	/*************************************************************
	 * Countable Interface Methods
	 ************************************************************/

	/**
	 * Returns the number of items in the list.
	 *
	 * @return int
	 */
	public function count(): int {
		return $this->length;
	}

	/*************************************************************
	 * ArrayAccess Interface Methods
	 ************************************************************/

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ): bool {
		return is_int( $offset ) && $offset >= 0 && $offset < $this->length;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet( $offset ): mixed {
		if ( ! is_int( $offset ) || $offset < 0 || $offset >= $this->length ) {
			return null;
		}

		return $this->nth( $offset )->data;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ): void {
		// Handles append calls like `$list[] = true;`.
		if ( null === $offset ) {
			$this->append( $value );
			return;
		}

		if ( ! is_int( $offset ) || $offset < 0 || $offset >= $this->length ) {
			return;
		}

		$item = $this->nth( $offset );
		$item->data = $value;

		if ( $offset < $this->current_position ) {
			++$this->current_position;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ): void {
		if ( ! is_int( $offset ) || $offset < 0 || $offset >= $this->length ) {
			return;
		}

		if ( 0 === $offset ) {
			$this->tail = $this->head->next;
			$this->head = $this->head->next;
			--$this->length;
			return;
		}

		$item       = $this->nth( $offset - 1 );
		$removed    = $item->next;
		$item->next = $removed->next;

		if ( $this->last === $removed ) {
			$this->last = $item->next;
		}

		if ( $offset < $this->current_position ) {
			--$this->current_position;
		} else if ( $offset === $this->current_position ) {
			$this->current_item = $this->current_item->next;
		}

		$removed->next = null;
	}

	/*************************************************************
	 * Iterator Interface Methods
	 ************************************************************/

	/**
	 * @inheritDoc
	 */
	public function current(): mixed {
		return $this->current_item ? $this->current_item->data : null;
	}

	/**
	 * @inheritDoc
	 */
	public function next(): void {
		if ( null === $this->current_item ) {
			return;
		}

		$this->current_item = $this->current_item->next;
		++$this->current_position;
	}

	/**
	 * @inheritDoc
	 */
	public function key(): mixed {
		return $this->current_position;
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool {
		return null !== $this->head && $this->current_position < $this->length;
	}

	/**
	 * @inheritDoc
	 */
	public function rewind(): void {
		$this->current_position = 0;
		$this->current_item    = $this->head;
	}
}
