<?php


/**
 * Source of knowledge about HTML according to the HTML 5 specification.
 *
 * @since 6.3.0
 */
class WP_HTML_Spec {
	/**
	 * Returns meta information about an HTML tag.
	 *
	 * @since 6.3.0
	 *
	 * @param string $tag_name
	 * @return WP_HTML_Element_Meta::class
	 */
	public static function element_info( $tag_name ) {
		switch ( strtolower( $tag_name ) ) {
			// Normal elements
			case 'a': return WP_HTMLAnchorElement::class;
			case 'abbr': return WP_HTMLAbbrElement::class;
			case 'address': return WP_HTMLAddressElement::class;
			case 'area': return WP_HTMLAreaElement::class;
			case 'article': return WP_HTMLArticleElement::class;
			case 'aside': return WP_HTMLAsideElement::class;
			case 'audio': return WP_HTMLAudioElement::class;
			case 'b': return WP_HTMLBElement::class;
			case 'base': return WP_HTMLBaseElement::class;
			case 'bdi': return WP_HTMLBdiElement::class;
			case 'bdo': return WP_HTMLBdoElement::class;
			case 'blockquote': return WP_HTMLBlockquoteElement::class;
			case 'body': return WP_HTMLBodyElement::class;
			case 'br': return WP_HTMLBrElement::class;
			case 'button': return WP_HTMLButtonElement::class;
			case 'canvas': return WP_HTMLCanvasElement::class;
			case 'caption': return WP_HTMLCaptionElement::class;
			case 'cite': return WP_HTMLCiteElement::class;
			case 'code': return WP_HTMLCodeElement::class;
			case 'col': return WP_HTMLColElement::class;
			case 'colgroup': return WP_HTMLColgroupElement::class;
			case 'data': return WP_HTMLDataElement::class;
			case 'datalist': return WP_HTMLDataListElement::class;
			case 'dd': return WP_HTMLDdElement::class;
			case 'del': return WP_HTMLDelElement::class;
			case 'details': return WP_HTMLDetailsElement::class;
			case 'dfn': return WP_HTMLDfnElement::class;
			case 'dialog': return WP_HTMLDialogElement::class;
			case 'div': return WP_HTMLDivElement::class;
			case 'dl': return WP_HTMLDlElement::class;
			case 'dt': return WP_HTMLDtElement::class;
			case 'em': return WP_HTMLEmElement::class;
			case 'embed': return WP_HTMLEmbedElement::class;
			case 'fieldset': return WP_HTMLFieldsetElement::class;
			case 'figcaption': return WP_HTMLFigcaptionElement::class;
			case 'figure': return WP_HTMLFigureElement::class;
			case 'footer': return WP_HTMLFooterElement::class;
			case 'form': return WP_HTMLFormElement::class;
			case 'h1': return WP_HTMLH1Element::class;
			case 'h2': return WP_HTMLH2Element::class;
			case 'h3': return WP_HTMLH3Element::class;
			case 'h4': return WP_HTMLH4Element::class;
			case 'h5': return WP_HTMLH5Element::class;
			case 'h6': return WP_HTMLH6Element::class;
			case 'head': return WP_HTMLHeadElement::class;
			case 'header': return WP_HTMLHeaderElement::class;
			case 'hgroup': return WP_HTMLHgropuElement::class;
			case 'hr': return WP_HTMLHrElement::class;
			case 'html': return WP_HTMLHtmlElement::class;
			case 'i': return WP_HTMLIElement::class;
			case 'iframe': return WP_HTMLIframeElement::class;
			case 'img': return WP_HTMLImgElement::class;
			case 'input': return WP_HTMLInputElement::class;
			case 'ins': return WP_HTMLInsElement::class;
			case 'kbd': return WP_HTMLKbdElement::class;
			case 'label': return WP_HTMLLabelElement::class;
			case 'legend': return WP_HTMLLegendElement::class;
			case 'li': return WP_HTMLLiElement::class;
			case 'link': return WP_HTMLLinkElement::class;
			case 'main': return WP_HTMLMainElement::class;
			case 'map': return WP_HTMLMapElement::class;
			case 'mark': return WP_HTMLMarkElement::class;
			case 'math': return WP_HTMLMathElement::class;
			case 'menu': return WP_HTMLMenuElement::class;
			case 'meta': return WP_HTMLMetaElement::class;
			case 'meter': return WP_HTMLMeterElement::class;
			case 'nav': return WP_HTMLNavElement::class;
			case 'noscript': return WP_HTMLNoscriptElement::class;
			case 'object': return WP_HTMLObjectElement::class;
			case 'ol': return WP_HTMLOlElement::class;
			case 'optgroup': return WP_HTMLOptgroupElement::class;
			case 'option': return WP_HTMLOptionElement::class;
			case 'output': return WP_HTMLOutputElement::class;
			case 'p': return WP_HTMLPElement::class;
			case 'picture': return WP_HTMLPictureElement::class;
			case 'pre': return WP_HTMLPreElement::class;
			case 'progress': return WP_HTMLProgressElement::class;
			case 'q': return WP_HTMLQElement::class;
			case 'rp': return WP_HTMLRpElement::class;
			case 'rt': return WP_HTMLRtElement::class;
			case 'ruby': return WP_HTMLRubyElement::class;
			case 's': return WP_HTMLSElement::class;
			case 'samp': return WP_HTMLSampElement::class;
			case 'script': return WP_HTMLScriptElement::class;
			case 'section': return WP_HTMLSectionElement::class;
			case 'select': return WP_HTMLSelectElement::class;
			case 'slot': return WP_HTMLSlotElement::class;
			case 'small': return WP_HTMLSmallElement::class;
			case 'source': return WP_HTMLSourceElement::class;
			case 'span': return WP_HTMLSpanElement::class;
			case 'strong': return WP_HTMLStrongElement::class;
			case 'style': return WP_HTMLStyleElement::class;
			case 'sub': return WP_HTMLSubElement::class;
			case 'summary': return WP_HTMLSummaryElement::class;
			case 'sup': return WP_HTMLSupElement::class;
			case 'svg': return WP_HTMLSvgElement::class;
			case 'table': return WP_HTMLTableElement::class;
			case 'tbody': return WP_HTMLTbodyElement::class;
			case 'td': return WP_HTMLTdElement::class;
			case 'template': return WP_HTMLTemplateElement::class;
			case 'textarea': return WP_HTMLTextareaElement::class;
			case 'tfoot': return WP_HTMLTfootElement::class;
			case 'th': return WP_HTMLThElement::class;
			case 'thead': return WP_HTMLTheadElement::class;
			case 'time': return WP_HTMLTimeElement::class;
			case 'title': return WP_HTMLTitleElement::class;
			case 'tr': return WP_HTMLTrElement::class;
			case 'track': return WP_HTMLTrackElement::class;
			case 'u': return WP_HTMLUElement::class;
			case 'ul': return WP_HTMLUlElement::class;
			case 'var': return WP_HTMLVarElement::class;
			case 'video': return WP_HTMLVideoElement::class;
			case 'wbr': return WP_HTMLWbrElement::class;

			// Deprecated elements
			case 'applet':
			case 'bgsound':
			case 'blink':
			case 'isindex':
			case 'keygen':
			case 'multicol':
			case 'nextid':
			case 'spacer':
				return WP_HTMLUnknownElement::class;

			// Neutralized elements
			case 'acronym':
			case 'basefont':
			case 'big':
			case 'center':
			case 'nobr':
			case 'noembed':
			case 'noframes':
			case 'plaintext':
			case 'rb':
			case 'rtc':
			case 'strike':
			case 'tt':
				return WP_HTMLElement::class;

			// Substitutions
			case 'listing':
			case 'xmp':
				return WP_HTMLPreElement::class;

			default:
				return WP_HTMLUnknownElement::class;
		}
	}
}

class WP_HTML_Element_Meta {
	const is_void = false;
	const is_html = true;
}

class WP_HTMLUnknownElement extends WP_HTML_Element_Meta { const is_html = false; }
class WP_HTMLElement extends WP_HTML_Element_Meta {}

class WP_HTMLAnchorElement extends WP_HTML_Element_Meta {}
class WP_HTMLAbbrElement extends WP_HTML_Element_Meta {}
class WP_HTMLAddressElement extends WP_HTML_Element_Meta {}
class WP_HTMLAreaElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLArticleElement extends WP_HTML_Element_Meta {}
class WP_HTMLAsideElement extends WP_HTML_Element_Meta {}
class WP_HTMLAudioElement extends WP_HTML_Element_Meta {}
class WP_HTMLBElement extends WP_HTML_Element_Meta {}
class WP_HTMLBaseElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLBdiElement extends WP_HTML_Element_Meta {}
class WP_HTMLBdoElement extends WP_HTML_Element_Meta {}
class WP_HTMLBlockquoteElement extends WP_HTML_Element_Meta {}
class WP_HTMLBodyElement extends WP_HTML_Element_Meta {}
class WP_HTMLBrElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLButtonElement extends WP_HTML_Element_Meta {}
class WP_HTMLCanvasElement extends WP_HTML_Element_Meta {}
class WP_HTMLCaptionElement extends WP_HTML_Element_Meta {}
class WP_HTMLCiteElement extends WP_HTML_Element_Meta {}
class WP_HTMLCodeElement extends WP_HTML_Element_Meta {}
class WP_HTMLColElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLColgroupElement extends WP_HTML_Element_Meta {}
class WP_HTMLDataElement extends WP_HTML_Element_Meta {}
class WP_HTMLDataListElement extends WP_HTML_Element_Meta {}
class WP_HTMLDdElement extends WP_HTML_Element_Meta {}
class WP_HTMLDelElement extends WP_HTML_Element_Meta {}
class WP_HTMLDetailsElement extends WP_HTML_Element_Meta {}
class WP_HTMLDfnElement extends WP_HTML_Element_Meta {}
class WP_HTMLDialogElement extends WP_HTML_Element_Meta {}
class WP_HTMLDivElement extends WP_HTML_Element_Meta {}
class WP_HTMLDlElement extends WP_HTML_Element_Meta {}
class WP_HTMLDtElement extends WP_HTML_Element_Meta {}
class WP_HTMLEmElement extends WP_HTML_Element_Meta {}
class WP_HTMLEmbedElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLFieldsetElement extends WP_HTML_Element_Meta {}
class WP_HTMLFigcaptionElement extends WP_HTML_Element_Meta {}
class WP_HTMLFigureElement extends WP_HTML_Element_Meta {}
class WP_HTMLFooterElement extends WP_HTML_Element_Meta {}
class WP_HTMLFormElement extends WP_HTML_Element_Meta {}
class WP_HTMLH1Element extends WP_HTML_Element_Meta {}
class WP_HTMLH2Element extends WP_HTML_Element_Meta {}
class WP_HTMLH3Element extends WP_HTML_Element_Meta {}
class WP_HTMLH4Element extends WP_HTML_Element_Meta {}
class WP_HTMLH5Element extends WP_HTML_Element_Meta {}
class WP_HTMLH6Element extends WP_HTML_Element_Meta {}
class WP_HTMLHeadElement extends WP_HTML_Element_Meta {}
class WP_HTMLHeaderElement extends WP_HTML_Element_Meta {}
class WP_HTMLHgropuElement extends WP_HTML_Element_Meta {}
class WP_HTMLHrElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLHtmlElement extends WP_HTML_Element_Meta {}
class WP_HTMLIElement extends WP_HTML_Element_Meta {}
class WP_HTMLIframeElement extends WP_HTML_Element_Meta {}
class WP_HTMLImgElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLInputElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLInsElement extends WP_HTML_Element_Meta {}
class WP_HTMLKbdElement extends WP_HTML_Element_Meta {}
class WP_HTMLLabelElement extends WP_HTML_Element_Meta {}
class WP_HTMLLegendElement extends WP_HTML_Element_Meta {}
class WP_HTMLLiElement extends WP_HTML_Element_Meta {}
class WP_HTMLLinkElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLMainElement extends WP_HTML_Element_Meta {}
class WP_HTMLMapElement extends WP_HTML_Element_Meta {}
class WP_HTMLMarkElement extends WP_HTML_Element_Meta {}
class WP_HTMLMathElement extends WP_HTML_Element_Meta {}
class WP_HTMLMenuElement extends WP_HTML_Element_Meta {}
class WP_HTMLMetaElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLMeterElement extends WP_HTML_Element_Meta {}
class WP_HTMLNavElement extends WP_HTML_Element_Meta {}
class WP_HTMLNoscriptElement extends WP_HTML_Element_Meta {}
class WP_HTMLObjectElement extends WP_HTML_Element_Meta {}
class WP_HTMLOlElement extends WP_HTML_Element_Meta {}
class WP_HTMLOptgroupElement extends WP_HTML_Element_Meta {}
class WP_HTMLOptionElement extends WP_HTML_Element_Meta {}
class WP_HTMLOutputElement extends WP_HTML_Element_Meta {}
class WP_HTMLPElement extends WP_HTML_Element_Meta {}
class WP_HTMLPictureElement extends WP_HTML_Element_Meta {}
class WP_HTMLPreElement extends WP_HTML_Element_Meta {}
class WP_HTMLProgressElement extends WP_HTML_Element_Meta {}
class WP_HTMLQElement extends WP_HTML_Element_Meta {}
class WP_HTMLRpElement extends WP_HTML_Element_Meta {}
class WP_HTMLRtElement extends WP_HTML_Element_Meta {}
class WP_HTMLRubyElement extends WP_HTML_Element_Meta {}
class WP_HTMLSElement extends WP_HTML_Element_Meta {}
class WP_HTMLSampElement extends WP_HTML_Element_Meta {}
class WP_HTMLScriptElement extends WP_HTML_Element_Meta {}
class WP_HTMLSectionElement extends WP_HTML_Element_Meta {}
class WP_HTMLSelectElement extends WP_HTML_Element_Meta {}
class WP_HTMLSlotElement extends WP_HTML_Element_Meta {}
class WP_HTMLSmallElement extends WP_HTML_Element_Meta {}
class WP_HTMLSourceElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLSpanElement extends WP_HTML_Element_Meta {}
class WP_HTMLStrongElement extends WP_HTML_Element_Meta {}
class WP_HTMLStyleElement extends WP_HTML_Element_Meta {}
class WP_HTMLSubElement extends WP_HTML_Element_Meta {}
class WP_HTMLSummaryElement extends WP_HTML_Element_Meta {}
class WP_HTMLSupElement extends WP_HTML_Element_Meta {}
class WP_HTMLSvgElement extends WP_HTML_Element_Meta {}
class WP_HTMLTableElement extends WP_HTML_Element_Meta {}
class WP_HTMLTbodyElement extends WP_HTML_Element_Meta {}
class WP_HTMLTdElement extends WP_HTML_Element_Meta {}
class WP_HTMLTemplateElement extends WP_HTML_Element_Meta {}
class WP_HTMLTextareaElement extends WP_HTML_Element_Meta {}
class WP_HTMLTfootElement extends WP_HTML_Element_Meta {}
class WP_HTMLThElement extends WP_HTML_Element_Meta {}
class WP_HTMLTheadElement extends WP_HTML_Element_Meta {}
class WP_HTMLTimeElement extends WP_HTML_Element_Meta {}
class WP_HTMLTitleElement extends WP_HTML_Element_Meta {}
class WP_HTMLTrElement extends WP_HTML_Element_Meta {}
class WP_HTMLTrackElement extends WP_HTML_Element_Meta { const is_void = true; }
class WP_HTMLUElement extends WP_HTML_Element_Meta {}
class WP_HTMLUlElement extends WP_HTML_Element_Meta {}
class WP_HTMLVarElement extends WP_HTML_Element_Meta {}
class WP_HTMLVideoElement extends WP_HTML_Element_Meta {}
class WP_HTMLWbrElement extends WP_HTML_Element_Meta { const is_void = true; }
