<?php

class WP_HTML_Processor extends WP_HTML_Tag_Processor {
	public $open_elements = array();

	public function next_tag( $query = null ) {
		if ( 0 < count( $this->open_elements ) ) {
			$element = WP_HTML_Spec::element_info( end( $this->open_elements ) );
			if ( $element::IS_VOID  || ( ! $element::IS_HTML && $this->has_self_closing_flag() ) ) {
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
			$this->set_bookmark( '__open_elements_' . count( $this->open_elements ) );
			return true;
		}

		if ( $this->is_tag_closer() ) {
			$this->release_bookmark( '__open_elements_' . count( $this->open_elements ) );
			array_pop( $this->open_elements );
		} else {
			$this->open_elements[] = $tag_name;
			$this->set_bookmark( '__open_elements_' . count( $this->open_elements ) );
		}

		return true;
	}

	public function next_sibling() {
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
		$starting_depth = count( $this->open_elements );

		while ( $this->next_tag() ) {
			$current_depth = count( $this->open_elements );

			if ( ! $this->is_tag_closer() && $current_depth === $starting_depth + 1 ) {
				return true;
			}
		}

		return false;
	}

	public function seek( $bookmark_name ) {
		parent::seek( $bookmark_name );

		foreach ( $this->bookmarks as $name => $mark ) {
			if ( str_starts_with( $name, '__open_elements_' ) && $mark->start > $this->bookmarks[ $bookmark_name ]->start ) {
				$this->release_bookmark( $name );
			}
		}
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
