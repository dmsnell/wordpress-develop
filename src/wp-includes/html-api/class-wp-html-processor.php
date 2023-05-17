<?php

require_once __DIR__ . '/class-wp-html-element-stack.php';

class WP_HTML_Processor extends WP_HTML_Tag_Processor {
	const NOT_IMPLEMENTED_YET = false;

	private $depth = 0;
	private static $query = array( 'tag_closers' => 'visit' );
	private $insertion_mode = 'in-body';

	/**
	 * @var int Unique id for creating bookmarks.
	 */
	private $bookmark_id = 0;

	/**
	 * @var WP_HTML_Element_Stack Refers to element opening tags.
	 */
	private $tag_openers = null;

	/**
	 * @var WP_HTML_Element_Stack Referes to element closing tags.
	 */
	private $tag_closers = null;

	/**
	 * Create a new HTML Processor for reading and modifying HTML structure.
	 *
	 * @param string $html Input HTML document.
	 */
	public function __construct( $html ) {
		parent::__construct( $html );

		$this->tag_openers = new WP_HTML_Element_Stack();
		$this->tag_closers = new WP_HTML_Element_Stack();
	}

	/**
	 * Advance the parser by one step.
	 *
	 * Implements the HTML fragment parsing algorithm.
	 * See https://html.spec.whatwg.org/#parsing-html-fragments
	 *
	 * Only parts of the full algorithm are supported in this class.
	 * For cases where the input HTML doesn't conform to the supported
	 * domain of the fragment parsing algorithm this method will abort
	 * and return `false`.
	 *
	 * @param string $insertion_mode Starting insertion mode for parser, best to leave as the default value
	 *                               unless knowingly handling HTML that will be included inside known tags.
	 *
	 * @return boolean Whether an element was found.
	 */
	public function step( $insertion_mode = null ) {
		try {
			switch ( $insertion_mode ?: $this->insertion_mode ) {
				case 'in-body':
					return $this->step_in_body();

				default:
					return self::NOT_IMPLEMENTED_YET;
			}
		} catch ( Exception $e ) {
			/*
			 * Exceptions are used in this class to escape deep call stacks that
			 * otherwise might involve messier calling and return conventions.
			 */
			return false;
		}
	}

	/**
	 * Parses next element in the 'in head' insertion mode.
	 *
	 * Not yet implemented.
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inhead
	 *
	 * @return false
	 */
	private function step_in_head() {
		return self::NOT_IMPLEMENTED_YET;
	}

	/**
	 * Parses next element in the 'in body' insertion mode.
	 *
	 * @see https://html.spec.whatwg.org/#parsing-main-inbody
	 *
	 * @return boolean Whether an element was found.
	 */
	private function step_in_body() {
		ignored:
		parent::set_bookmark( 'current' );
		if ( ! $this->next_tag( self::$query ) ) {
			return false;
		}

		$tag_name = $this->get_tag();
		$tag_type = $this->is_tag_closer() ? 'closer' : 'opener';
		$op_sigil = $this->is_tag_closer() ? '-' : '+';
		$op       = "{$op_sigil}{$tag_name}";

		switch ( $op ) {
			/*
			 * > A start tag whose tag name is "html"
			 */
			case '+HTML':
				goto ignored;

			/*
			 * > A start tag whose tag name is one of: "base", "basefont", "bgsound",
			 * > "link", "meta", "noframes", "script", "style", "template", "title"
			 *
			 * > An end tag whose tag name is "template"
			 */
			case '+BASE':
			case '+BASEFONT':
			case '+BGSOUND':
			case '+LINK':
			case '+META':
			case '+NOFRAMES':
			case '+SCRIPT':
			case '+STYLE':
			case '+TEMPLATE':
			case '+TITLE':
			case '-TEMPLATE':
				parent::seek( 'current' );
				$this->insertion_mode = 'in-head';
				return $this->step();

			/*
			 * > A start tag whose tag name is "body"
			 */
			case '+BODY':
				goto ignored;


			/*
			 * > A start tag whose tag name is "frameset"
			 */
			case '+FRAMESET':
				throw new Exception( self::NOT_IMPLEMENTED_YET );

			/*
			 * > An end tag whose tag name is "body"
			 * > An end tag whose tag name is "html"
			 */
			case '-BODY':
			case '-HTML':
				/*
				 * > If the stack of open elements does not have a body element in scope, this is a parse error; ignore the token.
				 *
				 * @TODO: We didn't construct an open HTML or BODY tag, but we have to make a choice here based on that.
				 *        Probably need to create these _or_ assume this will always transfer to "after body".
				 */
				$this->insertion_mode = 'after-body';
				return true;

			/*
			 * > A start tag whose tag name is one of: "address", "article", "aside", "blockquote", "center",
			 * > "details", "dialog", "dir", "div", "dl", "fieldset", "figcaption", "figure", "footer",
			 * > "header", "hgroup", "main", "menu", "nav", "ol", "p", "search", "section", "summary", "ul"
			 */
			case '+ADDRESS':
			case '+ARTICLE':
			case '+ASIDE':
			case '+BLOCKQUOTE':
			case '+CENTER':
			case '+DETAILS':
			case '+DIALOG':
			case '+DIR':
			case '+DIV':
			case '+DL':
			case '+FIELDSET':
			case '+FIGCAPTION':
			case '+FIGURE':
			case '+FOOTER':
			case '+HEADER':
			case '+HGROUP':
			case '+MAIN':
			case '+MENU':
			case '+NAV':
			case '+OL':
			case '+P':
			case '+SEARCH':
			case '+SECTION':
			case '+SUMMARY':
			case '+UL':
				if ( $this->has_in_scope( 'P', 'BUTTON' ) ) {
					$this->close_p_element();
				}

				$this->enter_element( $tag_name );
				return;

			/*
			 * > An end-of-file token
			 *
			 * Stop parsing.
			 */
			default:
				return false;
		}

		/*
		 * > A start tag whose tag name is "html"
		 */
		if ( 'HTML' === $tag_name && 'opener' === $tag_type ) {
			goto ignored;
		}

		/*
		 * > A start tag whose tag name is one of: "base", "basefont", "bgsound",
		 * > "link", "meta", "noframes", "script", "style", "template", "title"
		 *
		 * > An end tag whose tag name is "template"
		 */
		if (
			'opener' === $tag_type && (
				'BASE' === $tag_name ||
				'BASEFONT' === $tag_name ||
				'BGSOUND' === $tag_name ||
				'LINK' === $tag_name ||
				'META' === $tag_name ||
				'NOFRAMES' === $tag_name ||
				'SCRIPT' === $tag_name ||
				'STYLE' === $tag_name ||
				'TEMPLATE' === $tag_name ||
				'TITLE' === $tag_name
			) ||
			(
				'closer' === $tag_type &&
				'TEMPLATE' === $tag_name
			) )
		{
			parent::seek( 'current' );
			$this->insertion_mode = 'in-head';
			return $this->step();
		}

		/*
		 * > A start tag whose tag name is "body"
		 */
		if ( 'opener' === $tag_type && 'BODY' === $tag_name ) {
			goto ignored;
		}

		/*
		 * > A start tag whose tag name is "frameset"
		 */
		if ( 'opener' === $tag_type && 'FRAMESET' === $tag_name ) {
			return self::NOT_IMPLEMENTED_YET;
		}

		/*
		 * > An end-of-file token
		 *
		 * Stop parsing.
		 */

		/*
		 * > An end tag whose tag name is "body"
		 * > An end tag whose tag name is "html"
		 */
		if ( 'closer' === $tag_type && ( 'BODY' === $tag_name || 'HTML' === $tag_name ) ) {
			/*
			 * > If the stack of open elements does not have a body element in scope, this is a parse error; ignore the token.
			 *
			 * @TODO: We didn't construct an open HTML or BODY tag, but we have to make a choice here based on that.
			 *        Probably need to create these _or_ assume this will always transfer to "after body".
			 */
			$this->insertion_mode = 'after-body';
			return true;
		}

		/*
		 * > A start tag whose tag name is one of: "address", "article", "aside", "blockquote", "center",
		 * > "details", "dialog", "dir", "div", "dl", "fieldset", "figcaption", "figure", "footer",
		 * > "header", "hgroup", "main", "menu", "nav", "ol", "p", "search", "section", "summary", "ul"
		 */
		if (
			'opener' === $tag_type && (
				'ADDRESS' === $tag_name ||
				'ARTICLE' === $tag_name ||
				'ASIDE' === $tag_name ||
				'BLOCKQUOTE' === $tag_name ||
				'CENTER' === $tag_name ||
				'DETAILS' === $tag_name ||
				'DIALOG' === $tag_name ||
				'DIR' === $tag_name ||
				'DIV' === $tag_name ||
				'DL' === $tag_name ||
				'FIELDSET' === $tag_name ||
				'FIGCAPTION' === $tag_name ||
				'FIGURE' === $tag_name ||
				'FOOTER' === $tag_name ||
				'HEADER' === $tag_name ||
				'HGROUP' === $tag_name ||
				'MAIN' === $tag_name ||
				'MENU' === $tag_name ||
				'NAV' === $tag_name ||
				'OL' === $tag_name ||
				'P' === $tag_name ||
				'SEARCH' === $tag_name ||
				'SECTION' === $tag_name ||
				'SUMMARY' === $tag_name ||
				'UL' === $tag_name
			)
		) {
			if ( $this->has_in_scope( 'P', 'BUTTON' ) ) {
				$this->close_p_element();
			}

			$this->enter_element( $tag_name );
		}

		return false;
	}

	public function set_bookmark( $bookmark_name ) {
		return parent::set_bookmark( '_' . $bookmark_name );
	}

	public function release_bookmark( $bookmark_name ) {
		return parent::release_bookmark( '_' . $bookmark_name );
	}

	private function enter_element( $element ) {
		$this->depth++;

		parent::set_bookmark( "{$this->depth}_{$element}" );
	}

	private function exit_element( $element ) {
		parent::release_bookmark( "{$this->depth}_{$element}" );
	}

	/**
	 * @see https://html.spec.whatwg.org/#close-a-p-element
	 * @return void
	 */
	private function close_p_element() {
		$this->generate_implied_end_tags( 'P' );


	}

	/**
	 * @TODO: Implement this
	 *
	 * @see https://html.spec.whatwg.org/#generate-implied-end-tags
	 *
	 * @param string|null $except_for_this_element Perform as if this element doesn't exist in the stack of open elements.
	 * @return void
	 */
	private function generate_implied_end_tags( $except_for_this_element = null ) {

	}

	/**
	 * The current node is the bottommost node in this stack of open elements.
	 *
	 * @see https://html.spec.whatwg.org/#current-node
	 * @return false|mixed|string
	 */
	private function current_node() {
		if ( 0 === $this->depth ) {
			return false;
		}

		$max_depth = 0;
		foreach ( $this->bookmarks as $name => $bookmark ) {
			if ( '_' === $name[0] ) {
				continue;
			}

			list( $depth, $element ) = explode( '_', $name );
			if ( $depth === "{$this->depth}" ) {
				return $element;
			}
		}

		return false;
	}

	/**
	 * Indicates if the stack of open elements has an element in a given scope.
	 *
	 * @param $element
	 * @param $scope
	 * @return false
	 */
	private function has_in_scope( $element, $scope ) {
		throw new Exception( self::NOT_IMPLEMENTED_YET );
	}

	public function next_tag( $query = null ) {
		/*
		 * The first thing that needs to happen when stepping through the HTML is to
		 * close any void and self-closing elements. These appear on the open stack
		 * to support matching CSS selectors and gauging depths, but they don't
		 * truly have distinct openings and closings.
		 */
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
		parent::seek( '_' . $bookmark_name );

		$max_depth = $this->depth;
		foreach ( $this->bookmarks as $name => $mark ) {
			// Regular bookmarks are prefixed with "_" so they can be ignored here.
			if ( '_' === $name[0] ) {
				continue;
			}

			// Element stack bookmarks are like "3_P" and "4_DIV".
			if ( $mark->start > $this->bookmarks[ $bookmark_name ]->start ) {
				parent::release_bookmark( $name );
			} else {
				$this_depth = (int) explode( '_', $name )[0];
				$max_depth  = max( $max_depth, $this_depth );
			}
		}

		$this->depth = $max_depth;
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
