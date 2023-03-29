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

				$self_closes = $element::IS_VOID || ( ! $element::IS_HTML && $p->has_self_closing_flag() );
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

		if ( 0 < count( $this->open_elements ) ) {
			$element = WP_HTML_Spec::element_info( end( $this->open_elements ) );
			// @TODO: Handle self-closing HTML foreign elements: must convey self-closing flag on stack.
			if ( $element::IS_VOID ) {
				array_pop( $this->open_elements );
			}
		}

		if ( false === parent::next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			return false;
		}

		$tag_name = $this->get_tag();
		$element = WP_HTML_Spec::element_info( $tag_name );

		$self_closes = $element::IS_VOID || ( ! $element::IS_HTML && $this->has_self_closing_flag() );
		if ( $self_closes ) {
			$this->open_elements[] = $tag_name;
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
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		$starting_depth = count( $this->open_elements );

		while ( $this->next_tag() ) {
			$current_depth = count( $this->open_elements );

			if ( ! $this->is_tag_closer() && $current_depth === $starting_depth ) {
				return true;
			}

			if ( ! $this->is_tag_closer() && $current_depth < $starting_depth ) {
				return false;
			}
		}

		return false;
	}

	public function first_child() {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		$starting_depth = count( $this->open_elements );

		while ( $this->next_tag() ) {
			$current_depth = count( $this->open_elements );

			if ( ! $this->is_tag_closer() && $current_depth === $starting_depth + 1 ) {
				return true;
			}
		}

		return false;
	}

	private function find_closing_tag() {
		$starting_depth = count( $this->open_elements );

		while ( $this->next_tag() ) {
			$current_depth = count( $this->open_elements );

			if ( $this->is_tag_closer() && $current_depth < $starting_depth ) {
				return true;
			}
		}

		return false;
	}

	public function get_inner_content() {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		if ( ! $this->get_tag() || $this->is_tag_closer() ) {
			return false;
		}

		$element = WP_HTML_Spec::element_info( $this->get_tag() );
		if ( $element::IS_VOID || ( ! $element::IS_HTML && $this->has_self_closing_flag() ) ) {
			return false;
		}

		// @TODO: Unique bookmark names
		$this->set_bookmark( 'start' );
		if ( ! $this->find_closing_tag() ) {
			return false;
		}
		$this->set_bookmark( 'end' );

		$start = $this->bookmarks['start']->end + 1;
		$end = $this->bookmarks['end']->start - 1;
		$inner_content = substr( $this->html, $start, $end - $start + 1 );

		$this->release_bookmark( 'start' );
		$this->release_bookmark( 'end' );

		return $inner_content;
	}

	public function set_inner_content( $new_html ) {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		if ( ! $this->get_tag() || $this->is_tag_closer() ) {
			return false;
		}

		$element = WP_HTML_Spec::element_info( $this->get_tag() );
		if ( $element::IS_VOID || ( ! $element::IS_HTML && $this->has_self_closing_flag() ) ) {
			return false;
		}

		// @TODO: Unique bookmark names
		$this->set_bookmark( 'start' );
		if ( ! $this->find_closing_tag() ) {
			return false;
		}
		$this->set_bookmark( 'end' );

		$start = $this->bookmarks['start']->end + 1;
		$end = $this->bookmarks['end']->start;
		$this->lexical_updates[] = new WP_HTML_Text_Replacement( $start, $end, $new_html );
		$this->get_updated_html();
		$this->seek( 'start' );

		$this->release_bookmark( 'start' );
		$this->release_bookmark( 'end' );
	}

	public function get_outer_content() {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		if ( ! $this->get_tag() || $this->is_tag_closer() ) {
			return false;
		}

		$element = WP_HTML_Spec::element_info( $this->get_tag() );
		if ( $element::IS_VOID || ( ! $element::IS_HTML && $this->has_self_closing_flag() ) ) {
			$this->set_bookmark( 'start' );
			$here = $this->bookmarks['start'];
			return substr( $this->html, $here->start, $here->end - $here->start + 1 );
		}

		// @TODO: Unique bookmark names
		$this->set_bookmark( 'start' );
		if ( ! $this->find_closing_tag() ) {
			return false;
		}
		$this->set_bookmark( 'end' );

		$start = $this->bookmarks['start']->start;
		$end = $this->bookmarks['end']->end;
		$inner_content = substr( $this->html, $start, $end - $start + 1 );
		$this->seek( 'start' );

		$this->release_bookmark( 'start' );
		$this->release_bookmark( 'end' );

		return $inner_content;
	}

	public function set_outer_content( $new_html ) {
		if ( false === $this->fully_supported_input || false === $this->ensure_support() ) {
			return false;
		}

		if ( ! $this->get_tag() || $this->is_tag_closer() ) {
			return false;
		}

		$element = WP_HTML_Spec::element_info( $this->get_tag() );
		// @TODO: Replace void and self-closing tags.
		if ( $element::IS_VOID || ( ! $element::IS_HTML && $this->has_self_closing_flag() ) ) {
			return false;
		}

		// @TODO: Unique bookmark names
		$this->set_bookmark( 'start' );
		if ( ! $this->find_closing_tag() ) {
			return false;
		}
		$this->set_bookmark( 'end' );

		$start = $this->bookmarks['start']->start;
		$end = $this->bookmarks['end']->end + 1;
		$this->lexical_updates[] = new WP_HTML_Text_Replacement( $start, $end, $new_html );
		$this->get_updated_html();
		$this->bookmarks['start']->start = $start;
		$this->bookmarks['start']->end = $start;
		$this->seek( 'start' );

		$this->release_bookmark( 'start' );
		$this->release_bookmark( 'end' );
	}
}
