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

	/*
	 * These don't have to be globally unique; as constants they
	 * are provided as a means to link the strings to the meanings
     * but their text values are provided as a convenience for
	 * calling functions and sccripts.
	 */
	const FULL_PARSER = 'full';
	const FRAGMENT_PARSER = 'fragment';


	/*
	 * In some cases a transition to another insertion mode is followed
	 * by reprocessing the existing token in the stream. These constants
	 * indicate whether that should happen.
	 */
	const REPROCESS_THIS_NODE = 'reprocess-this-node';
	const PROCESS_NEXT_NODE = 'process-next-node';


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
	 * Specifies whether FRAMESET elements can be processed in the current mode.
	 *
	 * @see https://html.spec.whatwg.org/#frameset-ok-flag
	 *
	 * @var bool
	 */
	private $frameset_ok = true;

	/**
	 * Stack of template insertion modes.
	 *
	 * Not implemented yet.
	 *
	 * @var string[]
	 */
	private $template_insertion_mode_stack = array();

	/**
	 * Indicates if the stack of open elements contains a TEMPLATE element.
	 *
	 * This is an optimization to bypass scanning the stack of open elements
	 * in less common cases.
	 *
	 * @var bool
	 */
	private $template_element_is_on_stack_of_open_elements = false;

	/**
	 * Create an HTML processor in the full HTML parsing mode.
	 *
	 * Use this for cases where you have an entire HTML document from
	 * the start to the end. If you have a section of HTML that's part
	 * of a bigger document, then use `createFragment()` instead.
	 *
	 * @TODO: Proper version number.
	 * @since 6.3.0
	 *
	 * @param string $html     Input HTML document to process.
	 * @param string $encoding Text encoding of the document; only supported value is 'utf-8'.
	 * @return WP_HTML_Processor|null The created processor if successfull, otherwise null.
	 */
	public static function createDocument( $html, $encoding = 'utf-8' ) {
		$options = array(
			'parser_mode'    => self::FULL_PARSER,
			'insertion_mode' => self::INITIAL_MODE,
			'context_node'   => null,
		);

		return new self( $html, $options, $encoding );
	}

	/**
	 * Create an HTML processor in the fragment parsing mode.
	 *
	 * Use this for cases where you are processing chunks of HTML that
	 * will be found within a bigger HTML document, such as rendered
	 * block output that exists within a post, `the_content` inside a
	 * rendered site layout.
	 *
	 * Fragment parsing occurs within a context, which is an HTML element
	 * that the document will eventually be placed in. It becomes important
	 * when special elements have different rules than others, such as inside
	 * a TEXTAREA or a TITLE tag where things that look like tags are text,
	 * or inside a SCRIPT tag where things that look like HTML syntax are JS.
	 *
	 * The context value should be a representation of the tag into which the
	 * HTML is found. For most cases this will be the body element. The HTML
	 * form is provided because a context element may have attributes that
	 * impact the parse, such as with a SCRIPT tag and its `type` attribute.
	 *
	 * @TODO: Proper version number.
	 * @since 6.3.0
	 *
	 * @param string $html     Input HTML fragment to process.
	 * @param string $context  Context element for the fragment, defaults to `<body>`.
	 * @param string $encoding Text encoding of the document; only supported value is 'utf-8'.
	 * @return WP_HTML_Processor|null The created processor if successfull, otherwise null.
	 */
	public static function createFragment( $html, $context = '<body>', $encoding = 'utf8' ) {
		$p = new WP_HTML_Tag_Processor( $context );
		if ( ! $p->next_tag() ) {
			return null;
		}

		$context_node = WP_HTML_Spec::element_info( $p->next_tag() );
		$context_attributes = array();
		foreach ( $p->get_attribute_names_with_prefix( '' ) as $attribute_name ) {
			$context_attributes[ $attribute_name ] = $p->get_attribute( $attribute_name );
		}

		// @TODO: we have to manually "pump" the tokenizer to skip the initial content.
		$h = new self( $html );
		switch ( $context_node ) {
			case WP_HTMLTitleElement::class:
			case WP_HTMLTextareaElement::class:
				$h->
		}

		$options = array(
			'parser_mode'    => self::FRAGMENT_PARSER,
			'insertion_mode' => null,
			'context_node'   => array( $context_node, $context_attributes ),
		);

		return $h;
	}

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
	public function __construct( $html, $use_the_static_create_functions_instead ) {
		parent::__construct( $html );

		$this->tag_openers                = new WP_HTML_Element_Stack();
		$this->tag_closers                = new WP_HTML_Element_Stack();
		$this->active_formatting_elements = new WP_HTML_Element_Stack();

		$initial_state = $use_the_static_create_functions_instead;
		$this->insertion_mode = $initial_state['insertion_mode'];

		if ( self::FRAGMENT_PARSER === $initial_state['parser_mode'] ) {
			$this->context_node = array( $initial_state['context_node'], $initial_state['context_attributes'] );

			// @TODO: Put this somewhere.
			// @TODO: How can we bookmark this without having an instance yet?
			$this->tag_openers->push(
				new WP_HTML_Element_Stack_Item(
					$this->spot_bookmark( 0 ),
					'HTML',
					WP_HTML_Element_Stack_Item::NO_FLAGS
				)
			);
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
	 * @param string $node_to_process Indicates if the current or next token should be processed.
	 *
	 * @return boolean Whether an element was found.
	 */
	public function step( $node_to_process = self::PROCESS_NEXT_NODE ) {
		if ( self::PROCESS_NEXT_NODE === $node_to_process ) {
			$this->next_tag( self::$query );
		}

		// Finish stepping when there are no more tokens in the document.
		if ( null === $this->get_tag() ) {
			return false;
		}

		parent::set_bookmark( 'here' );

		try {
			switch ( $this->insertion_mode ) {
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
			return self::NOT_IMPLEMENTED_YET;
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
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function step_in_body() {
		$tag_name = $this->get_tag();
		$op_sigil = $this->is_tag_closer() ? '-' : '+';
		$op       = "{$op_sigil}{$tag_name}";

		/*
		 * @TODO: What's up with reconstructing active formatting elements for
		 *        text within IN_BODY? How can we get into this situation?
		 */

		switch ( $op ) {
			case '+DOCTYPE':
				// Ignore the token.
				return $this->step();

			/*
			 * > A start tag whose tag name is "html"
			 */
			case '+HTML':
				if ( $this->template_element_is_on_stack_of_open_elements ) {
					// Ignore the token.
					return $this->step();
				} else {
					throw new WP_HTML_API_Unsupported_Exception( 'Cannot add inner HTML attributes to outer HTML element.' );
				}

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
				$this->insertion_mode = self::IN_HEAD;
				return $this->step( self::REPROCESS_THIS_NODE );

			/*
			 * > A start tag whose tag name is "body"
			 */
			case '+BODY':
				/*
				 * > If the second element on the stack of open elements is not a body element,
				 * > if the stack of open elements has only one node on it, or if there is a
				 * > template element on the stack of open elements, then ignore the token. (fragment case)
				 *
				 * In a fragment case the first element on the stack of open elements will _always_
				 * be HTML _root_ with _no attributes_, and the second element on the stack will be
				 * the context node with its attributes. If the stack of open elements is only one
				 * element deep it implies that the BODY has been closed, _or_ in the case of a fragment
				 * parser, that there's no available BODY onto which to add new attributes.
				 */
				if (
					// @TODO: Should this be tag_openers of some "stack_of_open_elements".
					1 === $this->tag_openers->count() ||
					( $this->context_node && WP_HTMLBodyElement::class !== $this->context_node[0] ) ||
					$this->template_element_is_on_stack_of_open_elements
				) {
					// Ignore the token.
					return $this->step();
				} else {
					// @TODO: We could ignore this, but it could mess up CSS selectors. Do we support that?
					//        In always ignoring it we cut out all this cumbersome detection logic.
					throw new WP_HTML_API_Unsupported_Exception( 'Cannot add inner BODY attributes to outer BODY element.' );
				}


			/*
			 * > A start tag whose tag name is "frameset"
			 *
			 * @TODO: Framesets apparently refuse to exist within BODY tags.
			 *        But we still need to skip their children when we encounter them.
			 */
			case '+FRAMESET':
				if (
					1 === $this->tag_openers->count() ||
					( $this->context_node && WP_HTMLBodyElement::class !== $this->context_node[0] ) ||
					! $this->frameset_ok
				) {
					// Ignore the token.
					return $this->step();
				}

				/*
				 * When encountering a FRAMESET in this state it should climb to the top of the DOM
				 * tree under the HTML element and adopt all of the current open nodes, then switch
				 * to self::IN_FRAMESET insertion mode.
				 */
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
				if ( ! $this->tag_openers->has_element_in_particular_scope( WP_HTMLBodyElement::class ) ) {
					// Ignore the token.
					return $this->step();
				}

				/*
				 * > if there is a node in the stack of open elements that is not either a dd element,
				 * > a dt element, an li element, an optgroup element, an option element, a p element,
				 * > an rb element, an rp element, an rt element, an rtc element, a tbody element,
				 * > a td element, a tfoot element, a th element, a thead element, a tr element,
				 * > the body element, or the html element, then this is a parse error.
				 *
				 * Parse errors do not affect anything so there is nothing required to handle these cases.
				 */

				$this->insertion_mode = self::AFTER_BODY;
				return $this->step(
					'HTML' === $tag_name
						? self::REPROCESS_THIS_NODE
						: self::PROCESS_NEXT_NODE
				);

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
				if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
					$this->close_p_element();
				}

				// @TODO: We need to push the item onto the stack. Anything else?
				$this->insert_html_element();
				return true;

			case '+H1':
			case '+H2':
			case '+H3':
			case '+H4':
			case '+H5':
			case '+H6':
				if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
					$this->close_p_element();
				}

				switch ( $this->tag_openers->current_node()->element ) {
					case WP_HTMLH1Element::class:
					case WP_HTMLH2Element::class:
					case WP_HTMLH3Element::class:
					case WP_HTMLH4Element::class:
					case WP_HTMLH5Element::class:
					case WP_HTMLH6Element::class:
						/*
						 * > If the current node is an HTML element whose tag name is one of
						 * > "h1", "h2", "h3", "h4", "h5", or "h6", then this is a parse error;
						 * > pop the current node off the stack of open elements.
						 *
						 * If encountering a heading element inside another, the open one implicitly
						 * closes and the new one opens. Not sure why here we don't "close" the first
						 * one but effectively it does that.
						 *
						 * @TODO: Is this different when reconstructing the active formats from a P?
						 */
						$this->pop_node_off_of_stack_of_open_elements();
				}

				$this->insert_html_element();
				return true;


			case '+PRE':
			case '+LISTING':
				if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
					$this->close_p_element();
				}

				$this->insert_html_element();

				/*
				 * > If the next token is a U+000A LINE FEED (LF) character token, then ignore that
				 * > token and move on to the next one. (Newlines at the start of pre blocks are
				 * > ignored as an authoring convenience.)
				 *
				 * Ignoring this here so that the function getting content can handle it.
				 */
				$this->frameset_ok = false;
				return true;

			case '+FORM':
				if ( null !== $this->form_element_pointer && ! $this->template_element_is_on_stack_of_open_elements ) {
					// Ignore the token.
					return $this->step();
				}

				if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
					$this->close_p_element();
				}

				$this->insert_html_element();
				if ( ! $this->template_element_is_on_stack_of_open_elements ) {
					$this->form_element_pointer = $this->get_bookmark_of_current_element();
				}
				return true;

			case '+LI':
				$this->frameset_ok = false;
				throw new WP_HTML_API_Unsupported_Exception( 'Cannot descend into LI nodes yet.' );

				/*
				 * @TODO: Fix this logic.
				 *
				 * When encountering an LI close out all of the previous elements up to the containing LI,
				 * then close the containing LI, then open this LI.
				 *
				 * If there are markers or special nodes that remove the containing LIs from the scope then
				 * there's no need to close anything - they are structurally separated.
				 */
				$node = $this->tag_openers->current_node();
				while ( WP_HTMLLiElement::class === $node->element ) {
					$this->generate_implied_end_tags( WP_HTMLLiElement::class );

					while ( WP_HTMLLiElement::class !== $this->pop_element_from_stack_of_open_elements() ) {
						continue;
					}

					if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
						$this->close_p_element();
						$this->insert_html_element();
						return true;
					}

					$element = $node->element;
					if (
						$element::IS_SPECIAL &&
						WP_HTMLAddressElement::class !== $element &&
						WP_HTMLDivElement::class !== $element &&
						WP_HTMLPElement::class !== $element
					) {
						$this->close_p_element();
						$this->insert_html_element();
						return true;
					}

					// @TODO: Reset $node to next higher item in stack.
				}

				throw new Exception( 'This should never be reachable.' );

			case '+DD':
			case '+DT':
				// @TODO: This is just like the LI case, but different in that it has two loops.
				throw new WP_HTML_API_Unsupported_Exception( 'Cannot process DD or DT elementes yet.' );

			case '+PLAINTEXT':
				if ( $this->tag_openers->has_element_in_button_scope( WP_HTMLPElement::class ) ) {
					$this->close_p_element();
				}

				// @TODO: Close the parser at this point. We might need to invent an insertion mode
				//        that means "stop processing all elements."
				$this->insert_html_element();
				return true;

			case '+BUTTON':
				if ( $this->tag_openers->has_element_in_specific_scope( WP_HTMLButtonElement::class ) ) {
					$this->generate_implied_end_tags();

					// @TODO: Pop elements off of the open elements stack until a BUTTON has been removed.
				}

				$this->reconstruct_active_formatting_elements();
				$this->insert_html_element();
				$this->frameset_ok = false;
				return true;

			case '-ADDRESS':
			case '-ARTICLE':
			case '-ASIDE':
			case '-BLOCKQUOTE':
			case '-BUTTON':
			case '-CENTER':
			case '-DETAILS':
			case '-DIALOG':
			case '-DIR':
			case '-DIV':
			case '-DL':
			case '-FIELDSET':
			case '-FIGCAPTION':
			case '-FIGURE':
			case '-FOOTER':
			case '-HEADER':
			case '-HGROUP':
			case '-LISTING':
			case '-MAIN':
			case '-MENU':
			case '-NAV':
			case '-OL':
			case '-PRE':
			case '-SEARCH':
			case '-SECTION':
			case '-SUMMARY':
			case '-UL':
				if ( ! $this->tag_openers->has_element_in_particular_scope( WP_HTML_Spec::element_info( $tag_name ) ) ) {
					// Ignore token.
					return $this->step();
				}

				$this->generate_implied_end_tags();

				// @TODO: Should we mark parse errors?
				// @TODO: Pop elements off of the open elements stack until a BUTTON has been removed.
				return true;

			case '-FORM':
				if ( ! $this->template_element_is_on_stack_of_open_elements ) {
					// @TODO: Implement
					throw new WP_HTML_API_Unsupported_Exception( 'Cannot process FORM elements.' );
				} else {
					// @TODO: Implement
					throw new WP_HTML_API_Unsupported_Exception( 'Cannot process FORM elements.' );
				}

			case '-P':
				throw new WP_HTML_API_Unsupported_Exception( 'Cannot process P elements.' );

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
		//        We could be jumping into an already-parsed section via `seek()`, so we
		//        have to determine if we're in opened tags or not.
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
		//        We could be jumping into an already-parsed section via `seek()`, so we
		//        have to determine if we're in opened tags or not.
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

	/**
	 * Determines if an element exists on the stack of open elements.
	 *
	 * @param string $element Which element to find.
	 * @return boolean
	 * @throws WP_HTML_API_Unsupported_Exception
	 */
	private function has_element_on_stack_of_open_elements( $element ) {
		throw new WP_HTML_API_Unsupported_Exception();
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
