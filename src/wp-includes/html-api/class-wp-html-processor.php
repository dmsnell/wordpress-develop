<?php

require_once __DIR__ . '/class-wp-html-element-stack.php';
require_once __DIR__ . '/class-wp-html-spec.php';
require_once __DIR__ . '/class-wp-html-unsupported-exception.php';

class WP_HTML_Processor extends WP_HTML_Tag_Processor {
	const NOT_IMPLEMENTED_YET = false;

	/**
	 * These constants seem redundant, but they are in use to
	 * differentiate pure strings and the insertion modes.
	 *
	 * @TODO: This pollutes the top of the class.
	 */
	const INITIAL_MODE = 'initial';
	const BEFORE_HTML = 'before-html';
	const BEFORE_HEAD = 'before-head';
	const IN_HEAD = 'in-head';
	const IN_HEAD_NOSCRIPT = 'in-head-noscript';
	const AFTER_HEAD = 'after-head';
	const IN_BODY = 'in-body';
	const TEXT = 'text';
	const IN_TABLE = 'in-table';
	const IN_TABLE_TEXT = 'in-table-text';
	const IN_CAPTION = 'in-caption';
	const IN_COLUMN_GROUP = 'in-column-group';
	const IN_TABLE_BODY = 'in-table-body';
	const IN_ROW = 'in-row';
	const IN_CELL = 'in-cell';
	const IN_SELECT = 'in-select';
	const IN_SELECT_IN_TABLE = 'in-select-in-table';
	const IN_TEMPLATE = 'in-template';
	const AFTER_BODY = 'after-body';
	const IN_FRAMESET = 'in-frameset';
	const AFTER_FRAMESET = 'after-frameset';
	const AFTER_AFTER_BODY = 'after-after-body';
	const AFTER_AFTER_FRAMESET = 'after-after-frameset';


	private $depth = 0;
	private static $query = array( 'tag_closers' => 'visit' );

	/**
	 * @var int Unique id for creating bookmarks.
	 */
	private $bookmark_id = 0;

	/**
	 * @var WP_HTML_Element_Stack Refers to element opening tags.
	 */
	private $tag_openers = null;

	/**
	 * @var WP_HTML_Element_Stack Refers to element closing tags.
	 */
	private $tag_closers = null;

	/**
	 * Used to handle mis-nested formatting element tags.
	 *
	 * @see https://html.spec.whatwg.org/#the-list-of-active-formatting-elements
	 *
	 * @var WP_HTML_Element_Stack
	 */
	private $active_formatting_elements = null;

	/**
	 * @var string Tree construction insertion mode.
	 */
	private $insertion_mode = 'initial';

	/**
	 * Context node initializing HTML fragment parsing, if in that mode.
	 *
	 * @var [string, array]|null
	 */
	private $context_node = null;

	/**
	 * Points to the HEAD element once one has been parsed, either implicitly or explicitly.
	 *
	 * @TODO: Implement this.
	 *
	 * @see https://html.spec.whatwg.org/#head-element-pointer
	 *
	 * @var null
	 */
	private $head_element_pointer = null;

	/**
	 * Points to the last form element that was opened and whose end tag has not yet been seen, if any.
	 *
	 * @TODO: Implement this.
	 *
	 * This is used to make form controls associate with forms in the face of dramatically
	 * bad markup, for historical reasons. It is ignored inside template elements.
	 *
	 * @see https://html.spec.whatwg.org/#form-element-pointer
	 *
	 * @var null
	 */
	private $form_element_pointer = null;

	/**
	 * Original insertion mode when entering 'text' or 'in-table-text' modes.
	 *
	 * Not implemented yet.
	 *
	 * @var string|null
	 */
	private $original_insertion_mode = null;

	/**
	 * Stack of template insertion modes.
	 *
	 * Not implemented yet.
	 *
	 * @var string[]
	 */
	private $template_insertion_mode_stack = array();

	/**
	 * Create a new HTML Processor for reading and modifying HTML structure.
	 *
	 * ## Initial mode
	 *
	 * Most invocations of the HTML parser operate in the "fragment parsing" mode,
	 * which assumes that the given HTML document existing within an existing HTML
	 * document. For example, block HTML exists within a larger document, and some
	 * inner block HTML might exist within a TABLE element, which holds special
	 * parsing rules.
	 *
	 * The parser can operate in a full parsing mode or the fragment parsing mode,
	 * and it's important to indicate which is necessary when creating the HTML
	 * processor.
	 *
	 * Example
	 *     // Parse an entire HTML document
	 *     $p = new WP_HTML_Processor( $html, array( 'full', WP_HTML_Processor::INITIAL ) );
	 *
	 *     // Parse a full HTML document, but inside a BODY element. E.g. when parsing `post_content`.
	 *     $p = new WP_HTML_Processor( $html, array( 'full', WP_HTML_Processor::IN_BODY ) );
	 *
	 *     // Parse a chunk of HTML provided inside a post's block content.
	 *     $p = new WP_HTML_Processor( $html, array( 'fragment', '<body>' ) );
	 *
	 *     // Parse a chunk of HTML provided inside a post's block content, using the default initial mode.
	 *     $p = new WP_HTML_Processor( $html );
	 *
	 *     // Parse a chunk of HTML known to exist within a TEXTAREA element. E.g. when parsing code input.
	 *     $p = new WP_HTML_Processor( $html, array( 'fragment', '<textarea rows="5">' ) );
	 *
	 *     // Parse a chunk of HTML known to existing within a SCRIPT element. E.g. when sanitizing JavaScript code.
	 *     $p = new WP_HTML_Processor( $html, array( 'fragment', '<script>' ) );
	 *
	 * @param string $html Input HTML document.
	 * @param string[] $initial_mode Initial mode for parser, if provided. Defaults to a fragment parser in the BODY element.
	 * @param string $encoding Must be utf-8
	 */
	public function __construct( $html, $initial_mode = array( 'fragment', 'body' ), $encoding = 'utf8' ) {
		parent::__construct( $html );

		$this->tag_openers                = new WP_HTML_Element_Stack();
		$this->tag_closers                = new WP_HTML_Element_Stack();
		$this->active_formatting_elements = new WP_HTML_Element_Stack();

		list( $parser_type, $initial_context ) = $initial_mode;

		switch ( $parser_type ) {
			// Can we convert a full parser into a fragment parser re-using the existing
			// intial parsing state? E.g. is [ 'full', WP_HTML_Processor:IN_BODY ] the same as  [ 'fragment', '<body>' ]
			case 'full':
				$this->insertion_mode = $initial_context;
				break;

			case 'fragment':
				$p = new WP_HTML_Tag_Processor( $initial_context );
				$p->next_tag();
				$context_node = WP_HTML_Spec::element_info( $p->next_tag() );
				$context_attributes = array();
				foreach ( $p->get_attribute_names_with_prefix( '' ) as $attribute_name ) {
					$context_attributes[ $attribute_name ] = $p->get_attribute( $attribute_name );
				}

				$this->context_node = array( $context_node, $context_attributes );

				$this->tag_openers->push(
					new WP_HTML_Element_Stack_Item(
						$this->spot_bookmark( 0 ),
						'HTML',
						WP_HTML_Element_Stack_Item::NO_FLAGS
					)
				);

				// Create the context node somehow. We can't/don't want to prepend it
				// to the HTML, unless we _can_ do that without disturbing the output.
		}
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
				case self::IN_BODY:
					return $this->step_in_body();

				default:
					return self::NOT_IMPLEMENTED_YET;
			}
		} catch ( WP_HTML_API_Unsupported_Exception $e ) {
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
		if ( ! $this->next_tag( self::$query ) ) {
			return false;
		}

		$tag_name = $this->get_tag();
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
				throw new WP_HTML_API_Unsupported_Exception( 'Cannot process FRAMESET elements.');

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
	}

	public function set_bookmark( $bookmark_name ) {
		return parent::set_bookmark( '_' . $bookmark_name );
	}

	public function release_bookmark( $bookmark_name ) {
		return parent::release_bookmark( '_' . $bookmark_name );
	}

	/**
	 * Sets a non-prefixed bookmark for the current tag.
	 *
	 * @return string Name of the created bookmark.
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function tag_bookmark() {
		$name = (string) ++$this->bookmark_id;

		if ( false === parent::set_bookmark( $name ) ) {
			throw new WP_HTML_API_Unsupported_Exception( 'Could not set tag bookmark' );
		}

		return $name;
	}

	/**
	 * Sets a non-prefixed bookmark for a given point position in the input HTML.
	 *
	 * @param int $index Where in the document the bookmark points; should be on a token boundary.
	 * @return string Name of the created bookmark.
	 */
	private function spot_bookmark( $index ) {
		$name = (string) ++$this->bookmark_id;

		$this->bookmarks[ $name ] = new WP_HTML_Span( $index, $index );

		return $name;
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
	 * Closes elements that have implied end tags.
	 *
	 * @see https://html.spec.whatwg.org/#generate-implied-end-tags
	 *
	 * @param string|null $except_for_this_element Perform as if this element doesn't exist in the stack of open elements.
	 * @return void
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function generate_implied_end_tags( $except_for_this_element = null ) {
		$current_node = $this->tag_openers->current_node();

		$elements_with_implied_end_tags = array(
			WP_HTMLDdElement::class,
			WP_HTMLDtElement::class,
			WP_HTMLLiElement::class,
			WP_HTMLOptgroupElement::class,
			WP_HTMLOptionElement::class,
			WP_HTMLPElement::class,
			WP_HTMLRbElement::class,
			WP_HTMLRpElement::class,
			WP_HTMLRtElement::class,
			WP_HTMLRtcElement::class,
		);

		// @TODO: spot_bookmark needs to get "the current position".
		$tag = $this->tag_bookmark();
		$this->bookmarks[ $tag ]->end = $this->bookmarks[ $tag ]->start;

		// @TODO: Use $this->tag_closers to compute "open" tags. We're probably duplicating
		//        actual tag closers right now because we assume that if a tag is in the
		//        openers list, it has no closing tag. This is probably wrong.
		for ( $i = 0; $i < $this->tag_openers->count(); $i++ ) {
			$current_node = $this->tag_openers->peek( $i );
			$element      = $current_node->element;

			if ( $element === $except_for_this_element || ! in_array( $element, $elements_with_implied_end_tags, true ) ) {
				break;
			}

			$this->tag_closers->push(
				new WP_HTML_Element_Stack_Item(
					$tag,
					$current_node->element,
					WP_HTML_Element_Stack_Item::IS_CLOSER,
					$current_node
				)
			);
		}
	}

	/**
	 * Closes elements that have implied end tags, thoroughly.
	 *
	 * @see https://html.spec.whatwg.org/#generate-all-implied-end-tags-thoroughly
	 *
	 * @param string|null $except_for_this_element Perform as if this element doesn't exist in the stack of open elements.
	 * @return void
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function generate_implied_end_tags_thoroughly( $except_for_this_element = null ) {
		$current_node = $this->tag_openers->current_node();

		$elements_with_implied_end_tags = array(
			WP_HTMLCaptionElement::class,
			WP_HTMLColgroupElement::class,
			WP_HTMLDdElement::class,
			WP_HTMLDtElement::class,
			WP_HTMLLiElement::class,
			WP_HTMLOptgroupElement::class,
			WP_HTMLOptionElement::class,
			WP_HTMLPElement::class,
			WP_HTMLRbElement::class,
			WP_HTMLRpElement::class,
			WP_HTMLRtElement::class,
			WP_HTMLRtcElement::class,
			WP_HTMLTbodyElement::class,
			WP_HTMLTdElement::class,
			WP_HTMLTfootElement::class,
			WP_HTMLThElement::class,
			WP_HTMLTheadElement::class,
			WP_HTMLTrElement::class,
		);

		// @TODO: spot_bookmark needs to get "the current position".
		$tag = $this->tag_bookmark();
		$this->bookmarks[ $tag ]->end = $this->bookmarks[ $tag ]->start;

		// @TODO: Use $this->tag_closers to compute "open" tags. We're probably duplicating
		//        actual tag closers right now because we assume that if a tag is in the
		//        openers list, it has no closing tag. This is probably wrong.
		for ( $i = 0; $i < $this->tag_openers->count(); $i++ ) {
			$current_node = $this->tag_openers->peek( $i );
			$element      = $current_node->element;

			if ( $element === $except_for_this_element || ! in_array( $element, $elements_with_implied_end_tags, true ) ) {
				break;
			}

			$this->tag_closers->push(
				new WP_HTML_Element_Stack_Item(
					$tag,
					$current_node->element,
					WP_HTML_Element_Stack_Item::IS_CLOSER,
					$current_node
				)
			);
		}
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

	/*
	 * HTML5 Parsing Algorithms
	 */

	/**
	 * Resets the insertion mode appropriately.
	 *
	 * @see https://html.spec.whatwg.org/#reset-the-insertion-mode-appropriately
	 * @return void
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function reset_insertion_mode_appropriately() {
		$last = false;
		$depth = $this->tag_openers->count();

		for ( $nth_from_top = 0; $nth_from_top < $depth; $nth_from_top++ ) {
			$node = $this->tag_openers->peek( $nth_from_top );

			if ( $nth_from_top === $depth - 1 ) {
				$last = true;

				if ( null !== $this->context_node ) {
					$node = $this->context_node;
				}
			}

			switch ( $node->element ) {
				// SELECT is a nasty beast we don't support anyway..
				case WP_HTMLSelectElement::class:
					if ( $last ) {
						$this->insertion_mode = self::IN_SELECT;
						return;
					}

					$ancestor = $node;
					for ( $nth_ancestor = 0; $nth_ancestor + $nth_from_top < $depth; ) {
						if ( $ancestor === $this->tag_openers->peek( $depth - 1 ) ) {
							$this->insertion_mode = self::IN_SELECT;
							return;
						}

						$nth_ancestor++;
						$ancestor = $this->tag_openers->peek( $nth_ancestor + $nth_from_top );
						switch ( $ancestor->element ) {
							case WP_HTMLTemplateElement::class:
								$this->insertion_mode = self::IN_SELECT;
								return;

							case WP_HTMLTableElement::class:
								$this->insertion_mode = self::IN_SELECT_IN_TABLE;
								return;
						}
					}

				case WP_HTMLTdElement::class:
				case WP_HTMLThElement::class:
					if ( ! $last ) {
						$this->insertion_mode = self::IN_CELL;
						return;
					}
					break;

				case WP_HTMLTrElement::class:
					$this->insertion_mode = self::IN_ROW;
					return;

				case WP_HTMLTheadElement::class:
				case WP_HTMLTbodyElement::class:
				case WP_HTMLTfootElement::class:
					$this->insertion_mode = self::IN_TABLE_BODY;
					return;

				case WP_HTMLCaptionElement::class:
					$this->insertion_mode = self::IN_CAPTION;
					return;

				case WP_HTMLColgroupElement::class:
					$this->insertion_mode = self::IN_COLUMN_GROUP;
					return;

				case WP_HTMLTableElement::class:
					$this->insertion_mode = self::IN_TABLE;
					return;

				case WP_HTMLTemplateElement::class:
					// @TODO: support current template insertion mode/stack
					throw new WP_HTML_API_Unsupported_Exception( 'Cannot process TEMPLATE elements.' );

				case WP_HTMLHeadElement::class:
					$this->insertion_mode = self::IN_HEAD;
					return;

				case WP_HTMLBodyElement::class:
					$this->insertion_mode = self::IN_BODY;
					return;

				case WP_HTMLFramesetElement::class:
					$this->insertion_mode = self::IN_FRAMESET;
					return;

				case WP_HTMLHtmlElement::class:
					$this->insertion_mode = null === $this->head_element_pointer
						? self::BEFORE_HEAD
						: self::AFTER_HEAD;
					return;

				default:
					if ( $last ) {
						$this->insertion_mode = self::IN_BODY;
						return;
					}
			}
		}
	}

	/**
	 * Track a formatting element on the stack of active formatting elements.
	 *
	 * This is used to handle improperly closed or overlapping elements.
	 *
	 * @see https://html.spec.whatwg.org/#push-onto-the-list-of-active-formatting-elements
	 *
	 * @param string $element Which element to push onto the list of active formatting elements.
	 * @return void
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function push_onto_list_of_active_formatting_elements( $element ) {
		$same_element_count = 0;
		for ( $i = 0; $i < $this->active_formatting_elements->count(); $i++ ) {
			$stack_element = $this->active_formatting_elements->peek( $i );

			if ( $stack_element->element === WP_HTMLMarker::class ) {
				break;
			}

			if ( $stack_element->element !== $element ) {
				continue;
			}

			/*
			 * @TODO: Implement the "Noah's Ark Clause" by parsing and comparing
			 *        the attributes within each element. For now we assume that
			 *        if the tag name matches, it's the same element, but this
			 *        is wrong according to the specification.
			 */
			if ( 3 >= ++$same_element_count ) {
				$this->active_formatting_elements->remove( $stack_element );
				break;
			}
		}

		$this->active_formatting_elements->push( new WP_HTML_Element_Stack_Item( $this->tag_bookmark(), $element ) );
	}

	/**
	 * Reconstructs the active formatting elements.
	 *
	 * @see https://html.spec.whatwg.org/#reconstruct-the-active-formatting-elements
	 *
	 * @return bool Whether any formatting elements were reconstructed.
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	public function reconstruct_active_formatting_elements() {
		if ( 0 === $this->active_formatting_elements->count() ) {
			return false;
		}

		$last_entry = $this->active_formatting_elements->current_node();
		if ( $last_entry->element === WP_HTMLMarker::class ) {
			return false;
		}

		if ( $this->tag_openers->has_element( $last_entry->element ) ) {
			return false;
		}

		throw new WP_HTML_API_Unsupported_Exception( 'Cannot reconstruct the active formatting elements.' );
	}

	/**
	 * Clears the list of active formatting elements up to the last marker.
	 *
	 * @see https://html.spec.whatwg.org/#clear-the-list-of-active-formatting-elements-up-to-the-last-marker
	 *
	 * @return void
	 */
	public function clear_list_of_formatting_elements_up_to_the_last_marker() {
		$to_remove = array();

		for ( $i = 0; $i < $this->active_formatting_elements->count(); $i++ ) {
			$stack_item = $this->active_formatting_elements->peek( $i );

			if ( $stack_item->element === WP_HTMLMarker::class ) {
				break;
			}

			$to_remove[] = $stack_item;
		}

		for ( $i = 0; $i < count( $to_remove ); $i++ ) {
			$this->active_formatting_elements->remove( $to_remove[ $i ] );
		}
	}
}
