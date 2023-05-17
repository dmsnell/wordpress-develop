<?php


/**
 * Source of knowledge about HTML according to the HTML 5 specification.
 *
 * @since 6.3.0
 */
class WP_HTML_Spec {
	/**
	 * Returns class defining attributes of an element with the given name.
	 *
	 * @since 6.3.0
	 *
	 * @param string $tag_name
	 * @return WP_HTML_Element_Meta::class
	 */
	public static function element_info( $tag_name ) {
		// We have to force casing on this because someone might call it with mixed casing.
		switch ( strtoupper( $tag_name ) ) {
			// Normal elements
			case 'A':
				return WP_HTMLAnchorElement::class;
			case 'ABBR':
				return WP_HTMLAbbrElement::class;
			case 'ADDRESS':
				return WP_HTMLAddressElement::class;
			case 'AREA':
				return WP_HTMLAreaElement::class;
			case 'ARTICLE':
				return WP_HTMLArticleElement::class;
			case 'ASIDE':
				return WP_HTMLAsideElement::class;
			case 'AUDIO':
				return WP_HTMLAudioElement::class;
			case 'B':
				return WP_HTMLBElement::class;
			case 'BASE':
				return WP_HTMLBaseElement::class;
			case 'BDI':
				return WP_HTMLBdiElement::class;
			case 'BDO':
				return WP_HTMLBdoElement::class;
			case 'BLOCKQUOTE':
				return WP_HTMLBlockquoteElement::class;
			case 'BODY':
				return WP_HTMLBodyElement::class;
			case 'BR':
				return WP_HTMLBrElement::class;
			case 'BUTTON':
				return WP_HTMLButtonElement::class;
			case 'CANVAS':
				return WP_HTMLCanvasElement::class;
			case 'CAPTION':
				return WP_HTMLCaptionElement::class;
			case 'CITE':
				return WP_HTMLCiteElement::class;
			case 'CODE':
				return WP_HTMLCodeElement::class;
			case 'COL':
				return WP_HTMLColElement::class;
			case 'COLGROUP':
				return WP_HTMLColgroupElement::class;
			case 'DATA':
				return WP_HTMLDataElement::class;
			case 'DATALIST':
				return WP_HTMLDataListElement::class;
			case 'DD':
				return WP_HTMLDdElement::class;
			case 'DEL':
				return WP_HTMLDelElement::class;
			case 'DETAILS':
				return WP_HTMLDetailsElement::class;
			case 'DFN':
				return WP_HTMLDfnElement::class;
			case 'DIALOG':
				return WP_HTMLDialogElement::class;
			case 'DIV':
				return WP_HTMLDivElement::class;
			case 'DL':
				return WP_HTMLDlElement::class;
			case 'DT':
				return WP_HTMLDtElement::class;
			case 'EM':
				return WP_HTMLEmElement::class;
			case 'EMBED':
				return WP_HTMLEmbedElement::class;
			case 'FIELDSET':
				return WP_HTMLFieldsetElement::class;
			case 'FIGCAPTION':
				return WP_HTMLFigcaptionElement::class;
			case 'FIGURE':
				return WP_HTMLFigureElement::class;
			case 'FOOTER':
				return WP_HTMLFooterElement::class;
			case 'FORM':
				return WP_HTMLFormElement::class;
			case 'H1':
				return WP_HTMLH1Element::class;
			case 'H2':
				return WP_HTMLH2Element::class;
			case 'H3':
				return WP_HTMLH3Element::class;
			case 'H4':
				return WP_HTMLH4Element::class;
			case 'H5':
				return WP_HTMLH5Element::class;
			case 'H6':
				return WP_HTMLH6Element::class;
			case 'HEAD':
				return WP_HTMLHeadElement::class;
			case 'HEADER':
				return WP_HTMLHeaderElement::class;
			case 'HGROUP':
				return WP_HTMLHgropuElement::class;
			case 'HR':
				return WP_HTMLHrElement::class;
			case 'HTML':
				return WP_HTMLHtmlElement::class;
			case 'I':
				return WP_HTMLIElement::class;
			case 'IFRAME':
				return WP_HTMLIframeElement::class;
			case 'IMG':
				return WP_HTMLImgElement::class;
			case 'INPUT':
				return WP_HTMLInputElement::class;
			case 'INS':
				return WP_HTMLInsElement::class;
			case 'KBD':
				return WP_HTMLKbdElement::class;
			case 'LABEL':
				return WP_HTMLLabelElement::class;
			case 'LEGEND':
				return WP_HTMLLegendElement::class;
			case 'LI':
				return WP_HTMLLiElement::class;
			case 'LINK':
				return WP_HTMLLinkElement::class;
			case 'MAIN':
				return WP_HTMLMainElement::class;
			case 'MAP':
				return WP_HTMLMapElement::class;
			case 'MARK':
				return WP_HTMLMarkElement::class;
			case 'MATH':
				return WP_HTMLMathElement::class;
			case 'MENU':
				return WP_HTMLMenuElement::class;
			case 'META':
				return WP_HTMLMetaElement::class;
			case 'METER':
				return WP_HTMLMeterElement::class;
			case 'NAV':
				return WP_HTMLNavElement::class;
			case 'NOSCRIPT':
				return WP_HTMLNoscriptElement::class;
			case 'OBJECT':
				return WP_HTMLObjectElement::class;
			case 'OL':
				return WP_HTMLOlElement::class;
			case 'OPTGROUP':
				return WP_HTMLOptgroupElement::class;
			case 'OPTION':
				return WP_HTMLOptionElement::class;
			case 'OUTPUT':
				return WP_HTMLOutputElement::class;
			case 'P':
				return WP_HTMLPElement::class;
			case 'PICTURE':
				return WP_HTMLPictureElement::class;
			case 'PRE':
				return WP_HTMLPreElement::class;
			case 'PROGRESS':
				return WP_HTMLProgressElement::class;
			case 'Q':
				return WP_HTMLQElement::class;
			case 'RP':
				return WP_HTMLRpElement::class;
			case 'RT':
				return WP_HTMLRtElement::class;
			case 'RUBY':
				return WP_HTMLRubyElement::class;
			case 'S':
				return WP_HTMLSElement::class;
			case 'SAMP':
				return WP_HTMLSampElement::class;
			case 'SCRIPT':
				return WP_HTMLScriptElement::class;
			case 'SECTION':
				return WP_HTMLSectionElement::class;
			case 'SELECT':
				return WP_HTMLSelectElement::class;
			case 'SLOT':
				return WP_HTMLSlotElement::class;
			case 'SMALL':
				return WP_HTMLSmallElement::class;
			case 'SOURCE':
				return WP_HTMLSourceElement::class;
			case 'SPAN':
				return WP_HTMLSpanElement::class;
			case 'STRONG':
				return WP_HTMLStrongElement::class;
			case 'STYLE':
				return WP_HTMLStyleElement::class;
			case 'SUB':
				return WP_HTMLSubElement::class;
			case 'SUMMARY':
				return WP_HTMLSummaryElement::class;
			case 'SUP':
				return WP_HTMLSupElement::class;
			case 'SVG':
				return WP_HTMLSvgElement::class;
			case 'TABLE':
				return WP_HTMLTableElement::class;
			case 'TBODY':
				return WP_HTMLTbodyElement::class;
			case 'TD':
				return WP_HTMLTdElement::class;
			case 'TEMPLATE':
				return WP_HTMLTemplateElement::class;
			case 'TEXTAREA':
				return WP_HTMLTextareaElement::class;
			case 'TFOOT':
				return WP_HTMLTfootElement::class;
			case 'TH':
				return WP_HTMLThElement::class;
			case 'THEAD':
				return WP_HTMLTheadElement::class;
			case 'TIME':
				return WP_HTMLTimeElement::class;
			case 'TITLE':
				return WP_HTMLTitleElement::class;
			case 'TR':
				return WP_HTMLTrElement::class;
			case 'TRACK':
				return WP_HTMLTrackElement::class;
			case 'U':
				return WP_HTMLUElement::class;
			case 'UL':
				return WP_HTMLUlElement::class;
			case 'VAR':
				return WP_HTMLVarElement::class;
			case 'VIDEO':
				return WP_HTMLVideoElement::class;
			case 'WBR':
				return WP_HTMLWbrElement::class;

			// Deprecated elements
			case 'APPLET':
			case 'BLINK':
			case 'ISINDEX':
			case 'MULTICOL':
			case 'NEXTID':
			case 'SPACER':
				return WP_HTMLUnknownHTMLElement::class;

			case 'BGSOUND': // may be self-closing
				return WP_HTMLUnknownElement::class;

			case 'KEYGEN':
				return WP_HTML_Void_Element::class;

			// Neutralized elements
			case 'ACRONYM':
			case 'BIG':
			case 'CENTER':
			case 'NOBR':
			case 'NOEMBED':
			case 'NOFRAMES':
			case 'PLAINTEXT':
			case 'RB':
			case 'RTC':
			case 'STRIKE':
			case 'TT':
				return WP_HTMLElement::class;

			case 'BASEFONT':
				return WP_HTML_Void_Element::class;

			// Substitutions
			case 'LISTING':
			case 'XMP':
				return WP_HTMLPreElement::class;
		}

		$is_valid_custom_name = false !== strpos( $tag_name, '-' );

		return $is_valid_custom_name
			? WP_HTMLElement::class
			: WP_HTMLUnknownElement::class;
	}
}

class WP_HTML_Element_Meta {
	const IS_VOID = false;
	const IS_HTML = true;
	const IS_SPECIAL = false;
}

class WP_HTMLElement extends WP_HTML_Element_Meta {
}

class WP_HTML_Void_Element extends WP_HTMLElement {
	const IS_VOID = true;
}

class WP_HTMLUnknownElement extends WP_HTML_Element_Meta {
	const IS_HTML = false;
}

// this one is a bit weird, but so are the deprecated HTML elements belonging to this category.
class WP_HTMLUnknownHTMLElement extends WP_HTMLUnknownElement {
	const IS_HTML = true;
}

class WP_HTMLAnchorElement extends WP_HTML_Element_Meta {
}

class WP_HTMLAbbrElement extends WP_HTML_Element_Meta {
}

class WP_HTMLAddressElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

// @TODO: Add deprecated special rule: APPLET

class WP_HTMLAreaElement extends WP_HTML_Element_Meta {
	const IS_VOID = true;
	const IS_SPECIAL = true;
}

class WP_HTMLArticleElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLAsideElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLAudioElement extends WP_HTML_Element_Meta {
}

class WP_HTMLBElement extends WP_HTML_Element_Meta {
}

class WP_HTMLBaseElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLBdiElement extends WP_HTML_Element_Meta {
}

class WP_HTMLBdoElement extends WP_HTML_Element_Meta {
}

class WP_HTMLBlockquoteElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLBodyElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLBrElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLButtonElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLCanvasElement extends WP_HTML_Element_Meta {
}

class WP_HTMLCaptionElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLCiteElement extends WP_HTML_Element_Meta {
}

class WP_HTMLCodeElement extends WP_HTML_Element_Meta {
}

class WP_HTMLColElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLColgroupElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLDataElement extends WP_HTML_Element_Meta {
}

class WP_HTMLDataListElement extends WP_HTML_Element_Meta {
}

class WP_HTMLDdElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLDelElement extends WP_HTML_Element_Meta {
}

class WP_HTMLDetailsElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLDfnElement extends WP_HTML_Element_Meta {
}

class WP_HTMLDialogElement extends WP_HTML_Element_Meta {
}

class WP_HTMLDivElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLDlElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLDtElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLEmElement extends WP_HTML_Element_Meta {
}

class WP_HTMLEmbedElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLFieldsetElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLFigcaptionElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLFigureElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLFooterElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLFormElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLFramesetElement extends WP_HTML_Element_Meta {
}

class WP_HTMLH1Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLH2Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLH3Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLH4Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLH5Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLH6Element extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLHeadElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLHeaderElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLHgroupElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLHrElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLHtmlElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLIElement extends WP_HTML_Element_Meta {
}

class WP_HTMLIframeElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLImgElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLInputElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLInsElement extends WP_HTML_Element_Meta {
}

class WP_HTMLKbdElement extends WP_HTML_Element_Meta {
}

class WP_HTMLLabelElement extends WP_HTML_Element_Meta {
}

class WP_HTMLLegendElement extends WP_HTML_Element_Meta {
}

class WP_HTMLLiElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLLinkElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLMainElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLMapElement extends WP_HTML_Element_Meta {
}

class WP_HTMLMarkElement extends WP_HTML_Element_Meta {
}

class WP_HTMLMathElement extends WP_HTML_Element_Meta {
}

class WP_HTMLMenuElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLMetaElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLMeterElement extends WP_HTML_Element_Meta {
}

class WP_HTMLNavElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLNoscriptElement extends WP_HTML_Element_Meta {
}

class WP_HTMLObjectElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLOlElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLOptgroupElement extends WP_HTML_Element_Meta {
}

class WP_HTMLOptionElement extends WP_HTML_Element_Meta {
}

class WP_HTMLOutputElement extends WP_HTML_Element_Meta {
}

class WP_HTMLPElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLPictureElement extends WP_HTML_Element_Meta {
}

class WP_HTMLPreElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLProgressElement extends WP_HTML_Element_Meta {
}

class WP_HTMLQElement extends WP_HTML_Element_Meta {
}

class WP_HTMLRpElement extends WP_HTML_Element_Meta {
}

class WP_HTMLRtElement extends WP_HTML_Element_Meta {
}

class WP_HTMLRubyElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSampElement extends WP_HTML_Element_Meta {
}

class WP_HTMLScriptElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLSectionElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLSelectElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLSlotElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSmallElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSourceElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLSpanElement extends WP_HTML_Element_Meta {
}

class WP_HTMLStrongElement extends WP_HTML_Element_Meta {
}

class WP_HTMLStyleElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLSubElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSummaryElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLSupElement extends WP_HTML_Element_Meta {
}

class WP_HTMLSvgElement extends WP_HTML_Element_Meta {
}

class WP_HTMLTableElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTbodyElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTdElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTemplateElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTextareaElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTfootElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLThElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTheadElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTimeElement extends WP_HTML_Element_Meta {
}

class WP_HTMLTitleElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTrElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLTrackElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}

class WP_HTMLUElement extends WP_HTML_Element_Meta {
}

class WP_HTMLUlElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
}

class WP_HTMLVarElement extends WP_HTML_Element_Meta {
}

class WP_HTMLVideoElement extends WP_HTML_Element_Meta {
}

class WP_HTMLWbrElement extends WP_HTML_Element_Meta {
	const IS_SPECIAL = true;
	const IS_VOID = true;
}
