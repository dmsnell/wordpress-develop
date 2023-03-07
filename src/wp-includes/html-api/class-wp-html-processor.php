<?php

class WP_HTML_Processor extends WP_HTML_Tag_Processor {
	public $fully_supported_input = null;

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

		return parent::next_tag( $query );
	}

	public function proceed_to_end_of_next_tag() {
		if ( $this->fully_supported_input ) {
			return false;
		}

		$open_elements = array();

		while ( $this->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag_name = $this->get_tag();
			$element = WP_HTML_Spec::element_info( $tag_name );

			if ( $element::is_void ) {
				return true;
			}

			if ( $this->is_tag_closer() ) {
				if ( 0 === count( $open_elements ) || $open_elements[ count( $open_elements ) - 1 ] !== $tag_name ) {
					$this->fully_supported_input = true;
					return false;
				}

				array_pop( $open_elements );
				if ( 0 === count( $open_elements ) ) {
					return true;
				}
			}
		}
	}
}
