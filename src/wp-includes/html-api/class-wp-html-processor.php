<?php

class WP_HTML_Processor extends WP_HTML_Tag_Processor {
	public $fully_supported_input = null;
	public $open_elements = array();

	public function ensure_support() {
		if ( null !== $this->fully_supported_input ) {
			return $this->fully_supported_input;
		}

		$stack = array();

		$p = new WP_HTML_Tag_Processor( $this->html );
		while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag_name = $p->get_tag();

			if ( ! $p->is_tag_closer() ) {
				$element = WP_HTML_Spec::element_info( $tag_name );

				$self_closes = $element::is_void || ( ! $element::is_html && $p->has_self_closing_flag() );
				if ( ! $self_closes ) {
					$stack[] = $tag_name;
				}
			} else {
				if ( end( $stack ) === $tag_name ) {
					array_pop( $stack );
					continue;
				}

				$this->fully_supported_input = false;
				return false;
			}
		}

		$this->fully_supported_input = 0 === count( $stack );

		return $this->fully_supported_input;
	}

	public function next_tag( $query = null ) {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		if ( false === parent::next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			return false;
		}

		$tag_name = $this->get_tag();
		$element = WP_HTML_Spec::element_info( $tag_name );

		$self_closes = $element::is_void || ( ! $element::is_html && $this->has_self_closing_flag() );
		if ( $self_closes ) {
			return true;
		}

		if ( $this->is_tag_closer() ) {
			array_pop( $this->open_elements );
		} else {
			$this->open_elements[] = $tag_name;
		}

		return true;
	}

	public function next_sibling() {
		if ( $this->fully_supported_input ) {
			return false;
		}

		$starting_depth = count( $this->open_elements );

		/*
		 * If we aren't already inside a tag then advance to the first one.
		 * If that tag is self-closing then we're done. Otherwise, open the
		 * stack with that tag name and prepare to close out the stack.
		 */
		if ( ! $this->get_tag() ) {
			if ( ! $this->next_tag() ) {
				return false;
			}

			if ( $starting_depth === count( $this->open_elements ) ) {
				return true;
			}
		}

		while ( $this->next_tag() ) {
			if ( $starting_depth === count( $this->open_elements ) ) {
				return true;
			}
		}

		return false;
	}
}
