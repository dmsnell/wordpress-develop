<?php
/**
 * WordPress implementation for PHP functions either missing from older PHP versions or not included by default.
 *
 * @package PHP
 * @access private
 */

// If gettext isn't available
if ( !function_exists('_') ) {
	function _($string) {
		return $string;
	}
}

/**
 * Returns whether PCRE/u (PCRE_UTF8 modifier) is available for use.
 *
 * @ignore
 * @since 4.2.2
 * @access private
 *
 * @staticvar string $utf8_pcre
 *
 * @param bool $set - Used for testing only
 *             null   : default - get PCRE/u capability
 *             false  : Used for testing - return false for future calls to this function
 *             'reset': Used for testing - restore default behavior of this function
 */
function _wp_can_use_pcre_u( $set = null ) {
	static $utf8_pcre = 'reset';

	if ( null !== $set ) {
		$utf8_pcre = $set;
	}

	if ( 'reset' === $utf8_pcre ) {
		$utf8_pcre = @preg_match( '/^./u', 'a' );
	}

	return $utf8_pcre;
}

if ( ! function_exists( 'mb_substr' ) ) :
	/**
	 * Compat function to mimic mb_substr().
	 *
	 * @ignore
	 * @since 3.2.0
	 *
	 * @see _mb_substr()
	 *
	 * @param string      $str      The string to extract the substring from.
	 * @param int         $start    Position to being extraction from in `$str`.
	 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
	 *                              Default null.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return string Extracted substring.
	 */
	function mb_substr( $str, $start, $length = null, $encoding = null ) {
		return _mb_substr( $str, $start, $length, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_substr().
 *
 * Only understands UTF-8 and 8bit.  All other character sets will be treated as 8bit.
 * For $encoding === UTF-8, the $str input is expected to be a valid UTF-8 byte sequence.
 * The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since 3.2.0
 *
 * @param string      $str      The string to extract the substring from.
 * @param int         $start    Position to being extraction from in `$str`.
 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
 *                              Default null.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return string Extracted substring.
 */
function _mb_substr( $str, $start, $length = null, $encoding = null ) {
	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset' );
	}

	/*
	 * The solution below works only for UTF-8, so in case of a different
	 * charset just use built-in substr().
	 */
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) ) {
		return is_null( $length ) ? substr( $str, $start ) : substr( $str, $start, $length );
	}

	if ( _wp_can_use_pcre_u() ) {
		// Use the regex unicode support to separate the UTF-8 characters into an array.
		preg_match_all( '/./us', $str, $match );
		$chars = is_null( $length ) ? array_slice( $match[0], $start ) : array_slice( $match[0], $start, $length );
		return implode( '', $chars );
	}

	$regex = '/(
		  [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

	// Start with 1 element instead of 0 since the first thing we do is pop.
	$chars = array( '' );
	do {
		// We had some string left over from the last round, but we counted it in that last round.
		array_pop( $chars );

		/*
		 * Split by UTF-8 character, limit to 1000 characters (last array element will contain
		 * the rest of the string).
		 */
		$pieces = preg_split( $regex, $str, 1000, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$chars = array_merge( $chars, $pieces );

	// If there's anything left over, repeat the loop.
	} while ( count( $pieces ) > 1 && $str = array_pop( $pieces ) );

	return join( '', array_slice( $chars, $start, $length ) );
}

if ( ! function_exists( 'mb_strlen' ) ) :
	/**
	 * Compat function to mimic mb_strlen().
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @see _mb_strlen()
	 *
	 * @param string      $str      The string to retrieve the character length from.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return int String length of `$str`.
	 */
	function mb_strlen( $str, $encoding = null ) {
		return _mb_strlen( $str, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_strlen().
 *
 * Only understands UTF-8 and 8bit.  All other character sets will be treated as 8bit.
 * For $encoding === UTF-8, the `$str` input is expected to be a valid UTF-8 byte
 * sequence. The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since 4.2.0
 *
 * @param string      $str      The string to retrieve the character length from.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return int String length of `$str`.
 */
function _mb_strlen( $str, $encoding = null ) {
	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset' );
	}

	/*
	 * The solution below works only for UTF-8, so in case of a different charset
	 * just use built-in strlen().
	 */
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) ) {
		return strlen( $str );
	}

	if ( _wp_can_use_pcre_u() ) {
		// Use the regex unicode support to separate the UTF-8 characters into an array.
		preg_match_all( '/./us', $str, $match );
		return count( $match[0] );
	}

	$regex = '/(?:
		  [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

	// Start at 1 instead of 0 since the first thing we do is decrement.
	$count = 1;
	do {
		// We had some string left over from the last round, but we counted it in that last round.
		$count--;

		/*
		 * Split by UTF-8 character, limit to 1000 characters (last array element will contain
		 * the rest of the string).
		 */
		$pieces = preg_split( $regex, $str, 1000 );

		// Increment.
		$count += count( $pieces );

	// If there's anything left over, repeat the loop.
	} while ( $str = array_pop( $pieces ) );

	// Fencepost: preg_split() always returns one extra item in the array.
	return --$count;
}

if ( !function_exists('hash_hmac') ):
/**
 * Compat function to mimic hash_hmac().
 *
 * @ignore
 * @since 3.2.0
 *
 * @see _hash_hmac()
 *
 * @param string $algo       Hash algorithm. Accepts 'md5' or 'sha1'.
 * @param string $data       Data to be hashed.
 * @param string $key        Secret key to use for generating the hash.
 * @param bool   $raw_output Optional. Whether to output raw binary data (true),
 *                           or lowercase hexits (false). Default false.
 * @return string|false The hash in output determined by `$raw_output`. False if `$algo`
 *                      is unknown or invalid.
 */
function hash_hmac($algo, $data, $key, $raw_output = false) {
	return _hash_hmac($algo, $data, $key, $raw_output);
}
endif;

/**
 * Internal compat function to mimic hash_hmac().
 *
 * @ignore
 * @since 3.2.0
 *
 * @param string $algo       Hash algorithm. Accepts 'md5' or 'sha1'.
 * @param string $data       Data to be hashed.
 * @param string $key        Secret key to use for generating the hash.
 * @param bool   $raw_output Optional. Whether to output raw binary data (true),
 *                           or lowercase hexits (false). Default false.
 * @return string|false The hash in output determined by `$raw_output`. False if `$algo`
 *                      is unknown or invalid.
 */
function _hash_hmac($algo, $data, $key, $raw_output = false) {
	$packs = array('md5' => 'H32', 'sha1' => 'H40');

	if ( !isset($packs[$algo]) )
		return false;

	$pack = $packs[$algo];

	if (strlen($key) > 64)
		$key = pack($pack, $algo($key));

	$key = str_pad($key, 64, chr(0));

	$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
	$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

	$hmac = $algo($opad . pack($pack, $algo($ipad . $data)));

	if ( $raw_output )
		return pack( $pack, $hmac );
	return $hmac;
}

if ( !function_exists('json_encode') ) {
	function json_encode( $string ) {
		global $wp_json;

		if ( ! ( $wp_json instanceof Services_JSON ) ) {
			require_once( ABSPATH . WPINC . '/class-json.php' );
			$wp_json = new Services_JSON();
		}

		return $wp_json->encodeUnsafe( $string );
	}
}

if ( !function_exists('json_decode') ) {
	/**
	 * @global Services_JSON $wp_json
	 * @param string $string
	 * @param bool   $assoc_array
	 * @return object|array
	 */
	function json_decode( $string, $assoc_array = false ) {
		global $wp_json;

		if ( ! ($wp_json instanceof Services_JSON ) ) {
			require_once( ABSPATH . WPINC . '/class-json.php' );
			$wp_json = new Services_JSON();
		}

		$res = $wp_json->decode( $string );
		if ( $assoc_array )
			$res = _json_decode_object_helper( $res );
		return $res;
	}

	/**
	 * @param object $data
	 * @return array
	 */
	function _json_decode_object_helper($data) {
		if ( is_object($data) )
			$data = get_object_vars($data);
		return is_array($data) ? array_map(__FUNCTION__, $data) : $data;
	}
}

if ( ! function_exists( 'hash_equals' ) ) :
/**
 * Timing attack safe string comparison
 *
 * Compares two strings using the same time whether they're equal or not.
 *
 * This function was added in PHP 5.6.
 *
 * Note: It can leak the length of a string when arguments of differing length are supplied.
 *
 * @since 3.9.2
 *
 * @param string $a Expected string.
 * @param string $b Actual, user supplied, string.
 * @return bool Whether strings are equal.
 */
function hash_equals( $a, $b ) {
	$a_length = strlen( $a );
	if ( $a_length !== strlen( $b ) ) {
		return false;
	}
	$result = 0;

	// Do not attempt to "optimize" this.
	for ( $i = 0; $i < $a_length; $i++ ) {
		$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
	}

	return $result === 0;
}
endif;

// JSON_PRETTY_PRINT was introduced in PHP 5.4
// Defined here to prevent a notice when using it with wp_json_encode()
if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
	define( 'JSON_PRETTY_PRINT', 128 );
}

if ( ! function_exists( 'json_last_error_msg' ) ) :
	/**
	 * Retrieves the error string of the last json_encode() or json_decode() call.
	 *
	 * @since 4.4.0
	 *
	 * @internal This is a compatibility function for PHP <5.5
	 *
	 * @return bool|string Returns the error message on success, "No Error" if no error has occurred,
	 *                     or false on failure.
	 */
	function json_last_error_msg() {
		// See https://core.trac.wordpress.org/ticket/27799.
		if ( ! function_exists( 'json_last_error' ) ) {
			return false;
		}

		$last_error_code = json_last_error();

		// Just in case JSON_ERROR_NONE is not defined.
		$error_code_none = defined( 'JSON_ERROR_NONE' ) ? JSON_ERROR_NONE : 0;

		switch ( true ) {
			case $last_error_code === $error_code_none:
				return 'No error';

			case defined( 'JSON_ERROR_DEPTH' ) && JSON_ERROR_DEPTH === $last_error_code:
				return 'Maximum stack depth exceeded';

			case defined( 'JSON_ERROR_STATE_MISMATCH' ) && JSON_ERROR_STATE_MISMATCH === $last_error_code:
				return 'State mismatch (invalid or malformed JSON)';

			case defined( 'JSON_ERROR_CTRL_CHAR' ) && JSON_ERROR_CTRL_CHAR === $last_error_code:
				return 'Control character error, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_SYNTAX' ) && JSON_ERROR_SYNTAX === $last_error_code:
				return 'Syntax error';

			case defined( 'JSON_ERROR_UTF8' ) && JSON_ERROR_UTF8 === $last_error_code:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_RECURSION' ) && JSON_ERROR_RECURSION === $last_error_code:
				return 'Recursion detected';

			case defined( 'JSON_ERROR_INF_OR_NAN' ) && JSON_ERROR_INF_OR_NAN === $last_error_code:
				return 'Inf and NaN cannot be JSON encoded';

			case defined( 'JSON_ERROR_UNSUPPORTED_TYPE' ) && JSON_ERROR_UNSUPPORTED_TYPE === $last_error_code:
				return 'Type is not supported';

			default:
				return 'An unknown error occurred';
		}
	}
endif;

if ( ! interface_exists( 'JsonSerializable' ) ) {
	define( 'WP_JSON_SERIALIZE_COMPATIBLE', true );
	/**
	 * JsonSerializable interface.
	 *
	 * Compatibility shim for PHP <5.4
	 *
	 * @link https://secure.php.net/jsonserializable
	 *
	 * @since 4.4.0
	 */
	interface JsonSerializable {
		public function jsonSerialize();
	}
}

// random_int was introduced in PHP 7.0
if ( ! function_exists( 'random_int' ) ) {
	require ABSPATH . WPINC . '/random_compat/random.php';
}

if ( ! function_exists( 'array_replace_recursive' ) ) :
	/**
	 * PHP-agnostic version of {@link array_replace_recursive()}.
	 *
	 * The array_replace_recursive() function is a PHP 5.3 function. WordPress
	 * currently supports down to PHP 5.2, so this method is a workaround
	 * for PHP 5.2.
	 *
	 * Note: array_replace_recursive() supports infinite arguments, but for our use-
	 * case, we only need to support two arguments.
	 *
	 * Subject to removal once WordPress makes PHP 5.3.0 the minimum requirement.
	 *
	 * @since 4.5.3
	 *
	 * @see https://secure.php.net/manual/en/function.array-replace-recursive.php#109390
	 *
	 * @param  array $base         Array with keys needing to be replaced.
	 * @param  array $replacements Array with the replaced keys.
	 *
	 * @return array
	 */
	function array_replace_recursive( $base = array(), $replacements = array() ) {
		foreach ( array_slice( func_get_args(), 1 ) as $replacements ) {
			$bref_stack = array( &$base );
			$head_stack = array( $replacements );

			do {
				end( $bref_stack );

				$bref = &$bref_stack[ key( $bref_stack ) ];
				$head = array_pop( $head_stack );

				unset( $bref_stack[ key( $bref_stack ) ] );

				foreach ( array_keys( $head ) as $key ) {
					if ( isset( $key, $bref ) &&
					     isset( $bref[ $key ] ) && is_array( $bref[ $key ] ) &&
					     isset( $head[ $key ] ) && is_array( $head[ $key ] )
					) {
						$bref_stack[] = &$bref[ $key ];
						$head_stack[] = $head[ $key ];
					} else {
						$bref[ $key ] = $head[ $key ];
					}
				}
			} while ( count( $head_stack ) );
		}

		return $base;
	}
endif;

// SPL can be disabled on PHP 5.2
if ( ! function_exists( 'spl_autoload_register' ) ):
	$_wp_spl_autoloaders = array();

	/**
	 * Autoloader compatibility callback.
	 *
	 * @since 4.6.0
	 *
	 * @param string $classname Class to attempt autoloading.
	 */
	function __autoload( $classname ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as $autoloader ) {
			if ( ! is_callable( $autoloader ) ) {
				// Avoid the extra warning if the autoloader isn't callable.
				continue;
			}

			call_user_func( $autoloader, $classname );

			// If it has been autoloaded, stop processing.
			if ( class_exists( $classname, false ) ) {
				return;
			}
		}
	}

	/**
	 * Registers a function to be autoloaded.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $autoload_function The function to register.
	 * @param bool     $throw             Optional. Whether the function should throw an exception
	 *                                    if the function isn't callable. Default true.
	 * @param bool     $prepend           Whether the function should be prepended to the stack.
	 *                                    Default false.
	 */
	function spl_autoload_register( $autoload_function, $throw = true, $prepend = false ) {
		if ( $throw && ! is_callable( $autoload_function ) ) {
			// String not translated to match PHP core.
			throw new Exception( 'Function not callable' );
		}

		global $_wp_spl_autoloaders;

		// Don't allow multiple registration.
		if ( in_array( $autoload_function, $_wp_spl_autoloaders ) ) {
			return;
		}

		if ( $prepend ) {
			array_unshift( $_wp_spl_autoloaders, $autoload_function );
		} else {
			$_wp_spl_autoloaders[] = $autoload_function;
		}
	}

	/**
	 * Unregisters an autoloader function.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $function The function to unregister.
	 * @return bool True if the function was unregistered, false if it could not be.
	 */
	function spl_autoload_unregister( $function ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as &$autoloader ) {
			if ( $autoloader === $function ) {
				unset( $autoloader );
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the registered autoloader functions.
	 *
	 * @since 4.6.0
	 *
	 * @return array List of autoloader functions.
	 */
	function spl_autoload_functions() {
		return $GLOBALS['_wp_spl_autoloaders'];
	}
endif;

/**
 * Core class used by the HTML tag processor as a data structure for the attribute token,
 * allowing to drastically improve performance.
 *
 * This class is for internal usage of the WP_HTML_Tag_Processor class.
 *
 * @access private
 * @ignore
 *
 * @since 6.2.0
 * @since 6.5.0 Replaced `end` with `length` to more closely match `substr()`.
 *
 * @see WP_HTML_Tag_Processor
 */
class WP_HTML_Attribute_Token {
	/**
	 * Attribute name.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Byte offset in the input HTML where the attribute value starts.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public $value_starts_at;

	/**
	 * How many bytes the value occupies in the input HTML.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public $value_length;

	/**
	 * The string offset where the attribute name starts.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public $start;

	/**
	 * Byte length of text spanning the attribute inside a tag.
	 *
	 * This span starts at the first character of the attribute name
	 * and it ends after one of three cases:
	 *
	 *  - at the end of the attribute name for boolean attributes.
	 *  - at the end of the value for unquoted attributes.
	 *  - at the final single or double quote for quoted attributes.
	 *
	 * Example:
	 *
	 *     <div class="post">
	 *          ------------ length is 12, including quotes
	 *
	 *     <input type="checked" checked id="selector">
	 *                           ------- length is 6
	 *
	 *     <a rel=noopener>
	 *        ------------ length is 11
	 *
	 * @since 6.5.0 Replaced `end` with `length` to more closely match `substr()`.
	 *
	 * @var int
	 */
	public $length;

	/**
	 * Whether the attribute is a boolean attribute with value `true`.
	 *
	 * @since 6.2.0
	 *
	 * @var bool
	 */
	public $is_true;

	/**
	 * Constructor.
	 *
	 * @since 6.2.0
	 * @since 6.5.0 Replaced `end` with `length` to more closely match `substr()`.
	 *
	 * @param string $name Attribute name.
	 * @param int $value_start Byte offset where the attribute value starts.
	 * @param int $value_length Number of bytes attribute value spans.
	 * @param int $start The string offset where the attribute name starts.
	 * @param int $length Byte length of the entire attribute name or name and value pair expression.
	 * @param bool $is_true Whether the attribute is a boolean attribute with true value.
	 */
	public function __construct( $name, $value_start, $value_length, $start, $length, $is_true ) {
		$this->name            = $name;
		$this->value_starts_at = $value_start;
		$this->value_length    = $value_length;
		$this->start           = $start;
		$this->length          = $length;
		$this->is_true         = $is_true;
	}
}

/**
 * Core class used by the HTML API to represent a DOCTYPE declaration.
 *
 * This class parses DOCTYPE tokens for the full parser in the HTML Processor.
 * Most code interacting with HTML won't need to parse DOCTYPE declarations;
 * the HTML Processor is one exception. Consult the HTML Processor for proper
 * parsing of an HTML document.
 *
 * A DOCTYPE declaration may indicate its document compatibility mode, which impacts
 * the structure of the following HTML as well as the behavior of CSS class selectors.
 * There are three possible modes:
 *
 *  - "no-quirks" and "limited-quirks" modes (also called "standards mode").
 *  - "quirks" mode.
 *
 * These modes mostly determine whether CSS class name selectors match values in the
 * HTML `class` attribute in an ASCII-case-insensitive way (quirks mode), or whether
 * they match only when byte-for-byte identical (no-quirks mode).
 *
 * All HTML documents should start with the standard HTML5 DOCTYPE: `<!DOCTYPE html>`.
 *
 * > DOCTYPEs are required for legacy reasons. When omitted, browsers tend to use a different
 * > rendering mode that is incompatible with some specifications. Including the DOCTYPE in a
 * > document ensures that the browser makes a best-effort attempt at following the
 * > relevant specifications.
 *
 * @see https://html.spec.whatwg.org/#the-doctype
 *
 * DOCTYPE declarations comprise four properties: a name, public identifier, system identifier,
 * and an indication of which document compatibility mode they would imply if an HTML parser
 * hadn't already determined it from other information.
 *
 * @see https://html.spec.whatwg.org/#the-initial-insertion-mode
 *
 * Historically, the DOCTYPE declaration was used in SGML documents to instruct a parser how
 * to interpret the various tags and entities within a document. Its role in HTML diverged
 * from how it was used in SGML and no meaning should be back-read into HTML based on how it
 * is used in SGML, XML, or XHTML documents.
 *
 * @see https://www.iso.org/standard/16387.html
 *
 * @since 6.7.0
 * @since 7.1.0 Spec update: missing and empty SYSTEM identifiers are handled
 *              the same for determining the document mode.
 *
 * @access private
 *
 * @see WP_HTML_Processor
 */
class WP_HTML_Doctype_Info {
	/**
	 * Name of the DOCTYPE: should be "html" for HTML documents.
	 *
	 * This value should be considered "read only" and not modified.
	 *
	 * Historically the DOCTYPE name indicates name of the document's root element.
	 *
	 *     <!DOCTYPE html>
	 *               ╰──┴── name is "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $name = null;

	/**
	 * Public identifier of the DOCTYPE.
	 *
	 * This value should be considered "read only" and not modified.
	 *
	 * The public identifier is optional and should not appear in HTML documents.
	 * A `null` value indicates that no public identifier was present in the DOCTYPE.
	 *
	 * Historically the presence of the public identifier indicated that a document
	 * was meant to be shared between computer systems and the value indicated to a
	 * knowledgeable parser how to find the relevant document type definition (DTD).
	 *
	 *     <!DOCTYPE html PUBLIC "public id goes here in quotes">
	 *               │  │         ╰─── public identifier ─────╯
	 *               ╰──┴── name is "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $public_identifier = null;

	/**
	 * System identifier of the DOCTYPE.
	 *
	 * This value should be considered "read only" and not modified.
	 *
	 * The system identifier is optional and should not appear in HTML documents.
	 * A `null` value indicates that no system identifier was present in the DOCTYPE.
	 *
	 * Historically the system identifier specified where a relevant document type
	 * declaration for the given document is stored and may be retrieved.
	 *
	 *     <!DOCTYPE html SYSTEM "system id goes here in quotes">
	 *               │  │         ╰──── system identifier ────╯
	 *               ╰──┴── name is "html".
	 *
	 * If a public identifier were provided it would indicate to a knowledgeable
	 * parser how to interpret the system identifier.
	 *
	 *     <!DOCTYPE html PUBLIC "public id goes here in quotes" "system id goes here in quotes">
	 *               │  │         ╰─── public identifier ─────╯   ╰──── system identifier ────╯
	 *               ╰──┴── name is "html".
	 *
	 * @see https://html.spec.whatwg.org/#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @var string|null
	 */
	public $system_identifier = null;

	/**
	 * Which document compatibility mode this DOCTYPE declaration indicates.
	 *
	 * This value should be considered "read only" and not modified.
	 *
	 * When an HTML parser has not already set the document compatibility mode,
	 * (e.g. "quirks" or "no-quirks" mode), it will be inferred from the properties
	 * of the appropriate DOCTYPE declaration, if one exists. The DOCTYPE can
	 * indicate one of three possible document compatibility modes:
	 * "no-quirks", "limited-quirks", or "quirks".
	 *
	 * Browsers expose the resulting document mode via `document.compatMode`:
	 * - "BackCompat" indicates "quirks" mode.
	 * - "CSS1Compat" indicates "no-quirks" or "limited-quirks" (these modes are not
	 *   distinguished by `document.compatMode`).
	 *
	 * An appropriate DOCTYPE is one encountered in the "initial" insertion mode,
	 * before the HTML element has been opened and before finding any other
	 * DOCTYPE declaration tokens.
	 *
	 * @see https://html.spec.whatwg.org/#the-initial-insertion-mode
	 *
	 * @since 6.7.0
	 *
	 * @var string One of "no-quirks", "limited-quirks", or "quirks".
	 */
	public $indicated_compatibility_mode;

	/**
	 * Constructor.
	 *
	 * This class should not be instantiated directly.
	 * Use the static {@see self::from_doctype_token} method instead.
	 *
	 * The arguments to this constructor correspond to the "DOCTYPE token"
	 * as defined in the HTML specification.
	 *
	 * > DOCTYPE tokens have a name, a public identifier, a system identifier,
	 * > and a force-quirks flag. When a DOCTYPE token is created, its name, public identifier,
	 * > and system identifier must be marked as missing (which is a distinct state from the
	 * > empty string), and the force-quirks flag must be set to off (its other state is on).
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#tokenization
	 *
	 * @since 6.7.0
	 *
	 * @param string|null $name              Name of the DOCTYPE.
	 * @param string|null $public_identifier Public identifier of the DOCTYPE.
	 * @param string|null $system_identifier System identifier of the DOCTYPE.
	 * @param bool        $force_quirks_flag Whether the force-quirks flag is set for the token.
	 */
	private function __construct(
		$name,
		$public_identifier,
		$system_identifier,
		bool $force_quirks_flag
	) {
		$this->name              = $name;
		$this->public_identifier = $public_identifier;
		$this->system_identifier = $system_identifier;

		/*
		 * > If the DOCTYPE token matches one of the conditions in the following list,
		 * > then set the Document to quirks mode:
		 */

		/*
		 * > The force-quirks flag is set to on.
		 */
		if ( $force_quirks_flag ) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * Normative documents will contain the literal `<!DOCTYPE html>` with no
		 * public or system identifiers; short-circuit to avoid extra parsing.
		 */
		if ( 'html' === $name && null === $public_identifier && null === $system_identifier ) {
			$this->indicated_compatibility_mode = 'no-quirks';
			return;
		}

		/*
		 * > The name is not "html".
		 *
		 * The tokenizer must report the name in lower case even if provided in
		 * the document in upper case; thus no conversion is required here.
		 */
		if ( 'html' !== $name ) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * Set up some variables to handle the rest of the conditions.
		 *
		 * > set...the public identifier...to...the empty string if the public identifier was missing.
		 * > set...the system identifier...to...the empty string if the system identifier was missing.
		 * >
		 * > The system identifier and public identifier strings must be compared...
		 * > in an ASCII case-insensitive manner.
		 */
		$public_identifier = null === $public_identifier ? '' : strtolower( $public_identifier );
		$system_identifier = null === $system_identifier ? '' : strtolower( $system_identifier );

		/*
		 * > The public identifier is set to…
		 */
		if (
			'-//w3o//dtd w3 html strict 3.0//en//' === $public_identifier ||
			'-/w3c/dtd html 4.0 transitional/en' === $public_identifier ||
			'html' === $public_identifier
		) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * > The system identifier is set to…
		 */
		if ( 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' === $system_identifier ) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * All of the following conditions depend on matching the public identifier.
		 * If the public identifier is empty, none of the following conditions will match.
		 */
		if ( '' === $public_identifier ) {
			$this->indicated_compatibility_mode = 'no-quirks';
			return;
		}

		/*
		 * > The public identifier starts with…
		 *
		 * @todo Optimize this matching. It shouldn't be a large overall performance issue,
		 *       however, as only a single DOCTYPE declaration token should ever be parsed,
		 *       and normative documents will have exited before reaching this condition.
		 */
		if (
			str_starts_with( $public_identifier, '+//silmaril//dtd html pro v0r11 19970101//' ) ||
			str_starts_with( $public_identifier, '-//as//dtd html 3.0 aswedit + extensions//' ) ||
			str_starts_with( $public_identifier, '-//advasoft ltd//dtd html 3.0 aswedit + extensions//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0 strict//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 2.1e//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.2 final//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3.2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html level 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 0//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 1//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 2//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict level 3//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html strict//' ) ||
			str_starts_with( $public_identifier, '-//ietf//dtd html//' ) ||
			str_starts_with( $public_identifier, '-//metrius//dtd metrius presentational//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 html strict//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 html//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 2.0 tables//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 html strict//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 html//' ) ||
			str_starts_with( $public_identifier, '-//microsoft//dtd internet explorer 3.0 tables//' ) ||
			str_starts_with( $public_identifier, '-//netscape comm. corp.//dtd html//' ) ||
			str_starts_with( $public_identifier, '-//netscape comm. corp.//dtd strict html//' ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html 2.0//" ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html extended 1.0//" ) ||
			str_starts_with( $public_identifier, "-//o'reilly and associates//dtd html extended relaxed 1.0//" ) ||
			str_starts_with( $public_identifier, '-//sq//dtd html 2.0 hotmetal + extensions//' ) ||
			str_starts_with( $public_identifier, '-//softquad software//dtd hotmetal pro 6.0::19990601::extensions to html 4.0//' ) ||
			str_starts_with( $public_identifier, '-//softquad//dtd hotmetal pro 4.0::19971010::extensions to html 4.0//' ) ||
			str_starts_with( $public_identifier, '-//spyglass//dtd html 2.0 extended//' ) ||
			str_starts_with( $public_identifier, '-//sun microsystems corp.//dtd hotjava html//' ) ||
			str_starts_with( $public_identifier, '-//sun microsystems corp.//dtd hotjava strict html//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3 1995-03-24//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2 draft//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2 final//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 3.2s draft//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 4.0 frameset//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html 4.0 transitional//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html experimental 19960712//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd html experimental 970421//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd w3 html//' ) ||
			str_starts_with( $public_identifier, '-//w3o//dtd w3 html 3.0//' ) ||
			str_starts_with( $public_identifier, '-//webtechs//dtd mozilla html 2.0//' ) ||
			str_starts_with( $public_identifier, '-//webtechs//dtd mozilla html//' )
		) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * > The system identifier is missing or the empty string, and the
		 * > public identifier starts with…
		 */
		if (
			'' === $system_identifier && (
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 frameset//' ) ||
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 transitional//' )
			)
		) {
			$this->indicated_compatibility_mode = 'quirks';
			return;
		}

		/*
		 * > Otherwise, if the DOCTYPE token matches one of the conditions in
		 * > the following list, then set the Document to limited-quirks mode.
		 */

		/*
		 * > The public identifier starts with…
		 */
		if (
			str_starts_with( $public_identifier, '-//w3c//dtd xhtml 1.0 frameset//' ) ||
			str_starts_with( $public_identifier, '-//w3c//dtd xhtml 1.0 transitional//' )
		) {
			$this->indicated_compatibility_mode = 'limited-quirks';
			return;
		}

		/*
		 * > The system identifier is neither missing nor the empty string, and the
		 * > public identifier starts with…
		 */
		if (
			'' !== $system_identifier && (
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 frameset//' ) ||
				str_starts_with( $public_identifier, '-//w3c//dtd html 4.01 transitional//' )
			)
		) {
			$this->indicated_compatibility_mode = 'limited-quirks';
			return;
		}

		$this->indicated_compatibility_mode = 'no-quirks';
	}

	/**
	 * Creates a WP_HTML_Doctype_Info instance by parsing a raw DOCTYPE declaration token.
	 *
	 * Use this method to parse a DOCTYPE declaration token and get access to its properties
	 * via the returned WP_HTML_Doctype_Info class instance. The provided input must parse
	 * properly as a DOCTYPE declaration, though it must not represent a valid DOCTYPE.
	 *
	 * Example:
	 *
	 *     // Normative HTML DOCTYPE declaration.
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( '<!DOCTYPE html>' );
	 *     'no-quirks' === $doctype->indicated_compatibility_mode;
	 *
	 *     // A nonsensical DOCTYPE is still valid, and will indicate "quirks" mode.
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( '<!doctypeJSON SILLY "nonsense\'>' );
	 *     'quirks' === $doctype->indicated_compatibility_mode;
	 *
	 *     // Textual quirks present in raw HTML are handled appropriately.
	 *     $doctype = WP_HTML_Doctype_Info::from_doctype_token( "<!DOCTYPE\nhtml\n>" );
	 *     'no-quirks' === $doctype->indicated_compatibility_mode;
	 *
	 *     // Anything other than a proper DOCTYPE declaration token fails to parse.
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( ' <!DOCTYPE>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<!DOCTYPE ><p>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<!TYPEDOC>' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( 'html' );
	 *     null === WP_HTML_Doctype_Info::from_doctype_token( '<?xml version="1.0" encoding="UTF-8" ?>' );
	 *
	 * @since 6.7.0
	 *
	 * @param string $doctype_html The complete raw DOCTYPE HTML string, e.g. `<!DOCTYPE html>`.
	 *
	 * @return WP_HTML_Doctype_Info|null A WP_HTML_Doctype_Info instance will be returned if the
	 *                                   provided DOCTYPE HTML is a valid DOCTYPE. Otherwise, null.
	 */
	public static function from_doctype_token( string $doctype_html ) {
		$doctype_name      = null;
		$doctype_public_id = null;
		$doctype_system_id = null;

		$end = strlen( $doctype_html ) - 1;

		/*
		 * This parser combines the rules for parsing DOCTYPE tokens found in the HTML
		 * specification for the DOCTYPE related tokenizer states.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-state
		 */

		/*
		 * - Valid DOCTYPE HTML token must be at least `<!DOCTYPE>` assuming a complete token not
		 *   ending in end-of-file.
		 * - It must start with an ASCII case-insensitive match for `<!DOCTYPE`.
		 * - The only occurrence of `>` must be the final byte in the HTML string.
		 */
		if (
			$end < 9 ||
			0 !== substr_compare( $doctype_html, '<!DOCTYPE', 0, 9, true )
		) {
			return null;
		}

		$at = 9;
		// Is there one and only one `>`?
		if ( '>' !== $doctype_html[ $end ] || ( strcspn( $doctype_html, '>', $at ) + $at ) < $end ) {
			return null;
		}

		/*
		 * Perform newline normalization and ensure the $end value is correct after normalization.
		 *
		 * @see https://html.spec.whatwg.org/#preprocessing-the-input-stream
		 * @see https://infra.spec.whatwg.org/#normalize-newlines
		 */
		$doctype_html = str_replace( "\r\n", "\n", $doctype_html );
		$doctype_html = str_replace( "\r", "\n", $doctype_html );
		$end          = strlen( $doctype_html ) - 1;

		/*
		 * In this state, the doctype token has been found and its "content" optionally including the
		 * name, public identifier, and system identifier is between the current position and the end.
		 *
		 *     "<!DOCTYPE...declaration...>"
		 *               ╰─ $at           ╰─ $end
		 *
		 * It's also possible that the declaration part is empty.
		 *
		 *               ╭─ $at
		 *     "<!DOCTYPE>"
		 *               ╰─ $end
		 *
		 * Rules for parsing ">" which terminates the DOCTYPE do not need to be considered as they
		 * have been handled above in the condition that the provided DOCTYPE HTML must contain
		 * exactly one ">" character in the final position.
		 */

		/*
		 *
		 * Parsing effectively begins in "Before DOCTYPE name state". Ignore whitespace and
		 * proceed to the next state.
		 *
		 * @see https://html.spec.whatwg.org/#before-doctype-name-state
		 */
		$at += strspn( $doctype_html, " \t\n\f\r", $at );

		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		$name_length  = strcspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		$doctype_name = str_replace( "\0", "\u{FFFD}", strtolower( substr( $doctype_html, $at, $name_length ) ) );

		$at += $name_length;
		$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
		}

		/*
		 * "After DOCTYPE name state"
		 *
		 * Find a case-insensitive match for "PUBLIC" or "SYSTEM" at this point.
		 * Otherwise, set force-quirks and enter bogus DOCTYPE state (skip the rest of the doctype).
		 *
		 * @see https://html.spec.whatwg.org/#after-doctype-name-state
		 */
		if ( $at + 6 >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		/*
		 * > If the six characters starting from the current input character are an ASCII
		 * > case-insensitive match for the word "PUBLIC", then consume those characters
		 * > and switch to the after DOCTYPE public keyword state.
		 */
		if ( 0 === substr_compare( $doctype_html, 'PUBLIC', $at, 6, true ) ) {
			$at += 6;
			$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
			if ( $at >= $end ) {
				return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
			}
			goto parse_doctype_public_identifier;
		}

		/*
		 * > Otherwise, if the six characters starting from the current input character are an ASCII
		 * > case-insensitive match for the word "SYSTEM", then consume those characters and switch
		 * > to the after DOCTYPE system keyword state.
		 */
		if ( 0 === substr_compare( $doctype_html, 'SYSTEM', $at, 6, true ) ) {
			$at += 6;
			$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
			if ( $at >= $end ) {
				return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
			}
			goto parse_doctype_system_identifier;
		}

		/*
		 * > Otherwise, this is an invalid-character-sequence-after-doctype-name parse error.
		 * > Set the current DOCTYPE token's force-quirks flag to on. Reconsume in the bogus
		 * > DOCTYPE state.
		 */
		return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );

		parse_doctype_public_identifier:
		/*
		 * The parser should enter "DOCTYPE public identifier (double-quoted) state" or
		 * "DOCTYPE public identifier (single-quoted) state" by finding one of the valid quotes.
		 * Anything else forces quirks mode and ignores the rest of the contents.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-public-identifier-(double-quoted)-state
		 * @see https://html.spec.whatwg.org/#doctype-public-identifier-(single-quoted)-state
		 */
		$closer_quote = $doctype_html[ $at ];

		/*
		 * > This is a missing-quote-before-doctype-public-identifier parse error. Set the
		 * > current DOCTYPE token's force-quirks flag to on. Reconsume in the bogus DOCTYPE state.
		 */
		if ( '"' !== $closer_quote && "'" !== $closer_quote ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		$identifier_length = strcspn( $doctype_html, $closer_quote, $at, $end - $at );
		$doctype_public_id = str_replace( "\0", "\u{FFFD}", substr( $doctype_html, $at, $identifier_length ) );

		$at += $identifier_length;
		if ( $at >= $end || $closer_quote !== $doctype_html[ $at ] ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		/*
		 * "Between DOCTYPE public and system identifiers state"
		 *
		 * Advance through whitespace between public and system identifiers.
		 *
		 * @see https://html.spec.whatwg.org/#between-doctype-public-and-system-identifiers-state
		 */
		$at += strspn( $doctype_html, " \t\n\f\r", $at, $end - $at );
		if ( $at >= $end ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
		}

		parse_doctype_system_identifier:
		/*
		 * The parser should enter "DOCTYPE system identifier (double-quoted) state" or
		 * "DOCTYPE system identifier (single-quoted) state" by finding one of the valid quotes.
		 * Anything else forces quirks mode and ignores the rest of the contents.
		 *
		 * @see https://html.spec.whatwg.org/#doctype-system-identifier-(double-quoted)-state
		 * @see https://html.spec.whatwg.org/#doctype-system-identifier-(single-quoted)-state
		 */
		$closer_quote = $doctype_html[ $at ];

		/*
		 * > This is a missing-quote-before-doctype-system-identifier parse error. Set the
		 * > current DOCTYPE token's force-quirks flag to on. Reconsume in the bogus DOCTYPE state.
		 */
		if ( '"' !== $closer_quote && "'" !== $closer_quote ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		++$at;

		$identifier_length = strcspn( $doctype_html, $closer_quote, $at, $end - $at );
		$doctype_system_id = str_replace( "\0", "\u{FFFD}", substr( $doctype_html, $at, $identifier_length ) );

		$at += $identifier_length;
		if ( $at >= $end || $closer_quote !== $doctype_html[ $at ] ) {
			return new self( $doctype_name, $doctype_public_id, $doctype_system_id, true );
		}

		return new self( $doctype_name, $doctype_public_id, $doctype_system_id, false );
	}
}

/**
 * Core class used by the HTML tag processor to represent a textual span
 * inside an HTML document.
 *
 * This is a two-tuple in disguise, used to avoid the memory overhead
 * involved in using an array for the same purpose.
 *
 * This class is for internal usage of the WP_HTML_Tag_Processor class.
 *
 * @access private
 * @since 6.2.0
 * @since 6.5.0 Replaced `end` with `length` to more closely align with `substr()`.
 *
 * @see WP_HTML_Tag_Processor
 */
class WP_HTML_Span {
	/**
	 * Byte offset into document where span begins.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public $start;

	/**
	 * Byte length of this span.
	 *
	 * @since 6.5.0
	 *
	 * @var int
	 */
	public $length;

	/**
	 * Constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param int $start Byte offset into document where replacement span begins.
	 * @param int $length Byte length of span.
	 */
	public function __construct( $start, $length ) {
		$this->start  = $start;
		$this->length = $length;
	}
}

/**
 * Core class used by the HTML tag processor as a data structure for replacing
 * existing content from start to end, allowing to drastically improve performance.
 *
 * This class is for internal usage of the WP_HTML_Tag_Processor class.
 *
 * @access private
 * @since 6.2.0
 * @since 6.5.0 Replace `end` with `length` to more closely match `substr()`.
 *
 * @see WP_HTML_Tag_Processor
 */
class WP_HTML_Text_Replacement {
	/**
	 * Byte offset into document where replacement span begins.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public $start;

	/**
	 * Byte length of span being replaced.
	 *
	 * @since 6.5.0
	 *
	 * @var int
	 */
	public $length;

	/**
	 * Span of text to insert in document to replace existing content from start to end.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public $text;

	/**
	 * Constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param int $start Byte offset into document where replacement span begins.
	 * @param int $length Byte length of span in document being replaced.
	 * @param string $text Span of text to insert in document to replace existing content from start to end.
	 */
	public function __construct( $start, $length, $text ) {
		$this->start  = $start;
		$this->length = $length;
		$this->text   = $text;
	}
}

/**
 * WP_Token_Map class.
 *
 * Use this class in specific circumstances with a static set of lookup keys which map to
 * a static set of transformed values. For example, this class is used to map HTML named
 * character references to their equivalent UTF-8 values.
 *
 * This class works differently than code calling `in_array()` and other methods. It
 * internalizes lookup logic and provides helper interfaces to optimize lookup and
 * transformation. It provides a method for precomputing the lookup tables and storing
 * them as PHP source code.
 *
 * All tokens and substitutions must be shorter than 256 bytes.
 *
 * Example:
 *
 *     $smilies = WP_Token_Map::from_array( array(
 *         '8O' => '😯',
 *         ':(' => '🙁',
 *         ':)' => '🙂',
 *         ':?' => '😕',
 *      ) );
 *
 *      true  === $smilies->contains( ':)' );
 *      false === $smilies->contains( 'simile' );
 *
 *      '😕' === $smilies->read_token( 'Not sure :?.', 9, $length_of_smily_syntax );
 *      2    === $length_of_smily_syntax;
 *
 * ## Precomputing the Token Map.
 *
 * Creating the class involves some work sorting and organizing the tokens and their
 * replacement values. In order to skip this, it's possible for the class to export
 * its state and be used as actual PHP source code.
 *
 * Example:
 *
 *      // Export with four spaces as the indent, only for the sake of this docblock.
 *      // The default indent is a tab character.
 *      $indent = '    ';
 *      echo $smilies->precomputed_php_source_table( $indent );
 *
 *      // Output, to be pasted into a PHP source file:
 *      WP_Token_Map::from_precomputed_table(
 *          array(
 *              "storage_version" => "6.6.0",
 *              "key_length" => 2,
 *              "groups" => "",
 *              "long_words" => array(),
 *              "small_words" => "8O\x00:)\x00:(\x00:?\x00",
 *              "small_mappings" => array( "😯", "🙂", "🙁", "😕" )
 *          )
 *      );
 *
 * ## Large vs. small words.
 *
 * This class uses a short prefix called the "key" to optimize lookup of its tokens.
 * This means that some tokens may be shorter than or equal in length to that key.
 * Those words that are longer than the key are called "large" while those shorter
 * than or equal to the key length are called "small."
 *
 * This separation of large and small words is incidental to the way this class
 * optimizes lookup, and should be considered an internal implementation detail
 * of the class. It may still be important to be aware of it, however.
 *
 * ## Determining Key Length.
 *
 * The choice of the size of the key length should be based on the data being stored in
 * the token map. It should divide the data as evenly as possible, but should not create
 * so many groups that a large fraction of the groups only contain a single token.
 *
 * For the HTML5 named character references, a key length of 2 was found to provide a
 * sufficient spread and should be a good default for relatively large sets of tokens.
 *
 * However, for some data sets this might be too long. For example, a list of smilies
 * may be too small for a key length of 2. Perhaps 1 would be more appropriate. It's
 * best to experiment and determine empirically which values are appropriate.
 *
 * ## Generate Pre-Computed Source Code.
 *
 * Since the `WP_Token_Map` is designed for relatively static lookups, it can be
 * advantageous to precompute the values and instantiate a table that has already
 * sorted and grouped the tokens and built the lookup strings.
 *
 * This can be done with `WP_Token_Map::precomputed_php_source_table()`.
 *
 * Note that if there is a leading character that all tokens need, such as `&` for
 * HTML named character references, it can be beneficial to exclude this from the
 * token map. Instead, find occurrences of the leading character and then use the
 * token map to see if the following characters complete the token.
 *
 * Example:
 *
 *     $map = WP_Token_Map::from_array( array( 'simple_smile:' => '🙂', 'sob:' => '😭', 'soba:' => '🍜' ) );
 *     echo $map->precomputed_php_source_table();
 *     // Output
 *     WP_Token_Map::from_precomputed_table(
 *         array(
 *             "storage_version" => "6.6.0",
 *             "key_length" => 2,
 *             "groups" => "si\x00so\x00",
 *             "long_words" => array(
 *                 // simple_smile:[🙂].
 *                 "\x0bmple_smile:\x04🙂",
 *                 // soba:[🍜] sob:[😭].
 *                 "\x03ba:\x04🍜\x02b:\x04😭",
 *             ),
 *             "short_words" => "",
 *             "short_mappings" => array()
 *         }
 *     );
 *
 * This precomputed value can be stored directly in source code and will skip the
 * startup cost of generating the lookup strings. See `$html5_named_character_entities`.
 *
 * Note that any updates to the precomputed format should update the storage version
 * constant. It would also be best to provide an update function to take older known
 * versions and upgrade them in place when loading into `from_precomputed_table()`.
 *
 * ## Future Direction.
 *
 * It may be viable to dynamically increase the length limits such that there's no need to impose them.
 * The limit appears because of the packing structure, which indicates how many bytes each segment of
 * text in the lookup tables spans. If, however, care were taken to track the longest word length, then
 * the packing structure could change its representation to allow for that. Each additional byte storing
 * length, however, increases the memory overhead and lookup runtime.
 *
 * An alternative approach could be to borrow the UTF-8 variable-length encoding and store lengths of less
 * than 127 as a single byte with the high bit unset, storing longer lengths as the combination of
 * continuation bytes.
 *
 * Since it has not been shown during the development of this class that longer strings are required, this
 * update is deferred until such a need is clear.
 *
 * @since 6.6.0
 */
class WP_Token_Map {
	/**
	 * Denotes the version of the code which produces pre-computed source tables.
	 *
	 * This version will be used not only to verify pre-computed data, but also
	 * to upgrade pre-computed data from older versions. Choosing a name that
	 * corresponds to the WordPress release will help people identify where an
	 * old copy of data came from.
	 */
	const STORAGE_VERSION = '6.6.0-trunk';

	/**
	 * Maximum length for each key and each transformed value in the table (in bytes).
	 *
	 * @since 6.6.0
	 */
	const MAX_LENGTH = 256;

	/**
	 * How many bytes of each key are used to form a group key for lookup.
	 * This also determines whether a word is considered short or long.
	 *
	 * @since 6.6.0
	 *
	 * @var int
	 */
	private $key_length = 2;

	/**
	 * Stores an optimized form of the word set, where words are grouped
	 * by a prefix of the `$key_length` and then collapsed into a string.
	 *
	 * In each group, the keys and lookups form a packed data structure.
	 * The keys in the string are stripped of their "group key," which is
	 * the prefix of length `$this->key_length` shared by all of the items
	 * in the group. Each word in the string is prefixed by a single byte
	 * whose raw unsigned integer value represents how many bytes follow.
	 *
	 *     ┌────────────────┬───────────────┬─────────────────┬────────┐
	 *     │ Length of rest │ Rest of key   │ Length of value │ Value  │
	 *     │ of key (bytes) │               │ (bytes)         │        │
	 *     ├────────────────┼───────────────┼─────────────────┼────────┤
	 *     │ 0x08           │ nterDot;      │ 0x02            │ ·      │
	 *     └────────────────┴───────────────┴─────────────────┴────────┘
	 *
	 * In this example, the key `CenterDot;` has a group key `Ce`, leaving
	 * eight bytes for the rest of the key, `nterDot;`, and two bytes for
	 * the transformed value `·` (or U+B7 or "\xC2\xB7").
	 *
	 * Example:
	 *
	 *    // Stores array( 'CenterDot;' => '·', 'Cedilla;' => '¸' ).
	 *    $groups      = "Ce\x00";
	 *    $large_words = array( "\x08nterDot;\x02·\x06dilla;\x02¸" )
	 *
	 * The prefixes appear in the `$groups` string, each followed by a null
	 * byte. This makes for quick lookup of where in the group string the key
	 * is found, and then a simple division converts that offset into the index
	 * in the `$large_words` array where the group string is to be found.
	 *
	 * This lookup data structure is designed to optimize cache locality and
	 * minimize indirect memory reads when matching strings in the set.
	 *
	 * @since 6.6.0
	 *
	 * @var array
	 */
	private $large_words = array();

	/**
	 * Stores the group keys for sequential string lookup.
	 *
	 * The offset into this string where the group key appears corresponds with the index
	 * into the group array where the rest of the group string appears. This is an optimization
	 * to improve cache locality while searching and minimize indirect memory accesses.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	private $groups = '';

	/**
	 * Stores an optimized row of small words, where every entry is
	 * `$this->key_size + 1` bytes long and zero-extended.
	 *
	 * This packing allows for direct lookup of a short word followed
	 * by the null byte, if extended to `$this->key_size + 1`.
	 *
	 * Example:
	 *
	 *     // Stores array( 'GT', 'LT', 'gt', 'lt' ).
	 *     "GT\x00LT\x00gt\x00lt\x00"
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	private $small_words = '';

	/**
	 * Replacements for the small words, in the same order they appear.
	 *
	 * With the position of a small word it's possible to index the translation
	 * directly, as its position in the `$small_words` string corresponds to
	 * the index of the replacement in the `$small_mapping` array.
	 *
	 * Example:
	 *
	 *     array( '>', '<', '>', '<' )
	 *
	 * @since 6.6.0
	 *
	 * @var string[]
	 */
	private $small_mappings = array();

	/**
	 * Create a token map using an associative array of key/value pairs as the input.
	 *
	 * Example:
	 *
	 *     $smilies = WP_Token_Map::from_array( array(
	 *          '8O' => '😯',
	 *          ':(' => '🙁',
	 *          ':)' => '🙂',
	 *          ':?' => '😕',
	 *       ) );
	 *
	 * @since 6.6.0
	 *
	 * @param array $mappings   The keys transform into the values, both are strings.
	 * @param int   $key_length Determines the group key length. Leave at the default value
	 *                          of 2 unless there's an empirical reason to change it.
	 *
	 * @return WP_Token_Map|null Token map, unless unable to create it.
	 */
	public static function from_array( array $mappings, int $key_length = 2 ) {
		$map             = new WP_Token_Map();
		$map->key_length = $key_length;

		// Start by grouping words.

		$groups = array();
		$shorts = array();
		foreach ( $mappings as $word => $mapping ) {
			if (
				self::MAX_LENGTH <= strlen( $word ) ||
				self::MAX_LENGTH <= strlen( $mapping )
			) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: 1: maximum byte length (a count) */
						__( 'Token Map tokens and substitutions must all be shorter than %1$d bytes.' ),
						self::MAX_LENGTH
					),
					'6.6.0'
				);
				return null;
			}

			$length = strlen( $word );

			if ( $key_length >= $length ) {
				$shorts[] = $word;
			} else {
				$group = substr( $word, 0, $key_length );

				if ( ! isset( $groups[ $group ] ) ) {
					$groups[ $group ] = array();
				}

				$groups[ $group ][] = array( substr( $word, $key_length ), $mapping );
			}
		}

		/*
		 * Sort the words to ensure that no smaller substring of a match masks the full match.
		 * For example, `Cap` should not match before `CapitalDifferentialD`.
		 */
		usort( $shorts, 'WP_Token_Map::longest_first_then_alphabetical' );
		foreach ( $groups as $group_key => $group ) {
			usort(
				$groups[ $group_key ],
				static function ( array $a, array $b ): int {
					return self::longest_first_then_alphabetical( $a[0], $b[0] );
				}
			);
		}

		// Finally construct the optimized lookups.

		foreach ( $shorts as $word ) {
			$map->small_words     .= str_pad( $word, $key_length + 1, "\x00", STR_PAD_RIGHT );
			$map->small_mappings[] = $mappings[ $word ];
		}

		$group_keys = array_keys( $groups );
		sort( $group_keys );

		foreach ( $group_keys as $group ) {
			$map->groups .= "{$group}\x00";

			$group_string = '';

			foreach ( $groups[ $group ] as $group_word ) {
				list( $word, $mapping ) = $group_word;

				$word_length    = pack( 'C', strlen( $word ) );
				$mapping_length = pack( 'C', strlen( $mapping ) );
				$group_string  .= "{$word_length}{$word}{$mapping_length}{$mapping}";
			}

			$map->large_words[] = $group_string;
		}

		return $map;
	}

	/**
	 * Creates a token map from a pre-computed table.
	 * This skips the initialization cost of generating the table.
	 *
	 * This function should only be used to load data created with
	 * WP_Token_Map::precomputed_php_source_tag().
	 *
	 * @since 6.6.0
	 *
	 * @param array $state {
	 *     Stores pre-computed state for directly loading into a Token Map.
	 *
	 *     @type string $storage_version Which version of the code produced this state.
	 *     @type int    $key_length      Group key length.
	 *     @type string $groups          Group lookup index.
	 *     @type array  $large_words     Large word groups and packed strings.
	 *     @type string $small_words     Small words packed string.
	 *     @type array  $small_mappings  Small word mappings.
	 * }
	 *
	 * @return WP_Token_Map Map with precomputed data loaded.
	 */
	public static function from_precomputed_table( $state ) {
		$has_necessary_state = isset(
			$state['storage_version'],
			$state['key_length'],
			$state['groups'],
			$state['large_words'],
			$state['small_words'],
			$state['small_mappings']
		);

		if ( ! $has_necessary_state ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'Missing required inputs to pre-computed WP_Token_Map.' ),
				'6.6.0'
			);
			return null;
		}

		if ( self::STORAGE_VERSION !== $state['storage_version'] ) {
			_doing_it_wrong(
				__METHOD__,
				/* translators: 1: version string, 2: version string. */
				sprintf( __( 'Loaded version \'%1$s\' incompatible with expected version \'%2$s\'.' ), $state['storage_version'], self::STORAGE_VERSION ),
				'6.6.0'
			);
			return null;
		}

		$map = new WP_Token_Map();

		$map->key_length     = $state['key_length'];
		$map->groups         = $state['groups'];
		$map->large_words    = $state['large_words'];
		$map->small_words    = $state['small_words'];
		$map->small_mappings = $state['small_mappings'];

		return $map;
	}

	/**
	 * Indicates if a given word is a lookup key in the map.
	 *
	 * Example:
	 *
	 *     true  === $smilies->contains( ':)' );
	 *     false === $smilies->contains( 'simile' );
	 *
	 * @since 6.6.0
	 *
	 * @param string $word             Determine if this word is a lookup key in the map.
	 * @param string $case_sensitivity Optional. Pass 'ascii-case-insensitive' to ignore ASCII case when matching. Default 'case-sensitive'.
	 * @return bool Whether there's an entry for the given word in the map.
	 */
	public function contains( string $word, string $case_sensitivity = 'case-sensitive' ): bool {
		if ( str_contains( $word, "\x00" ) ) {
			return false;
		}

		$ignore_case = 'ascii-case-insensitive' === $case_sensitivity;

		if ( $this->key_length >= strlen( $word ) ) {
			if ( 0 === strlen( $this->small_words ) ) {
				return false;
			}

			$term    = str_pad( $word, $this->key_length + 1, "\x00", STR_PAD_RIGHT );
			$word_at = $ignore_case ? stripos( $this->small_words, $term ) : strpos( $this->small_words, $term );
			if ( false === $word_at ) {
				return false;
			}

			return true;
		}

		$group_key = substr( $word, 0, $this->key_length );
		$group_at  = $ignore_case ? stripos( $this->groups, $group_key ) : strpos( $this->groups, $group_key );
		if ( false === $group_at ) {
			return false;
		}
		$group        = $this->large_words[ $group_at / ( $this->key_length + 1 ) ];
		$group_length = strlen( $group );
		$slug         = substr( $word, $this->key_length );
		$length       = strlen( $slug );
		$at           = 0;

		while ( $at < $group_length ) {
			$token_length   = unpack( 'C', $group[ $at++ ] )[1];
			$token_at       = $at;
			$at            += $token_length;
			$mapping_length = unpack( 'C', $group[ $at++ ] )[1];
			$mapping_at     = $at;

			if ( $token_length === $length && 0 === substr_compare( $group, $slug, $token_at, $token_length, $ignore_case ) ) {
				return true;
			}

			$at = $mapping_at + $mapping_length;
		}

		return false;
	}

	/**
	 * If the text starting at a given offset is a lookup key in the map,
	 * return the corresponding transformation from the map, else `false`.
	 *
	 * This function returns the translated string, but accepts an optional
	 * parameter `$matched_token_byte_length`, which communicates how many
	 * bytes long the lookup key was, if it found one. This can be used to
	 * advance a cursor in calling code if a lookup key was found.
	 *
	 * Example:
	 *
	 *     false === $smilies->read_token( 'Not sure :?.', 0, $token_byte_length );
	 *     '😕'  === $smilies->read_token( 'Not sure :?.', 9, $token_byte_length );
	 *     2     === $token_byte_length;
	 *
	 * Example:
	 *
	 *     while ( $at < strlen( $input ) ) {
	 *         $next_at = strpos( $input, ':', $at );
	 *         if ( false === $next_at ) {
	 *             break;
	 *         }
	 *
	 *         $smily = $smilies->read_token( $input, $next_at, $token_byte_length );
	 *         if ( false === $next_at ) {
	 *             ++$at;
	 *             continue;
	 *         }
	 *
	 *         $prefix  = substr( $input, $at, $next_at - $at );
	 *         $at     += $token_byte_length;
	 *         $output .= "{$prefix}{$smily}";
	 *     }
	 *
	 * @since 6.6.0
	 *
	 * @param string   $text                       String in which to search for a lookup key.
	 * @param int      $offset                     Optional. How many bytes into the string where the lookup key ought to start. Default 0.
	 * @param int|null &$matched_token_byte_length Optional. Holds byte-length of found token matched, otherwise not set. Default null.
	 * @param string   $case_sensitivity           Optional. Pass 'ascii-case-insensitive' to ignore ASCII case when matching. Default 'case-sensitive'.
	 *
	 * @return string|null Mapped value of lookup key if found, otherwise `null`.
	 */
	public function read_token( string $text, int $offset = 0, &$matched_token_byte_length = null, $case_sensitivity = 'case-sensitive' ) {
		$ignore_case = 'ascii-case-insensitive' === $case_sensitivity;
		$text_length = strlen( $text );

		// Search for a long word first, if the text is long enough, and if that fails, a short one.
		if ( $text_length > $this->key_length ) {
			/*
			 * Keys cannot contain null bytes, which is taken care of for the full words,
			 * but here it’s required to reject group keys with null bytes so that the
			 * lookup doesn’t get off track when scanning the group string.
			 */
			if ( strcspn( $text, "\x00", $offset, $this->key_length ) < $this->key_length ) {
				return null;
			}

			$group_key = substr( $text, $offset, $this->key_length );
			$group_at  = $ignore_case ? stripos( $this->groups, $group_key ) : strpos( $this->groups, $group_key );
			if ( false === $group_at ) {
				// Perhaps a short word then.
				return strlen( $this->small_words ) > 0
					? $this->read_small_token( $text, $offset, $matched_token_byte_length, $case_sensitivity )
					: null;
			}

			$group        = $this->large_words[ $group_at / ( $this->key_length + 1 ) ];
			$group_length = strlen( $group );
			$at           = 0;
			while ( $at < $group_length ) {
				$token_length   = unpack( 'C', $group[ $at++ ] )[1];
				$token          = substr( $group, $at, $token_length );
				$at            += $token_length;
				$mapping_length = unpack( 'C', $group[ $at++ ] )[1];
				$mapping_at     = $at;

				if ( 0 === substr_compare( $text, $token, $offset + $this->key_length, $token_length, $ignore_case ) ) {
					$matched_token_byte_length = $this->key_length + $token_length;
					return substr( $group, $mapping_at, $mapping_length );
				}

				$at = $mapping_at + $mapping_length;
			}
		}

		// Perhaps a short word then.
		return strlen( $this->small_words ) > 0
			? $this->read_small_token( $text, $offset, $matched_token_byte_length, $case_sensitivity )
			: null;
	}

	/**
	 * Finds a match for a short word at the index.
	 *
	 * @since 6.6.0
	 *
	 * @param string   $text                       String in which to search for a lookup key.
	 * @param int      $offset                     Optional. How many bytes into the string where the lookup key ought to start. Default 0.
	 * @param int|null &$matched_token_byte_length Optional. Holds byte-length of found lookup key if matched, otherwise not set. Default null.
	 * @param string   $case_sensitivity           Optional. Pass 'ascii-case-insensitive' to ignore ASCII case when matching. Default 'case-sensitive'.
	 *
	 * @return string|null Mapped value of lookup key if found, otherwise `null`.
	 */
	private function read_small_token( string $text, int $offset = 0, &$matched_token_byte_length = null, $case_sensitivity = 'case-sensitive' ) {
		$ignore_case  = 'ascii-case-insensitive' === $case_sensitivity;
		$small_length = strlen( $this->small_words );
		$search_text  = substr( $text, $offset, $this->key_length );
		if ( $ignore_case ) {
			$search_text = strtoupper( $search_text );
		}
		$starting_char = $search_text[0];

		$at = 0;
		while ( $at < $small_length ) {
			if (
				$starting_char !== $this->small_words[ $at ] &&
				( ! $ignore_case || strtoupper( $this->small_words[ $at ] ) !== $starting_char )
			) {
				$at += $this->key_length + 1;
				continue;
			}

			for ( $adjust = 1; $adjust < $this->key_length; $adjust++ ) {
				if ( "\x00" === $this->small_words[ $at + $adjust ] ) {
					$matched_token_byte_length = $adjust;
					return $this->small_mappings[ $at / ( $this->key_length + 1 ) ];
				}

				if (
					$search_text[ $adjust ] !== $this->small_words[ $at + $adjust ] &&
					( ! $ignore_case || strtoupper( $this->small_words[ $at + $adjust ] !== $search_text[ $adjust ] ) )
				) {
					$at += $this->key_length + 1;
					continue 2;
				}
			}

			$matched_token_byte_length = $adjust;
			return $this->small_mappings[ $at / ( $this->key_length + 1 ) ];
		}

		return null;
	}

	/**
	 * Exports the token map into an associate array of key/value pairs.
	 *
	 * Example:
	 *
	 *     $smilies->to_array() === array(
	 *         '8O' => '😯',
	 *         ':(' => '🙁',
	 *         ':)' => '🙂',
	 *         ':?' => '😕',
	 *     );
	 *
	 * @return array The lookup key/substitution values as an associate array.
	 */
	public function to_array(): array {
		$tokens = array();

		$at            = 0;
		$small_mapping = 0;
		$small_length  = strlen( $this->small_words );
		while ( $at < $small_length ) {
			$key            = rtrim( substr( $this->small_words, $at, $this->key_length + 1 ), "\x00" );
			$value          = $this->small_mappings[ $small_mapping++ ];
			$tokens[ $key ] = $value;

			$at += $this->key_length + 1;
		}

		foreach ( $this->large_words as $index => $group ) {
			$prefix       = substr( $this->groups, $index * ( $this->key_length + 1 ), 2 );
			$group_length = strlen( $group );
			$at           = 0;
			while ( $at < $group_length ) {
				$length = unpack( 'C', $group[ $at++ ] )[1];
				$key    = $prefix . substr( $group, $at, $length );

				$at    += $length;
				$length = unpack( 'C', $group[ $at++ ] )[1];
				$value  = substr( $group, $at, $length );

				$tokens[ $key ] = $value;
				$at            += $length;
			}
		}

		return $tokens;
	}

	/**
	 * Export the token map for quick loading in PHP source code.
	 *
	 * This function has a specific purpose, to make loading of static token maps fast.
	 * It's used to ensure that the HTML character reference lookups add a minimal cost
	 * to initializing the PHP process.
	 *
	 * Example:
	 *
	 *     echo $smilies->precomputed_php_source_table();
	 *
	 *     // Output.
	 *     WP_Token_Map::from_precomputed_table(
	 *         array(
	 *             "storage_version" => "6.6.0",
	 *             "key_length" => 2,
	 *             "groups" => "",
	 *             "long_words" => array(),
	 *             "small_words" => "8O\x00:)\x00:(\x00:?\x00",
	 *             "small_mappings" => array( "😯", "🙂", "🙁", "😕" )
	 *         )
	 *     );
	 *
	 * @since 6.6.0
	 *
	 * @param string $indent Optional. Use this string for indentation, or rely on the default horizontal tab character. Default "\t".
	 * @return string Value which can be pasted into a PHP source file for quick loading of table.
	 */
	public function precomputed_php_source_table( string $indent = "\t" ): string {
		$i1 = $indent;
		$i2 = $i1 . $indent;
		$i3 = $i2 . $indent;

		$class_version = self::STORAGE_VERSION;

		$output  = self::class . "::from_precomputed_table(\n";
		$output .= "{$i1}array(\n";
		$output .= "{$i2}\"storage_version\" => \"{$class_version}\",\n";
		$output .= "{$i2}\"key_length\" => {$this->key_length},\n";

		$group_line = str_replace( "\x00", "\\x00", $this->groups );
		$output    .= "{$i2}\"groups\" => \"{$group_line}\",\n";

		$output .= "{$i2}\"large_words\" => array(\n";

		$prefixes = explode( "\x00", $this->groups );
		foreach ( $prefixes as $index => $prefix ) {
			if ( '' === $prefix ) {
				break;
			}
			$group        = $this->large_words[ $index ];
			$group_length = strlen( $group );
			$comment_line = "{$i3}//";
			$data_line    = "{$i3}\"";
			$at           = 0;
			while ( $at < $group_length ) {
				$token_length   = unpack( 'C', $group[ $at++ ] )[1];
				$token          = substr( $group, $at, $token_length );
				$at            += $token_length;
				$mapping_length = unpack( 'C', $group[ $at++ ] )[1];
				$mapping        = substr( $group, $at, $mapping_length );
				$at            += $mapping_length;

				$token_digits   = str_pad( dechex( $token_length ), 2, '0', STR_PAD_LEFT );
				$mapping_digits = str_pad( dechex( $mapping_length ), 2, '0', STR_PAD_LEFT );

				$mapping = preg_replace_callback(
					"~[\\x00-\\x1f\\x22\\x5c]~",
					static function ( $match_result ) {
						switch ( $match_result[0] ) {
							case '"':
								return '\\"';

							case '\\':
								return '\\\\';

							default:
								$hex = dechex( ord( $match_result[0] ) );
								return "\\x{$hex}";
						}
					},
					$mapping
				);

				$comment_line .= " {$prefix}{$token}[{$mapping}]";
				$data_line    .= "\\x{$token_digits}{$token}\\x{$mapping_digits}{$mapping}";
			}
			$comment_line .= ".\n";
			$data_line    .= "\",\n";

			$output .= $comment_line;
			$output .= $data_line;
		}

		$output .= "{$i2}),\n";

		$small_words  = array();
		$small_length = strlen( $this->small_words );
		$at           = 0;
		while ( $at < $small_length ) {
			$small_words[] = substr( $this->small_words, $at, $this->key_length + 1 );
			$at           += $this->key_length + 1;
		}

		$small_text = str_replace( "\x00", '\x00', implode( '', $small_words ) );
		$output    .= "{$i2}\"small_words\" => \"{$small_text}\",\n";

		$output .= "{$i2}\"small_mappings\" => array(\n";
		foreach ( $this->small_mappings as $mapping ) {
			$output .= "{$i3}\"{$mapping}\",\n";
		}
		$output .= "{$i2})\n";
		$output .= "{$i1})\n";
		$output .= ')';

		return $output;
	}

	/**
	 * Compares two strings, returning the longest, or whichever
	 * is first alphabetically if they are the same length.
	 *
	 * This is an important sort when building the token map because
	 * it should not form a match on a substring of a longer potential
	 * match. For example, it should not detect `Cap` when matching
	 * against the string `CapitalDifferentialD`.
	 *
	 * @since 6.6.0
	 *
	 * @param string $a First string to compare.
	 * @param string $b Second string to compare.
	 * @return int -1 or lower if `$a` is less than `$b`; 1 or greater if `$a` is greater than `$b`, and 0 if they are equal.
	 */
	private static function longest_first_then_alphabetical( string $a, string $b ): int {
		if ( $a === $b ) {
			return 0;
		}

		$length_a = strlen( $a );
		$length_b = strlen( $b );

		// Longer strings are less-than for comparison's sake.
		if ( $length_a !== $length_b ) {
			return $length_b - $length_a;
		}

		return strcmp( $a, $b );
	}
}

/**
 * Auto-generated class for looking up HTML named character references.
 *
 * @package WordPress
 * @since 6.6.0
 */
global $html5_named_character_references;

/**
 * Set of named character references in the HTML5 specification.
 *
 * ⚠️ !!! THIS ENTIRE CLASS INSTANCE IS AUTOMATICALLY GENERATED !!! ⚠️
 * Do not modify this directly.
 *
 * To regenerate, run the generation script directly.
 *
 * Example:
 *
 *     php tests/phpunit/data/html5-entities/generate-html5-named-character-references.php
 *
 * This list will never change, according to the spec. Each named
 * character reference is case-sensitive and the presence or absence
 * of the semicolon is significant. Without the semicolon, the rules
 * for an ambiguous ampersand govern whether the following text is
 * to be interpreted as a character reference or not.
 *
 * The list of entities is sourced directly from the WHATWG server
 * and cached in the test directory to avoid needing to download it
 * every time this file is updated.
 *
 * @link https://html.spec.whatwg.org/entities.json.
 */
$html5_named_character_references = WP_Token_Map::from_precomputed_table(
	array(
		"storage_version" => "6.6.0-trunk",
		"key_length" => 2,
		"groups" => "AE\x00AM\x00Aa\x00Ab\x00Ac\x00Af\x00Ag\x00Al\x00Am\x00An\x00Ao\x00Ap\x00Ar\x00As\x00At\x00Au\x00Ba\x00Bc\x00Be\x00Bf\x00Bo\x00Br\x00Bs\x00Bu\x00CH\x00CO\x00Ca\x00Cc\x00Cd\x00Ce\x00Cf\x00Ch\x00Ci\x00Cl\x00Co\x00Cr\x00Cs\x00Cu\x00DD\x00DJ\x00DS\x00DZ\x00Da\x00Dc\x00De\x00Df\x00Di\x00Do\x00Ds\x00EN\x00ET\x00Ea\x00Ec\x00Ed\x00Ef\x00Eg\x00El\x00Em\x00Eo\x00Ep\x00Eq\x00Es\x00Et\x00Eu\x00Ex\x00Fc\x00Ff\x00Fi\x00Fo\x00Fs\x00GJ\x00GT\x00Ga\x00Gb\x00Gc\x00Gd\x00Gf\x00Gg\x00Go\x00Gr\x00Gs\x00Gt\x00HA\x00Ha\x00Hc\x00Hf\x00Hi\x00Ho\x00Hs\x00Hu\x00IE\x00IJ\x00IO\x00Ia\x00Ic\x00Id\x00If\x00Ig\x00Im\x00In\x00Io\x00Is\x00It\x00Iu\x00Jc\x00Jf\x00Jo\x00Js\x00Ju\x00KH\x00KJ\x00Ka\x00Kc\x00Kf\x00Ko\x00Ks\x00LJ\x00LT\x00La\x00Lc\x00Le\x00Lf\x00Ll\x00Lm\x00Lo\x00Ls\x00Lt\x00Ma\x00Mc\x00Me\x00Mf\x00Mi\x00Mo\x00Ms\x00Mu\x00NJ\x00Na\x00Nc\x00Ne\x00Nf\x00No\x00Ns\x00Nt\x00Nu\x00OE\x00Oa\x00Oc\x00Od\x00Of\x00Og\x00Om\x00Oo\x00Op\x00Or\x00Os\x00Ot\x00Ou\x00Ov\x00Pa\x00Pc\x00Pf\x00Ph\x00Pi\x00Pl\x00Po\x00Pr\x00Ps\x00QU\x00Qf\x00Qo\x00Qs\x00RB\x00RE\x00Ra\x00Rc\x00Re\x00Rf\x00Rh\x00Ri\x00Ro\x00Rr\x00Rs\x00Ru\x00SH\x00SO\x00Sa\x00Sc\x00Sf\x00Sh\x00Si\x00Sm\x00So\x00Sq\x00Ss\x00St\x00Su\x00TH\x00TR\x00TS\x00Ta\x00Tc\x00Tf\x00Th\x00Ti\x00To\x00Tr\x00Ts\x00Ua\x00Ub\x00Uc\x00Ud\x00Uf\x00Ug\x00Um\x00Un\x00Uo\x00Up\x00Ur\x00Us\x00Ut\x00Uu\x00VD\x00Vb\x00Vc\x00Vd\x00Ve\x00Vf\x00Vo\x00Vs\x00Vv\x00Wc\x00We\x00Wf\x00Wo\x00Ws\x00Xf\x00Xi\x00Xo\x00Xs\x00YA\x00YI\x00YU\x00Ya\x00Yc\x00Yf\x00Yo\x00Ys\x00Yu\x00ZH\x00Za\x00Zc\x00Zd\x00Ze\x00Zf\x00Zo\x00Zs\x00aa\x00ab\x00ac\x00ae\x00af\x00ag\x00al\x00am\x00an\x00ao\x00ap\x00ar\x00as\x00at\x00au\x00aw\x00bN\x00ba\x00bb\x00bc\x00bd\x00be\x00bf\x00bi\x00bk\x00bl\x00bn\x00bo\x00bp\x00br\x00bs\x00bu\x00ca\x00cc\x00cd\x00ce\x00cf\x00ch\x00ci\x00cl\x00co\x00cr\x00cs\x00ct\x00cu\x00cw\x00cy\x00dA\x00dH\x00da\x00db\x00dc\x00dd\x00de\x00df\x00dh\x00di\x00dj\x00dl\x00do\x00dr\x00ds\x00dt\x00du\x00dw\x00dz\x00eD\x00ea\x00ec\x00ed\x00ee\x00ef\x00eg\x00el\x00em\x00en\x00eo\x00ep\x00eq\x00er\x00es\x00et\x00eu\x00ex\x00fa\x00fc\x00fe\x00ff\x00fi\x00fj\x00fl\x00fn\x00fo\x00fp\x00fr\x00fs\x00gE\x00ga\x00gb\x00gc\x00gd\x00ge\x00gf\x00gg\x00gi\x00gj\x00gl\x00gn\x00go\x00gr\x00gs\x00gt\x00gv\x00hA\x00ha\x00hb\x00hc\x00he\x00hf\x00hk\x00ho\x00hs\x00hy\x00ia\x00ic\x00ie\x00if\x00ig\x00ii\x00ij\x00im\x00in\x00io\x00ip\x00iq\x00is\x00it\x00iu\x00jc\x00jf\x00jm\x00jo\x00js\x00ju\x00ka\x00kc\x00kf\x00kg\x00kh\x00kj\x00ko\x00ks\x00lA\x00lB\x00lE\x00lH\x00la\x00lb\x00lc\x00ld\x00le\x00lf\x00lg\x00lh\x00lj\x00ll\x00lm\x00ln\x00lo\x00lp\x00lr\x00ls\x00lt\x00lu\x00lv\x00mD\x00ma\x00mc\x00md\x00me\x00mf\x00mh\x00mi\x00ml\x00mn\x00mo\x00mp\x00ms\x00mu\x00nG\x00nL\x00nR\x00nV\x00na\x00nb\x00nc\x00nd\x00ne\x00nf\x00ng\x00nh\x00ni\x00nj\x00nl\x00nm\x00no\x00np\x00nr\x00ns\x00nt\x00nu\x00nv\x00nw\x00oS\x00oa\x00oc\x00od\x00oe\x00of\x00og\x00oh\x00oi\x00ol\x00om\x00oo\x00op\x00or\x00os\x00ot\x00ou\x00ov\x00pa\x00pc\x00pe\x00pf\x00ph\x00pi\x00pl\x00pm\x00po\x00pr\x00ps\x00pu\x00qf\x00qi\x00qo\x00qp\x00qs\x00qu\x00rA\x00rB\x00rH\x00ra\x00rb\x00rc\x00rd\x00re\x00rf\x00rh\x00ri\x00rl\x00rm\x00rn\x00ro\x00rp\x00rr\x00rs\x00rt\x00ru\x00rx\x00sa\x00sb\x00sc\x00sd\x00se\x00sf\x00sh\x00si\x00sl\x00sm\x00so\x00sp\x00sq\x00sr\x00ss\x00st\x00su\x00sw\x00sz\x00ta\x00tb\x00tc\x00td\x00te\x00tf\x00th\x00ti\x00to\x00tp\x00tr\x00ts\x00tw\x00uA\x00uH\x00ua\x00ub\x00uc\x00ud\x00uf\x00ug\x00uh\x00ul\x00um\x00uo\x00up\x00ur\x00us\x00ut\x00uu\x00uw\x00vA\x00vB\x00vD\x00va\x00vc\x00vd\x00ve\x00vf\x00vl\x00vn\x00vo\x00vp\x00vr\x00vs\x00vz\x00wc\x00we\x00wf\x00wo\x00wp\x00wr\x00ws\x00xc\x00xd\x00xf\x00xh\x00xi\x00xl\x00xm\x00xn\x00xo\x00xr\x00xs\x00xu\x00xv\x00xw\x00ya\x00yc\x00ye\x00yf\x00yi\x00yo\x00ys\x00yu\x00za\x00zc\x00zd\x00ze\x00zf\x00zh\x00zi\x00zo\x00zs\x00zw\x00",
		"large_words" => array(
			// AElig;[Æ] AElig[Æ].
			"\x04lig;\x02Æ\x03lig\x02Æ",
			// AMP;[&] AMP[&].
			"\x02P;\x01&\x01P\x01&",
			// Aacute;[Á] Aacute[Á].
			"\x05cute;\x02Á\x04cute\x02Á",
			// Abreve;[Ă].
			"\x05reve;\x02Ă",
			// Acirc;[Â] Acirc[Â] Acy;[А].
			"\x04irc;\x02Â\x03irc\x02Â\x02y;\x02А",
			// Afr;[𝔄].
			"\x02r;\x04𝔄",
			// Agrave;[À] Agrave[À].
			"\x05rave;\x02À\x04rave\x02À",
			// Alpha;[Α].
			"\x04pha;\x02Α",
			// Amacr;[Ā].
			"\x04acr;\x02Ā",
			// And;[⩓].
			"\x02d;\x03⩓",
			// Aogon;[Ą] Aopf;[𝔸].
			"\x04gon;\x02Ą\x03pf;\x04𝔸",
			// ApplyFunction;[⁡].
			"\x0cplyFunction;\x03⁡",
			// Aring;[Å] Aring[Å].
			"\x04ing;\x02Å\x03ing\x02Å",
			// Assign;[≔] Ascr;[𝒜].
			"\x05sign;\x03≔\x03cr;\x04𝒜",
			// Atilde;[Ã] Atilde[Ã].
			"\x05ilde;\x02Ã\x04ilde\x02Ã",
			// Auml;[Ä] Auml[Ä].
			"\x03ml;\x02Ä\x02ml\x02Ä",
			// Backslash;[∖] Barwed;[⌆] Barv;[⫧].
			"\x08ckslash;\x03∖\x05rwed;\x03⌆\x03rv;\x03⫧",
			// Bcy;[Б].
			"\x02y;\x02Б",
			// Bernoullis;[ℬ] Because;[∵] Beta;[Β].
			"\x09rnoullis;\x03ℬ\x06cause;\x03∵\x03ta;\x02Β",
			// Bfr;[𝔅].
			"\x02r;\x04𝔅",
			// Bopf;[𝔹].
			"\x03pf;\x04𝔹",
			// Breve;[˘].
			"\x04eve;\x02˘",
			// Bscr;[ℬ].
			"\x03cr;\x03ℬ",
			// Bumpeq;[≎].
			"\x05mpeq;\x03≎",
			// CHcy;[Ч].
			"\x03cy;\x02Ч",
			// COPY;[©] COPY[©].
			"\x03PY;\x02©\x02PY\x02©",
			// CapitalDifferentialD;[ⅅ] Cayleys;[ℭ] Cacute;[Ć] Cap;[⋒].
			"\x13pitalDifferentialD;\x03ⅅ\x06yleys;\x03ℭ\x05cute;\x02Ć\x02p;\x03⋒",
			// Cconint;[∰] Ccaron;[Č] Ccedil;[Ç] Ccedil[Ç] Ccirc;[Ĉ].
			"\x06onint;\x03∰\x05aron;\x02Č\x05edil;\x02Ç\x04edil\x02Ç\x04irc;\x02Ĉ",
			// Cdot;[Ċ].
			"\x03ot;\x02Ċ",
			// CenterDot;[·] Cedilla;[¸].
			"\x08nterDot;\x02·\x06dilla;\x02¸",
			// Cfr;[ℭ].
			"\x02r;\x03ℭ",
			// Chi;[Χ].
			"\x02i;\x02Χ",
			// CircleMinus;[⊖] CircleTimes;[⊗] CirclePlus;[⊕] CircleDot;[⊙].
			"\x0arcleMinus;\x03⊖\x0arcleTimes;\x03⊗\x09rclePlus;\x03⊕\x08rcleDot;\x03⊙",
			// ClockwiseContourIntegral;[∲] CloseCurlyDoubleQuote;[”] CloseCurlyQuote;[’].
			"\x17ockwiseContourIntegral;\x03∲\x14oseCurlyDoubleQuote;\x03”\x0eoseCurlyQuote;\x03’",
			// CounterClockwiseContourIntegral;[∳] ContourIntegral;[∮] Congruent;[≡] Coproduct;[∐] Colone;[⩴] Conint;[∯] Colon;[∷] Copf;[ℂ].
			"\x1eunterClockwiseContourIntegral;\x03∳\x0entourIntegral;\x03∮\x08ngruent;\x03≡\x08product;\x03∐\x05lone;\x03⩴\x05nint;\x03∯\x04lon;\x03∷\x03pf;\x03ℂ",
			// Cross;[⨯].
			"\x04oss;\x03⨯",
			// Cscr;[𝒞].
			"\x03cr;\x04𝒞",
			// CupCap;[≍] Cup;[⋓].
			"\x05pCap;\x03≍\x02p;\x03⋓",
			// DDotrahd;[⤑] DD;[ⅅ].
			"\x07otrahd;\x03⤑\x01;\x03ⅅ",
			// DJcy;[Ђ].
			"\x03cy;\x02Ђ",
			// DScy;[Ѕ].
			"\x03cy;\x02Ѕ",
			// DZcy;[Џ].
			"\x03cy;\x02Џ",
			// Dagger;[‡] Dashv;[⫤] Darr;[↡].
			"\x05gger;\x03‡\x04shv;\x03⫤\x03rr;\x03↡",
			// Dcaron;[Ď] Dcy;[Д].
			"\x05aron;\x02Ď\x02y;\x02Д",
			// Delta;[Δ] Del;[∇].
			"\x04lta;\x02Δ\x02l;\x03∇",
			// Dfr;[𝔇].
			"\x02r;\x04𝔇",
			// DiacriticalDoubleAcute;[˝] DiacriticalAcute;[´] DiacriticalGrave;[`] DiacriticalTilde;[˜] DiacriticalDot;[˙] DifferentialD;[ⅆ] Diamond;[⋄].
			"\x15acriticalDoubleAcute;\x02˝\x0facriticalAcute;\x02´\x0facriticalGrave;\x01`\x0facriticalTilde;\x02˜\x0dacriticalDot;\x02˙\x0cfferentialD;\x03ⅆ\x06amond;\x03⋄",
			// DoubleLongLeftRightArrow;[⟺] DoubleContourIntegral;[∯] DoubleLeftRightArrow;[⇔] DoubleLongRightArrow;[⟹] DoubleLongLeftArrow;[⟸] DownLeftRightVector;[⥐] DownRightTeeVector;[⥟] DownRightVectorBar;[⥗] DoubleUpDownArrow;[⇕] DoubleVerticalBar;[∥] DownLeftTeeVector;[⥞] DownLeftVectorBar;[⥖] DoubleRightArrow;[⇒] DownArrowUpArrow;[⇵] DoubleDownArrow;[⇓] DoubleLeftArrow;[⇐] DownRightVector;[⇁] DoubleRightTee;[⊨] DownLeftVector;[↽] DoubleLeftTee;[⫤] DoubleUpArrow;[⇑] DownArrowBar;[⤓] DownTeeArrow;[↧] DoubleDot;[¨] DownArrow;[↓] DownBreve;[̑] Downarrow;[⇓] DotEqual;[≐] DownTee;[⊤] DotDot;[⃜] Dopf;[𝔻] Dot;[¨].
			"\x17ubleLongLeftRightArrow;\x03⟺\x14ubleContourIntegral;\x03∯\x13ubleLeftRightArrow;\x03⇔\x13ubleLongRightArrow;\x03⟹\x12ubleLongLeftArrow;\x03⟸\x12wnLeftRightVector;\x03⥐\x11wnRightTeeVector;\x03⥟\x11wnRightVectorBar;\x03⥗\x10ubleUpDownArrow;\x03⇕\x10ubleVerticalBar;\x03∥\x10wnLeftTeeVector;\x03⥞\x10wnLeftVectorBar;\x03⥖\x0fubleRightArrow;\x03⇒\x0fwnArrowUpArrow;\x03⇵\x0eubleDownArrow;\x03⇓\x0eubleLeftArrow;\x03⇐\x0ewnRightVector;\x03⇁\x0dubleRightTee;\x03⊨\x0dwnLeftVector;\x03↽\x0cubleLeftTee;\x03⫤\x0cubleUpArrow;\x03⇑\x0bwnArrowBar;\x03⤓\x0bwnTeeArrow;\x03↧\x08ubleDot;\x02¨\x08wnArrow;\x03↓\x08wnBreve;\x02̑\x08wnarrow;\x03⇓\x07tEqual;\x03≐\x06wnTee;\x03⊤\x05tDot;\x03⃜\x03pf;\x04𝔻\x02t;\x02¨",
			// Dstrok;[Đ] Dscr;[𝒟].
			"\x05trok;\x02Đ\x03cr;\x04𝒟",
			// ENG;[Ŋ].
			"\x02G;\x02Ŋ",
			// ETH;[Ð] ETH[Ð].
			"\x02H;\x02Ð\x01H\x02Ð",
			// Eacute;[É] Eacute[É].
			"\x05cute;\x02É\x04cute\x02É",
			// Ecaron;[Ě] Ecirc;[Ê] Ecirc[Ê] Ecy;[Э].
			"\x05aron;\x02Ě\x04irc;\x02Ê\x03irc\x02Ê\x02y;\x02Э",
			// Edot;[Ė].
			"\x03ot;\x02Ė",
			// Efr;[𝔈].
			"\x02r;\x04𝔈",
			// Egrave;[È] Egrave[È].
			"\x05rave;\x02È\x04rave\x02È",
			// Element;[∈].
			"\x06ement;\x03∈",
			// EmptyVerySmallSquare;[▫] EmptySmallSquare;[◻] Emacr;[Ē].
			"\x13ptyVerySmallSquare;\x03▫\x0fptySmallSquare;\x03◻\x04acr;\x02Ē",
			// Eogon;[Ę] Eopf;[𝔼].
			"\x04gon;\x02Ę\x03pf;\x04𝔼",
			// Epsilon;[Ε].
			"\x06silon;\x02Ε",
			// Equilibrium;[⇌] EqualTilde;[≂] Equal;[⩵].
			"\x0auilibrium;\x03⇌\x09ualTilde;\x03≂\x04ual;\x03⩵",
			// Escr;[ℰ] Esim;[⩳].
			"\x03cr;\x03ℰ\x03im;\x03⩳",
			// Eta;[Η].
			"\x02a;\x02Η",
			// Euml;[Ë] Euml[Ë].
			"\x03ml;\x02Ë\x02ml\x02Ë",
			// ExponentialE;[ⅇ] Exists;[∃].
			"\x0bponentialE;\x03ⅇ\x05ists;\x03∃",
			// Fcy;[Ф].
			"\x02y;\x02Ф",
			// Ffr;[𝔉].
			"\x02r;\x04𝔉",
			// FilledVerySmallSquare;[▪] FilledSmallSquare;[◼].
			"\x14lledVerySmallSquare;\x03▪\x10lledSmallSquare;\x03◼",
			// Fouriertrf;[ℱ] ForAll;[∀] Fopf;[𝔽].
			"\x09uriertrf;\x03ℱ\x05rAll;\x03∀\x03pf;\x04𝔽",
			// Fscr;[ℱ].
			"\x03cr;\x03ℱ",
			// GJcy;[Ѓ].
			"\x03cy;\x02Ѓ",
			// GT;[>].
			"\x01;\x01>",
			// Gammad;[Ϝ] Gamma;[Γ].
			"\x05mmad;\x02Ϝ\x04mma;\x02Γ",
			// Gbreve;[Ğ].
			"\x05reve;\x02Ğ",
			// Gcedil;[Ģ] Gcirc;[Ĝ] Gcy;[Г].
			"\x05edil;\x02Ģ\x04irc;\x02Ĝ\x02y;\x02Г",
			// Gdot;[Ġ].
			"\x03ot;\x02Ġ",
			// Gfr;[𝔊].
			"\x02r;\x04𝔊",
			// Gg;[⋙].
			"\x01;\x03⋙",
			// Gopf;[𝔾].
			"\x03pf;\x04𝔾",
			// GreaterSlantEqual;[⩾] GreaterEqualLess;[⋛] GreaterFullEqual;[≧] GreaterGreater;[⪢] GreaterEqual;[≥] GreaterTilde;[≳] GreaterLess;[≷].
			"\x10eaterSlantEqual;\x03⩾\x0featerEqualLess;\x03⋛\x0featerFullEqual;\x03≧\x0deaterGreater;\x03⪢\x0beaterEqual;\x03≥\x0beaterTilde;\x03≳\x0aeaterLess;\x03≷",
			// Gscr;[𝒢].
			"\x03cr;\x04𝒢",
			// Gt;[≫].
			"\x01;\x03≫",
			// HARDcy;[Ъ].
			"\x05RDcy;\x02Ъ",
			// Hacek;[ˇ] Hat;[^].
			"\x04cek;\x02ˇ\x02t;\x01^",
			// Hcirc;[Ĥ].
			"\x04irc;\x02Ĥ",
			// Hfr;[ℌ].
			"\x02r;\x03ℌ",
			// HilbertSpace;[ℋ].
			"\x0blbertSpace;\x03ℋ",
			// HorizontalLine;[─] Hopf;[ℍ].
			"\x0drizontalLine;\x03─\x03pf;\x03ℍ",
			// Hstrok;[Ħ] Hscr;[ℋ].
			"\x05trok;\x02Ħ\x03cr;\x03ℋ",
			// HumpDownHump;[≎] HumpEqual;[≏].
			"\x0bmpDownHump;\x03≎\x08mpEqual;\x03≏",
			// IEcy;[Е].
			"\x03cy;\x02Е",
			// IJlig;[Ĳ].
			"\x04lig;\x02Ĳ",
			// IOcy;[Ё].
			"\x03cy;\x02Ё",
			// Iacute;[Í] Iacute[Í].
			"\x05cute;\x02Í\x04cute\x02Í",
			// Icirc;[Î] Icirc[Î] Icy;[И].
			"\x04irc;\x02Î\x03irc\x02Î\x02y;\x02И",
			// Idot;[İ].
			"\x03ot;\x02İ",
			// Ifr;[ℑ].
			"\x02r;\x03ℑ",
			// Igrave;[Ì] Igrave[Ì].
			"\x05rave;\x02Ì\x04rave\x02Ì",
			// ImaginaryI;[ⅈ] Implies;[⇒] Imacr;[Ī] Im;[ℑ].
			"\x09aginaryI;\x03ⅈ\x06plies;\x03⇒\x04acr;\x02Ī\x01;\x03ℑ",
			// InvisibleComma;[⁣] InvisibleTimes;[⁢] Intersection;[⋂] Integral;[∫] Int;[∬].
			"\x0dvisibleComma;\x03⁣\x0dvisibleTimes;\x03⁢\x0btersection;\x03⋂\x07tegral;\x03∫\x02t;\x03∬",
			// Iogon;[Į] Iopf;[𝕀] Iota;[Ι].
			"\x04gon;\x02Į\x03pf;\x04𝕀\x03ta;\x02Ι",
			// Iscr;[ℐ].
			"\x03cr;\x03ℐ",
			// Itilde;[Ĩ].
			"\x05ilde;\x02Ĩ",
			// Iukcy;[І] Iuml;[Ï] Iuml[Ï].
			"\x04kcy;\x02І\x03ml;\x02Ï\x02ml\x02Ï",
			// Jcirc;[Ĵ] Jcy;[Й].
			"\x04irc;\x02Ĵ\x02y;\x02Й",
			// Jfr;[𝔍].
			"\x02r;\x04𝔍",
			// Jopf;[𝕁].
			"\x03pf;\x04𝕁",
			// Jsercy;[Ј] Jscr;[𝒥].
			"\x05ercy;\x02Ј\x03cr;\x04𝒥",
			// Jukcy;[Є].
			"\x04kcy;\x02Є",
			// KHcy;[Х].
			"\x03cy;\x02Х",
			// KJcy;[Ќ].
			"\x03cy;\x02Ќ",
			// Kappa;[Κ].
			"\x04ppa;\x02Κ",
			// Kcedil;[Ķ] Kcy;[К].
			"\x05edil;\x02Ķ\x02y;\x02К",
			// Kfr;[𝔎].
			"\x02r;\x04𝔎",
			// Kopf;[𝕂].
			"\x03pf;\x04𝕂",
			// Kscr;[𝒦].
			"\x03cr;\x04𝒦",
			// LJcy;[Љ].
			"\x03cy;\x02Љ",
			// LT;[<].
			"\x01;\x01<",
			// Laplacetrf;[ℒ] Lacute;[Ĺ] Lambda;[Λ] Lang;[⟪] Larr;[↞].
			"\x09placetrf;\x03ℒ\x05cute;\x02Ĺ\x05mbda;\x02Λ\x03ng;\x03⟪\x03rr;\x03↞",
			// Lcaron;[Ľ] Lcedil;[Ļ] Lcy;[Л].
			"\x05aron;\x02Ľ\x05edil;\x02Ļ\x02y;\x02Л",
			// LeftArrowRightArrow;[⇆] LeftDoubleBracket;[⟦] LeftDownTeeVector;[⥡] LeftDownVectorBar;[⥙] LeftTriangleEqual;[⊴] LeftAngleBracket;[⟨] LeftUpDownVector;[⥑] LessEqualGreater;[⋚] LeftRightVector;[⥎] LeftTriangleBar;[⧏] LeftUpTeeVector;[⥠] LeftUpVectorBar;[⥘] LeftDownVector;[⇃] LeftRightArrow;[↔] Leftrightarrow;[⇔] LessSlantEqual;[⩽] LeftTeeVector;[⥚] LeftVectorBar;[⥒] LessFullEqual;[≦] LeftArrowBar;[⇤] LeftTeeArrow;[↤] LeftTriangle;[⊲] LeftUpVector;[↿] LeftCeiling;[⌈] LessGreater;[≶] LeftVector;[↼] LeftArrow;[←] LeftFloor;[⌊] Leftarrow;[⇐] LessTilde;[≲] LessLess;[⪡] LeftTee;[⊣].
			"\x12ftArrowRightArrow;\x03⇆\x10ftDoubleBracket;\x03⟦\x10ftDownTeeVector;\x03⥡\x10ftDownVectorBar;\x03⥙\x10ftTriangleEqual;\x03⊴\x0fftAngleBracket;\x03⟨\x0fftUpDownVector;\x03⥑\x0fssEqualGreater;\x03⋚\x0eftRightVector;\x03⥎\x0eftTriangleBar;\x03⧏\x0eftUpTeeVector;\x03⥠\x0eftUpVectorBar;\x03⥘\x0dftDownVector;\x03⇃\x0dftRightArrow;\x03↔\x0dftrightarrow;\x03⇔\x0dssSlantEqual;\x03⩽\x0cftTeeVector;\x03⥚\x0cftVectorBar;\x03⥒\x0cssFullEqual;\x03≦\x0bftArrowBar;\x03⇤\x0bftTeeArrow;\x03↤\x0bftTriangle;\x03⊲\x0bftUpVector;\x03↿\x0aftCeiling;\x03⌈\x0assGreater;\x03≶\x09ftVector;\x03↼\x08ftArrow;\x03←\x08ftFloor;\x03⌊\x08ftarrow;\x03⇐\x08ssTilde;\x03≲\x07ssLess;\x03⪡\x06ftTee;\x03⊣",
			// Lfr;[𝔏].
			"\x02r;\x04𝔏",
			// Lleftarrow;[⇚] Ll;[⋘].
			"\x09eftarrow;\x03⇚\x01;\x03⋘",
			// Lmidot;[Ŀ].
			"\x05idot;\x02Ŀ",
			// LongLeftRightArrow;[⟷] Longleftrightarrow;[⟺] LowerRightArrow;[↘] LongRightArrow;[⟶] Longrightarrow;[⟹] LowerLeftArrow;[↙] LongLeftArrow;[⟵] Longleftarrow;[⟸] Lopf;[𝕃].
			"\x11ngLeftRightArrow;\x03⟷\x11ngleftrightarrow;\x03⟺\x0ewerRightArrow;\x03↘\x0dngRightArrow;\x03⟶\x0dngrightarrow;\x03⟹\x0dwerLeftArrow;\x03↙\x0cngLeftArrow;\x03⟵\x0cngleftarrow;\x03⟸\x03pf;\x04𝕃",
			// Lstrok;[Ł] Lscr;[ℒ] Lsh;[↰].
			"\x05trok;\x02Ł\x03cr;\x03ℒ\x02h;\x03↰",
			// Lt;[≪].
			"\x01;\x03≪",
			// Map;[⤅].
			"\x02p;\x03⤅",
			// Mcy;[М].
			"\x02y;\x02М",
			// MediumSpace;[ ] Mellintrf;[ℳ].
			"\x0adiumSpace;\x03 \x08llintrf;\x03ℳ",
			// Mfr;[𝔐].
			"\x02r;\x04𝔐",
			// MinusPlus;[∓].
			"\x08nusPlus;\x03∓",
			// Mopf;[𝕄].
			"\x03pf;\x04𝕄",
			// Mscr;[ℳ].
			"\x03cr;\x03ℳ",
			// Mu;[Μ].
			"\x01;\x02Μ",
			// NJcy;[Њ].
			"\x03cy;\x02Њ",
			// Nacute;[Ń].
			"\x05cute;\x02Ń",
			// Ncaron;[Ň] Ncedil;[Ņ] Ncy;[Н].
			"\x05aron;\x02Ň\x05edil;\x02Ņ\x02y;\x02Н",
			// NegativeVeryThinSpace;[​] NestedGreaterGreater;[≫] NegativeMediumSpace;[​] NegativeThickSpace;[​] NegativeThinSpace;[​] NestedLessLess;[≪] NewLine;[\xa].
			"\x14gativeVeryThinSpace;\x03​\x13stedGreaterGreater;\x03≫\x12gativeMediumSpace;\x03​\x11gativeThickSpace;\x03​\x10gativeThinSpace;\x03​\x0dstedLessLess;\x03≪\x06wLine;\x01\xa",
			// Nfr;[𝔑].
			"\x02r;\x04𝔑",
			// NotNestedGreaterGreater;[⪢̸] NotSquareSupersetEqual;[⋣] NotPrecedesSlantEqual;[⋠] NotRightTriangleEqual;[⋭] NotSucceedsSlantEqual;[⋡] NotDoubleVerticalBar;[∦] NotGreaterSlantEqual;[⩾̸] NotLeftTriangleEqual;[⋬] NotSquareSubsetEqual;[⋢] NotGreaterFullEqual;[≧̸] NotRightTriangleBar;[⧐̸] NotLeftTriangleBar;[⧏̸] NotGreaterGreater;[≫̸] NotLessSlantEqual;[⩽̸] NotNestedLessLess;[⪡̸] NotReverseElement;[∌] NotSquareSuperset;[⊐̸] NotTildeFullEqual;[≇] NonBreakingSpace;[ ] NotPrecedesEqual;[⪯̸] NotRightTriangle;[⋫] NotSucceedsEqual;[⪰̸] NotSucceedsTilde;[≿̸] NotSupersetEqual;[⊉] NotGreaterEqual;[≱] NotGreaterTilde;[≵] NotHumpDownHump;[≎̸] NotLeftTriangle;[⋪] NotSquareSubset;[⊏̸] NotGreaterLess;[≹] NotLessGreater;[≸] NotSubsetEqual;[⊈] NotVerticalBar;[∤] NotEqualTilde;[≂̸] NotTildeEqual;[≄] NotTildeTilde;[≉] NotCongruent;[≢] NotHumpEqual;[≏̸] NotLessEqual;[≰] NotLessTilde;[≴] NotLessLess;[≪̸] NotPrecedes;[⊀] NotSucceeds;[⊁] NotSuperset;[⊃⃒] NotElement;[∉] NotGreater;[≯] NotCupCap;[≭] NotExists;[∄] NotSubset;[⊂⃒] NotEqual;[≠] NotTilde;[≁] NoBreak;[⁠] NotLess;[≮] Nopf;[ℕ] Not;[⫬].
			"\x16tNestedGreaterGreater;\x05⪢̸\x15tSquareSupersetEqual;\x03⋣\x14tPrecedesSlantEqual;\x03⋠\x14tRightTriangleEqual;\x03⋭\x14tSucceedsSlantEqual;\x03⋡\x13tDoubleVerticalBar;\x03∦\x13tGreaterSlantEqual;\x05⩾̸\x13tLeftTriangleEqual;\x03⋬\x13tSquareSubsetEqual;\x03⋢\x12tGreaterFullEqual;\x05≧̸\x12tRightTriangleBar;\x05⧐̸\x11tLeftTriangleBar;\x05⧏̸\x10tGreaterGreater;\x05≫̸\x10tLessSlantEqual;\x05⩽̸\x10tNestedLessLess;\x05⪡̸\x10tReverseElement;\x03∌\x10tSquareSuperset;\x05⊐̸\x10tTildeFullEqual;\x03≇\x0fnBreakingSpace;\x02 \x0ftPrecedesEqual;\x05⪯̸\x0ftRightTriangle;\x03⋫\x0ftSucceedsEqual;\x05⪰̸\x0ftSucceedsTilde;\x05≿̸\x0ftSupersetEqual;\x03⊉\x0etGreaterEqual;\x03≱\x0etGreaterTilde;\x03≵\x0etHumpDownHump;\x05≎̸\x0etLeftTriangle;\x03⋪\x0etSquareSubset;\x05⊏̸\x0dtGreaterLess;\x03≹\x0dtLessGreater;\x03≸\x0dtSubsetEqual;\x03⊈\x0dtVerticalBar;\x03∤\x0ctEqualTilde;\x05≂̸\x0ctTildeEqual;\x03≄\x0ctTildeTilde;\x03≉\x0btCongruent;\x03≢\x0btHumpEqual;\x05≏̸\x0btLessEqual;\x03≰\x0btLessTilde;\x03≴\x0atLessLess;\x05≪̸\x0atPrecedes;\x03⊀\x0atSucceeds;\x03⊁\x0atSuperset;\x06⊃⃒\x09tElement;\x03∉\x09tGreater;\x03≯\x08tCupCap;\x03≭\x08tExists;\x03∄\x08tSubset;\x06⊂⃒\x07tEqual;\x03≠\x07tTilde;\x03≁\x06Break;\x03⁠\x06tLess;\x03≮\x03pf;\x03ℕ\x02t;\x03⫬",
			// Nscr;[𝒩].
			"\x03cr;\x04𝒩",
			// Ntilde;[Ñ] Ntilde[Ñ].
			"\x05ilde;\x02Ñ\x04ilde\x02Ñ",
			// Nu;[Ν].
			"\x01;\x02Ν",
			// OElig;[Œ].
			"\x04lig;\x02Œ",
			// Oacute;[Ó] Oacute[Ó].
			"\x05cute;\x02Ó\x04cute\x02Ó",
			// Ocirc;[Ô] Ocirc[Ô] Ocy;[О].
			"\x04irc;\x02Ô\x03irc\x02Ô\x02y;\x02О",
			// Odblac;[Ő].
			"\x05blac;\x02Ő",
			// Ofr;[𝔒].
			"\x02r;\x04𝔒",
			// Ograve;[Ò] Ograve[Ò].
			"\x05rave;\x02Ò\x04rave\x02Ò",
			// Omicron;[Ο] Omacr;[Ō] Omega;[Ω].
			"\x06icron;\x02Ο\x04acr;\x02Ō\x04ega;\x02Ω",
			// Oopf;[𝕆].
			"\x03pf;\x04𝕆",
			// OpenCurlyDoubleQuote;[“] OpenCurlyQuote;[‘].
			"\x13enCurlyDoubleQuote;\x03“\x0denCurlyQuote;\x03‘",
			// Or;[⩔].
			"\x01;\x03⩔",
			// Oslash;[Ø] Oslash[Ø] Oscr;[𝒪].
			"\x05lash;\x02Ø\x04lash\x02Ø\x03cr;\x04𝒪",
			// Otilde;[Õ] Otimes;[⨷] Otilde[Õ].
			"\x05ilde;\x02Õ\x05imes;\x03⨷\x04ilde\x02Õ",
			// Ouml;[Ö] Ouml[Ö].
			"\x03ml;\x02Ö\x02ml\x02Ö",
			// OverParenthesis;[⏜] OverBracket;[⎴] OverBrace;[⏞] OverBar;[‾].
			"\x0eerParenthesis;\x03⏜\x0aerBracket;\x03⎴\x08erBrace;\x03⏞\x06erBar;\x03‾",
			// PartialD;[∂].
			"\x07rtialD;\x03∂",
			// Pcy;[П].
			"\x02y;\x02П",
			// Pfr;[𝔓].
			"\x02r;\x04𝔓",
			// Phi;[Φ].
			"\x02i;\x02Φ",
			// Pi;[Π].
			"\x01;\x02Π",
			// PlusMinus;[±].
			"\x08usMinus;\x02±",
			// Poincareplane;[ℌ] Popf;[ℙ].
			"\x0cincareplane;\x03ℌ\x03pf;\x03ℙ",
			// PrecedesSlantEqual;[≼] PrecedesEqual;[⪯] PrecedesTilde;[≾] Proportional;[∝] Proportion;[∷] Precedes;[≺] Product;[∏] Prime;[″] Pr;[⪻].
			"\x11ecedesSlantEqual;\x03≼\x0cecedesEqual;\x03⪯\x0cecedesTilde;\x03≾\x0boportional;\x03∝\x09oportion;\x03∷\x07ecedes;\x03≺\x06oduct;\x03∏\x04ime;\x03″\x01;\x03⪻",
			// Pscr;[𝒫] Psi;[Ψ].
			"\x03cr;\x04𝒫\x02i;\x02Ψ",
			// QUOT;[\"] QUOT[\"].
			"\x03OT;\x01\"\x02OT\x01\"",
			// Qfr;[𝔔].
			"\x02r;\x04𝔔",
			// Qopf;[ℚ].
			"\x03pf;\x03ℚ",
			// Qscr;[𝒬].
			"\x03cr;\x04𝒬",
			// RBarr;[⤐].
			"\x04arr;\x03⤐",
			// REG;[®] REG[®].
			"\x02G;\x02®\x01G\x02®",
			// Racute;[Ŕ] Rarrtl;[⤖] Rang;[⟫] Rarr;[↠].
			"\x05cute;\x02Ŕ\x05rrtl;\x03⤖\x03ng;\x03⟫\x03rr;\x03↠",
			// Rcaron;[Ř] Rcedil;[Ŗ] Rcy;[Р].
			"\x05aron;\x02Ř\x05edil;\x02Ŗ\x02y;\x02Р",
			// ReverseUpEquilibrium;[⥯] ReverseEquilibrium;[⇋] ReverseElement;[∋] Re;[ℜ].
			"\x13verseUpEquilibrium;\x03⥯\x11verseEquilibrium;\x03⇋\x0dverseElement;\x03∋\x01;\x03ℜ",
			// Rfr;[ℜ].
			"\x02r;\x03ℜ",
			// Rho;[Ρ].
			"\x02o;\x02Ρ",
			// RightArrowLeftArrow;[⇄] RightDoubleBracket;[⟧] RightDownTeeVector;[⥝] RightDownVectorBar;[⥕] RightTriangleEqual;[⊵] RightAngleBracket;[⟩] RightUpDownVector;[⥏] RightTriangleBar;[⧐] RightUpTeeVector;[⥜] RightUpVectorBar;[⥔] RightDownVector;[⇂] RightTeeVector;[⥛] RightVectorBar;[⥓] RightArrowBar;[⇥] RightTeeArrow;[↦] RightTriangle;[⊳] RightUpVector;[↾] RightCeiling;[⌉] RightVector;[⇀] RightArrow;[→] RightFloor;[⌋] Rightarrow;[⇒] RightTee;[⊢].
			"\x12ghtArrowLeftArrow;\x03⇄\x11ghtDoubleBracket;\x03⟧\x11ghtDownTeeVector;\x03⥝\x11ghtDownVectorBar;\x03⥕\x11ghtTriangleEqual;\x03⊵\x10ghtAngleBracket;\x03⟩\x10ghtUpDownVector;\x03⥏\x0fghtTriangleBar;\x03⧐\x0fghtUpTeeVector;\x03⥜\x0fghtUpVectorBar;\x03⥔\x0eghtDownVector;\x03⇂\x0dghtTeeVector;\x03⥛\x0dghtVectorBar;\x03⥓\x0cghtArrowBar;\x03⇥\x0cghtTeeArrow;\x03↦\x0cghtTriangle;\x03⊳\x0cghtUpVector;\x03↾\x0bghtCeiling;\x03⌉\x0aghtVector;\x03⇀\x09ghtArrow;\x03→\x09ghtFloor;\x03⌋\x09ghtarrow;\x03⇒\x07ghtTee;\x03⊢",
			// RoundImplies;[⥰] Ropf;[ℝ].
			"\x0bundImplies;\x03⥰\x03pf;\x03ℝ",
			// Rrightarrow;[⇛].
			"\x0aightarrow;\x03⇛",
			// Rscr;[ℛ] Rsh;[↱].
			"\x03cr;\x03ℛ\x02h;\x03↱",
			// RuleDelayed;[⧴].
			"\x0aleDelayed;\x03⧴",
			// SHCHcy;[Щ] SHcy;[Ш].
			"\x05CHcy;\x02Щ\x03cy;\x02Ш",
			// SOFTcy;[Ь].
			"\x05FTcy;\x02Ь",
			// Sacute;[Ś].
			"\x05cute;\x02Ś",
			// Scaron;[Š] Scedil;[Ş] Scirc;[Ŝ] Scy;[С] Sc;[⪼].
			"\x05aron;\x02Š\x05edil;\x02Ş\x04irc;\x02Ŝ\x02y;\x02С\x01;\x03⪼",
			// Sfr;[𝔖].
			"\x02r;\x04𝔖",
			// ShortRightArrow;[→] ShortDownArrow;[↓] ShortLeftArrow;[←] ShortUpArrow;[↑].
			"\x0eortRightArrow;\x03→\x0dortDownArrow;\x03↓\x0dortLeftArrow;\x03←\x0bortUpArrow;\x03↑",
			// Sigma;[Σ].
			"\x04gma;\x02Σ",
			// SmallCircle;[∘].
			"\x0aallCircle;\x03∘",
			// Sopf;[𝕊].
			"\x03pf;\x04𝕊",
			// SquareSupersetEqual;[⊒] SquareIntersection;[⊓] SquareSubsetEqual;[⊑] SquareSuperset;[⊐] SquareSubset;[⊏] SquareUnion;[⊔] Square;[□] Sqrt;[√].
			"\x12uareSupersetEqual;\x03⊒\x11uareIntersection;\x03⊓\x10uareSubsetEqual;\x03⊑\x0duareSuperset;\x03⊐\x0buareSubset;\x03⊏\x0auareUnion;\x03⊔\x05uare;\x03□\x03rt;\x03√",
			// Sscr;[𝒮].
			"\x03cr;\x04𝒮",
			// Star;[⋆].
			"\x03ar;\x03⋆",
			// SucceedsSlantEqual;[≽] SucceedsEqual;[⪰] SucceedsTilde;[≿] SupersetEqual;[⊇] SubsetEqual;[⊆] Succeeds;[≻] SuchThat;[∋] Superset;[⊃] Subset;[⋐] Supset;[⋑] Sub;[⋐] Sum;[∑] Sup;[⋑].
			"\x11cceedsSlantEqual;\x03≽\x0ccceedsEqual;\x03⪰\x0ccceedsTilde;\x03≿\x0cpersetEqual;\x03⊇\x0absetEqual;\x03⊆\x07cceeds;\x03≻\x07chThat;\x03∋\x07perset;\x03⊃\x05bset;\x03⋐\x05pset;\x03⋑\x02b;\x03⋐\x02m;\x03∑\x02p;\x03⋑",
			// THORN;[Þ] THORN[Þ].
			"\x04ORN;\x02Þ\x03ORN\x02Þ",
			// TRADE;[™].
			"\x04ADE;\x03™",
			// TSHcy;[Ћ] TScy;[Ц].
			"\x04Hcy;\x02Ћ\x03cy;\x02Ц",
			// Tab;[\x9] Tau;[Τ].
			"\x02b;\x01\x9\x02u;\x02Τ",
			// Tcaron;[Ť] Tcedil;[Ţ] Tcy;[Т].
			"\x05aron;\x02Ť\x05edil;\x02Ţ\x02y;\x02Т",
			// Tfr;[𝔗].
			"\x02r;\x04𝔗",
			// ThickSpace;[  ] Therefore;[∴] ThinSpace;[ ] Theta;[Θ].
			"\x09ickSpace;\x06  \x08erefore;\x03∴\x08inSpace;\x03 \x04eta;\x02Θ",
			// TildeFullEqual;[≅] TildeEqual;[≃] TildeTilde;[≈] Tilde;[∼].
			"\x0dldeFullEqual;\x03≅\x09ldeEqual;\x03≃\x09ldeTilde;\x03≈\x04lde;\x03∼",
			// Topf;[𝕋].
			"\x03pf;\x04𝕋",
			// TripleDot;[⃛].
			"\x08ipleDot;\x03⃛",
			// Tstrok;[Ŧ] Tscr;[𝒯].
			"\x05trok;\x02Ŧ\x03cr;\x04𝒯",
			// Uarrocir;[⥉] Uacute;[Ú] Uacute[Ú] Uarr;[↟].
			"\x07rrocir;\x03⥉\x05cute;\x02Ú\x04cute\x02Ú\x03rr;\x03↟",
			// Ubreve;[Ŭ] Ubrcy;[Ў].
			"\x05reve;\x02Ŭ\x04rcy;\x02Ў",
			// Ucirc;[Û] Ucirc[Û] Ucy;[У].
			"\x04irc;\x02Û\x03irc\x02Û\x02y;\x02У",
			// Udblac;[Ű].
			"\x05blac;\x02Ű",
			// Ufr;[𝔘].
			"\x02r;\x04𝔘",
			// Ugrave;[Ù] Ugrave[Ù].
			"\x05rave;\x02Ù\x04rave\x02Ù",
			// Umacr;[Ū].
			"\x04acr;\x02Ū",
			// UnderParenthesis;[⏝] UnderBracket;[⎵] UnderBrace;[⏟] UnionPlus;[⊎] UnderBar;[_] Union;[⋃].
			"\x0fderParenthesis;\x03⏝\x0bderBracket;\x03⎵\x09derBrace;\x03⏟\x08ionPlus;\x03⊎\x07derBar;\x01_\x04ion;\x03⋃",
			// Uogon;[Ų] Uopf;[𝕌].
			"\x04gon;\x02Ų\x03pf;\x04𝕌",
			// UpArrowDownArrow;[⇅] UpperRightArrow;[↗] UpperLeftArrow;[↖] UpEquilibrium;[⥮] UpDownArrow;[↕] Updownarrow;[⇕] UpArrowBar;[⤒] UpTeeArrow;[↥] UpArrow;[↑] Uparrow;[⇑] Upsilon;[Υ] UpTee;[⊥] Upsi;[ϒ].
			"\x0fArrowDownArrow;\x03⇅\x0eperRightArrow;\x03↗\x0dperLeftArrow;\x03↖\x0cEquilibrium;\x03⥮\x0aDownArrow;\x03↕\x0adownarrow;\x03⇕\x09ArrowBar;\x03⤒\x09TeeArrow;\x03↥\x06Arrow;\x03↑\x06arrow;\x03⇑\x06silon;\x02Υ\x04Tee;\x03⊥\x03si;\x02ϒ",
			// Uring;[Ů].
			"\x04ing;\x02Ů",
			// Uscr;[𝒰].
			"\x03cr;\x04𝒰",
			// Utilde;[Ũ].
			"\x05ilde;\x02Ũ",
			// Uuml;[Ü] Uuml[Ü].
			"\x03ml;\x02Ü\x02ml\x02Ü",
			// VDash;[⊫].
			"\x04ash;\x03⊫",
			// Vbar;[⫫].
			"\x03ar;\x03⫫",
			// Vcy;[В].
			"\x02y;\x02В",
			// Vdashl;[⫦] Vdash;[⊩].
			"\x05ashl;\x03⫦\x04ash;\x03⊩",
			// VerticalSeparator;[❘] VerticalTilde;[≀] VeryThinSpace;[ ] VerticalLine;[|] VerticalBar;[∣] Verbar;[‖] Vert;[‖] Vee;[⋁].
			"\x10rticalSeparator;\x03❘\x0crticalTilde;\x03≀\x0cryThinSpace;\x03 \x0brticalLine;\x01|\x0articalBar;\x03∣\x05rbar;\x03‖\x03rt;\x03‖\x02e;\x03⋁",
			// Vfr;[𝔙].
			"\x02r;\x04𝔙",
			// Vopf;[𝕍].
			"\x03pf;\x04𝕍",
			// Vscr;[𝒱].
			"\x03cr;\x04𝒱",
			// Vvdash;[⊪].
			"\x05dash;\x03⊪",
			// Wcirc;[Ŵ].
			"\x04irc;\x02Ŵ",
			// Wedge;[⋀].
			"\x04dge;\x03⋀",
			// Wfr;[𝔚].
			"\x02r;\x04𝔚",
			// Wopf;[𝕎].
			"\x03pf;\x04𝕎",
			// Wscr;[𝒲].
			"\x03cr;\x04𝒲",
			// Xfr;[𝔛].
			"\x02r;\x04𝔛",
			// Xi;[Ξ].
			"\x01;\x02Ξ",
			// Xopf;[𝕏].
			"\x03pf;\x04𝕏",
			// Xscr;[𝒳].
			"\x03cr;\x04𝒳",
			// YAcy;[Я].
			"\x03cy;\x02Я",
			// YIcy;[Ї].
			"\x03cy;\x02Ї",
			// YUcy;[Ю].
			"\x03cy;\x02Ю",
			// Yacute;[Ý] Yacute[Ý].
			"\x05cute;\x02Ý\x04cute\x02Ý",
			// Ycirc;[Ŷ] Ycy;[Ы].
			"\x04irc;\x02Ŷ\x02y;\x02Ы",
			// Yfr;[𝔜].
			"\x02r;\x04𝔜",
			// Yopf;[𝕐].
			"\x03pf;\x04𝕐",
			// Yscr;[𝒴].
			"\x03cr;\x04𝒴",
			// Yuml;[Ÿ].
			"\x03ml;\x02Ÿ",
			// ZHcy;[Ж].
			"\x03cy;\x02Ж",
			// Zacute;[Ź].
			"\x05cute;\x02Ź",
			// Zcaron;[Ž] Zcy;[З].
			"\x05aron;\x02Ž\x02y;\x02З",
			// Zdot;[Ż].
			"\x03ot;\x02Ż",
			// ZeroWidthSpace;[​] Zeta;[Ζ].
			"\x0droWidthSpace;\x03​\x03ta;\x02Ζ",
			// Zfr;[ℨ].
			"\x02r;\x03ℨ",
			// Zopf;[ℤ].
			"\x03pf;\x03ℤ",
			// Zscr;[𝒵].
			"\x03cr;\x04𝒵",
			// aacute;[á] aacute[á].
			"\x05cute;\x02á\x04cute\x02á",
			// abreve;[ă].
			"\x05reve;\x02ă",
			// acirc;[â] acute;[´] acirc[â] acute[´] acE;[∾̳] acd;[∿] acy;[а] ac;[∾].
			"\x04irc;\x02â\x04ute;\x02´\x03irc\x02â\x03ute\x02´\x02E;\x05∾̳\x02d;\x03∿\x02y;\x02а\x01;\x03∾",
			// aelig;[æ] aelig[æ].
			"\x04lig;\x02æ\x03lig\x02æ",
			// afr;[𝔞] af;[⁡].
			"\x02r;\x04𝔞\x01;\x03⁡",
			// agrave;[à] agrave[à].
			"\x05rave;\x02à\x04rave\x02à",
			// alefsym;[ℵ] aleph;[ℵ] alpha;[α].
			"\x06efsym;\x03ℵ\x04eph;\x03ℵ\x04pha;\x02α",
			// amacr;[ā] amalg;[⨿] amp;[&] amp[&].
			"\x04acr;\x02ā\x04alg;\x03⨿\x02p;\x01&\x01p\x01&",
			// andslope;[⩘] angmsdaa;[⦨] angmsdab;[⦩] angmsdac;[⦪] angmsdad;[⦫] angmsdae;[⦬] angmsdaf;[⦭] angmsdag;[⦮] angmsdah;[⦯] angrtvbd;[⦝] angrtvb;[⊾] angzarr;[⍼] andand;[⩕] angmsd;[∡] angsph;[∢] angle;[∠] angrt;[∟] angst;[Å] andd;[⩜] andv;[⩚] ange;[⦤] and;[∧] ang;[∠].
			"\x07dslope;\x03⩘\x07gmsdaa;\x03⦨\x07gmsdab;\x03⦩\x07gmsdac;\x03⦪\x07gmsdad;\x03⦫\x07gmsdae;\x03⦬\x07gmsdaf;\x03⦭\x07gmsdag;\x03⦮\x07gmsdah;\x03⦯\x07grtvbd;\x03⦝\x06grtvb;\x03⊾\x06gzarr;\x03⍼\x05dand;\x03⩕\x05gmsd;\x03∡\x05gsph;\x03∢\x04gle;\x03∠\x04grt;\x03∟\x04gst;\x02Å\x03dd;\x03⩜\x03dv;\x03⩚\x03ge;\x03⦤\x02d;\x03∧\x02g;\x03∠",
			// aogon;[ą] aopf;[𝕒].
			"\x04gon;\x02ą\x03pf;\x04𝕒",
			// approxeq;[≊] apacir;[⩯] approx;[≈] apid;[≋] apos;['] apE;[⩰] ape;[≊] ap;[≈].
			"\x07proxeq;\x03≊\x05acir;\x03⩯\x05prox;\x03≈\x03id;\x03≋\x03os;\x01'\x02E;\x03⩰\x02e;\x03≊\x01;\x03≈",
			// aring;[å] aring[å].
			"\x04ing;\x02å\x03ing\x02å",
			// asympeq;[≍] asymp;[≈] ascr;[𝒶] ast;[*].
			"\x06ympeq;\x03≍\x04ymp;\x03≈\x03cr;\x04𝒶\x02t;\x01*",
			// atilde;[ã] atilde[ã].
			"\x05ilde;\x02ã\x04ilde\x02ã",
			// auml;[ä] auml[ä].
			"\x03ml;\x02ä\x02ml\x02ä",
			// awconint;[∳] awint;[⨑].
			"\x07conint;\x03∳\x04int;\x03⨑",
			// bNot;[⫭].
			"\x03ot;\x03⫭",
			// backepsilon;[϶] backprime;[‵] backsimeq;[⋍] backcong;[≌] barwedge;[⌅] backsim;[∽] barvee;[⊽] barwed;[⌅].
			"\x0ackepsilon;\x02϶\x08ckprime;\x03‵\x08cksimeq;\x03⋍\x07ckcong;\x03≌\x07rwedge;\x03⌅\x06cksim;\x03∽\x05rvee;\x03⊽\x05rwed;\x03⌅",
			// bbrktbrk;[⎶] bbrk;[⎵].
			"\x07rktbrk;\x03⎶\x03rk;\x03⎵",
			// bcong;[≌] bcy;[б].
			"\x04ong;\x03≌\x02y;\x02б",
			// bdquo;[„].
			"\x04quo;\x03„",
			// because;[∵] bemptyv;[⦰] between;[≬] becaus;[∵] bernou;[ℬ] bepsi;[϶] beta;[β] beth;[ℶ].
			"\x06cause;\x03∵\x06mptyv;\x03⦰\x06tween;\x03≬\x05caus;\x03∵\x05rnou;\x03ℬ\x04psi;\x02϶\x03ta;\x02β\x03th;\x03ℶ",
			// bfr;[𝔟].
			"\x02r;\x04𝔟",
			// bigtriangledown;[▽] bigtriangleup;[△] bigotimes;[⨂] bigoplus;[⨁] bigsqcup;[⨆] biguplus;[⨄] bigwedge;[⋀] bigcirc;[◯] bigodot;[⨀] bigstar;[★] bigcap;[⋂] bigcup;[⋃] bigvee;[⋁].
			"\x0egtriangledown;\x03▽\x0cgtriangleup;\x03△\x08gotimes;\x03⨂\x07goplus;\x03⨁\x07gsqcup;\x03⨆\x07guplus;\x03⨄\x07gwedge;\x03⋀\x06gcirc;\x03◯\x06godot;\x03⨀\x06gstar;\x03★\x05gcap;\x03⋂\x05gcup;\x03⋃\x05gvee;\x03⋁",
			// bkarow;[⤍].
			"\x05arow;\x03⤍",
			// blacktriangleright;[▸] blacktriangledown;[▾] blacktriangleleft;[◂] blacktriangle;[▴] blacklozenge;[⧫] blacksquare;[▪] blank;[␣] blk12;[▒] blk14;[░] blk34;[▓] block;[█].
			"\x11acktriangleright;\x03▸\x10acktriangledown;\x03▾\x10acktriangleleft;\x03◂\x0cacktriangle;\x03▴\x0backlozenge;\x03⧫\x0aacksquare;\x03▪\x04ank;\x03␣\x04k12;\x03▒\x04k14;\x03░\x04k34;\x03▓\x04ock;\x03█",
			// bnequiv;[≡⃥] bnot;[⌐] bne;[=⃥].
			"\x06equiv;\x06≡⃥\x03ot;\x03⌐\x02e;\x04=⃥",
			// boxminus;[⊟] boxtimes;[⊠] boxplus;[⊞] bottom;[⊥] bowtie;[⋈] boxbox;[⧉] boxDL;[╗] boxDR;[╔] boxDl;[╖] boxDr;[╓] boxHD;[╦] boxHU;[╩] boxHd;[╤] boxHu;[╧] boxUL;[╝] boxUR;[╚] boxUl;[╜] boxUr;[╙] boxVH;[╬] boxVL;[╣] boxVR;[╠] boxVh;[╫] boxVl;[╢] boxVr;[╟] boxdL;[╕] boxdR;[╒] boxdl;[┐] boxdr;[┌] boxhD;[╥] boxhU;[╨] boxhd;[┬] boxhu;[┴] boxuL;[╛] boxuR;[╘] boxul;[┘] boxur;[└] boxvH;[╪] boxvL;[╡] boxvR;[╞] boxvh;[┼] boxvl;[┤] boxvr;[├] bopf;[𝕓] boxH;[═] boxV;[║] boxh;[─] boxv;[│] bot;[⊥].
			"\x07xminus;\x03⊟\x07xtimes;\x03⊠\x06xplus;\x03⊞\x05ttom;\x03⊥\x05wtie;\x03⋈\x05xbox;\x03⧉\x04xDL;\x03╗\x04xDR;\x03╔\x04xDl;\x03╖\x04xDr;\x03╓\x04xHD;\x03╦\x04xHU;\x03╩\x04xHd;\x03╤\x04xHu;\x03╧\x04xUL;\x03╝\x04xUR;\x03╚\x04xUl;\x03╜\x04xUr;\x03╙\x04xVH;\x03╬\x04xVL;\x03╣\x04xVR;\x03╠\x04xVh;\x03╫\x04xVl;\x03╢\x04xVr;\x03╟\x04xdL;\x03╕\x04xdR;\x03╒\x04xdl;\x03┐\x04xdr;\x03┌\x04xhD;\x03╥\x04xhU;\x03╨\x04xhd;\x03┬\x04xhu;\x03┴\x04xuL;\x03╛\x04xuR;\x03╘\x04xul;\x03┘\x04xur;\x03└\x04xvH;\x03╪\x04xvL;\x03╡\x04xvR;\x03╞\x04xvh;\x03┼\x04xvl;\x03┤\x04xvr;\x03├\x03pf;\x04𝕓\x03xH;\x03═\x03xV;\x03║\x03xh;\x03─\x03xv;\x03│\x02t;\x03⊥",
			// bprime;[‵].
			"\x05rime;\x03‵",
			// brvbar;[¦] breve;[˘] brvbar[¦].
			"\x05vbar;\x02¦\x04eve;\x02˘\x04vbar\x02¦",
			// bsolhsub;[⟈] bsemi;[⁏] bsime;[⋍] bsolb;[⧅] bscr;[𝒷] bsim;[∽] bsol;[\\].
			"\x07olhsub;\x03⟈\x04emi;\x03⁏\x04ime;\x03⋍\x04olb;\x03⧅\x03cr;\x04𝒷\x03im;\x03∽\x03ol;\x01\\",
			// bullet;[•] bumpeq;[≏] bumpE;[⪮] bumpe;[≏] bull;[•] bump;[≎].
			"\x05llet;\x03•\x05mpeq;\x03≏\x04mpE;\x03⪮\x04mpe;\x03≏\x03ll;\x03•\x03mp;\x03≎",
			// capbrcup;[⩉] cacute;[ć] capand;[⩄] capcap;[⩋] capcup;[⩇] capdot;[⩀] caret;[⁁] caron;[ˇ] caps;[∩︀] cap;[∩].
			"\x07pbrcup;\x03⩉\x05cute;\x02ć\x05pand;\x03⩄\x05pcap;\x03⩋\x05pcup;\x03⩇\x05pdot;\x03⩀\x04ret;\x03⁁\x04ron;\x02ˇ\x03ps;\x06∩︀\x02p;\x03∩",
			// ccupssm;[⩐] ccaron;[č] ccedil;[ç] ccaps;[⩍] ccedil[ç] ccirc;[ĉ] ccups;[⩌].
			"\x06upssm;\x03⩐\x05aron;\x02č\x05edil;\x02ç\x04aps;\x03⩍\x04edil\x02ç\x04irc;\x02ĉ\x04ups;\x03⩌",
			// cdot;[ċ].
			"\x03ot;\x02ċ",
			// centerdot;[·] cemptyv;[⦲] cedil;[¸] cedil[¸] cent;[¢] cent[¢].
			"\x08nterdot;\x02·\x06mptyv;\x03⦲\x04dil;\x02¸\x03dil\x02¸\x03nt;\x02¢\x02nt\x02¢",
			// cfr;[𝔠].
			"\x02r;\x04𝔠",
			// checkmark;[✓] check;[✓] chcy;[ч] chi;[χ].
			"\x08eckmark;\x03✓\x04eck;\x03✓\x03cy;\x02ч\x02i;\x02χ",
			// circlearrowright;[↻] circlearrowleft;[↺] circledcirc;[⊚] circleddash;[⊝] circledast;[⊛] circledR;[®] circledS;[Ⓢ] cirfnint;[⨐] cirscir;[⧂] circeq;[≗] cirmid;[⫯] cirE;[⧃] circ;[ˆ] cire;[≗] cir;[○].
			"\x0frclearrowright;\x03↻\x0erclearrowleft;\x03↺\x0arcledcirc;\x03⊚\x0arcleddash;\x03⊝\x09rcledast;\x03⊛\x07rcledR;\x02®\x07rcledS;\x03Ⓢ\x07rfnint;\x03⨐\x06rscir;\x03⧂\x05rceq;\x03≗\x05rmid;\x03⫯\x03rE;\x03⧃\x03rc;\x02ˆ\x03re;\x03≗\x02r;\x03○",
			// clubsuit;[♣] clubs;[♣].
			"\x07ubsuit;\x03♣\x04ubs;\x03♣",
			// complement;[∁] complexes;[ℂ] coloneq;[≔] congdot;[⩭] colone;[≔] commat;[@] compfn;[∘] conint;[∮] coprod;[∐] copysr;[℗] colon;[:] comma;[,] comp;[∁] cong;[≅] copf;[𝕔] copy;[©] copy[©].
			"\x09mplement;\x03∁\x08mplexes;\x03ℂ\x06loneq;\x03≔\x06ngdot;\x03⩭\x05lone;\x03≔\x05mmat;\x01@\x05mpfn;\x03∘\x05nint;\x03∮\x05prod;\x03∐\x05pysr;\x03℗\x04lon;\x01:\x04mma;\x01,\x03mp;\x03∁\x03ng;\x03≅\x03pf;\x04𝕔\x03py;\x02©\x02py\x02©",
			// crarr;[↵] cross;[✗].
			"\x04arr;\x03↵\x04oss;\x03✗",
			// csube;[⫑] csupe;[⫒] cscr;[𝒸] csub;[⫏] csup;[⫐].
			"\x04ube;\x03⫑\x04upe;\x03⫒\x03cr;\x04𝒸\x03ub;\x03⫏\x03up;\x03⫐",
			// ctdot;[⋯].
			"\x04dot;\x03⋯",
			// curvearrowright;[↷] curvearrowleft;[↶] curlyeqprec;[⋞] curlyeqsucc;[⋟] curlywedge;[⋏] cupbrcap;[⩈] curlyvee;[⋎] cudarrl;[⤸] cudarrr;[⤵] cularrp;[⤽] curarrm;[⤼] cularr;[↶] cupcap;[⩆] cupcup;[⩊] cupdot;[⊍] curarr;[↷] curren;[¤] cuepr;[⋞] cuesc;[⋟] cupor;[⩅] curren[¤] cuvee;[⋎] cuwed;[⋏] cups;[∪︀] cup;[∪].
			"\x0ervearrowright;\x03↷\x0drvearrowleft;\x03↶\x0arlyeqprec;\x03⋞\x0arlyeqsucc;\x03⋟\x09rlywedge;\x03⋏\x07pbrcap;\x03⩈\x07rlyvee;\x03⋎\x06darrl;\x03⤸\x06darrr;\x03⤵\x06larrp;\x03⤽\x06rarrm;\x03⤼\x05larr;\x03↶\x05pcap;\x03⩆\x05pcup;\x03⩊\x05pdot;\x03⊍\x05rarr;\x03↷\x05rren;\x02¤\x04epr;\x03⋞\x04esc;\x03⋟\x04por;\x03⩅\x04rren\x02¤\x04vee;\x03⋎\x04wed;\x03⋏\x03ps;\x06∪︀\x02p;\x03∪",
			// cwconint;[∲] cwint;[∱].
			"\x07conint;\x03∲\x04int;\x03∱",
			// cylcty;[⌭].
			"\x05lcty;\x03⌭",
			// dArr;[⇓].
			"\x03rr;\x03⇓",
			// dHar;[⥥].
			"\x03ar;\x03⥥",
			// dagger;[†] daleth;[ℸ] dashv;[⊣] darr;[↓] dash;[‐].
			"\x05gger;\x03†\x05leth;\x03ℸ\x04shv;\x03⊣\x03rr;\x03↓\x03sh;\x03‐",
			// dbkarow;[⤏] dblac;[˝].
			"\x06karow;\x03⤏\x04lac;\x02˝",
			// dcaron;[ď] dcy;[д].
			"\x05aron;\x02ď\x02y;\x02д",
			// ddagger;[‡] ddotseq;[⩷] ddarr;[⇊] dd;[ⅆ].
			"\x06agger;\x03‡\x06otseq;\x03⩷\x04arr;\x03⇊\x01;\x03ⅆ",
			// demptyv;[⦱] delta;[δ] deg;[°] deg[°].
			"\x06mptyv;\x03⦱\x04lta;\x02δ\x02g;\x02°\x01g\x02°",
			// dfisht;[⥿] dfr;[𝔡].
			"\x05isht;\x03⥿\x02r;\x04𝔡",
			// dharl;[⇃] dharr;[⇂].
			"\x04arl;\x03⇃\x04arr;\x03⇂",
			// divideontimes;[⋇] diamondsuit;[♦] diamond;[⋄] digamma;[ϝ] divide;[÷] divonx;[⋇] diams;[♦] disin;[⋲] divide[÷] diam;[⋄] die;[¨] div;[÷].
			"\x0cvideontimes;\x03⋇\x0aamondsuit;\x03♦\x06amond;\x03⋄\x06gamma;\x02ϝ\x05vide;\x02÷\x05vonx;\x03⋇\x04ams;\x03♦\x04sin;\x03⋲\x04vide\x02÷\x03am;\x03⋄\x02e;\x02¨\x02v;\x02÷",
			// djcy;[ђ].
			"\x03cy;\x02ђ",
			// dlcorn;[⌞] dlcrop;[⌍].
			"\x05corn;\x03⌞\x05crop;\x03⌍",
			// downharpoonright;[⇂] downharpoonleft;[⇃] doublebarwedge;[⌆] downdownarrows;[⇊] dotsquare;[⊡] downarrow;[↓] doteqdot;[≑] dotminus;[∸] dotplus;[∔] dollar;[$] doteq;[≐] dopf;[𝕕] dot;[˙].
			"\x0fwnharpoonright;\x03⇂\x0ewnharpoonleft;\x03⇃\x0dublebarwedge;\x03⌆\x0dwndownarrows;\x03⇊\x08tsquare;\x03⊡\x08wnarrow;\x03↓\x07teqdot;\x03≑\x07tminus;\x03∸\x06tplus;\x03∔\x05llar;\x01$\x04teq;\x03≐\x03pf;\x04𝕕\x02t;\x02˙",
			// drbkarow;[⤐] drcorn;[⌟] drcrop;[⌌].
			"\x07bkarow;\x03⤐\x05corn;\x03⌟\x05crop;\x03⌌",
			// dstrok;[đ] dscr;[𝒹] dscy;[ѕ] dsol;[⧶].
			"\x05trok;\x02đ\x03cr;\x04𝒹\x03cy;\x02ѕ\x03ol;\x03⧶",
			// dtdot;[⋱] dtrif;[▾] dtri;[▿].
			"\x04dot;\x03⋱\x04rif;\x03▾\x03ri;\x03▿",
			// duarr;[⇵] duhar;[⥯].
			"\x04arr;\x03⇵\x04har;\x03⥯",
			// dwangle;[⦦].
			"\x06angle;\x03⦦",
			// dzigrarr;[⟿] dzcy;[џ].
			"\x07igrarr;\x03⟿\x03cy;\x02џ",
			// eDDot;[⩷] eDot;[≑].
			"\x04Dot;\x03⩷\x03ot;\x03≑",
			// eacute;[é] easter;[⩮] eacute[é].
			"\x05cute;\x02é\x05ster;\x03⩮\x04cute\x02é",
			// ecaron;[ě] ecolon;[≕] ecirc;[ê] ecir;[≖] ecirc[ê] ecy;[э].
			"\x05aron;\x02ě\x05olon;\x03≕\x04irc;\x02ê\x03ir;\x03≖\x03irc\x02ê\x02y;\x02э",
			// edot;[ė].
			"\x03ot;\x02ė",
			// ee;[ⅇ].
			"\x01;\x03ⅇ",
			// efDot;[≒] efr;[𝔢].
			"\x04Dot;\x03≒\x02r;\x04𝔢",
			// egrave;[è] egsdot;[⪘] egrave[è] egs;[⪖] eg;[⪚].
			"\x05rave;\x02è\x05sdot;\x03⪘\x04rave\x02è\x02s;\x03⪖\x01;\x03⪚",
			// elinters;[⏧] elsdot;[⪗] ell;[ℓ] els;[⪕] el;[⪙].
			"\x07inters;\x03⏧\x05sdot;\x03⪗\x02l;\x03ℓ\x02s;\x03⪕\x01;\x03⪙",
			// emptyset;[∅] emptyv;[∅] emsp13;[ ] emsp14;[ ] emacr;[ē] empty;[∅] emsp;[ ].
			"\x07ptyset;\x03∅\x05ptyv;\x03∅\x05sp13;\x03 \x05sp14;\x03 \x04acr;\x02ē\x04pty;\x03∅\x03sp;\x03 ",
			// ensp;[ ] eng;[ŋ].
			"\x03sp;\x03 \x02g;\x02ŋ",
			// eogon;[ę] eopf;[𝕖].
			"\x04gon;\x02ę\x03pf;\x04𝕖",
			// epsilon;[ε] eparsl;[⧣] eplus;[⩱] epsiv;[ϵ] epar;[⋕] epsi;[ε].
			"\x06silon;\x02ε\x05arsl;\x03⧣\x04lus;\x03⩱\x04siv;\x02ϵ\x03ar;\x03⋕\x03si;\x02ε",
			// eqslantless;[⪕] eqslantgtr;[⪖] eqvparsl;[⧥] eqcolon;[≕] equivDD;[⩸] eqcirc;[≖] equals;[=] equest;[≟] eqsim;[≂] equiv;[≡].
			"\x0aslantless;\x03⪕\x09slantgtr;\x03⪖\x07vparsl;\x03⧥\x06colon;\x03≕\x06uivDD;\x03⩸\x05circ;\x03≖\x05uals;\x01=\x05uest;\x03≟\x04sim;\x03≂\x04uiv;\x03≡",
			// erDot;[≓] erarr;[⥱].
			"\x04Dot;\x03≓\x04arr;\x03⥱",
			// esdot;[≐] escr;[ℯ] esim;[≂].
			"\x04dot;\x03≐\x03cr;\x03ℯ\x03im;\x03≂",
			// eta;[η] eth;[ð] eth[ð].
			"\x02a;\x02η\x02h;\x02ð\x01h\x02ð",
			// euml;[ë] euro;[€] euml[ë].
			"\x03ml;\x02ë\x03ro;\x03€\x02ml\x02ë",
			// exponentiale;[ⅇ] expectation;[ℰ] exist;[∃] excl;[!].
			"\x0bponentiale;\x03ⅇ\x0apectation;\x03ℰ\x04ist;\x03∃\x03cl;\x01!",
			// fallingdotseq;[≒].
			"\x0cllingdotseq;\x03≒",
			// fcy;[ф].
			"\x02y;\x02ф",
			// female;[♀].
			"\x05male;\x03♀",
			// ffilig;[ﬃ] ffllig;[ﬄ] fflig;[ﬀ] ffr;[𝔣].
			"\x05ilig;\x03ﬃ\x05llig;\x03ﬄ\x04lig;\x03ﬀ\x02r;\x04𝔣",
			// filig;[ﬁ].
			"\x04lig;\x03ﬁ",
			// fjlig;[fj].
			"\x04lig;\x02fj",
			// fllig;[ﬂ] fltns;[▱] flat;[♭].
			"\x04lig;\x03ﬂ\x04tns;\x03▱\x03at;\x03♭",
			// fnof;[ƒ].
			"\x03of;\x02ƒ",
			// forall;[∀] forkv;[⫙] fopf;[𝕗] fork;[⋔].
			"\x05rall;\x03∀\x04rkv;\x03⫙\x03pf;\x04𝕗\x03rk;\x03⋔",
			// fpartint;[⨍].
			"\x07artint;\x03⨍",
			// frac12;[½] frac13;[⅓] frac14;[¼] frac15;[⅕] frac16;[⅙] frac18;[⅛] frac23;[⅔] frac25;[⅖] frac34;[¾] frac35;[⅗] frac38;[⅜] frac45;[⅘] frac56;[⅚] frac58;[⅝] frac78;[⅞] frac12[½] frac14[¼] frac34[¾] frasl;[⁄] frown;[⌢].
			"\x05ac12;\x02½\x05ac13;\x03⅓\x05ac14;\x02¼\x05ac15;\x03⅕\x05ac16;\x03⅙\x05ac18;\x03⅛\x05ac23;\x03⅔\x05ac25;\x03⅖\x05ac34;\x02¾\x05ac35;\x03⅗\x05ac38;\x03⅜\x05ac45;\x03⅘\x05ac56;\x03⅚\x05ac58;\x03⅝\x05ac78;\x03⅞\x04ac12\x02½\x04ac14\x02¼\x04ac34\x02¾\x04asl;\x03⁄\x04own;\x03⌢",
			// fscr;[𝒻].
			"\x03cr;\x04𝒻",
			// gEl;[⪌] gE;[≧].
			"\x02l;\x03⪌\x01;\x03≧",
			// gacute;[ǵ] gammad;[ϝ] gamma;[γ] gap;[⪆].
			"\x05cute;\x02ǵ\x05mmad;\x02ϝ\x04mma;\x02γ\x02p;\x03⪆",
			// gbreve;[ğ].
			"\x05reve;\x02ğ",
			// gcirc;[ĝ] gcy;[г].
			"\x04irc;\x02ĝ\x02y;\x02г",
			// gdot;[ġ].
			"\x03ot;\x02ġ",
			// geqslant;[⩾] gesdotol;[⪄] gesdoto;[⪂] gesdot;[⪀] gesles;[⪔] gescc;[⪩] geqq;[≧] gesl;[⋛︀] gel;[⋛] geq;[≥] ges;[⩾] ge;[≥].
			"\x07qslant;\x03⩾\x07sdotol;\x03⪄\x06sdoto;\x03⪂\x05sdot;\x03⪀\x05sles;\x03⪔\x04scc;\x03⪩\x03qq;\x03≧\x03sl;\x06⋛︀\x02l;\x03⋛\x02q;\x03≥\x02s;\x03⩾\x01;\x03≥",
			// gfr;[𝔤].
			"\x02r;\x04𝔤",
			// ggg;[⋙] gg;[≫].
			"\x02g;\x03⋙\x01;\x03≫",
			// gimel;[ℷ].
			"\x04mel;\x03ℷ",
			// gjcy;[ѓ].
			"\x03cy;\x02ѓ",
			// glE;[⪒] gla;[⪥] glj;[⪤] gl;[≷].
			"\x02E;\x03⪒\x02a;\x03⪥\x02j;\x03⪤\x01;\x03≷",
			// gnapprox;[⪊] gneqq;[≩] gnsim;[⋧] gnap;[⪊] gneq;[⪈] gnE;[≩] gne;[⪈].
			"\x07approx;\x03⪊\x04eqq;\x03≩\x04sim;\x03⋧\x03ap;\x03⪊\x03eq;\x03⪈\x02E;\x03≩\x02e;\x03⪈",
			// gopf;[𝕘].
			"\x03pf;\x04𝕘",
			// grave;[`].
			"\x04ave;\x01`",
			// gsime;[⪎] gsiml;[⪐] gscr;[ℊ] gsim;[≳].
			"\x04ime;\x03⪎\x04iml;\x03⪐\x03cr;\x03ℊ\x03im;\x03≳",
			// gtreqqless;[⪌] gtrapprox;[⪆] gtreqless;[⋛] gtquest;[⩼] gtrless;[≷] gtlPar;[⦕] gtrarr;[⥸] gtrdot;[⋗] gtrsim;[≳] gtcir;[⩺] gtdot;[⋗] gtcc;[⪧] gt;[>].
			"\x09reqqless;\x03⪌\x08rapprox;\x03⪆\x08reqless;\x03⋛\x06quest;\x03⩼\x06rless;\x03≷\x05lPar;\x03⦕\x05rarr;\x03⥸\x05rdot;\x03⋗\x05rsim;\x03≳\x04cir;\x03⩺\x04dot;\x03⋗\x03cc;\x03⪧\x01;\x01>",
			// gvertneqq;[≩︀] gvnE;[≩︀].
			"\x08ertneqq;\x06≩︀\x03nE;\x06≩︀",
			// hArr;[⇔].
			"\x03rr;\x03⇔",
			// harrcir;[⥈] hairsp;[ ] hamilt;[ℋ] hardcy;[ъ] harrw;[↭] half;[½] harr;[↔].
			"\x06rrcir;\x03⥈\x05irsp;\x03 \x05milt;\x03ℋ\x05rdcy;\x02ъ\x04rrw;\x03↭\x03lf;\x02½\x03rr;\x03↔",
			// hbar;[ℏ].
			"\x03ar;\x03ℏ",
			// hcirc;[ĥ].
			"\x04irc;\x02ĥ",
			// heartsuit;[♥] hearts;[♥] hellip;[…] hercon;[⊹].
			"\x08artsuit;\x03♥\x05arts;\x03♥\x05llip;\x03…\x05rcon;\x03⊹",
			// hfr;[𝔥].
			"\x02r;\x04𝔥",
			// hksearow;[⤥] hkswarow;[⤦].
			"\x07searow;\x03⤥\x07swarow;\x03⤦",
			// hookrightarrow;[↪] hookleftarrow;[↩] homtht;[∻] horbar;[―] hoarr;[⇿] hopf;[𝕙].
			"\x0dokrightarrow;\x03↪\x0cokleftarrow;\x03↩\x05mtht;\x03∻\x05rbar;\x03―\x04arr;\x03⇿\x03pf;\x04𝕙",
			// hslash;[ℏ] hstrok;[ħ] hscr;[𝒽].
			"\x05lash;\x03ℏ\x05trok;\x02ħ\x03cr;\x04𝒽",
			// hybull;[⁃] hyphen;[‐].
			"\x05bull;\x03⁃\x05phen;\x03‐",
			// iacute;[í] iacute[í].
			"\x05cute;\x02í\x04cute\x02í",
			// icirc;[î] icirc[î] icy;[и] ic;[⁣].
			"\x04irc;\x02î\x03irc\x02î\x02y;\x02и\x01;\x03⁣",
			// iexcl;[¡] iecy;[е] iexcl[¡].
			"\x04xcl;\x02¡\x03cy;\x02е\x03xcl\x02¡",
			// iff;[⇔] ifr;[𝔦].
			"\x02f;\x03⇔\x02r;\x04𝔦",
			// igrave;[ì] igrave[ì].
			"\x05rave;\x02ì\x04rave\x02ì",
			// iiiint;[⨌] iinfin;[⧜] iiint;[∭] iiota;[℩] ii;[ⅈ].
			"\x05iint;\x03⨌\x05nfin;\x03⧜\x04int;\x03∭\x04ota;\x03℩\x01;\x03ⅈ",
			// ijlig;[ĳ].
			"\x04lig;\x02ĳ",
			// imagline;[ℐ] imagpart;[ℑ] imacr;[ī] image;[ℑ] imath;[ı] imped;[Ƶ] imof;[⊷].
			"\x07agline;\x03ℐ\x07agpart;\x03ℑ\x04acr;\x02ī\x04age;\x03ℑ\x04ath;\x02ı\x04ped;\x02Ƶ\x03of;\x03⊷",
			// infintie;[⧝] integers;[ℤ] intercal;[⊺] intlarhk;[⨗] intprod;[⨼] incare;[℅] inodot;[ı] intcal;[⊺] infin;[∞] int;[∫] in;[∈].
			"\x07fintie;\x03⧝\x07tegers;\x03ℤ\x07tercal;\x03⊺\x07tlarhk;\x03⨗\x06tprod;\x03⨼\x05care;\x03℅\x05odot;\x02ı\x05tcal;\x03⊺\x04fin;\x03∞\x02t;\x03∫\x01;\x03∈",
			// iogon;[į] iocy;[ё] iopf;[𝕚] iota;[ι].
			"\x04gon;\x02į\x03cy;\x02ё\x03pf;\x04𝕚\x03ta;\x02ι",
			// iprod;[⨼].
			"\x04rod;\x03⨼",
			// iquest;[¿] iquest[¿].
			"\x05uest;\x02¿\x04uest\x02¿",
			// isindot;[⋵] isinsv;[⋳] isinE;[⋹] isins;[⋴] isinv;[∈] iscr;[𝒾] isin;[∈].
			"\x06indot;\x03⋵\x05insv;\x03⋳\x04inE;\x03⋹\x04ins;\x03⋴\x04inv;\x03∈\x03cr;\x04𝒾\x03in;\x03∈",
			// itilde;[ĩ] it;[⁢].
			"\x05ilde;\x02ĩ\x01;\x03⁢",
			// iukcy;[і] iuml;[ï] iuml[ï].
			"\x04kcy;\x02і\x03ml;\x02ï\x02ml\x02ï",
			// jcirc;[ĵ] jcy;[й].
			"\x04irc;\x02ĵ\x02y;\x02й",
			// jfr;[𝔧].
			"\x02r;\x04𝔧",
			// jmath;[ȷ].
			"\x04ath;\x02ȷ",
			// jopf;[𝕛].
			"\x03pf;\x04𝕛",
			// jsercy;[ј] jscr;[𝒿].
			"\x05ercy;\x02ј\x03cr;\x04𝒿",
			// jukcy;[є].
			"\x04kcy;\x02є",
			// kappav;[ϰ] kappa;[κ].
			"\x05ppav;\x02ϰ\x04ppa;\x02κ",
			// kcedil;[ķ] kcy;[к].
			"\x05edil;\x02ķ\x02y;\x02к",
			// kfr;[𝔨].
			"\x02r;\x04𝔨",
			// kgreen;[ĸ].
			"\x05reen;\x02ĸ",
			// khcy;[х].
			"\x03cy;\x02х",
			// kjcy;[ќ].
			"\x03cy;\x02ќ",
			// kopf;[𝕜].
			"\x03pf;\x04𝕜",
			// kscr;[𝓀].
			"\x03cr;\x04𝓀",
			// lAtail;[⤛] lAarr;[⇚] lArr;[⇐].
			"\x05tail;\x03⤛\x04arr;\x03⇚\x03rr;\x03⇐",
			// lBarr;[⤎].
			"\x04arr;\x03⤎",
			// lEg;[⪋] lE;[≦].
			"\x02g;\x03⪋\x01;\x03≦",
			// lHar;[⥢].
			"\x03ar;\x03⥢",
			// laemptyv;[⦴] larrbfs;[⤟] larrsim;[⥳] lacute;[ĺ] lagran;[ℒ] lambda;[λ] langle;[⟨] larrfs;[⤝] larrhk;[↩] larrlp;[↫] larrpl;[⤹] larrtl;[↢] latail;[⤙] langd;[⦑] laquo;[«] larrb;[⇤] lates;[⪭︀] lang;[⟨] laquo[«] larr;[←] late;[⪭] lap;[⪅] lat;[⪫].
			"\x07emptyv;\x03⦴\x06rrbfs;\x03⤟\x06rrsim;\x03⥳\x05cute;\x02ĺ\x05gran;\x03ℒ\x05mbda;\x02λ\x05ngle;\x03⟨\x05rrfs;\x03⤝\x05rrhk;\x03↩\x05rrlp;\x03↫\x05rrpl;\x03⤹\x05rrtl;\x03↢\x05tail;\x03⤙\x04ngd;\x03⦑\x04quo;\x02«\x04rrb;\x03⇤\x04tes;\x06⪭︀\x03ng;\x03⟨\x03quo\x02«\x03rr;\x03←\x03te;\x03⪭\x02p;\x03⪅\x02t;\x03⪫",
			// lbrksld;[⦏] lbrkslu;[⦍] lbrace;[{] lbrack;[[] lbarr;[⤌] lbbrk;[❲] lbrke;[⦋].
			"\x06rksld;\x03⦏\x06rkslu;\x03⦍\x05race;\x01{\x05rack;\x01[\x04arr;\x03⤌\x04brk;\x03❲\x04rke;\x03⦋",
			// lcaron;[ľ] lcedil;[ļ] lceil;[⌈] lcub;[{] lcy;[л].
			"\x05aron;\x02ľ\x05edil;\x02ļ\x04eil;\x03⌈\x03ub;\x01{\x02y;\x02л",
			// ldrushar;[⥋] ldrdhar;[⥧] ldquor;[„] ldquo;[“] ldca;[⤶] ldsh;[↲].
			"\x07rushar;\x03⥋\x06rdhar;\x03⥧\x05quor;\x03„\x04quo;\x03“\x03ca;\x03⤶\x03sh;\x03↲",
			// leftrightsquigarrow;[↭] leftrightharpoons;[⇋] leftharpoondown;[↽] leftrightarrows;[⇆] leftleftarrows;[⇇] leftrightarrow;[↔] leftthreetimes;[⋋] leftarrowtail;[↢] leftharpoonup;[↼] lessapprox;[⪅] lesseqqgtr;[⪋] leftarrow;[←] lesseqgtr;[⋚] leqslant;[⩽] lesdotor;[⪃] lesdoto;[⪁] lessdot;[⋖] lessgtr;[≶] lesssim;[≲] lesdot;[⩿] lesges;[⪓] lescc;[⪨] leqq;[≦] lesg;[⋚︀] leg;[⋚] leq;[≤] les;[⩽] le;[≤].
			"\x12ftrightsquigarrow;\x03↭\x10ftrightharpoons;\x03⇋\x0eftharpoondown;\x03↽\x0eftrightarrows;\x03⇆\x0dftleftarrows;\x03⇇\x0dftrightarrow;\x03↔\x0dftthreetimes;\x03⋋\x0cftarrowtail;\x03↢\x0cftharpoonup;\x03↼\x09ssapprox;\x03⪅\x09sseqqgtr;\x03⪋\x08ftarrow;\x03←\x08sseqgtr;\x03⋚\x07qslant;\x03⩽\x07sdotor;\x03⪃\x06sdoto;\x03⪁\x06ssdot;\x03⋖\x06ssgtr;\x03≶\x06sssim;\x03≲\x05sdot;\x03⩿\x05sges;\x03⪓\x04scc;\x03⪨\x03qq;\x03≦\x03sg;\x06⋚︀\x02g;\x03⋚\x02q;\x03≤\x02s;\x03⩽\x01;\x03≤",
			// lfisht;[⥼] lfloor;[⌊] lfr;[𝔩].
			"\x05isht;\x03⥼\x05loor;\x03⌊\x02r;\x04𝔩",
			// lgE;[⪑] lg;[≶].
			"\x02E;\x03⪑\x01;\x03≶",
			// lharul;[⥪] lhard;[↽] lharu;[↼] lhblk;[▄].
			"\x05arul;\x03⥪\x04ard;\x03↽\x04aru;\x03↼\x04blk;\x03▄",
			// ljcy;[љ].
			"\x03cy;\x02љ",
			// llcorner;[⌞] llhard;[⥫] llarr;[⇇] lltri;[◺] ll;[≪].
			"\x07corner;\x03⌞\x05hard;\x03⥫\x04arr;\x03⇇\x04tri;\x03◺\x01;\x03≪",
			// lmoustache;[⎰] lmidot;[ŀ] lmoust;[⎰].
			"\x09oustache;\x03⎰\x05idot;\x02ŀ\x05oust;\x03⎰",
			// lnapprox;[⪉] lneqq;[≨] lnsim;[⋦] lnap;[⪉] lneq;[⪇] lnE;[≨] lne;[⪇].
			"\x07approx;\x03⪉\x04eqq;\x03≨\x04sim;\x03⋦\x03ap;\x03⪉\x03eq;\x03⪇\x02E;\x03≨\x02e;\x03⪇",
			// longleftrightarrow;[⟷] longrightarrow;[⟶] looparrowright;[↬] longleftarrow;[⟵] looparrowleft;[↫] longmapsto;[⟼] lotimes;[⨴] lozenge;[◊] loplus;[⨭] lowast;[∗] lowbar;[_] loang;[⟬] loarr;[⇽] lobrk;[⟦] lopar;[⦅] lopf;[𝕝] lozf;[⧫] loz;[◊].
			"\x11ngleftrightarrow;\x03⟷\x0dngrightarrow;\x03⟶\x0doparrowright;\x03↬\x0cngleftarrow;\x03⟵\x0coparrowleft;\x03↫\x09ngmapsto;\x03⟼\x06times;\x03⨴\x06zenge;\x03◊\x05plus;\x03⨭\x05wast;\x03∗\x05wbar;\x01_\x04ang;\x03⟬\x04arr;\x03⇽\x04brk;\x03⟦\x04par;\x03⦅\x03pf;\x04𝕝\x03zf;\x03⧫\x02z;\x03◊",
			// lparlt;[⦓] lpar;[(].
			"\x05arlt;\x03⦓\x03ar;\x01(",
			// lrcorner;[⌟] lrhard;[⥭] lrarr;[⇆] lrhar;[⇋] lrtri;[⊿] lrm;[‎].
			"\x07corner;\x03⌟\x05hard;\x03⥭\x04arr;\x03⇆\x04har;\x03⇋\x04tri;\x03⊿\x02m;\x03‎",
			// lsaquo;[‹] lsquor;[‚] lstrok;[ł] lsime;[⪍] lsimg;[⪏] lsquo;[‘] lscr;[𝓁] lsim;[≲] lsqb;[[] lsh;[↰].
			"\x05aquo;\x03‹\x05quor;\x03‚\x05trok;\x02ł\x04ime;\x03⪍\x04img;\x03⪏\x04quo;\x03‘\x03cr;\x04𝓁\x03im;\x03≲\x03qb;\x01[\x02h;\x03↰",
			// ltquest;[⩻] lthree;[⋋] ltimes;[⋉] ltlarr;[⥶] ltrPar;[⦖] ltcir;[⩹] ltdot;[⋖] ltrie;[⊴] ltrif;[◂] ltcc;[⪦] ltri;[◃] lt;[<].
			"\x06quest;\x03⩻\x05hree;\x03⋋\x05imes;\x03⋉\x05larr;\x03⥶\x05rPar;\x03⦖\x04cir;\x03⩹\x04dot;\x03⋖\x04rie;\x03⊴\x04rif;\x03◂\x03cc;\x03⪦\x03ri;\x03◃\x01;\x01<",
			// lurdshar;[⥊] luruhar;[⥦].
			"\x07rdshar;\x03⥊\x06ruhar;\x03⥦",
			// lvertneqq;[≨︀] lvnE;[≨︀].
			"\x08ertneqq;\x06≨︀\x03nE;\x06≨︀",
			// mDDot;[∺].
			"\x04Dot;\x03∺",
			// mapstodown;[↧] mapstoleft;[↤] mapstoup;[↥] maltese;[✠] mapsto;[↦] marker;[▮] macr;[¯] male;[♂] malt;[✠] macr[¯] map;[↦].
			"\x09pstodown;\x03↧\x09pstoleft;\x03↤\x07pstoup;\x03↥\x06ltese;\x03✠\x05psto;\x03↦\x05rker;\x03▮\x03cr;\x02¯\x03le;\x03♂\x03lt;\x03✠\x02cr\x02¯\x02p;\x03↦",
			// mcomma;[⨩] mcy;[м].
			"\x05omma;\x03⨩\x02y;\x02м",
			// mdash;[—].
			"\x04ash;\x03—",
			// measuredangle;[∡].
			"\x0casuredangle;\x03∡",
			// mfr;[𝔪].
			"\x02r;\x04𝔪",
			// mho;[℧].
			"\x02o;\x03℧",
			// minusdu;[⨪] midast;[*] midcir;[⫰] middot;[·] minusb;[⊟] minusd;[∸] micro;[µ] middot[·] minus;[−] micro[µ] mid;[∣].
			"\x06nusdu;\x03⨪\x05dast;\x01*\x05dcir;\x03⫰\x05ddot;\x02·\x05nusb;\x03⊟\x05nusd;\x03∸\x04cro;\x02µ\x04ddot\x02·\x04nus;\x03−\x03cro\x02µ\x02d;\x03∣",
			// mlcp;[⫛] mldr;[…].
			"\x03cp;\x03⫛\x03dr;\x03…",
			// mnplus;[∓].
			"\x05plus;\x03∓",
			// models;[⊧] mopf;[𝕞].
			"\x05dels;\x03⊧\x03pf;\x04𝕞",
			// mp;[∓].
			"\x01;\x03∓",
			// mstpos;[∾] mscr;[𝓂].
			"\x05tpos;\x03∾\x03cr;\x04𝓂",
			// multimap;[⊸] mumap;[⊸] mu;[μ].
			"\x07ltimap;\x03⊸\x04map;\x03⊸\x01;\x02μ",
			// nGtv;[≫̸] nGg;[⋙̸] nGt;[≫⃒].
			"\x03tv;\x05≫̸\x02g;\x05⋙̸\x02t;\x06≫⃒",
			// nLeftrightarrow;[⇎] nLeftarrow;[⇍] nLtv;[≪̸] nLl;[⋘̸] nLt;[≪⃒].
			"\x0eeftrightarrow;\x03⇎\x09eftarrow;\x03⇍\x03tv;\x05≪̸\x02l;\x05⋘̸\x02t;\x06≪⃒",
			// nRightarrow;[⇏].
			"\x0aightarrow;\x03⇏",
			// nVDash;[⊯] nVdash;[⊮].
			"\x05Dash;\x03⊯\x05dash;\x03⊮",
			// naturals;[ℕ] napprox;[≉] natural;[♮] nacute;[ń] nabla;[∇] napid;[≋̸] napos;[ŉ] natur;[♮] nang;[∠⃒] napE;[⩰̸] nap;[≉].
			"\x07turals;\x03ℕ\x06pprox;\x03≉\x06tural;\x03♮\x05cute;\x02ń\x04bla;\x03∇\x04pid;\x05≋̸\x04pos;\x02ŉ\x04tur;\x03♮\x03ng;\x06∠⃒\x03pE;\x05⩰̸\x02p;\x03≉",
			// nbumpe;[≏̸] nbump;[≎̸] nbsp;[ ] nbsp[ ].
			"\x05umpe;\x05≏̸\x04ump;\x05≎̸\x03sp;\x02 \x02sp\x02 ",
			// ncongdot;[⩭̸] ncaron;[ň] ncedil;[ņ] ncong;[≇] ncap;[⩃] ncup;[⩂] ncy;[н].
			"\x07ongdot;\x05⩭̸\x05aron;\x02ň\x05edil;\x02ņ\x04ong;\x03≇\x03ap;\x03⩃\x03up;\x03⩂\x02y;\x02н",
			// ndash;[–].
			"\x04ash;\x03–",
			// nearrow;[↗] nexists;[∄] nearhk;[⤤] nequiv;[≢] nesear;[⤨] nexist;[∄] neArr;[⇗] nearr;[↗] nedot;[≐̸] nesim;[≂̸] ne;[≠].
			"\x06arrow;\x03↗\x06xists;\x03∄\x05arhk;\x03⤤\x05quiv;\x03≢\x05sear;\x03⤨\x05xist;\x03∄\x04Arr;\x03⇗\x04arr;\x03↗\x04dot;\x05≐̸\x04sim;\x05≂̸\x01;\x03≠",
			// nfr;[𝔫].
			"\x02r;\x04𝔫",
			// ngeqslant;[⩾̸] ngeqq;[≧̸] ngsim;[≵] ngeq;[≱] nges;[⩾̸] ngtr;[≯] ngE;[≧̸] nge;[≱] ngt;[≯].
			"\x08eqslant;\x05⩾̸\x04eqq;\x05≧̸\x04sim;\x03≵\x03eq;\x03≱\x03es;\x05⩾̸\x03tr;\x03≯\x02E;\x05≧̸\x02e;\x03≱\x02t;\x03≯",
			// nhArr;[⇎] nharr;[↮] nhpar;[⫲].
			"\x04Arr;\x03⇎\x04arr;\x03↮\x04par;\x03⫲",
			// nisd;[⋺] nis;[⋼] niv;[∋] ni;[∋].
			"\x03sd;\x03⋺\x02s;\x03⋼\x02v;\x03∋\x01;\x03∋",
			// njcy;[њ].
			"\x03cy;\x02њ",
			// nleftrightarrow;[↮] nleftarrow;[↚] nleqslant;[⩽̸] nltrie;[⋬] nlArr;[⇍] nlarr;[↚] nleqq;[≦̸] nless;[≮] nlsim;[≴] nltri;[⋪] nldr;[‥] nleq;[≰] nles;[⩽̸] nlE;[≦̸] nle;[≰] nlt;[≮].
			"\x0eeftrightarrow;\x03↮\x09eftarrow;\x03↚\x08eqslant;\x05⩽̸\x05trie;\x03⋬\x04Arr;\x03⇍\x04arr;\x03↚\x04eqq;\x05≦̸\x04ess;\x03≮\x04sim;\x03≴\x04tri;\x03⋪\x03dr;\x03‥\x03eq;\x03≰\x03es;\x05⩽̸\x02E;\x05≦̸\x02e;\x03≰\x02t;\x03≮",
			// nmid;[∤].
			"\x03id;\x03∤",
			// notindot;[⋵̸] notinva;[∉] notinvb;[⋷] notinvc;[⋶] notniva;[∌] notnivb;[⋾] notnivc;[⋽] notinE;[⋹̸] notin;[∉] notni;[∌] nopf;[𝕟] not;[¬] not[¬].
			"\x07tindot;\x05⋵̸\x06tinva;\x03∉\x06tinvb;\x03⋷\x06tinvc;\x03⋶\x06tniva;\x03∌\x06tnivb;\x03⋾\x06tnivc;\x03⋽\x05tinE;\x05⋹̸\x04tin;\x03∉\x04tni;\x03∌\x03pf;\x04𝕟\x02t;\x02¬\x01t\x02¬",
			// nparallel;[∦] npolint;[⨔] npreceq;[⪯̸] nparsl;[⫽⃥] nprcue;[⋠] npart;[∂̸] nprec;[⊀] npar;[∦] npre;[⪯̸] npr;[⊀].
			"\x08arallel;\x03∦\x06olint;\x03⨔\x06receq;\x05⪯̸\x05arsl;\x06⫽⃥\x05rcue;\x03⋠\x04art;\x05∂̸\x04rec;\x03⊀\x03ar;\x03∦\x03re;\x05⪯̸\x02r;\x03⊀",
			// nrightarrow;[↛] nrarrc;[⤳̸] nrarrw;[↝̸] nrtrie;[⋭] nrArr;[⇏] nrarr;[↛] nrtri;[⋫].
			"\x0aightarrow;\x03↛\x05arrc;\x05⤳̸\x05arrw;\x05↝̸\x05trie;\x03⋭\x04Arr;\x03⇏\x04arr;\x03↛\x04tri;\x03⋫",
			// nshortparallel;[∦] nsubseteqq;[⫅̸] nsupseteqq;[⫆̸] nshortmid;[∤] nsubseteq;[⊈] nsupseteq;[⊉] nsqsube;[⋢] nsqsupe;[⋣] nsubset;[⊂⃒] nsucceq;[⪰̸] nsupset;[⊃⃒] nsccue;[⋡] nsimeq;[≄] nsime;[≄] nsmid;[∤] nspar;[∦] nsubE;[⫅̸] nsube;[⊈] nsucc;[⊁] nsupE;[⫆̸] nsupe;[⊉] nsce;[⪰̸] nscr;[𝓃] nsim;[≁] nsub;[⊄] nsup;[⊅] nsc;[⊁].
			"\x0dhortparallel;\x03∦\x09ubseteqq;\x05⫅̸\x09upseteqq;\x05⫆̸\x08hortmid;\x03∤\x08ubseteq;\x03⊈\x08upseteq;\x03⊉\x06qsube;\x03⋢\x06qsupe;\x03⋣\x06ubset;\x06⊂⃒\x06ucceq;\x05⪰̸\x06upset;\x06⊃⃒\x05ccue;\x03⋡\x05imeq;\x03≄\x04ime;\x03≄\x04mid;\x03∤\x04par;\x03∦\x04ubE;\x05⫅̸\x04ube;\x03⊈\x04ucc;\x03⊁\x04upE;\x05⫆̸\x04upe;\x03⊉\x03ce;\x05⪰̸\x03cr;\x04𝓃\x03im;\x03≁\x03ub;\x03⊄\x03up;\x03⊅\x02c;\x03⊁",
			// ntrianglerighteq;[⋭] ntrianglelefteq;[⋬] ntriangleright;[⋫] ntriangleleft;[⋪] ntilde;[ñ] ntilde[ñ] ntgl;[≹] ntlg;[≸].
			"\x0frianglerighteq;\x03⋭\x0erianglelefteq;\x03⋬\x0driangleright;\x03⋫\x0criangleleft;\x03⋪\x05ilde;\x02ñ\x04ilde\x02ñ\x03gl;\x03≹\x03lg;\x03≸",
			// numero;[№] numsp;[ ] num;[#] nu;[ν].
			"\x05mero;\x03№\x04msp;\x03 \x02m;\x01#\x01;\x02ν",
			// nvinfin;[⧞] nvltrie;[⊴⃒] nvrtrie;[⊵⃒] nvDash;[⊭] nvHarr;[⤄] nvdash;[⊬] nvlArr;[⤂] nvrArr;[⤃] nvsim;[∼⃒] nvap;[≍⃒] nvge;[≥⃒] nvgt;[>⃒] nvle;[≤⃒] nvlt;[<⃒].
			"\x06infin;\x03⧞\x06ltrie;\x06⊴⃒\x06rtrie;\x06⊵⃒\x05Dash;\x03⊭\x05Harr;\x03⤄\x05dash;\x03⊬\x05lArr;\x03⤂\x05rArr;\x03⤃\x04sim;\x06∼⃒\x03ap;\x06≍⃒\x03ge;\x06≥⃒\x03gt;\x04>⃒\x03le;\x06≤⃒\x03lt;\x04<⃒",
			// nwarrow;[↖] nwarhk;[⤣] nwnear;[⤧] nwArr;[⇖] nwarr;[↖].
			"\x06arrow;\x03↖\x05arhk;\x03⤣\x05near;\x03⤧\x04Arr;\x03⇖\x04arr;\x03↖",
			// oS;[Ⓢ].
			"\x01;\x03Ⓢ",
			// oacute;[ó] oacute[ó] oast;[⊛].
			"\x05cute;\x02ó\x04cute\x02ó\x03st;\x03⊛",
			// ocirc;[ô] ocir;[⊚] ocirc[ô] ocy;[о].
			"\x04irc;\x02ô\x03ir;\x03⊚\x03irc\x02ô\x02y;\x02о",
			// odblac;[ő] odsold;[⦼] odash;[⊝] odiv;[⨸] odot;[⊙].
			"\x05blac;\x02ő\x05sold;\x03⦼\x04ash;\x03⊝\x03iv;\x03⨸\x03ot;\x03⊙",
			// oelig;[œ].
			"\x04lig;\x02œ",
			// ofcir;[⦿] ofr;[𝔬].
			"\x04cir;\x03⦿\x02r;\x04𝔬",
			// ograve;[ò] ograve[ò] ogon;[˛] ogt;[⧁].
			"\x05rave;\x02ò\x04rave\x02ò\x03on;\x02˛\x02t;\x03⧁",
			// ohbar;[⦵] ohm;[Ω].
			"\x04bar;\x03⦵\x02m;\x02Ω",
			// oint;[∮].
			"\x03nt;\x03∮",
			// olcross;[⦻] olarr;[↺] olcir;[⦾] oline;[‾] olt;[⧀].
			"\x06cross;\x03⦻\x04arr;\x03↺\x04cir;\x03⦾\x04ine;\x03‾\x02t;\x03⧀",
			// omicron;[ο] ominus;[⊖] omacr;[ō] omega;[ω] omid;[⦶].
			"\x06icron;\x02ο\x05inus;\x03⊖\x04acr;\x02ō\x04ega;\x02ω\x03id;\x03⦶",
			// oopf;[𝕠].
			"\x03pf;\x04𝕠",
			// operp;[⦹] oplus;[⊕] opar;[⦷].
			"\x04erp;\x03⦹\x04lus;\x03⊕\x03ar;\x03⦷",
			// orderof;[ℴ] orslope;[⩗] origof;[⊶] orarr;[↻] order;[ℴ] ordf;[ª] ordm;[º] oror;[⩖] ord;[⩝] ordf[ª] ordm[º] orv;[⩛] or;[∨].
			"\x06derof;\x03ℴ\x06slope;\x03⩗\x05igof;\x03⊶\x04arr;\x03↻\x04der;\x03ℴ\x03df;\x02ª\x03dm;\x02º\x03or;\x03⩖\x02d;\x03⩝\x02df\x02ª\x02dm\x02º\x02v;\x03⩛\x01;\x03∨",
			// oslash;[ø] oslash[ø] oscr;[ℴ] osol;[⊘].
			"\x05lash;\x02ø\x04lash\x02ø\x03cr;\x03ℴ\x03ol;\x03⊘",
			// otimesas;[⨶] otilde;[õ] otimes;[⊗] otilde[õ].
			"\x07imesas;\x03⨶\x05ilde;\x02õ\x05imes;\x03⊗\x04ilde\x02õ",
			// ouml;[ö] ouml[ö].
			"\x03ml;\x02ö\x02ml\x02ö",
			// ovbar;[⌽].
			"\x04bar;\x03⌽",
			// parallel;[∥] parsim;[⫳] parsl;[⫽] para;[¶] part;[∂] par;[∥] para[¶].
			"\x07rallel;\x03∥\x05rsim;\x03⫳\x04rsl;\x03⫽\x03ra;\x02¶\x03rt;\x03∂\x02r;\x03∥\x02ra\x02¶",
			// pcy;[п].
			"\x02y;\x02п",
			// pertenk;[‱] percnt;[%] period;[.] permil;[‰] perp;[⊥].
			"\x06rtenk;\x03‱\x05rcnt;\x01%\x05riod;\x01.\x05rmil;\x03‰\x03rp;\x03⊥",
			// pfr;[𝔭].
			"\x02r;\x04𝔭",
			// phmmat;[ℳ] phone;[☎] phiv;[ϕ] phi;[φ].
			"\x05mmat;\x03ℳ\x04one;\x03☎\x03iv;\x02ϕ\x02i;\x02φ",
			// pitchfork;[⋔] piv;[ϖ] pi;[π].
			"\x08tchfork;\x03⋔\x02v;\x02ϖ\x01;\x02π",
			// plusacir;[⨣] planckh;[ℎ] pluscir;[⨢] plussim;[⨦] plustwo;[⨧] planck;[ℏ] plankv;[ℏ] plusdo;[∔] plusdu;[⨥] plusmn;[±] plusb;[⊞] pluse;[⩲] plusmn[±] plus;[+].
			"\x07usacir;\x03⨣\x06anckh;\x03ℎ\x06uscir;\x03⨢\x06ussim;\x03⨦\x06ustwo;\x03⨧\x05anck;\x03ℏ\x05ankv;\x03ℏ\x05usdo;\x03∔\x05usdu;\x03⨥\x05usmn;\x02±\x04usb;\x03⊞\x04use;\x03⩲\x04usmn\x02±\x03us;\x01+",
			// pm;[±].
			"\x01;\x02±",
			// pointint;[⨕] pound;[£] popf;[𝕡] pound[£].
			"\x07intint;\x03⨕\x04und;\x02£\x03pf;\x04𝕡\x03und\x02£",
			// preccurlyeq;[≼] precnapprox;[⪹] precapprox;[⪷] precneqq;[⪵] precnsim;[⋨] profalar;[⌮] profline;[⌒] profsurf;[⌓] precsim;[≾] preceq;[⪯] primes;[ℙ] prnsim;[⋨] propto;[∝] prurel;[⊰] prcue;[≼] prime;[′] prnap;[⪹] prsim;[≾] prap;[⪷] prec;[≺] prnE;[⪵] prod;[∏] prop;[∝] prE;[⪳] pre;[⪯] pr;[≺].
			"\x0aeccurlyeq;\x03≼\x0aecnapprox;\x03⪹\x09ecapprox;\x03⪷\x07ecneqq;\x03⪵\x07ecnsim;\x03⋨\x07ofalar;\x03⌮\x07ofline;\x03⌒\x07ofsurf;\x03⌓\x06ecsim;\x03≾\x05eceq;\x03⪯\x05imes;\x03ℙ\x05nsim;\x03⋨\x05opto;\x03∝\x05urel;\x03⊰\x04cue;\x03≼\x04ime;\x03′\x04nap;\x03⪹\x04sim;\x03≾\x03ap;\x03⪷\x03ec;\x03≺\x03nE;\x03⪵\x03od;\x03∏\x03op;\x03∝\x02E;\x03⪳\x02e;\x03⪯\x01;\x03≺",
			// pscr;[𝓅] psi;[ψ].
			"\x03cr;\x04𝓅\x02i;\x02ψ",
			// puncsp;[ ].
			"\x05ncsp;\x03 ",
			// qfr;[𝔮].
			"\x02r;\x04𝔮",
			// qint;[⨌].
			"\x03nt;\x03⨌",
			// qopf;[𝕢].
			"\x03pf;\x04𝕢",
			// qprime;[⁗].
			"\x05rime;\x03⁗",
			// qscr;[𝓆].
			"\x03cr;\x04𝓆",
			// quaternions;[ℍ] quatint;[⨖] questeq;[≟] quest;[?] quot;[\"] quot[\"].
			"\x0aaternions;\x03ℍ\x06atint;\x03⨖\x06esteq;\x03≟\x04est;\x01?\x03ot;\x01\"\x02ot\x01\"",
			// rAtail;[⤜] rAarr;[⇛] rArr;[⇒].
			"\x05tail;\x03⤜\x04arr;\x03⇛\x03rr;\x03⇒",
			// rBarr;[⤏].
			"\x04arr;\x03⤏",
			// rHar;[⥤].
			"\x03ar;\x03⥤",
			// rationals;[ℚ] raemptyv;[⦳] rarrbfs;[⤠] rarrsim;[⥴] racute;[ŕ] rangle;[⟩] rarrap;[⥵] rarrfs;[⤞] rarrhk;[↪] rarrlp;[↬] rarrpl;[⥅] rarrtl;[↣] ratail;[⤚] radic;[√] rangd;[⦒] range;[⦥] raquo;[»] rarrb;[⇥] rarrc;[⤳] rarrw;[↝] ratio;[∶] race;[∽̱] rang;[⟩] raquo[»] rarr;[→].
			"\x08tionals;\x03ℚ\x07emptyv;\x03⦳\x06rrbfs;\x03⤠\x06rrsim;\x03⥴\x05cute;\x02ŕ\x05ngle;\x03⟩\x05rrap;\x03⥵\x05rrfs;\x03⤞\x05rrhk;\x03↪\x05rrlp;\x03↬\x05rrpl;\x03⥅\x05rrtl;\x03↣\x05tail;\x03⤚\x04dic;\x03√\x04ngd;\x03⦒\x04nge;\x03⦥\x04quo;\x02»\x04rrb;\x03⇥\x04rrc;\x03⤳\x04rrw;\x03↝\x04tio;\x03∶\x03ce;\x05∽̱\x03ng;\x03⟩\x03quo\x02»\x03rr;\x03→",
			// rbrksld;[⦎] rbrkslu;[⦐] rbrace;[}] rbrack;[]] rbarr;[⤍] rbbrk;[❳] rbrke;[⦌].
			"\x06rksld;\x03⦎\x06rkslu;\x03⦐\x05race;\x01}\x05rack;\x01]\x04arr;\x03⤍\x04brk;\x03❳\x04rke;\x03⦌",
			// rcaron;[ř] rcedil;[ŗ] rceil;[⌉] rcub;[}] rcy;[р].
			"\x05aron;\x02ř\x05edil;\x02ŗ\x04eil;\x03⌉\x03ub;\x01}\x02y;\x02р",
			// rdldhar;[⥩] rdquor;[”] rdquo;[”] rdca;[⤷] rdsh;[↳].
			"\x06ldhar;\x03⥩\x05quor;\x03”\x04quo;\x03”\x03ca;\x03⤷\x03sh;\x03↳",
			// realpart;[ℜ] realine;[ℛ] reals;[ℝ] real;[ℜ] rect;[▭] reg;[®] reg[®].
			"\x07alpart;\x03ℜ\x06aline;\x03ℛ\x04als;\x03ℝ\x03al;\x03ℜ\x03ct;\x03▭\x02g;\x02®\x01g\x02®",
			// rfisht;[⥽] rfloor;[⌋] rfr;[𝔯].
			"\x05isht;\x03⥽\x05loor;\x03⌋\x02r;\x04𝔯",
			// rharul;[⥬] rhard;[⇁] rharu;[⇀] rhov;[ϱ] rho;[ρ].
			"\x05arul;\x03⥬\x04ard;\x03⇁\x04aru;\x03⇀\x03ov;\x02ϱ\x02o;\x02ρ",
			// rightleftharpoons;[⇌] rightharpoondown;[⇁] rightrightarrows;[⇉] rightleftarrows;[⇄] rightsquigarrow;[↝] rightthreetimes;[⋌] rightarrowtail;[↣] rightharpoonup;[⇀] risingdotseq;[≓] rightarrow;[→] ring;[˚].
			"\x10ghtleftharpoons;\x03⇌\x0fghtharpoondown;\x03⇁\x0fghtrightarrows;\x03⇉\x0eghtleftarrows;\x03⇄\x0eghtsquigarrow;\x03↝\x0eghtthreetimes;\x03⋌\x0dghtarrowtail;\x03↣\x0dghtharpoonup;\x03⇀\x0bsingdotseq;\x03≓\x09ghtarrow;\x03→\x03ng;\x02˚",
			// rlarr;[⇄] rlhar;[⇌] rlm;[‏].
			"\x04arr;\x03⇄\x04har;\x03⇌\x02m;\x03‏",
			// rmoustache;[⎱] rmoust;[⎱].
			"\x09oustache;\x03⎱\x05oust;\x03⎱",
			// rnmid;[⫮].
			"\x04mid;\x03⫮",
			// rotimes;[⨵] roplus;[⨮] roang;[⟭] roarr;[⇾] robrk;[⟧] ropar;[⦆] ropf;[𝕣].
			"\x06times;\x03⨵\x05plus;\x03⨮\x04ang;\x03⟭\x04arr;\x03⇾\x04brk;\x03⟧\x04par;\x03⦆\x03pf;\x04𝕣",
			// rppolint;[⨒] rpargt;[⦔] rpar;[)].
			"\x07polint;\x03⨒\x05argt;\x03⦔\x03ar;\x01)",
			// rrarr;[⇉].
			"\x04arr;\x03⇉",
			// rsaquo;[›] rsquor;[’] rsquo;[’] rscr;[𝓇] rsqb;[]] rsh;[↱].
			"\x05aquo;\x03›\x05quor;\x03’\x04quo;\x03’\x03cr;\x04𝓇\x03qb;\x01]\x02h;\x03↱",
			// rtriltri;[⧎] rthree;[⋌] rtimes;[⋊] rtrie;[⊵] rtrif;[▸] rtri;[▹].
			"\x07riltri;\x03⧎\x05hree;\x03⋌\x05imes;\x03⋊\x04rie;\x03⊵\x04rif;\x03▸\x03ri;\x03▹",
			// ruluhar;[⥨].
			"\x06luhar;\x03⥨",
			// rx;[℞].
			"\x01;\x03℞",
			// sacute;[ś].
			"\x05cute;\x02ś",
			// sbquo;[‚].
			"\x04quo;\x03‚",
			// scpolint;[⨓] scaron;[š] scedil;[ş] scnsim;[⋩] sccue;[≽] scirc;[ŝ] scnap;[⪺] scsim;[≿] scap;[⪸] scnE;[⪶] scE;[⪴] sce;[⪰] scy;[с] sc;[≻].
			"\x07polint;\x03⨓\x05aron;\x02š\x05edil;\x02ş\x05nsim;\x03⋩\x04cue;\x03≽\x04irc;\x02ŝ\x04nap;\x03⪺\x04sim;\x03≿\x03ap;\x03⪸\x03nE;\x03⪶\x02E;\x03⪴\x02e;\x03⪰\x02y;\x02с\x01;\x03≻",
			// sdotb;[⊡] sdote;[⩦] sdot;[⋅].
			"\x04otb;\x03⊡\x04ote;\x03⩦\x03ot;\x03⋅",
			// setminus;[∖] searrow;[↘] searhk;[⤥] seswar;[⤩] seArr;[⇘] searr;[↘] setmn;[∖] sect;[§] semi;[;] sext;[✶] sect[§].
			"\x07tminus;\x03∖\x06arrow;\x03↘\x05arhk;\x03⤥\x05swar;\x03⤩\x04Arr;\x03⇘\x04arr;\x03↘\x04tmn;\x03∖\x03ct;\x02§\x03mi;\x01;\x03xt;\x03✶\x02ct\x02§",
			// sfrown;[⌢] sfr;[𝔰].
			"\x05rown;\x03⌢\x02r;\x04𝔰",
			// shortparallel;[∥] shortmid;[∣] shchcy;[щ] sharp;[♯] shcy;[ш] shy;[­] shy[­].
			"\x0cortparallel;\x03∥\x07ortmid;\x03∣\x05chcy;\x02щ\x04arp;\x03♯\x03cy;\x02ш\x02y;\x02­\x01y\x02­",
			// simplus;[⨤] simrarr;[⥲] sigmaf;[ς] sigmav;[ς] simdot;[⩪] sigma;[σ] simeq;[≃] simgE;[⪠] simlE;[⪟] simne;[≆] sime;[≃] simg;[⪞] siml;[⪝] sim;[∼].
			"\x06mplus;\x03⨤\x06mrarr;\x03⥲\x05gmaf;\x02ς\x05gmav;\x02ς\x05mdot;\x03⩪\x04gma;\x02σ\x04meq;\x03≃\x04mgE;\x03⪠\x04mlE;\x03⪟\x04mne;\x03≆\x03me;\x03≃\x03mg;\x03⪞\x03ml;\x03⪝\x02m;\x03∼",
			// slarr;[←].
			"\x04arr;\x03←",
			// smallsetminus;[∖] smeparsl;[⧤] smashp;[⨳] smile;[⌣] smtes;[⪬︀] smid;[∣] smte;[⪬] smt;[⪪].
			"\x0callsetminus;\x03∖\x07eparsl;\x03⧤\x05ashp;\x03⨳\x04ile;\x03⌣\x04tes;\x06⪬︀\x03id;\x03∣\x03te;\x03⪬\x02t;\x03⪪",
			// softcy;[ь] solbar;[⌿] solb;[⧄] sopf;[𝕤] sol;[/].
			"\x05ftcy;\x02ь\x05lbar;\x03⌿\x03lb;\x03⧄\x03pf;\x04𝕤\x02l;\x01/",
			// spadesuit;[♠] spades;[♠] spar;[∥].
			"\x08adesuit;\x03♠\x05ades;\x03♠\x03ar;\x03∥",
			// sqsubseteq;[⊑] sqsupseteq;[⊒] sqsubset;[⊏] sqsupset;[⊐] sqcaps;[⊓︀] sqcups;[⊔︀] sqsube;[⊑] sqsupe;[⊒] square;[□] squarf;[▪] sqcap;[⊓] sqcup;[⊔] sqsub;[⊏] sqsup;[⊐] squf;[▪] squ;[□].
			"\x09subseteq;\x03⊑\x09supseteq;\x03⊒\x07subset;\x03⊏\x07supset;\x03⊐\x05caps;\x06⊓︀\x05cups;\x06⊔︀\x05sube;\x03⊑\x05supe;\x03⊒\x05uare;\x03□\x05uarf;\x03▪\x04cap;\x03⊓\x04cup;\x03⊔\x04sub;\x03⊏\x04sup;\x03⊐\x03uf;\x03▪\x02u;\x03□",
			// srarr;[→].
			"\x04arr;\x03→",
			// ssetmn;[∖] ssmile;[⌣] sstarf;[⋆] sscr;[𝓈].
			"\x05etmn;\x03∖\x05mile;\x03⌣\x05tarf;\x03⋆\x03cr;\x04𝓈",
			// straightepsilon;[ϵ] straightphi;[ϕ] starf;[★] strns;[¯] star;[☆].
			"\x0eraightepsilon;\x02ϵ\x0araightphi;\x02ϕ\x04arf;\x03★\x04rns;\x02¯\x03ar;\x03☆",
			// succcurlyeq;[≽] succnapprox;[⪺] subsetneqq;[⫋] succapprox;[⪸] supsetneqq;[⫌] subseteqq;[⫅] subsetneq;[⊊] supseteqq;[⫆] supsetneq;[⊋] subseteq;[⊆] succneqq;[⪶] succnsim;[⋩] supseteq;[⊇] subedot;[⫃] submult;[⫁] subplus;[⪿] subrarr;[⥹] succsim;[≿] supdsub;[⫘] supedot;[⫄] suphsol;[⟉] suphsub;[⫗] suplarr;[⥻] supmult;[⫂] supplus;[⫀] subdot;[⪽] subset;[⊂] subsim;[⫇] subsub;[⫕] subsup;[⫓] succeq;[⪰] supdot;[⪾] supset;[⊃] supsim;[⫈] supsub;[⫔] supsup;[⫖] subnE;[⫋] subne;[⊊] supnE;[⫌] supne;[⊋] subE;[⫅] sube;[⊆] succ;[≻] sung;[♪] sup1;[¹] sup2;[²] sup3;[³] supE;[⫆] supe;[⊇] sub;[⊂] sum;[∑] sup1[¹] sup2[²] sup3[³] sup;[⊃].
			"\x0acccurlyeq;\x03≽\x0accnapprox;\x03⪺\x09bsetneqq;\x03⫋\x09ccapprox;\x03⪸\x09psetneqq;\x03⫌\x08bseteqq;\x03⫅\x08bsetneq;\x03⊊\x08pseteqq;\x03⫆\x08psetneq;\x03⊋\x07bseteq;\x03⊆\x07ccneqq;\x03⪶\x07ccnsim;\x03⋩\x07pseteq;\x03⊇\x06bedot;\x03⫃\x06bmult;\x03⫁\x06bplus;\x03⪿\x06brarr;\x03⥹\x06ccsim;\x03≿\x06pdsub;\x03⫘\x06pedot;\x03⫄\x06phsol;\x03⟉\x06phsub;\x03⫗\x06plarr;\x03⥻\x06pmult;\x03⫂\x06pplus;\x03⫀\x05bdot;\x03⪽\x05bset;\x03⊂\x05bsim;\x03⫇\x05bsub;\x03⫕\x05bsup;\x03⫓\x05cceq;\x03⪰\x05pdot;\x03⪾\x05pset;\x03⊃\x05psim;\x03⫈\x05psub;\x03⫔\x05psup;\x03⫖\x04bnE;\x03⫋\x04bne;\x03⊊\x04pnE;\x03⫌\x04pne;\x03⊋\x03bE;\x03⫅\x03be;\x03⊆\x03cc;\x03≻\x03ng;\x03♪\x03p1;\x02¹\x03p2;\x02²\x03p3;\x02³\x03pE;\x03⫆\x03pe;\x03⊇\x02b;\x03⊂\x02m;\x03∑\x02p1\x02¹\x02p2\x02²\x02p3\x02³\x02p;\x03⊃",
			// swarrow;[↙] swarhk;[⤦] swnwar;[⤪] swArr;[⇙] swarr;[↙].
			"\x06arrow;\x03↙\x05arhk;\x03⤦\x05nwar;\x03⤪\x04Arr;\x03⇙\x04arr;\x03↙",
			// szlig;[ß] szlig[ß].
			"\x04lig;\x02ß\x03lig\x02ß",
			// target;[⌖] tau;[τ].
			"\x05rget;\x03⌖\x02u;\x02τ",
			// tbrk;[⎴].
			"\x03rk;\x03⎴",
			// tcaron;[ť] tcedil;[ţ] tcy;[т].
			"\x05aron;\x02ť\x05edil;\x02ţ\x02y;\x02т",
			// tdot;[⃛].
			"\x03ot;\x03⃛",
			// telrec;[⌕].
			"\x05lrec;\x03⌕",
			// tfr;[𝔱].
			"\x02r;\x04𝔱",
			// thickapprox;[≈] therefore;[∴] thetasym;[ϑ] thicksim;[∼] there4;[∴] thetav;[ϑ] thinsp;[ ] thksim;[∼] theta;[θ] thkap;[≈] thorn;[þ] thorn[þ].
			"\x0aickapprox;\x03≈\x08erefore;\x03∴\x07etasym;\x02ϑ\x07icksim;\x03∼\x05ere4;\x03∴\x05etav;\x02ϑ\x05insp;\x03 \x05ksim;\x03∼\x04eta;\x02θ\x04kap;\x03≈\x04orn;\x02þ\x03orn\x02þ",
			// timesbar;[⨱] timesb;[⊠] timesd;[⨰] tilde;[˜] times;[×] times[×] tint;[∭].
			"\x07mesbar;\x03⨱\x05mesb;\x03⊠\x05mesd;\x03⨰\x04lde;\x02˜\x04mes;\x02×\x03mes\x02×\x03nt;\x03∭",
			// topfork;[⫚] topbot;[⌶] topcir;[⫱] toea;[⤨] topf;[𝕥] tosa;[⤩] top;[⊤].
			"\x06pfork;\x03⫚\x05pbot;\x03⌶\x05pcir;\x03⫱\x03ea;\x03⤨\x03pf;\x04𝕥\x03sa;\x03⤩\x02p;\x03⊤",
			// tprime;[‴].
			"\x05rime;\x03‴",
			// trianglerighteq;[⊵] trianglelefteq;[⊴] triangleright;[▹] triangledown;[▿] triangleleft;[◃] triangleq;[≜] triangle;[▵] triminus;[⨺] trpezium;[⏢] triplus;[⨹] tritime;[⨻] tridot;[◬] trade;[™] trisb;[⧍] trie;[≜].
			"\x0eianglerighteq;\x03⊵\x0dianglelefteq;\x03⊴\x0ciangleright;\x03▹\x0biangledown;\x03▿\x0biangleleft;\x03◃\x08iangleq;\x03≜\x07iangle;\x03▵\x07iminus;\x03⨺\x07pezium;\x03⏢\x06iplus;\x03⨹\x06itime;\x03⨻\x05idot;\x03◬\x04ade;\x03™\x04isb;\x03⧍\x03ie;\x03≜",
			// tstrok;[ŧ] tshcy;[ћ] tscr;[𝓉] tscy;[ц].
			"\x05trok;\x02ŧ\x04hcy;\x02ћ\x03cr;\x04𝓉\x03cy;\x02ц",
			// twoheadrightarrow;[↠] twoheadleftarrow;[↞] twixt;[≬].
			"\x10oheadrightarrow;\x03↠\x0foheadleftarrow;\x03↞\x04ixt;\x03≬",
			// uArr;[⇑].
			"\x03rr;\x03⇑",
			// uHar;[⥣].
			"\x03ar;\x03⥣",
			// uacute;[ú] uacute[ú] uarr;[↑].
			"\x05cute;\x02ú\x04cute\x02ú\x03rr;\x03↑",
			// ubreve;[ŭ] ubrcy;[ў].
			"\x05reve;\x02ŭ\x04rcy;\x02ў",
			// ucirc;[û] ucirc[û] ucy;[у].
			"\x04irc;\x02û\x03irc\x02û\x02y;\x02у",
			// udblac;[ű] udarr;[⇅] udhar;[⥮].
			"\x05blac;\x02ű\x04arr;\x03⇅\x04har;\x03⥮",
			// ufisht;[⥾] ufr;[𝔲].
			"\x05isht;\x03⥾\x02r;\x04𝔲",
			// ugrave;[ù] ugrave[ù].
			"\x05rave;\x02ù\x04rave\x02ù",
			// uharl;[↿] uharr;[↾] uhblk;[▀].
			"\x04arl;\x03↿\x04arr;\x03↾\x04blk;\x03▀",
			// ulcorner;[⌜] ulcorn;[⌜] ulcrop;[⌏] ultri;[◸].
			"\x07corner;\x03⌜\x05corn;\x03⌜\x05crop;\x03⌏\x04tri;\x03◸",
			// umacr;[ū] uml;[¨] uml[¨].
			"\x04acr;\x02ū\x02l;\x02¨\x01l\x02¨",
			// uogon;[ų] uopf;[𝕦].
			"\x04gon;\x02ų\x03pf;\x04𝕦",
			// upharpoonright;[↾] upharpoonleft;[↿] updownarrow;[↕] upuparrows;[⇈] uparrow;[↑] upsilon;[υ] uplus;[⊎] upsih;[ϒ] upsi;[υ].
			"\x0dharpoonright;\x03↾\x0charpoonleft;\x03↿\x0adownarrow;\x03↕\x09uparrows;\x03⇈\x06arrow;\x03↑\x06silon;\x02υ\x04lus;\x03⊎\x04sih;\x02ϒ\x03si;\x02υ",
			// urcorner;[⌝] urcorn;[⌝] urcrop;[⌎] uring;[ů] urtri;[◹].
			"\x07corner;\x03⌝\x05corn;\x03⌝\x05crop;\x03⌎\x04ing;\x02ů\x04tri;\x03◹",
			// uscr;[𝓊].
			"\x03cr;\x04𝓊",
			// utilde;[ũ] utdot;[⋰] utrif;[▴] utri;[▵].
			"\x05ilde;\x02ũ\x04dot;\x03⋰\x04rif;\x03▴\x03ri;\x03▵",
			// uuarr;[⇈] uuml;[ü] uuml[ü].
			"\x04arr;\x03⇈\x03ml;\x02ü\x02ml\x02ü",
			// uwangle;[⦧].
			"\x06angle;\x03⦧",
			// vArr;[⇕].
			"\x03rr;\x03⇕",
			// vBarv;[⫩] vBar;[⫨].
			"\x04arv;\x03⫩\x03ar;\x03⫨",
			// vDash;[⊨].
			"\x04ash;\x03⊨",
			// vartriangleright;[⊳] vartriangleleft;[⊲] varsubsetneqq;[⫋︀] varsupsetneqq;[⫌︀] varsubsetneq;[⊊︀] varsupsetneq;[⊋︀] varepsilon;[ϵ] varnothing;[∅] varpropto;[∝] varkappa;[ϰ] varsigma;[ς] vartheta;[ϑ] vangrt;[⦜] varphi;[ϕ] varrho;[ϱ] varpi;[ϖ] varr;[↕].
			"\x0frtriangleright;\x03⊳\x0ertriangleleft;\x03⊲\x0crsubsetneqq;\x06⫋︀\x0crsupsetneqq;\x06⫌︀\x0brsubsetneq;\x06⊊︀\x0brsupsetneq;\x06⊋︀\x09repsilon;\x02ϵ\x09rnothing;\x03∅\x08rpropto;\x03∝\x07rkappa;\x02ϰ\x07rsigma;\x02ς\x07rtheta;\x02ϑ\x05ngrt;\x03⦜\x05rphi;\x02ϕ\x05rrho;\x02ϱ\x04rpi;\x02ϖ\x03rr;\x03↕",
			// vcy;[в].
			"\x02y;\x02в",
			// vdash;[⊢].
			"\x04ash;\x03⊢",
			// veebar;[⊻] vellip;[⋮] verbar;[|] veeeq;[≚] vert;[|] vee;[∨].
			"\x05ebar;\x03⊻\x05llip;\x03⋮\x05rbar;\x01|\x04eeq;\x03≚\x03rt;\x01|\x02e;\x03∨",
			// vfr;[𝔳].
			"\x02r;\x04𝔳",
			// vltri;[⊲].
			"\x04tri;\x03⊲",
			// vnsub;[⊂⃒] vnsup;[⊃⃒].
			"\x04sub;\x06⊂⃒\x04sup;\x06⊃⃒",
			// vopf;[𝕧].
			"\x03pf;\x04𝕧",
			// vprop;[∝].
			"\x04rop;\x03∝",
			// vrtri;[⊳].
			"\x04tri;\x03⊳",
			// vsubnE;[⫋︀] vsubne;[⊊︀] vsupnE;[⫌︀] vsupne;[⊋︀] vscr;[𝓋].
			"\x05ubnE;\x06⫋︀\x05ubne;\x06⊊︀\x05upnE;\x06⫌︀\x05upne;\x06⊋︀\x03cr;\x04𝓋",
			// vzigzag;[⦚].
			"\x06igzag;\x03⦚",
			// wcirc;[ŵ].
			"\x04irc;\x02ŵ",
			// wedbar;[⩟] wedgeq;[≙] weierp;[℘] wedge;[∧].
			"\x05dbar;\x03⩟\x05dgeq;\x03≙\x05ierp;\x03℘\x04dge;\x03∧",
			// wfr;[𝔴].
			"\x02r;\x04𝔴",
			// wopf;[𝕨].
			"\x03pf;\x04𝕨",
			// wp;[℘].
			"\x01;\x03℘",
			// wreath;[≀] wr;[≀].
			"\x05eath;\x03≀\x01;\x03≀",
			// wscr;[𝓌].
			"\x03cr;\x04𝓌",
			// xcirc;[◯] xcap;[⋂] xcup;[⋃].
			"\x04irc;\x03◯\x03ap;\x03⋂\x03up;\x03⋃",
			// xdtri;[▽].
			"\x04tri;\x03▽",
			// xfr;[𝔵].
			"\x02r;\x04𝔵",
			// xhArr;[⟺] xharr;[⟷].
			"\x04Arr;\x03⟺\x04arr;\x03⟷",
			// xi;[ξ].
			"\x01;\x02ξ",
			// xlArr;[⟸] xlarr;[⟵].
			"\x04Arr;\x03⟸\x04arr;\x03⟵",
			// xmap;[⟼].
			"\x03ap;\x03⟼",
			// xnis;[⋻].
			"\x03is;\x03⋻",
			// xoplus;[⨁] xotime;[⨂] xodot;[⨀] xopf;[𝕩].
			"\x05plus;\x03⨁\x05time;\x03⨂\x04dot;\x03⨀\x03pf;\x04𝕩",
			// xrArr;[⟹] xrarr;[⟶].
			"\x04Arr;\x03⟹\x04arr;\x03⟶",
			// xsqcup;[⨆] xscr;[𝓍].
			"\x05qcup;\x03⨆\x03cr;\x04𝓍",
			// xuplus;[⨄] xutri;[△].
			"\x05plus;\x03⨄\x04tri;\x03△",
			// xvee;[⋁].
			"\x03ee;\x03⋁",
			// xwedge;[⋀].
			"\x05edge;\x03⋀",
			// yacute;[ý] yacute[ý] yacy;[я].
			"\x05cute;\x02ý\x04cute\x02ý\x03cy;\x02я",
			// ycirc;[ŷ] ycy;[ы].
			"\x04irc;\x02ŷ\x02y;\x02ы",
			// yen;[¥] yen[¥].
			"\x02n;\x02¥\x01n\x02¥",
			// yfr;[𝔶].
			"\x02r;\x04𝔶",
			// yicy;[ї].
			"\x03cy;\x02ї",
			// yopf;[𝕪].
			"\x03pf;\x04𝕪",
			// yscr;[𝓎].
			"\x03cr;\x04𝓎",
			// yucy;[ю] yuml;[ÿ] yuml[ÿ].
			"\x03cy;\x02ю\x03ml;\x02ÿ\x02ml\x02ÿ",
			// zacute;[ź].
			"\x05cute;\x02ź",
			// zcaron;[ž] zcy;[з].
			"\x05aron;\x02ž\x02y;\x02з",
			// zdot;[ż].
			"\x03ot;\x02ż",
			// zeetrf;[ℨ] zeta;[ζ].
			"\x05etrf;\x03ℨ\x03ta;\x02ζ",
			// zfr;[𝔷].
			"\x02r;\x04𝔷",
			// zhcy;[ж].
			"\x03cy;\x02ж",
			// zigrarr;[⇝].
			"\x06grarr;\x03⇝",
			// zopf;[𝕫].
			"\x03pf;\x04𝕫",
			// zscr;[𝓏].
			"\x03cr;\x04𝓏",
			// zwnj;[‌] zwj;[‍].
			"\x03nj;\x03‌\x02j;\x03‍",
		),
		"small_words" => "GT\x00LT\x00gt\x00lt\x00",
		"small_mappings" => array(
			">",
			"<",
			">",
			"<",
		)
	)
);

/**
 * HTML API: WP_HTML_Decoder class
 *
 * Decodes spans of raw text found inside HTML content.
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since 6.6.0
 */
class WP_HTML_Decoder {
	/**
	 * Indicates if an attribute value starts with a given raw string value.
	 *
	 * Use this method to determine if an attribute value starts with a given string, regardless
	 * of how it might be encoded in HTML. For instance, `http:` could be represented as `http:`
	 * or as `http&colon;` or as `&#x68;ttp:` or as `h&#116;tp&colon;`, or in many other ways.
	 *
	 * This is equivalent to a byte-prefix test against the decoded attribute value, without
	 * the need to allocate and decode the full string.
	 *
	 * Example:
	 *
	 *     $value = 'http&colon;//wordpress.org/';
	 *     true   === WP_HTML_Decoder::attribute_starts_with( $value, 'http:', 'ascii-case-insensitive' );
	 *     false  === WP_HTML_Decoder::attribute_starts_with( $value, 'https:', 'ascii-case-insensitive' );
	 *
	 * @since 6.6.0
	 *
	 * @param string $haystack         String containing the raw non-decoded attribute value.
	 * @param string $search_text      Does the attribute value start with this plain string.
	 * @param string $case_sensitivity Optional. Pass 'ascii-case-insensitive' to ignore ASCII case when matching.
	 *                                 Default 'case-sensitive'.
	 * @return bool Whether the attribute value starts with the given string.
	 */
	public static function attribute_starts_with( $haystack, $search_text, $case_sensitivity = 'case-sensitive' ): bool {
		$search_length = strlen( $search_text );
		$loose_case    = 'ascii-case-insensitive' === $case_sensitivity;
		$haystack_end  = strlen( $haystack );
		$search_at     = 0;
		$haystack_at   = 0;

		while ( $search_at < $search_length && $haystack_at < $haystack_end ) {
			$chars_match = $loose_case
				? strtolower( $haystack[ $haystack_at ] ) === strtolower( $search_text[ $search_at ] )
				: $haystack[ $haystack_at ] === $search_text[ $search_at ];

			$is_introducer = '&' === $haystack[ $haystack_at ];
			$next_chunk    = $is_introducer
				? self::read_character_reference( 'attribute', $haystack, $haystack_at, $token_length )
				: null;

			// If there's no character reference and the characters don't match, the match fails.
			if ( null === $next_chunk && ! $chars_match ) {
				return false;
			}

			// If there's no character reference but the characters do match, then it could still match.
			if ( null === $next_chunk && $chars_match ) {
				++$haystack_at;
				++$search_at;
				continue;
			}

			/**
			 * The decoded character reference in `$next_chunk` must be compared with the
			 * corresponding `$search_text` bytes checking for matching prefixes. The remaining
			 * search text may be shorter than the decoded chunk, in which case a partial match
			 * satisfies the prefix. Otherwise, if the decoded chunk is fully matched, the
			 * comparison must continue after advancing the appropriate byte lengths: the character
			 * reference token length in the haystack and the decoded chunk length in the
			 * search text.
			 *
			 * For example, consider searches that have reached the character reference
			 * `&fjlig;` (7 bytes), decoded into the 2-byte chunk `fj`:
			 *
			 *                       $haystack_at
			 *                       │
			 *                       │      ┌─after matching `fj` continue here
			 *                       │      │ (advance by $token_length, 7 bytes)
			 *                       ↓      ↓
			 *     Haystack:    start&fjlig;ord
			 *                       ╰──┬──╯
			 *                          fj - the decoded chunk, tested against the search text.
			 *
			 *                       $search_at
			 *                       │
			 *                       │ ┌─after matching `fj` continue here
			 *                       │ │ (advance by $match_length, 2 bytes)
			 *                       ↓ ↓
			 *     Search A:    startfjord      Compare 2 bytes: `fj` matches,
			 *                                  continue matching at `o`.
			 *
			 *                       $search_at
			 *                       ↓
			 *     Search B:    startf          Compare 1 byte: `f` matches and the
			 *                                  search text is exhausted — prefix confirmed.
			 *
			 *                       $search_at
			 *                       ↓
			 *     Search C:    startfr         Compare 2 bytes: `fj` differs
			 *                                  from `fr`, no match is possible.
			 *
			 * The `min()` is required in both directions: Search A fails if the
			 * comparison length comes from the search text, Search B if it comes
			 * from the chunk.
			 *
			 * After a match each cursor must advance by the appropriate length, the haystack
			 * cursor by the character reference token length, and the search cursor by the
			 * matched length.
			 */
			$match_length = min( strlen( $next_chunk ), $search_length - $search_at );
			if ( 0 !== substr_compare( $search_text, $next_chunk, $search_at, $match_length, $loose_case ) ) {
				return false;
			}

			// The character reference matched, so continue checking.
			$haystack_at += $token_length;
			$search_at   += $match_length;
		}

		return $search_at === $search_length;
	}

	/**
	 * Returns a string containing the decoded value of a given HTML text node.
	 *
	 * Text nodes appear in HTML DATA sections, which are the text segments inside
	 * and around tags, excepting SCRIPT and STYLE elements (and some others),
	 * whose inner text is not decoded. Use this function to read the decoded
	 * value of such a text span in an HTML document.
	 *
	 * Example:
	 *
	 *     '“😄”' === WP_HTML_Decoder::decode_text_node( '&#x93;&#x1f604;&#x94' );
	 *
	 * @since 6.6.0
	 *
	 * @param string $text Text containing raw and non-decoded text node to decode.
	 * @return string Decoded UTF-8 value of given text node.
	 */
	public static function decode_text_node( $text ): string {
		return static::decode( 'data', $text );
	}

	/**
	 * Returns a string containing the decoded value of a given HTML attribute.
	 *
	 * Text found inside an HTML attribute has different parsing rules than for
	 * text found inside other markup, or DATA segments. Use this function to
	 * read the decoded value of an HTML string inside a quoted attribute.
	 *
	 * Example:
	 *
	 *     '“😄”' === WP_HTML_Decoder::decode_attribute( '&#x93;&#x1f604;&#x94' );
	 *
	 * @since 6.6.0
	 *
	 * @param string $text Text containing raw and non-decoded attribute value to decode.
	 * @return string Decoded UTF-8 value of given attribute value.
	 */
	public static function decode_attribute( $text ): string {
		return static::decode( 'attribute', $text );
	}

	/**
	 * Decodes a span of HTML text, depending on the context in which it's found.
	 *
	 * This is a low-level method; prefer calling WP_HTML_Decoder::decode_attribute() or
	 * WP_HTML_Decoder::decode_text_node() instead. It's provided for cases where this
	 * may be difficult to do from calling code.
	 *
	 * Example:
	 *
	 *     '©' = WP_HTML_Decoder::decode( 'data', '&copy;' );
	 *
	 * @since 6.6.0
	 *
	 * @access private
	 *
	 * @param string $context `attribute` for decoding attribute values, `data` otherwise.
	 * @param string $text    Text document containing span of text to decode.
	 * @return string Decoded UTF-8 string.
	 */
	public static function decode( $context, $text ): string {
		$decoded = '';
		$end     = strlen( $text );
		$at      = 0;
		$was_at  = 0;

		while ( $at < $end ) {
			$next_character_reference_at = strpos( $text, '&', $at );
			if ( false === $next_character_reference_at ) {
				break;
			}

			$character_reference = self::read_character_reference( $context, $text, $next_character_reference_at, $token_length );
			if ( isset( $character_reference ) ) {
				$at       = $next_character_reference_at;
				$decoded .= substr( $text, $was_at, $at - $was_at );
				$decoded .= $character_reference;
				$at      += $token_length;
				$was_at   = $at;
				continue;
			}

			++$at;
		}

		if ( 0 === $was_at ) {
			return $text;
		}

		if ( $was_at < $end ) {
			$decoded .= substr( $text, $was_at, $end - $was_at );
		}

		return $decoded;
	}

	/**
	 * Attempt to read a character reference at the given location in a given string,
	 * depending on the context in which it's found.
	 *
	 * If a character reference is found, this function will return the translated value
	 * that the reference maps to. It will then set `$match_byte_length` the
	 * number of bytes of input it read while consuming the character reference. This
	 * gives calling code the opportunity to advance its cursor when traversing a string
	 * and decoding.
	 *
	 * Example:
	 *
	 *     null === WP_HTML_Decoder::read_character_reference( 'attribute', 'Ships&hellip;', 0 );
	 *     '…'  === WP_HTML_Decoder::read_character_reference( 'attribute', 'Ships&hellip;', 5, $token_length );
	 *     8    === $token_length; // `&hellip;`
	 *
	 *     null === WP_HTML_Decoder::read_character_reference( 'attribute', '&notin', 0 );
	 *     '∉'  === WP_HTML_Decoder::read_character_reference( 'attribute', '&notin;', 0, $token_length );
	 *     7    === $token_length; // `&notin;`
	 *
	 *     '¬'  === WP_HTML_Decoder::read_character_reference( 'data', '&notin', 0, $token_length );
	 *     4    === $token_length; // `&not`
	 *     '∉'  === WP_HTML_Decoder::read_character_reference( 'data', '&notin;', 0, $token_length );
	 *     7    === $token_length; // `&notin;`
	 *
	 * @since 6.6.0
	 *
	 * @global WP_Token_Map $html5_named_character_references Mappings for HTML5 named character references.
	 *
	 * @param string $context            `attribute` for decoding attribute values, `data` otherwise.
	 * @param string $text               Text document containing span of text to decode.
	 * @param int    $at                 Optional. Byte offset into text where span begins, defaults to the beginning (0).
	 * @param int    &$match_byte_length Optional. Set to byte-length of character reference if provided and if a match
	 *                                   is found, otherwise not set. Default null.
	 * @return ?string Decoded character reference in UTF-8 if found, otherwise null.
	 */
	public static function read_character_reference( $context, $text, $at = 0, &$match_byte_length = null ) {
		/**
		 * Mappings for HTML5 named character references.
		 *
		 * @var WP_Token_Map $html5_named_character_references
		 */
		global $html5_named_character_references;

		$length = strlen( $text );
		if ( $at + 1 >= $length ) {
			return null;
		}

		if ( '&' !== $text[ $at ] ) {
			return null;
		}

		/*
		 * Numeric character references.
		 *
		 * When truncated, these will encode the code point found by parsing the
		 * digits that are available. For example, when `&#x1f170;` is truncated
		 * to `&#x1f1` it will encode `Ǳ`. It does not:
		 *  - know how to parse the original `🅰`.
		 *  - fail to parse and return plaintext `&#x1f1`.
		 *  - fail to parse and return the replacement character `�`
		 */
		if ( '#' === $text[ $at + 1 ] ) {
			if ( $at + 2 >= $length ) {
				return null;
			}

			/** Tracks inner parsing within the numeric character reference. */
			$digits_at = $at + 2;

			if ( 'x' === $text[ $digits_at ] || 'X' === $text[ $digits_at ] ) {
				$numeric_base   = 16;
				$numeric_digits = '0123456789abcdefABCDEF';
				$max_digits     = 6; // &#x10FFFF;
				++$digits_at;
			} else {
				$numeric_base   = 10;
				$numeric_digits = '0123456789';
				$max_digits     = 7; // &#1114111;
			}

			// Cannot encode invalid Unicode code points. Max is to U+10FFFF.
			$zero_count    = strspn( $text, '0', $digits_at );
			$digit_count   = strspn( $text, $numeric_digits, $digits_at + $zero_count );
			$after_digits  = $digits_at + $zero_count + $digit_count;
			$has_semicolon = $after_digits < $length && ';' === $text[ $after_digits ];
			$end_of_span   = $has_semicolon ? $after_digits + 1 : $after_digits;

			// `&#` or `&#x` without digits returns into plaintext.
			if ( 0 === $digit_count && 0 === $zero_count ) {
				return null;
			}

			// Whereas `&#` and only zeros is invalid.
			if ( 0 === $digit_count ) {
				$match_byte_length = $end_of_span - $at;
				return '�';
			}

			// If there are too many digits then it's not worth parsing. It's invalid.
			if ( $digit_count > $max_digits ) {
				$match_byte_length = $end_of_span - $at;
				return '�';
			}

			$digits     = substr( $text, $digits_at + $zero_count, $digit_count );
			$code_point = intval( $digits, $numeric_base );

			/*
			 * Noncharacters, 0x0D, and non-ASCII-whitespace control characters.
			 *
			 * > A noncharacter is a code point that is in the range U+FDD0 to U+FDEF,
			 * > inclusive, or U+FFFE, U+FFFF, U+1FFFE, U+1FFFF, U+2FFFE, U+2FFFF,
			 * > U+3FFFE, U+3FFFF, U+4FFFE, U+4FFFF, U+5FFFE, U+5FFFF, U+6FFFE,
			 * > U+6FFFF, U+7FFFE, U+7FFFF, U+8FFFE, U+8FFFF, U+9FFFE, U+9FFFF,
			 * > U+AFFFE, U+AFFFF, U+BFFFE, U+BFFFF, U+CFFFE, U+CFFFF, U+DFFFE,
			 * > U+DFFFF, U+EFFFE, U+EFFFF, U+FFFFE, U+FFFFF, U+10FFFE, or U+10FFFF.
			 *
			 * A C0 control is a code point that is in the range of U+00 to U+1F,
			 * but ASCII whitespace includes U+09, U+0A, U+0C, and U+0D.
			 *
			 * These characters are invalid but still decode as any valid character.
			 * This comment is here to note and explain why there's no check to
			 * remove these characters or replace them.
			 *
			 * @see https://infra.spec.whatwg.org/#noncharacter
			 */

			/*
			 * Code points in the C1 controls area need to be remapped as if they
			 * were stored in Windows-1252. Note! This transformation only happens
			 * for numeric character references. The raw code points in the byte
			 * stream are not translated.
			 *
			 * > If the number is one of the numbers in the first column of
			 * > the following table, then find the row with that number in
			 * > the first column, and set the character reference code to
			 * > the number in the second column of that row.
			 */
			if ( $code_point >= 0x80 && $code_point <= 0x9F ) {
				$windows_1252_mapping = array(
					0x20AC, // 0x80 -> EURO SIGN (€).
					0x81,   // 0x81 -> (no change).
					0x201A, // 0x82 -> SINGLE LOW-9 QUOTATION MARK (‚).
					0x0192, // 0x83 -> LATIN SMALL LETTER F WITH HOOK (ƒ).
					0x201E, // 0x84 -> DOUBLE LOW-9 QUOTATION MARK („).
					0x2026, // 0x85 -> HORIZONTAL ELLIPSIS (…).
					0x2020, // 0x86 -> DAGGER (†).
					0x2021, // 0x87 -> DOUBLE DAGGER (‡).
					0x02C6, // 0x88 -> MODIFIER LETTER CIRCUMFLEX ACCENT (ˆ).
					0x2030, // 0x89 -> PER MILLE SIGN (‰).
					0x0160, // 0x8A -> LATIN CAPITAL LETTER S WITH CARON (Š).
					0x2039, // 0x8B -> SINGLE LEFT-POINTING ANGLE QUOTATION MARK (‹).
					0x0152, // 0x8C -> LATIN CAPITAL LIGATURE OE (Œ).
					0x8D,   // 0x8D -> (no change).
					0x017D, // 0x8E -> LATIN CAPITAL LETTER Z WITH CARON (Ž).
					0x8F,   // 0x8F -> (no change).
					0x90,   // 0x90 -> (no change).
					0x2018, // 0x91 -> LEFT SINGLE QUOTATION MARK (‘).
					0x2019, // 0x92 -> RIGHT SINGLE QUOTATION MARK (’).
					0x201C, // 0x93 -> LEFT DOUBLE QUOTATION MARK (“).
					0x201D, // 0x94 -> RIGHT DOUBLE QUOTATION MARK (”).
					0x2022, // 0x95 -> BULLET (•).
					0x2013, // 0x96 -> EN DASH (–).
					0x2014, // 0x97 -> EM DASH (—).
					0x02DC, // 0x98 -> SMALL TILDE (˜).
					0x2122, // 0x99 -> TRADE MARK SIGN (™).
					0x0161, // 0x9A -> LATIN SMALL LETTER S WITH CARON (š).
					0x203A, // 0x9B -> SINGLE RIGHT-POINTING ANGLE QUOTATION MARK (›).
					0x0153, // 0x9C -> LATIN SMALL LIGATURE OE (œ).
					0x9D,   // 0x9D -> (no change).
					0x017E, // 0x9E -> LATIN SMALL LETTER Z WITH CARON (ž).
					0x0178, // 0x9F -> LATIN CAPITAL LETTER Y WITH DIAERESIS (Ÿ).
				);

				$code_point = $windows_1252_mapping[ $code_point - 0x80 ];
			}

			$match_byte_length = $end_of_span - $at;
			return self::code_point_to_utf8_bytes( $code_point );
		}

		/** Tracks inner parsing within the named character reference. */
		$name_at = $at + 1;
		// Minimum named character reference is two characters. E.g. `GT`.
		if ( $name_at + 2 > $length ) {
			return null;
		}

		$name_length = 0;
		$replacement = $html5_named_character_references->read_token( $text, $name_at, $name_length );
		if ( null === $replacement ) {
			return null;
		}

		$after_name = $name_at + $name_length;

		/**
		 * For historical reasons, a matched named character reference is left as literal
		 * text (its decoded replacement is not used) when all of the following hold:
		 *
		 * 1. It was matched in attribute context.
		 * 2. The match does not end in U+003B SEMICOLON (;) — i.e. it is one of the
		 *    legacy forms recognized without a trailing semicolon.
		 * 3. The next input character is U+003D EQUALS SIGN (=) or an ASCII alphanumeric.
		 *
		 * Some illustrative examples follow. Note that both `not` and `not;` appear in the
		 * named character references list. References start with `&` and typically end with
		 * `;`, but the legacy forms are recognized without one.
		 *
		 * - In _data context_, "&notme" is decoded to "¬me": condition 1 fails (not an
		 *   attribute), so the reference is decoded.
		 * - In _attribute context_, "&not;me" is decoded to "¬me": the longest match is
		 *   "not;", which ends in a semicolon, so condition 2 fails.
		 * - In _attribute context_, "&not己" is decoded to "¬己": the following character
		 *   "己" is a letter but not an ASCII alphanumeric (nor "="), so condition 3 fails.
		 * - In _attribute context_, "&not" is decoded to "¬": there is no next input
		 *   character, so condition 3 fails.
		 * - In _attribute context_, "&not=me" is left as the literal text "&not=me": all
		 *   three conditions hold.
		 * - In _attribute context_, "&notme" is left as the literal text "&notme": all
		 *   three conditions hold.
		 *
		 * Without these special rules, ordinary URL query strings could have surprising
		 * replacements applied. Consider:
		 *
		 *     <a href="/?random&degree&gt=0&lt=360&not=90">
		 *
		 * The literal attribute value `/?random&degree&gt=0&lt=360&not=90` is preserved
		 * by the special handling. Otherwise, the value would decode to
		 * `/?random°ree>=0<=360¬=90`, which is unlikely to be the author's intent.
		 *
		 * (Authors should not rely on this. Escaping the example as
		 * `/?random&amp;degree&amp;gt=0&amp;lt=360&amp;not=90` produces the intended
		 * value regardless of the following character.)
		 *
		 * @see https://html.spec.whatwg.org/multipage/parsing.html#named-character-reference-state
		 * @see https://html.spec.whatwg.org/multipage/named-characters.html#named-character-references
		 */
		if ( 'attribute' !== $context || ';' === $text[ $after_name - 1 ] || $after_name >= $length ) {
			$match_byte_length = $after_name - $at;
			return $replacement;
		}

		$follower_byte = ord( $text[ $after_name ] );
		if (
			0x3D === $follower_byte || //                              EQUALS SIGN
			( $follower_byte >= 0x30 && $follower_byte <= 0x39 ) || // ASCII digits 0-9
			( $follower_byte >= 0x41 && $follower_byte <= 0x5A ) || // ASCII upper alpha A-Z
			( $follower_byte >= 0x61 && $follower_byte <= 0x7A )    // ASCII lower alpha a-z
		) {
			return null;
		}

		$match_byte_length = $after_name - $at;
		return $replacement;
	}

	/**
	 * Encode a code point number into the UTF-8 encoding.
	 *
	 * This encoder implements the UTF-8 encoding algorithm for converting
	 * a code point into a byte sequence. If it receives an invalid code
	 * point it will return the Unicode Replacement Character U+FFFD `�`.
	 *
	 * Example:
	 *
	 *     '🅰' === WP_HTML_Decoder::code_point_to_utf8_bytes( 0x1f170 );
	 *
	 *     // Half of a surrogate pair is an invalid code point.
	 *     '�' === WP_HTML_Decoder::code_point_to_utf8_bytes( 0xd83c );
	 *
	 * @since 6.6.0
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc3629 For the UTF-8 standard.
	 *
	 * @param int $code_point Which code point to convert.
	 * @return string Converted code point, or `�` if invalid.
	 */
	public static function code_point_to_utf8_bytes( $code_point ): string {
		// Pre-check to ensure a valid code point.
		if (
			$code_point < 0 ||
			( $code_point >= 0xD800 && $code_point <= 0xDFFF ) ||
			$code_point > 0x10FFFF
		) {
			return "\xEF\xBF\xBD";
		}

		if ( $code_point <= 0x7F ) {
			return chr( $code_point );
		}

		if ( $code_point <= 0x7FF ) {
			$byte1 = chr( ( $code_point >> 6 ) | 0xC0 );
			$byte2 = chr( $code_point & 0x3F | 0x80 );

			return "{$byte1}{$byte2}";
		}

		if ( $code_point <= 0xFFFF ) {
			$byte1 = chr( ( $code_point >> 12 ) | 0xE0 );
			$byte2 = chr( ( $code_point >> 6 ) & 0x3F | 0x80 );
			$byte3 = chr( $code_point & 0x3F | 0x80 );

			return "{$byte1}{$byte2}{$byte3}";
		}

		// Any values above U+10FFFF are eliminated above in the pre-check.
		$byte1 = chr( ( $code_point >> 18 ) | 0xF0 );
		$byte2 = chr( ( $code_point >> 12 ) & 0x3F | 0x80 );
		$byte3 = chr( ( $code_point >> 6 ) & 0x3F | 0x80 );
		$byte4 = chr( $code_point & 0x3F | 0x80 );

		return "{$byte1}{$byte2}{$byte3}{$byte4}";
	}
}

/**
 * Core class used to modify attributes in an HTML document for tags matching a query.
 *
 * ## Usage
 *
 * Use of this class requires three steps:
 *
 *  1. Create a new class instance with your input HTML document.
 *  2. Find the tag(s) you are looking for.
 *  3. Request changes to the attributes in those tag(s).
 *
 * Example:
 *
 *     $tags = new WP_HTML_Tag_Processor( $html );
 *     if ( $tags->next_tag( 'option' ) ) {
 *         $tags->set_attribute( 'selected', true );
 *     }
 *
 * ### Finding tags
 *
 * The `next_tag()` function moves the internal cursor through
 * your input HTML document until it finds a tag meeting any of
 * the supplied restrictions in the optional query argument. If
 * no argument is provided then it will find the next HTML tag,
 * regardless of what kind it is.
 *
 * If you want to _find whatever the next tag is_:
 *
 *     $tags->next_tag();
 *
 * | Goal                                                      | Query                                                                           |
 * |-----------------------------------------------------------|---------------------------------------------------------------------------------|
 * | Find any tag.                                             | `$tags->next_tag();`                                                            |
 * | Find next image tag.                                      | `$tags->next_tag( array( 'tag_name' => 'img' ) );`                              |
 * | Find next image tag (without passing the array).          | `$tags->next_tag( 'img' );`                                                     |
 * | Find next tag containing the `fullwidth` CSS class.       | `$tags->next_tag( array( 'class_name' => 'fullwidth' ) );`                      |
 * | Find next image tag containing the `fullwidth` CSS class. | `$tags->next_tag( array( 'tag_name' => 'img', 'class_name' => 'fullwidth' ) );` |
 *
 * If a tag was found meeting your criteria then `next_tag()`
 * will return `true` and you can proceed to modify it. If it
 * returns `false`, however, it failed to find the tag and
 * moved the cursor to the end of the file.
 *
 * Once the cursor reaches the end of the file the processor
 * is done and if you want to reach an earlier tag you will
 * need to recreate the processor and start over, as it's
 * unable to back up or move in reverse.
 *
 * See the section on bookmarks for an exception to this
 * no-backing-up rule.
 *
 * #### Custom queries
 *
 * Sometimes it's necessary to further inspect an HTML tag than
 * the query syntax here permits. In these cases one may further
 * inspect the search results using the read-only functions
 * provided by the processor or external state or variables.
 *
 * Example:
 *
 *     // Paint up to the first five DIV or SPAN tags marked with the "jazzy" style.
 *     $remaining_count = 5;
 *     while ( $remaining_count > 0 && $tags->next_tag() ) {
 *         if (
 *              ( 'DIV' === $tags->get_tag() || 'SPAN' === $tags->get_tag() ) &&
 *              'jazzy' === $tags->get_attribute( 'data-style' )
 *         ) {
 *             $tags->add_class( 'theme-style-everest-jazz' );
 *             $remaining_count--;
 *         }
 *     }
 *
 * `get_attribute()` will return `null` if the attribute wasn't present
 * on the tag when it was called. It may return `""` (the empty string)
 * in cases where the attribute was present but its value was empty.
 * For boolean attributes, those whose name is present but no value is
 * given, it will return `true` (the only way to set `false` for an
 * attribute is to remove it).
 *
 * #### When matching fails
 *
 * When `next_tag()` returns `false` it could mean different things:
 *
 *  - The requested tag wasn't found in the input document.
 *  - The input document ended in the middle of an HTML syntax element.
 *
 * When a document ends in the middle of a syntax element it will pause
 * the processor. This is to make it possible in the future to extend the
 * input document and proceed - an important requirement for chunked
 * streaming parsing of a document.
 *
 * Example:
 *
 *     $processor = new WP_HTML_Tag_Processor( 'This <div is="a" partial="token' );
 *     false === $processor->next_tag();
 *
 * If a special element (see next section) is encountered but no closing tag
 * is found it will count as an incomplete tag. The parser will pause as if
 * the opening tag were incomplete.
 *
 * Example:
 *
 *     $processor = new WP_HTML_Tag_Processor( '<style>// there could be more styling to come' );
 *     false === $processor->next_tag();
 *
 *     $processor = new WP_HTML_Tag_Processor( '<style>// this is everything</style><div>' );
 *     true === $processor->next_tag( 'DIV' );
 *
 * #### Special self-contained elements
 *
 * Some HTML elements are handled in a special way; their start and end tags
 * act like a void tag. These are special because their contents can't contain
 * HTML markup. Everything inside these elements is handled in a special way
 * and content that _appears_ like HTML tags inside of them isn't. There can
 * be no nesting in these elements.
 *
 * In the following list, "raw text" means that all of the content in the HTML
 * until the matching closing tag is treated verbatim without any replacements
 * and without any parsing.
 *
 *  - IFRAME allows no content but requires a closing tag.
 *  - NOEMBED (deprecated) content is raw text.
 *  - NOFRAMES (deprecated) content is raw text.
 *  - SCRIPT content is plaintext apart from legacy rules allowing `</script>` inside an HTML comment.
 *  - STYLE content is raw text.
 *  - TITLE content is plain text but character references are decoded.
 *  - TEXTAREA content is plain text but character references are decoded.
 *  - XMP (deprecated) content is raw text.
 *
 * ### Modifying HTML attributes for a found tag
 *
 * Once you've found the start of an opening tag you can modify
 * any number of the attributes on that tag. You can set a new
 * value for an attribute, remove the entire attribute, or do
 * nothing and move on to the next opening tag.
 *
 * Example:
 *
 *     if ( $tags->next_tag( array( 'class_name' => 'wp-group-block' ) ) ) {
 *         $tags->set_attribute( 'title', 'This groups the contained content.' );
 *         $tags->remove_attribute( 'data-test-id' );
 *     }
 *
 * If `set_attribute()` is called for an existing attribute it will
 * overwrite the existing value. Similarly, calling `remove_attribute()`
 * for a non-existing attribute has no effect on the document. Both
 * of these methods are safe to call without knowing if a given attribute
 * exists beforehand.
 *
 * ### Modifying CSS classes for a found tag
 *
 * The tag processor treats the `class` attribute as a special case.
 * Because it's a common operation to add or remove CSS classes, this
 * interface adds helper methods to make that easier.
 *
 * As with attribute values, adding or removing CSS classes is a safe
 * operation that doesn't require checking if the attribute or class
 * exists before making changes. If removing the only class then the
 * entire `class` attribute will be removed.
 *
 * Example:
 *
 *     // from `<span>Yippee!</span>`
 *     //   to `<span class="is-active">Yippee!</span>`
 *     $tags->add_class( 'is-active' );
 *
 *     // from `<span class="excited">Yippee!</span>`
 *     //   to `<span class="excited is-active">Yippee!</span>`
 *     $tags->add_class( 'is-active' );
 *
 *     // from `<span class="is-active heavy-accent">Yippee!</span>`
 *     //   to `<span class="is-active heavy-accent">Yippee!</span>`
 *     $tags->add_class( 'is-active' );
 *
 *     // from `<input type="text" class="is-active rugby not-disabled" length="24">`
 *     //   to `<input type="text" class="is-active not-disabled" length="24">
 *     $tags->remove_class( 'rugby' );
 *
 *     // from `<input type="text" class="rugby" length="24">`
 *     //   to `<input type="text" length="24">
 *     $tags->remove_class( 'rugby' );
 *
 *     // from `<input type="text" length="24">`
 *     //   to `<input type="text" length="24">
 *     $tags->remove_class( 'rugby' );
 *
 * When class changes are enqueued but a direct change to `class` is made via
 * `set_attribute` then the changes to `set_attribute` (or `remove_attribute`)
 * will take precedence over those made through `add_class` and `remove_class`.
 *
 * ### Bookmarks
 *
 * While scanning through the input HTML document it's possible to set
 * a named bookmark when a particular tag is found. Later on, after
 * continuing to scan other tags, it's possible to `seek` to one of
 * the set bookmarks and then proceed again from that point forward.
 *
 * Because bookmarks create processing overhead one should avoid
 * creating too many of them. As a rule, create only bookmarks
 * of known string literal names; avoid creating "mark_{$index}"
 * and so on. It's fine from a performance standpoint to create a
 * bookmark and update it frequently, such as within a loop.
 *
 *     $total_todos = 0;
 *     while ( $p->next_tag( array( 'tag_name' => 'UL', 'class_name' => 'todo' ) ) ) {
 *         $p->set_bookmark( 'list-start' );
 *         while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
 *             if ( 'UL' === $p->get_tag() && $p->is_tag_closer() ) {
 *                 $p->set_bookmark( 'list-end' );
 *                 $p->seek( 'list-start' );
 *                 $p->set_attribute( 'data-contained-todos', (string) $total_todos );
 *                 $total_todos = 0;
 *                 $p->seek( 'list-end' );
 *                 break;
 *             }
 *
 *             if ( 'LI' === $p->get_tag() && ! $p->is_tag_closer() ) {
 *                 $total_todos++;
 *             }
 *         }
 *     }
 *
 * ## Tokens and finer-grained processing.
 *
 * It's possible to scan through every lexical token in the
 * HTML document using the `next_token()` function. This
 * alternative form takes no argument and provides no built-in
 * query syntax.
 *
 * Example:
 *
 *      $title = '(untitled)';
 *      $text  = '';
 *      while ( $processor->next_token() ) {
 *          switch ( $processor->get_token_name() ) {
 *              case '#text':
 *                  $text .= $processor->get_modifiable_text();
 *                  break;
 *
 *              case 'BR':
 *                  $text .= "\n";
 *                  break;
 *
 *              case 'TITLE':
 *                  $title = $processor->get_modifiable_text();
 *                  break;
 *          }
 *      }
 *      return trim( "# {$title}\n\n{$text}" );
 *
 * ### Tokens and _modifiable text_.
 *
 * #### Special "atomic" HTML elements.
 *
 * Not all HTML elements are able to contain other elements inside of them.
 * For instance, the contents inside a TITLE element are plaintext (except
 * that character references like &amp; will be decoded). This means that
 * if the string `<img>` appears inside a TITLE element, then it's not an
 * image tag, but rather it's text describing an image tag. Likewise, the
 * contents of a SCRIPT or STYLE element are handled entirely separately in
 * a browser than the contents of other elements because they represent a
 * different language than HTML.
 *
 * For these elements the Tag Processor treats the entire sequence as one,
 * from the opening tag, including its contents, through its closing tag.
 * This means that it's not possible to match the closing tag for a
 * SCRIPT element unless it's unexpected; the Tag Processor already matched
 * it when it found the opening tag.
 *
 * The inner contents of these elements are that element's _modifiable text_.
 *
 * The special elements are:
 *  - `SCRIPT` whose contents are treated as raw plaintext but supports a legacy
 *    style of including JavaScript inside of HTML comments to avoid accidentally
 *    closing the SCRIPT from inside a JavaScript string. E.g. `console.log( '</script>' )`.
 *  - `TITLE` and `TEXTAREA` whose contents are treated as plaintext and then any
 *    character references are decoded. E.g. `1 &lt; 2 < 3` becomes `1 < 2 < 3`.
 *  - `IFRAME`, `NOEMBED`, `NOFRAMES`, `STYLE`, `XMP` whose contents are treated as
 *    raw plaintext and left as-is. E.g. `1 &lt; 2 < 3` remains `1 &lt; 2 < 3`.
 *
 * #### Other tokens with modifiable text.
 *
 * There are also non-elements which are void/self-closing in nature and contain
 * modifiable text that is part of that individual syntax token itself.
 *
 *  - `#text` nodes, whose entire token _is_ the modifiable text.
 *  - HTML comments and tokens that become comments due to some syntax error. The
 *    text for these tokens is the portion of the comment inside of the syntax.
 *    E.g. for `<!-- comment -->` the text is `" comment "` (note the spaces are included).
 *  - `CDATA` sections, whose text is the content inside of the section itself. E.g. for
 *    `<![CDATA[some content]]>` the text is `"some content"` (with restrictions [1]).
 *  - "Funky comments," which are a special case of invalid closing tags whose name is
 *    invalid. The text for these nodes is the text that a browser would transform into
 *    an HTML comment when parsing. E.g. for `</%post_author>` the text is `%post_author`.
 *  - `DOCTYPE` declarations like `<DOCTYPE html>` which have no closing tag.
 *  - Processing instruction nodes like `<?wp __( "Like" ); ?>` (with restrictions [2]).
 *  - The empty end tag `</>` which is ignored in the browser and DOM.
 *
 * [1]: There are no CDATA sections in HTML. When encountering `<![CDATA[`, everything
 *      until the next `>` becomes a bogus HTML comment, meaning there can be no CDATA
 *      section in an HTML document containing `>`. The Tag Processor will first find
 *      all valid and bogus HTML comments, and then if the comment _would_ have been a
 *      CDATA section _were they to exist_, it will indicate this as the type of comment.
 *
 * [2]: HTML recognizes processing instructions whose target starts with an ASCII letter
 *      or `_` and continues with ASCII alphanumerics, `-`, or `_`. The reserved `xml`
 *      and `xml-stylesheet` targets, as well as XML-valid targets with characters
 *      outside this set, transform into bogus comments in the DOM instead. Processing
 *      instructions exhibit the same constraint as CDATA sections, in that `>` cannot
 *      exist within the token since the processing instruction ends at the first `>`.
 *
 * ## Design and limitations
 *
 * The Tag Processor is designed to linearly scan HTML documents and tokenize
 * HTML tags and their attributes. It's designed to do this as efficiently as
 * possible without compromising parsing integrity. Therefore it will be
 * slower than some methods of modifying HTML, such as those incorporating
 * over-simplified PCRE patterns, but will not introduce the defects and
 * failures that those methods bring in, which lead to broken page renders
 * and often to security vulnerabilities. On the other hand, it will be faster
 * than full-blown HTML parsers such as DOMDocument and use considerably
 * less memory. It requires a negligible memory overhead, enough to consider
 * it a zero-overhead system.
 *
 * The performance characteristics are maintained by avoiding tree construction
 * and semantic cleanups which are specified in HTML5. Because of this, for
 * example, it's not possible for the Tag Processor to associate any given
 * opening tag with its corresponding closing tag, or to return the inner markup
 * inside an element. Systems may be built on top of the Tag Processor to do
 * this, but the Tag Processor is and should be constrained so it can remain an
 * efficient, low-level, and reliable HTML scanner.
 *
 * The Tag Processor's design incorporates a "garbage-in-garbage-out" philosophy.
 * HTML5 specifies that certain invalid content be transformed into different forms
 * for display, such as removing null bytes from an input document and replacing
 * invalid characters with the Unicode replacement character `U+FFFD` (visually "�").
 * Where errors or transformations exist within the HTML5 specification, the Tag Processor
 * leaves those invalid inputs untouched, passing them through to the final browser
 * to handle. While this implies that certain operations will be non-spec-compliant,
 * such as reading the value of an attribute with invalid content, it also preserves a
 * simplicity and efficiency for handling those error cases.
 *
 * Most operations within the Tag Processor are designed to minimize the difference
 * between an input and output document for any given change. For example, the
 * `add_class` and `remove_class` methods preserve whitespace and the class ordering
 * within the `class` attribute; and when encountering tags with duplicated attributes,
 * the Tag Processor will leave those invalid duplicate attributes where they are but
 * update the proper attribute which the browser will read for parsing its value. An
 * exception to this rule is that all attribute updates store their values as
 * double-quoted strings, meaning that attributes on input with single-quoted or
 * unquoted values will appear in the output with double-quotes.
 *
 * ### Scripting Flag
 *
 * The Tag Processor parses HTML with the "scripting flag" disabled. This means
 * that it doesn't run any scripts while parsing the page. In a browser with
 * JavaScript enabled, for example, the script can change the parse of the
 * document as it loads. On the server, however, evaluating JavaScript is not
 * only impractical, but also unwanted.
 *
 * Practically this means that the Tag Processor will descend into NOSCRIPT
 * elements and process its child tags. Were the scripting flag enabled, such
 * as in a typical browser, the contents of NOSCRIPT are skipped entirely.
 *
 * This allows the HTML API to process the content that will be presented in
 * a browser when scripting is disabled, but it offers a different view of a
 * page than most browser sessions will experience. E.g. the tags inside the
 * NOSCRIPT disappear.
 *
 * ### Text Encoding
 *
 * The Tag Processor assumes that the input HTML document is encoded with a
 * text encoding compatible with 7-bit ASCII's '<', '>', '&', ';', '/', '=',
 * "'", '"', 'a' - 'z', 'A' - 'Z', and the whitespace characters ' ', tab,
 * carriage-return, newline, and form-feed.
 *
 * In practice, this includes almost every single-byte encoding as well as
 * UTF-8. Notably, however, it does not include UTF-16. If providing input
 * that's incompatible, then convert the encoding beforehand.
 *
 * @since 6.2.0
 * @since 6.2.1 Fix: Support for various invalid comments; attribute updates are case-insensitive.
 * @since 6.3.2 Fix: Skip HTML-like content inside rawtext elements such as STYLE.
 * @since 6.5.0 Pauses processor when input ends in an incomplete syntax token.
 *              Introduces "special" elements which act like void elements, e.g. TITLE, STYLE.
 *              Allows scanning through all tokens and processing modifiable text, where applicable.
 */
class WP_HTML_Tag_Processor {
	/**
	 * The maximum number of bookmarks allowed to exist at
	 * any given time.
	 *
	 * @since 6.2.0
	 * @var int
	 *
	 * @see WP_HTML_Tag_Processor::set_bookmark()
	 */
	const MAX_BOOKMARKS = 10;

	/**
	 * Maximum number of times seek() can be called.
	 * Prevents accidental infinite loops.
	 *
	 * @since 6.2.0
	 * @var int
	 *
	 * @see WP_HTML_Tag_Processor::seek()
	 */
	const MAX_SEEK_OPS = 1000;

	/**
	 * The HTML document to parse.
	 *
	 * @since 6.2.0
	 * @var string
	 */
	protected $html;

	/**
	 * The last query passed to next_tag().
	 *
	 * @since 6.2.0
	 * @var array|null
	 */
	private $last_query;

	/**
	 * The tag name this processor currently scans for.
	 *
	 * @since 6.2.0
	 * @var string|null
	 */
	private $sought_tag_name;

	/**
	 * The CSS class name this processor currently scans for.
	 *
	 * @since 6.2.0
	 * @var string|null
	 */
	private $sought_class_name;

	/**
	 * The match offset this processor currently scans for.
	 *
	 * @since 6.2.0
	 * @var int|null
	 */
	private $sought_match_offset;

	/**
	 * Whether to visit tag closers, e.g. </div>, when walking an input document.
	 *
	 * @since 6.2.0
	 * @var bool
	 */
	private $stop_on_tag_closers;

	/**
	 * Specifies mode of operation of the parser at any given time.
	 *
	 * | State                    | Meaning                                                              |
	 * |--------------------------|----------------------------------------------------------------------|
	 * | *Ready*                  | The parser is ready to run.                                          |
	 * | *Complete*               | There is nothing left to parse.                                      |
	 * | *Incomplete*             | The HTML ended in the middle of a token; nothing more can be parsed. |
	 * | *Matched tag*            | Found an HTML tag; it's possible to modify its attributes.           |
	 * | *Text node*              | Found a #text node; this is plaintext and modifiable.                |
	 * | *CDATA node*             | Found a CDATA section; this is modifiable.                           |
	 * | *Comment*                | Found a comment or bogus comment; this is modifiable.                |
	 * | *Presumptuous*           | Found an empty tag closer: `</>`.                                    |
	 * | *Funky comment*          | Found a tag closer with an invalid tag name; this is modifiable.     |
	 * | *Processing instruction* | Found a processing instruction, e.g. `<?pi-target data>`.            |
	 *
	 * @since 6.5.0
	 *
	 * @see WP_HTML_Tag_Processor::STATE_READY
	 * @see WP_HTML_Tag_Processor::STATE_COMPLETE
	 * @see WP_HTML_Tag_Processor::STATE_INCOMPLETE_INPUT
	 * @see WP_HTML_Tag_Processor::STATE_MATCHED_TAG
	 * @see WP_HTML_Tag_Processor::STATE_TEXT_NODE
	 * @see WP_HTML_Tag_Processor::STATE_CDATA_NODE
	 * @see WP_HTML_Tag_Processor::STATE_COMMENT
	 * @see WP_HTML_Tag_Processor::STATE_DOCTYPE
	 * @see WP_HTML_Tag_Processor::STATE_PRESUMPTUOUS_TAG
	 * @see WP_HTML_Tag_Processor::STATE_FUNKY_COMMENT
	 * @see WP_HTML_Tag_Processor::STATE_PROCESSING_INSTRUCTION
	 *
	 * @var string
	 */
	protected $parser_state = self::STATE_READY;

	/**
	 * Indicates if the document is in quirks mode or no-quirks mode.
	 *
	 *  Impact on HTML parsing:
	 *
	 *   - In `NO_QUIRKS_MODE` (also known as "standard mode"):
	 *       - CSS class and ID selectors match byte-for-byte (case-sensitively).
	 *       - A TABLE start tag `<table>` implicitly closes any open `P` element.
	 *
	 *   - In `QUIRKS_MODE`:
	 *       - CSS class and ID selectors match in an ASCII case-insensitive manner.
	 *       - A TABLE start tag `<table>` opens a `TABLE` element as a child of a `P`
	 *         element if one is open.
	 *
	 * Quirks and no-quirks mode are thus mostly about styling, but have an impact when
	 * tables are found inside paragraph elements.
	 *
	 * @see self::QUIRKS_MODE
	 * @see self::NO_QUIRKS_MODE
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	protected $compat_mode = self::NO_QUIRKS_MODE;

	/**
	 * Indicates whether the parser is inside foreign content,
	 * e.g. inside an SVG or MathML element.
	 *
	 * One of 'html', 'svg', or 'math'.
	 *
	 * Several parsing rules change based on whether the parser
	 * is inside foreign content, including whether CDATA sections
	 * are allowed and whether a self-closing flag indicates that
	 * an element has no content.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	private $parsing_namespace = 'html';

	/**
	 * What kind of syntax token became an HTML comment.
	 *
	 * Since there are many ways in which HTML syntax can create an HTML comment,
	 * this indicates which of those caused it. This allows the Tag Processor to
	 * represent more from the original input document than would appear in the DOM.
	 *
	 * @since 6.5.0
	 *
	 * @var string|null
	 */
	protected $comment_type = null;

	/**
	 * What kind of text the matched text node represents, if it was subdivided.
	 *
	 * @see self::TEXT_IS_NULL_SEQUENCE
	 * @see self::TEXT_IS_WHITESPACE
	 * @see self::TEXT_IS_GENERIC
	 * @see self::subdivide_text_appropriately
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	protected $text_node_classification = self::TEXT_IS_GENERIC;

	/**
	 * How many bytes from the original HTML document have been read and parsed.
	 *
	 * This value points to the latest byte offset in the input document which
	 * has been already parsed. It is the internal cursor for the Tag Processor
	 * and updates while scanning through the HTML tokens.
	 *
	 * @since 6.2.0
	 * @var int
	 */
	private $bytes_already_parsed = 0;

	/**
	 * Byte offset in input document where current token starts.
	 *
	 * Example:
	 *
	 *     <div id="test">...
	 *     01234
	 *     - token starts at 0
	 *
	 * @since 6.5.0
	 *
	 * @var int|null
	 */
	private $token_starts_at;

	/**
	 * Byte length of current token.
	 *
	 * Example:
	 *
	 *     <div id="test">...
	 *     0123456789012345
	 *     - token length is 15 - 0 = 15
	 *
	 *     a <!-- comment --> is a token.
	 *     0123456789 123456789 123456789
	 *     - token length is 18 - 2 = 16
	 *
	 * @since 6.5.0
	 *
	 * @var int|null
	 */
	private $token_length;

	/**
	 * Whether the current tag token has the self-closing flag.
	 *
	 * @since 7.1.0
	 *
	 * @var bool
	 */
	private $has_self_closing_flag = false;

	/**
	 * Byte offset in input document where current tag name starts.
	 *
	 * Example:
	 *
	 *     <div id="test">...
	 *     01234
	 *      - tag name starts at 1
	 *
	 * @since 6.2.0
	 *
	 * @var int|null
	 */
	private $tag_name_starts_at;

	/**
	 * Byte length of current tag name.
	 *
	 * Example:
	 *
	 *     <div id="test">...
	 *     01234
	 *      --- tag name length is 3
	 *
	 * @since 6.2.0
	 *
	 * @var int|null
	 */
	private $tag_name_length;

	/**
	 * Byte offset into input document where current modifiable text starts.
	 *
	 * @since 6.5.0
	 *
	 * @var int
	 */
	private $text_starts_at;

	/**
	 * Byte length of modifiable text.
	 *
	 * @since 6.5.0
	 *
	 * @var int
	 */
	private $text_length;

	/**
	 * Whether the current tag is an opening tag, e.g. <div>, or a closing tag, e.g. </div>.
	 *
	 * @var bool
	 */
	private $is_closing_tag;

	/**
	 * Lazily-built index of attributes found within an HTML tag, keyed by the attribute name.
	 *
	 * Example:
	 *
	 *     // Supposing the parser is working through this content
	 *     // and stops after recognizing the `id` attribute.
	 *     // <div id="test-4" class=outline title="data:text/plain;base64=asdk3nk1j3fo8">
	 *     //                 ^ parsing will continue from this point.
	 *     $this->attributes = array(
	 *         'id' => new WP_HTML_Attribute_Token( 'id', 9, 6, 5, 11, false )
	 *     );
	 *
	 *     // When picking up parsing again, or when asking to find the
	 *     // `class` attribute we will continue and add to this array.
	 *     $this->attributes = array(
	 *         'id'    => new WP_HTML_Attribute_Token( 'id', 9, 6, 5, 11, false ),
	 *         'class' => new WP_HTML_Attribute_Token( 'class', 23, 7, 17, 13, false )
	 *     );
	 *
	 *     // Note that only the `class` attribute value is stored in the index.
	 *     // That's because it is the only value used by this class at the moment.
	 *
	 * @since 6.2.0
	 * @var WP_HTML_Attribute_Token[]
	 * @phpstan-var array<non-empty-string, WP_HTML_Attribute_Token>
	 */
	private $attributes = array();

	/**
	 * Tracks spans of duplicate attributes on a given tag, used for removing
	 * all copies of an attribute when calling `remove_attribute()`.
	 *
	 * @since 6.3.2
	 *
	 * @var (WP_HTML_Span[])[]|null
	 */
	private $duplicate_attributes = null;

	/**
	 * Which class names to add or remove from a tag.
	 *
	 * These are tracked separately from attribute updates because they are
	 * semantically distinct, whereas this interface exists for the common
	 * case of adding and removing class names while other attributes are
	 * generally modified as with DOM `setAttribute` calls.
	 *
	 * When modifying an HTML document these will eventually be collapsed
	 * into a single `set_attribute( 'class', $changes )` call.
	 *
	 * Example:
	 *
	 *     // Add the `wp-block-group` class, remove the `wp-group` class.
	 *     $classname_updates = array(
	 *         // Indexed by a comparable class name.
	 *         'wp-block-group' => WP_HTML_Tag_Processor::ADD_CLASS,
	 *         'wp-group'       => WP_HTML_Tag_Processor::REMOVE_CLASS
	 *     );
	 *
	 * @since 6.2.0
	 * @var array<non-empty-string, self::ADD_CLASS|self::REMOVE_CLASS>
	 */
	private $classname_updates = array();

	/**
	 * Tracks a semantic location in the original HTML which
	 * shifts with updates as they are applied to the document.
	 *
	 * @since 6.2.0
	 * @var WP_HTML_Span[]
	 */
	protected $bookmarks = array();

	const ADD_CLASS    = true;
	const REMOVE_CLASS = false;
	const SKIP_CLASS   = null;

	/**
	 * Lexical replacements to apply to input HTML document.
	 *
	 * "Lexical" in this class refers to the part of this class which
	 * operates on pure text _as text_ and not as HTML. There's a line
	 * between the public interface, with HTML-semantic methods like
	 * `set_attribute` and `add_class`, and an internal state that tracks
	 * text offsets in the input document.
	 *
	 * When higher-level HTML methods are called, those have to transform their
	 * operations (such as setting an attribute's value) into text diffing
	 * operations (such as replacing the sub-string from indices A to B with
	 * some given new string). These text-diffing operations are the lexical
	 * updates.
	 *
	 * As new higher-level methods are added they need to collapse their
	 * operations into these lower-level lexical updates since that's the
	 * Tag Processor's internal language of change. Any code which creates
	 * these lexical updates must ensure that they do not cross HTML syntax
	 * boundaries, however, so these should never be exposed outside of this
	 * class or any classes which intentionally expand its functionality.
	 *
	 * These are enqueued while editing the document instead of being immediately
	 * applied to avoid processing overhead, string allocations, and string
	 * copies when applying many updates to a single document.
	 *
	 * Example:
	 *
	 *     // Replace an attribute stored with a new value, indices
	 *     // sourced from the lazily-parsed HTML recognizer.
	 *     $start  = $attributes['src']->start;
	 *     $length = $attributes['src']->length;
	 *     $modifications[] = new WP_HTML_Text_Replacement( $start, $length, $new_value );
	 *
	 *     // Correspondingly, something like this will appear in this array.
	 *     $lexical_updates = array(
	 *         WP_HTML_Text_Replacement( 14, 28, 'https://my-site.my-domain/wp-content/uploads/2014/08/kittens.jpg' )
	 *     );
	 *
	 * @since 6.2.0
	 * @var array<int|string, WP_HTML_Text_Replacement>
	 */
	protected $lexical_updates = array();

	/**
	 * Tracks and limits `seek()` calls to prevent accidental infinite loops.
	 *
	 * @since 6.2.0
	 * @var int
	 *
	 * @see WP_HTML_Tag_Processor::seek()
	 */
	protected $seek_count = 0;

	/**
	 * Whether the parser should skip over an immediately-following linefeed
	 * character, as is the case with LISTING, PRE, and TEXTAREA.
	 *
	 * > If the next token is a U+000A LINE FEED (LF) character token, then
	 * > ignore that token and move on to the next one. (Newlines at the start
	 * > of [these] elements are ignored as an authoring convenience.)
	 *
	 * @since 6.7.0
	 *
	 * @var int|null
	 */
	private $skip_newline_at = null;

	/**
	 * Constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param string $html HTML to process.
	 */
	public function __construct( $html ) {
		if ( ! is_string( $html ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The HTML parameter must be a string.' ),
				'6.9.0'
			);
			$html = '';
		}
		$this->html = $html;
	}

	/**
	 * Switches parsing mode into a new namespace, such as when
	 * encountering an SVG tag and entering foreign content.
	 *
	 * @since 6.7.0
	 *
	 * @param string $new_namespace One of 'html', 'svg', or 'math' indicating into what
	 *                              namespace the next tokens will be processed.
	 * @return bool Whether the namespace was valid and changed.
	 */
	public function change_parsing_namespace( string $new_namespace ): bool {
		if ( ! in_array( $new_namespace, array( 'html', 'math', 'svg' ), true ) ) {
			return false;
		}

		$this->parsing_namespace = $new_namespace;
		return true;
	}

	/**
	 * Finds the next tag matching the $query.
	 *
	 * @since 6.2.0
	 * @since 6.5.0 No longer processes incomplete tokens at end of document; pauses the processor at start of token.
	 *
	 * @param array|string|null $query {
	 *     Optional. Which tag name to find, having which class, etc. Default is to find any tag.
	 *
	 *     @type string|null $tag_name     Which tag to find, or `null` for "any tag."
	 *     @type int|null    $match_offset Find the Nth tag matching all search criteria.
	 *                                     1 for "first" tag, 3 for "third," etc.
	 *                                     Defaults to first tag.
	 *     @type string|null $class_name   Tag must contain this whole class name to match.
	 *     @type string|null $tag_closers  "visit" or "skip": whether to stop on tag closers, e.g. </div>.
	 * }
	 * @return bool Whether a tag was matched.
	 *
	 * @phpstan-impure
	 */
	public function next_tag( $query = null ): bool {
		$this->parse_query( $query );
		$already_found = 0;

		do {
			if ( false === $this->next_token() ) {
				return false;
			}

			if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
				continue;
			}

			if ( $this->matches() ) {
				++$already_found;
			}
		} while ( $already_found < $this->sought_match_offset );

		return true;
	}

	/**
	 * Finds the next token in the HTML document.
	 *
	 * An HTML document can be viewed as a stream of tokens,
	 * where tokens are things like HTML tags, HTML comments,
	 * text nodes, etc. This method finds the next token in
	 * the HTML document and returns whether it found one.
	 *
	 * If it starts parsing a token and reaches the end of the
	 * document then it will seek to the start of the last
	 * token and pause, returning `false` to indicate that it
	 * failed to find a complete token.
	 *
	 * Possible token types, based on the HTML specification:
	 *
	 *  - an HTML tag, whether opening, closing, or void.
	 *  - a text node - the plaintext inside tags.
	 *  - an HTML comment.
	 *  - a DOCTYPE declaration.
	 *  - an HTML processing instruction, e.g. `<?pi …data?>`.
	 *
	 * @since 6.5.0
	 * @since 6.7.0 Recognizes CDATA sections within foreign content.
	 * @since 7.1.0 Recognizes processing instructions.
	 *
	 * @return bool Whether a token was parsed.
	 */
	public function next_token(): bool {
		return $this->base_class_next_token();
	}

	/**
	 * Internal method which finds the next token in the HTML document.
	 *
	 * This method is a protected internal function which implements the logic for
	 * finding the next token in a document. It exists so that the parser can update
	 * its state without affecting the location of the cursor in the document and
	 * without triggering subclass methods for things like `next_token()`, e.g. when
	 * applying patches before searching for the next token.
	 *
	 * @since 6.5.0
	 * @ignore
	 *
	 * @return bool Whether a token was parsed.
	 */
	private function base_class_next_token(): bool {
		$was_at = $this->bytes_already_parsed;
		$this->after_tag();

		// Don't proceed if there's nothing more to scan.
		if (
			self::STATE_COMPLETE === $this->parser_state ||
			self::STATE_INCOMPLETE_INPUT === $this->parser_state
		) {
			return false;
		}

		/*
		 * The next step in the parsing loop determines the parsing state;
		 * clear it so that state doesn't linger from the previous step.
		 */
		$this->parser_state = self::STATE_READY;

		if ( $this->bytes_already_parsed >= strlen( $this->html ) ) {
			$this->parser_state = self::STATE_COMPLETE;
			return false;
		}

		// Find the next tag if it exists.
		if ( false === $this->parse_next_tag() ) {
			if ( self::STATE_INCOMPLETE_INPUT === $this->parser_state ) {
				$this->bytes_already_parsed = $was_at;
			}

			return false;
		}

		/*
		 * For legacy reasons the rest of this function handles tags and their
		 * attributes. If the processor has reached the end of the document
		 * or if it matched any other token then it should return here to avoid
		 * attempting to process tag-specific syntax.
		 */
		if (
			self::STATE_INCOMPLETE_INPUT !== $this->parser_state &&
			self::STATE_COMPLETE !== $this->parser_state &&
			self::STATE_MATCHED_TAG !== $this->parser_state
		) {
			return true;
		}

		// Parse all of its attributes.
		while ( $this->parse_next_attribute() ) {
			continue;
		}

		// Ensure that the tag closes before the end of the document.
		if (
			self::STATE_INCOMPLETE_INPUT === $this->parser_state ||
			$this->bytes_already_parsed >= strlen( $this->html )
		) {
			// Does this appropriately clear state (parsed attributes)?
			$this->parser_state         = self::STATE_INCOMPLETE_INPUT;
			$this->bytes_already_parsed = $was_at;

			return false;
		}

		$tag_ends_at = strpos( $this->html, '>', $this->bytes_already_parsed );
		if ( false === $tag_ends_at ) {
			$this->parser_state         = self::STATE_INCOMPLETE_INPUT;
			$this->bytes_already_parsed = $was_at;

			return false;
		}
		$this->parser_state         = self::STATE_MATCHED_TAG;
		$this->bytes_already_parsed = $tag_ends_at + 1;
		$this->token_length         = $this->bytes_already_parsed - $this->token_starts_at;

		/*
		 * Certain tags require additional processing. The first-letter pre-check
		 * avoids unnecessary string allocation when comparing the tag names.
		 *
		 *  - IFRAME
		 *  - LISTING (deprecated)
		 *  - NOEMBED (deprecated)
		 *  - NOFRAMES (deprecated)
		 *  - PRE
		 *  - SCRIPT
		 *  - STYLE
		 *  - TEXTAREA
		 *  - TITLE
		 *  - XMP (deprecated)
		 */
		if (
			$this->is_closing_tag ||
			'html' !== $this->parsing_namespace ||
			1 !== strspn( $this->html, 'iIlLnNpPsStTxX', $this->tag_name_starts_at, 1 )
		) {
			return true;
		}

		$tag_name = $this->get_tag();

		/*
		 * For LISTING, PRE, and TEXTAREA, the first linefeed of an immediately-following
		 * text node is ignored as an authoring convenience.
		 *
		 * @see static::skip_newline_at
		 */
		if ( 'LISTING' === $tag_name || 'PRE' === $tag_name ) {
			$this->skip_newline_at = $this->bytes_already_parsed;
			return true;
		}

		/*
		 * There are certain elements whose children are not DATA but are instead
		 * RCDATA or RAWTEXT. These cannot contain other elements, and the contents
		 * are parsed as plaintext, with character references decoded in RCDATA but
		 * not in RAWTEXT.
		 *
		 * These elements are described here as "self-contained" or special atomic
		 * elements whose end tag is consumed with the opening tag, and they will
		 * contain modifiable text inside of them.
		 *
		 * Preserve the opening tag pointers, as these will be overwritten
		 * when finding the closing tag. They will be reset after finding
		 * the closing tag to point to the opening of the special atomic
		 * tag sequence.
		 */
		$tag_name_starts_at    = $this->tag_name_starts_at;
		$tag_name_length       = $this->tag_name_length;
		$tag_ends_at           = $this->token_starts_at + $this->token_length;
		$has_self_closing_flag = $this->has_self_closing_flag;
		$attributes            = $this->attributes;
		$duplicate_attributes  = $this->duplicate_attributes;

		// Find the closing tag if necessary.
		switch ( $tag_name ) {
			case 'SCRIPT':
				$found_closer = $this->skip_script_data();
				break;

			case 'TEXTAREA':
			case 'TITLE':
				$found_closer = $this->skip_rcdata( $tag_name );
				break;

			/*
			 * In the browser this list would include the NOSCRIPT element,
			 * but the Tag Processor is an environment with the scripting
			 * flag disabled, meaning that it needs to descend into the
			 * NOSCRIPT element to be able to properly process what will be
			 * sent to a browser.
			 *
			 * Note that this rule makes HTML5 syntax incompatible with XML,
			 * because the parsing of this token depends on client application.
			 * The NOSCRIPT element cannot be represented in the XHTML syntax.
			 */
			case 'IFRAME':
			case 'NOEMBED':
			case 'NOFRAMES':
			case 'STYLE':
			case 'XMP':
				$found_closer = $this->skip_rawtext( $tag_name );
				break;

			// No other tags should be treated in their entirety here.
			default:
				return true;
		}

		if ( ! $found_closer ) {
			$this->parser_state         = self::STATE_INCOMPLETE_INPUT;
			$this->bytes_already_parsed = $was_at;
			return false;
		}

		/*
		 * The values here look like they reference the opening tag but they reference
		 * the closing tag instead. This is why the opening tag values were stored
		 * above in a variable. It reads confusingly here, but that's because the
		 * functions that skip the contents have moved all the internal cursors past
		 * the inner content of the tag.
		 */
		$this->token_starts_at       = $was_at;
		$this->token_length          = $this->bytes_already_parsed - $this->token_starts_at;
		$this->text_starts_at        = $tag_ends_at;
		$this->text_length           = $this->tag_name_starts_at - $this->text_starts_at;
		$this->tag_name_starts_at    = $tag_name_starts_at;
		$this->tag_name_length       = $tag_name_length;
		$this->has_self_closing_flag = $has_self_closing_flag;
		$this->attributes            = $attributes;
		$this->duplicate_attributes  = $duplicate_attributes;

		return true;
	}

	/**
	 * Whether the processor paused because the input HTML document ended
	 * in the middle of a syntax element, such as in the middle of a tag.
	 *
	 * Example:
	 *
	 *     $processor = new WP_HTML_Tag_Processor( '<input type="text" value="Th' );
	 *     false      === $processor->next_tag();
	 *     true       === $processor->paused_at_incomplete_token();
	 *
	 * @since 6.5.0
	 *
	 * @return bool Whether the parse paused at the start of an incomplete token.
	 */
	public function paused_at_incomplete_token(): bool {
		return self::STATE_INCOMPLETE_INPUT === $this->parser_state;
	}

	/**
	 * Generator for a foreach loop to step through each class name for the matched tag.
	 *
	 * This generator function is designed to be used inside a "foreach" loop.
	 *
	 * Example:
	 *
	 *     $p = new WP_HTML_Tag_Processor( "<div class='free &lt;egg&lt;\tlang-en'>" );
	 *     $p->next_tag();
	 *     foreach ( $p->class_list() as $class_name ) {
	 *         echo "{$class_name} ";
	 *     }
	 *     // Outputs: "free <egg> lang-en "
	 *
	 * @since 6.4.0
	 *
	 * @return Generator<int, non-empty-string>
	 */
	public function class_list() {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return;
		}

		/** @var string $class contains the string value of the class attribute, with character references decoded. */
		$class = $this->get_attribute( 'class' );

		if ( ! is_string( $class ) ) {
			return;
		}

		$seen = array();

		$is_quirks = self::QUIRKS_MODE === $this->compat_mode;

		$at = 0;
		while ( $at < strlen( $class ) ) {
			// Skip past any initial boundary characters.
			$at += strspn( $class, " \t\f\r\n", $at );
			if ( $at >= strlen( $class ) ) {
				return;
			}

			// Find the byte length until the next boundary.
			$length = strcspn( $class, " \t\f\r\n", $at );
			if ( 0 === $length ) {
				return;
			}

			$name = substr( $class, $at, $length );
			if ( $is_quirks ) {
				$name = strtolower( $name );
			}
			$at += $length;

			/*
			 * It's expected that the number of class names for a given tag is relatively small.
			 * Given this, it is probably faster overall to scan an array for a value rather
			 * than to use the class name as a key and check if it's a key of $seen.
			 */
			if ( in_array( $name, $seen, true ) ) {
				continue;
			}

			$seen[] = $name;
			yield $name;
		}
	}


	/**
	 * Returns if a matched tag contains the given ASCII case-insensitive class name.
	 *
	 * @since 6.4.0
	 *
	 * @param string $wanted_class Look for this CSS class name, ASCII case-insensitive.
	 * @return bool|null Whether the matched tag contains the given class name, or null if not matched.
	 */
	public function has_class( $wanted_class ) {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return null;
		}

		$case_insensitive = self::QUIRKS_MODE === $this->compat_mode;

		$wanted_length = strlen( $wanted_class );
		foreach ( $this->class_list() as $class_name ) {
			if (
				strlen( $class_name ) === $wanted_length &&
				0 === substr_compare( $class_name, $wanted_class, 0, strlen( $wanted_class ), $case_insensitive )
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Sets a bookmark in the HTML document.
	 *
	 * Bookmarks represent specific places or tokens in the HTML
	 * document, such as a tag opener or closer. When applying
	 * edits to a document, such as setting an attribute, the
	 * text offsets of that token may shift; the bookmark is
	 * kept updated with those shifts and remains stable unless
	 * the entire span of text in which the token sits is removed.
	 *
	 * Release bookmarks when they are no longer needed.
	 *
	 * Example:
	 *
	 *     <main><h2>Surprising fact you may not know!</h2></main>
	 *           ^  ^
	 *            \-|-- this `H2` opener bookmark tracks the token
	 *
	 *     <main class="clickbait"><h2>Surprising fact you may no…
	 *                             ^  ^
	 *                              \-|-- it shifts with edits
	 *
	 * Bookmarks provide the ability to seek to a previously-scanned
	 * place in the HTML document. This avoids the need to re-scan
	 * the entire document.
	 *
	 * Example:
	 *
	 *     <ul><li>One</li><li>Two</li><li>Three</li></ul>
	 *                                 ^^^^
	 *                                 want to note this last item
	 *
	 *     $p = new WP_HTML_Tag_Processor( $html );
	 *     $in_list = false;
	 *     while ( $p->next_tag( array( 'tag_closers' => $in_list ? 'visit' : 'skip' ) ) ) {
	 *         if ( 'UL' === $p->get_tag() ) {
	 *             if ( $p->is_tag_closer() ) {
	 *                 $in_list = false;
	 *                 $p->set_bookmark( 'resume' );
	 *                 if ( $p->seek( 'last-li' ) ) {
	 *                     $p->add_class( 'last-li' );
	 *                 }
	 *                 $p->seek( 'resume' );
	 *                 $p->release_bookmark( 'last-li' );
	 *                 $p->release_bookmark( 'resume' );
	 *             } else {
	 *                 $in_list = true;
	 *             }
	 *         }
	 *
	 *         if ( 'LI' === $p->get_tag() ) {
	 *             $p->set_bookmark( 'last-li' );
	 *         }
	 *     }
	 *
	 * Bookmarks intentionally hide the internal string offsets
	 * to which they refer. They are maintained internally as
	 * updates are applied to the HTML document and therefore
	 * retain their "position" - the location to which they
	 * originally pointed. The inability to use bookmarks with
	 * functions like `substr` is therefore intentional to guard
	 * against accidentally breaking the HTML.
	 *
	 * Because bookmarks allocate memory and require processing
	 * for every applied update, they are limited and require
	 * a name. They should not be created with programmatically-made
	 * names, such as "li_{$index}" with some loop. As a general
	 * rule they should only be created with string-literal names
	 * like "start-of-section" or "last-paragraph".
	 *
	 * Bookmarks are a powerful tool to enable complicated behavior.
	 * Consider double-checking that you need this tool if you are
	 * reaching for it, as inappropriate use could lead to broken
	 * HTML structure or unwanted processing overhead.
	 *
	 * @since 6.2.0
	 *
	 * @param string $name Identifies this particular bookmark.
	 * @return bool Whether the bookmark was successfully created.
	 */
	public function set_bookmark( $name ): bool {
		// It only makes sense to set a bookmark if the parser has paused on a concrete token.
		if (
			self::STATE_COMPLETE === $this->parser_state ||
			self::STATE_INCOMPLETE_INPUT === $this->parser_state
		) {
			return false;
		}

		if ( ! array_key_exists( $name, $this->bookmarks ) && count( $this->bookmarks ) >= static::MAX_BOOKMARKS ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'Too many bookmarks: cannot create any more.' ),
				'6.2.0'
			);
			return false;
		}

		$this->bookmarks[ $name ] = new WP_HTML_Span( $this->token_starts_at, $this->token_length );

		return true;
	}


	/**
	 * Removes a bookmark that is no longer needed.
	 *
	 * Releasing a bookmark frees up the small
	 * performance overhead it requires.
	 *
	 * @param string $name Name of the bookmark to remove.
	 * @return bool Whether the bookmark already existed before removal.
	 */
	public function release_bookmark( $name ): bool {
		if ( ! array_key_exists( $name, $this->bookmarks ) ) {
			return false;
		}

		unset( $this->bookmarks[ $name ] );

		return true;
	}

	/**
	 * Skips contents of generic rawtext elements.
	 *
	 * @since 6.3.2
	 * @ignore
	 *
	 * @see https://html.spec.whatwg.org/#generic-raw-text-element-parsing-algorithm
	 *
	 * @param string $tag_name The uppercase tag name which will close the RAWTEXT region.
	 * @return bool Whether an end to the RAWTEXT region was found before the end of the document.
	 */
	private function skip_rawtext( string $tag_name ): bool {
		/*
		 * These two functions distinguish themselves on whether character references are
		 * decoded, and since functionality to read the inner markup isn't supported, it's
		 * not necessary to implement these two functions separately.
		 */
		return $this->skip_rcdata( $tag_name );
	}

	/**
	 * Skips contents of RCDATA elements, namely title and textarea tags.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @see https://html.spec.whatwg.org/multipage/parsing.html#rcdata-state
	 *
	 * @param string $tag_name The uppercase tag name which will close the RCDATA region.
	 * @return bool Whether an end to the RCDATA region was found before the end of the document.
	 */
	private function skip_rcdata( string $tag_name ): bool {
		$html       = $this->html;
		$doc_length = strlen( $html );
		$tag_length = strlen( $tag_name );

		$at = $this->bytes_already_parsed;

		while ( false !== $at && $at < $doc_length ) {
			$at                       = strpos( $this->html, '</', $at );
			$this->tag_name_starts_at = $at;

			// Fail if there is no possible tag closer.
			if ( false === $at || ( $at + 2 + $tag_length ) >= $doc_length ) {
				return false;
			}

			$at += 2;

			/*
			 * Find a case-insensitive match to the tag name.
			 *
			 * Because tag names are limited to US-ASCII there is no
			 * need to perform any kind of Unicode normalization when
			 * comparing; any character which could be impacted by such
			 * normalization could not be part of a tag name.
			 */
			for ( $i = 0; $i < $tag_length; $i++ ) {
				$tag_char  = $tag_name[ $i ];
				$html_char = $html[ $at + $i ];

				if ( $html_char !== $tag_char && strtoupper( $html_char ) !== $tag_char ) {
					$at += $i;
					continue 2;
				}
			}

			$at                        += $tag_length;
			$this->bytes_already_parsed = $at;

			if ( $at >= strlen( $html ) ) {
				return false;
			}

			/*
			 * Ensure that the tag name terminates to avoid matching on
			 * substrings of a longer tag name. For example, the sequence
			 * "</textarearug" should not match for "</textarea" even
			 * though "textarea" is found within the text.
			 */
			$c = $html[ $at ];
			if ( ' ' !== $c && "\t" !== $c && "\r" !== $c && "\n" !== $c && '/' !== $c && '>' !== $c ) {
				continue;
			}

			while ( $this->parse_next_attribute() ) {
				continue;
			}

			$at = $this->bytes_already_parsed;
			if ( $at >= strlen( $this->html ) ) {
				return false;
			}

			if ( '>' === $html[ $at ] ) {
				$this->bytes_already_parsed = $at + 1;
				return true;
			}

			if ( $at + 1 >= strlen( $this->html ) ) {
				return false;
			}

			if ( '/' === $html[ $at ] && '>' === $html[ $at + 1 ] ) {
				$this->bytes_already_parsed = $at + 2;
				return true;
			}
		}

		return false;
	}

	/**
	 * Skips contents of script tags.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @return bool Whether the script tag was closed before the end of the document.
	 */
	private function skip_script_data(): bool {
		$state      = 'unescaped';
		$html       = $this->html;
		$doc_length = strlen( $html );
		$at         = $this->bytes_already_parsed;

		while ( false !== $at && $at < $doc_length ) {
			$at += strcspn( $html, '-<', $at );

			/*
			 * Optimization: Terminating a complete script element requires at least eight
			 * additional bytes in the document. Some checks below may cause local escaped
			 * state transitions when processing shorter strings, but those transitions are
			 * irrelevant if the script tag is incomplete and the function must return false.
			 *
			 * This may need updating if those transitions become significant or exported from
			 * this function in some way, such as when building safe methods to embed JavaScript
			 * or data inside a SCRIPT element.
			 *
			 *     $at may be here.
			 *        ↓
			 *     ...</script>
			 *         ╰──┬───╯
			 *     $at + 8 additional bytes are required for a non-false return value.
			 *
			 * This single check eliminates the need to check lengths for the shorter spans:
			 *
			 *           $at may be here.
			 *                  ↓
			 *     <script><!-- --></script>
			 *                   ├╯
			 *             $at + 2 additional characters does not require a length check.
			 *
			 * The transition from "escaped" to "unescaped" is not relevant if the document ends:
			 *
			 *           $at may be here.
			 *                  ↓
			 *     <script><!-- -->[[END-OF-DOCUMENT]]
			 *                   ╰──┬───╯
			 *             $at + 8 additional bytes is not satisfied, return false.
			 */
			if ( $at + 8 >= $doc_length ) {
				return false;
			}

			/*
			 * For all script states a "-->"  transitions
			 * back into the normal unescaped script mode,
			 * even if that's the current state.
			 */
			if (
				'-' === $html[ $at ] &&
				'-' === $html[ $at + 1 ] &&
				'>' === $html[ $at + 2 ]
			) {
				$at   += 3;
				$state = 'unescaped';
				continue;
			}

			/*
			 * Everything of interest past here starts with "<".
			 * Check this character and advance position regardless.
			 */
			if ( '<' !== $html[ $at++ ] ) {
				continue;
			}

			/*
			 * "<!--" only transitions from _unescaped_ to _escaped_. This byte sequence is only
			 * significant in the _unescaped_ state and is ignored in any other state.
			 */
			if (
				'unescaped' === $state &&
				'!' === $html[ $at ] &&
				'-' === $html[ $at + 1 ] &&
				'-' === $html[ $at + 2 ]
			) {
				$at += 3;

				/*
				 * The parser is ready to enter the _escaped_ state, but may remain in the
				 * _unescaped_ state. This occurs when "<!--" is immediately followed by a
				 * sequence of 0 or more "-" followed by ">". This is similar to abruptly closed
				 * HTML comments like "<!-->" or "<!--->".
				 *
				 * Note that this check may advance the position significantly and requires a
				 * length check to prevent bad offsets on inputs like `<script><!---------`.
				 */
				$at += strspn( $html, '-', $at );
				if ( $at < $doc_length && '>' === $html[ $at ] ) {
					++$at;
					continue;
				}

				$state = 'escaped';
				continue;
			}

			if ( '/' === $html[ $at ] ) {
				$closer_potentially_starts_at = $at - 1;
				$is_closing                   = true;
				++$at;
			} else {
				$is_closing = false;
			}

			/*
			 * At this point the only remaining state-changes occur with the
			 * <script> and </script> tags; unless one of these appears next,
			 * proceed scanning to the next potential token in the text.
			 */
			if ( ! (
				( 's' === $html[ $at ] || 'S' === $html[ $at ] ) &&
				( 'c' === $html[ $at + 1 ] || 'C' === $html[ $at + 1 ] ) &&
				( 'r' === $html[ $at + 2 ] || 'R' === $html[ $at + 2 ] ) &&
				( 'i' === $html[ $at + 3 ] || 'I' === $html[ $at + 3 ] ) &&
				( 'p' === $html[ $at + 4 ] || 'P' === $html[ $at + 4 ] ) &&
				( 't' === $html[ $at + 5 ] || 'T' === $html[ $at + 5 ] )
			) ) {
				continue;
			}

			/*
			 * Ensure that the script tag terminates to avoid matching on
			 * substrings of a non-match. For example, the sequence
			 * "<script123" should not end a script region even though
			 * "<script" is found within the text.
			 */
			$at += 6;
			$c   = $html[ $at ];
			if (
				/**
				 * These characters trigger state transitions of interest:
				 *
				 * - @see {https://html.spec.whatwg.org/multipage/parsing.html#script-data-end-tag-name-state}
				 * - @see {https://html.spec.whatwg.org/multipage/parsing.html#script-data-escaped-end-tag-name-state}
				 * - @see {https://html.spec.whatwg.org/multipage/parsing.html#script-data-double-escape-start-state}
				 * - @see {https://html.spec.whatwg.org/multipage/parsing.html#script-data-double-escape-end-state}
				 *
				 * The "\r" character is not present in the above references. However, "\r" must be
				 * treated the same as "\n". This is because the HTML Standard requires newline
				 * normalization during preprocessing which applies this replacement.
				 *
				 * - @see https://html.spec.whatwg.org/multipage/parsing.html#preprocessing-the-input-stream
				 * - @see https://infra.spec.whatwg.org/#normalize-newlines
				 */
				'>' !== $c &&
				' ' !== $c &&
				"\n" !== $c &&
				'/' !== $c &&
				"\t" !== $c &&
				"\f" !== $c &&
				"\r" !== $c
			) {
				continue;
			}

			if ( 'escaped' === $state && ! $is_closing ) {
				$state = 'double-escaped';
				continue;
			}

			if ( 'double-escaped' === $state && $is_closing ) {
				$state = 'escaped';
				continue;
			}

			if ( $is_closing ) {
				$this->bytes_already_parsed = $closer_potentially_starts_at;
				$this->tag_name_starts_at   = $closer_potentially_starts_at;
				if ( $this->bytes_already_parsed >= $doc_length ) {
					return false;
				}

				while ( $this->parse_next_attribute() ) {
					continue;
				}

				if ( $this->bytes_already_parsed >= $doc_length ) {
					return false;
				}

				if ( '>' === $html[ $this->bytes_already_parsed ] ) {
					++$this->bytes_already_parsed;
					return true;
				}
			}

			++$at;
		}

		return false;
	}

	/**
	 * Parses the next tag.
	 *
	 * This will find and start parsing the next tag, including
	 * the opening `<`, the potential closer `/`, and the tag
	 * name. It does not parse the attributes or scan to the
	 * closing `>`; these are left for other methods.
	 *
	 * @since 6.2.0
	 * @since 6.2.1 Support abruptly-closed comments, invalid-tag-closer-comments, and empty elements.
	 * @ignore
	 *
	 * @return bool Whether a tag was found before the end of the document.
	 */
	private function parse_next_tag(): bool {
		$this->after_tag();

		$html       = $this->html;
		$doc_length = strlen( $html );
		$was_at     = $this->bytes_already_parsed;
		$at         = $was_at;

		while ( $at < $doc_length ) {
			$at = strpos( $html, '<', $at );
			if ( false === $at ) {
				break;
			}

			if ( $at > $was_at ) {
				/*
				 * A "<" normally starts a new HTML tag or syntax token, but in cases where the
				 * following character can't produce a valid token, the "<" is instead treated
				 * as plaintext and the parser should skip over it. This avoids a problem when
				 * following earlier practices of typing emoji with text, e.g. "<3". This
				 * should be a heart, not a tag. It's supposed to be rendered, not hidden.
				 *
				 * At this point the parser checks if this is one of those cases and if it is
				 * will continue searching for the next "<" in search of a token boundary.
				 *
				 * @see https://html.spec.whatwg.org/#tag-open-state
				 */
				if ( 1 !== strspn( $html, '!/?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $at + 1, 1 ) ) {
					++$at;
					continue;
				}

				$this->parser_state         = self::STATE_TEXT_NODE;
				$this->token_starts_at      = $was_at;
				$this->token_length         = $at - $was_at;
				$this->text_starts_at       = $was_at;
				$this->text_length          = $this->token_length;
				$this->bytes_already_parsed = $at;
				return true;
			}

			$this->token_starts_at = $at;

			if ( $at + 1 < $doc_length && '/' === $this->html[ $at + 1 ] ) {
				$this->is_closing_tag = true;
				++$at;
			} else {
				$this->is_closing_tag = false;
			}

			/*
			 * HTML tag names must start with [a-zA-Z] otherwise they are not tags.
			 * For example, "<3" is rendered as text, not a tag opener. If at least
			 * one letter follows the "<" then _it is_ a tag, but if the following
			 * character is anything else it _is not a tag_.
			 *
			 * It's not uncommon to find non-tags starting with `<` in an HTML
			 * document, so it's good for performance to make this pre-check before
			 * continuing to attempt to parse a tag name.
			 *
			 * Reference:
			 * * https://html.spec.whatwg.org/multipage/parsing.html#data-state
			 * * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
			 */
			$tag_name_prefix_length = strspn( $html, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $at + 1 );
			if ( $tag_name_prefix_length > 0 ) {
				++$at;
				$this->parser_state         = self::STATE_MATCHED_TAG;
				$this->tag_name_starts_at   = $at;
				$this->tag_name_length      = $tag_name_prefix_length + strcspn( $html, " \t\f\r\n/>", $at + $tag_name_prefix_length );
				$this->bytes_already_parsed = $at + $this->tag_name_length;
				return true;
			}

			/*
			 * Abort if no tag is found before the end of
			 * the document. There is nothing left to parse.
			 */
			if ( $at + 1 >= $doc_length ) {
				$this->parser_state = self::STATE_INCOMPLETE_INPUT;

				return false;
			}

			/*
			 * `<!` transitions to markup declaration open state
			 * https://html.spec.whatwg.org/multipage/parsing.html#markup-declaration-open-state
			 */
			if ( ! $this->is_closing_tag && '!' === $html[ $at + 1 ] ) {
				/*
				 * `<!--` transitions to a comment state – apply further comment rules.
				 * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
				 */
				if ( 0 === substr_compare( $html, '--', $at + 2, 2 ) ) {
					$closer_at = $at + 4;
					// If it's not possible to close the comment then there is nothing more to scan.
					if ( $doc_length <= $closer_at ) {
						$this->parser_state = self::STATE_INCOMPLETE_INPUT;

						return false;
					}

					// Abruptly-closed empty comments are a sequence of dashes followed by `>`.
					$span_of_dashes = strspn( $html, '-', $closer_at );
					if ( $doc_length <= $span_of_dashes + $closer_at ) {
						$this->parser_state = self::STATE_INCOMPLETE_INPUT;

						return false;
					}

					if ( '>' === $html[ $closer_at + $span_of_dashes ] ) {
						/*
						 * @todo When implementing `set_modifiable_text()` ensure that updates to this token
						 *       don't break the syntax for short comments, e.g. `<!--->`. Unlike other comment
						 *       and bogus comment syntax, these leave no clear insertion point for text and
						 *       they need to be modified specially in order to contain text. E.g. to store
						 *       `?` as the modifiable text, the `<!--->` needs to become `<!--?-->`, which
						 *       involves inserting an additional `-` into the token after the modifiable text.
						 */
						$this->parser_state = self::STATE_COMMENT;
						$this->comment_type = self::COMMENT_AS_ABRUPTLY_CLOSED_COMMENT;
						$this->token_length = $closer_at + $span_of_dashes + 1 - $this->token_starts_at;

						// Only provide modifiable text if the token is long enough to contain it.
						if ( $span_of_dashes >= 2 ) {
							$this->comment_type   = self::COMMENT_AS_HTML_COMMENT;
							$this->text_starts_at = $this->token_starts_at + 4;
							$this->text_length    = $span_of_dashes - 2;
						}

						$this->bytes_already_parsed = $closer_at + $span_of_dashes + 1;
						return true;
					}

					/*
					 * Comments may be closed by either a --> or an invalid --!>.
					 * The first occurrence closes the comment.
					 *
					 * See https://html.spec.whatwg.org/#parse-error-incorrectly-closed-comment
					 */
					--$closer_at; // Pre-increment inside condition below reduces risk of accidental infinite looping.
					while ( ++$closer_at < $doc_length ) {
						$closer_at = strpos( $html, '--', $closer_at );
						if ( false === $closer_at ) {
							$this->parser_state = self::STATE_INCOMPLETE_INPUT;

							return false;
						}

						if ( $closer_at + 2 < $doc_length && '>' === $html[ $closer_at + 2 ] ) {
							$this->parser_state         = self::STATE_COMMENT;
							$this->comment_type         = self::COMMENT_AS_HTML_COMMENT;
							$this->token_length         = $closer_at + 3 - $this->token_starts_at;
							$this->text_starts_at       = $this->token_starts_at + 4;
							$this->text_length          = $closer_at - $this->text_starts_at;
							$this->bytes_already_parsed = $closer_at + 3;
							return true;
						}

						if (
							$closer_at + 3 < $doc_length &&
							'!' === $html[ $closer_at + 2 ] &&
							'>' === $html[ $closer_at + 3 ]
						) {
							$this->parser_state         = self::STATE_COMMENT;
							$this->comment_type         = self::COMMENT_AS_HTML_COMMENT;
							$this->token_length         = $closer_at + 4 - $this->token_starts_at;
							$this->text_starts_at       = $this->token_starts_at + 4;
							$this->text_length          = $closer_at - $this->text_starts_at;
							$this->bytes_already_parsed = $closer_at + 4;
							return true;
						}
					}
				}

				/*
				 * `<!DOCTYPE` transitions to DOCTYPE state – skip to the nearest >
				 * These are ASCII-case-insensitive.
				 * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
				 */
				if (
					$doc_length > $at + 8 &&
					( 'D' === $html[ $at + 2 ] || 'd' === $html[ $at + 2 ] ) &&
					( 'O' === $html[ $at + 3 ] || 'o' === $html[ $at + 3 ] ) &&
					( 'C' === $html[ $at + 4 ] || 'c' === $html[ $at + 4 ] ) &&
					( 'T' === $html[ $at + 5 ] || 't' === $html[ $at + 5 ] ) &&
					( 'Y' === $html[ $at + 6 ] || 'y' === $html[ $at + 6 ] ) &&
					( 'P' === $html[ $at + 7 ] || 'p' === $html[ $at + 7 ] ) &&
					( 'E' === $html[ $at + 8 ] || 'e' === $html[ $at + 8 ] )
				) {
					$closer_at = strpos( $html, '>', $at + 9 );
					if ( false === $closer_at ) {
						$this->parser_state = self::STATE_INCOMPLETE_INPUT;

						return false;
					}

					$this->parser_state         = self::STATE_DOCTYPE;
					$this->token_length         = $closer_at + 1 - $this->token_starts_at;
					$this->text_starts_at       = $this->token_starts_at + 9;
					$this->text_length          = $closer_at - $this->text_starts_at;
					$this->bytes_already_parsed = $closer_at + 1;
					return true;
				}

				if (
					'html' !== $this->parsing_namespace &&
					strlen( $html ) > $at + 8 &&
					'[' === $html[ $at + 2 ] &&
					'C' === $html[ $at + 3 ] &&
					'D' === $html[ $at + 4 ] &&
					'A' === $html[ $at + 5 ] &&
					'T' === $html[ $at + 6 ] &&
					'A' === $html[ $at + 7 ] &&
					'[' === $html[ $at + 8 ]
				) {
					$closer_at = strpos( $html, ']]>', $at + 9 );
					if ( false === $closer_at ) {
						$this->parser_state = self::STATE_INCOMPLETE_INPUT;

						return false;
					}

					$this->parser_state         = self::STATE_CDATA_NODE;
					$this->text_starts_at       = $at + 9;
					$this->text_length          = $closer_at - $this->text_starts_at;
					$this->token_length         = $closer_at + 3 - $this->token_starts_at;
					$this->bytes_already_parsed = $closer_at + 3;
					return true;
				}

				/*
				 * Anything else here is an incorrectly-opened comment and transitions
				 * to the bogus comment state - skip to the nearest >. If no closer is
				 * found then the HTML was truncated inside the markup declaration.
				 */
				$closer_at = strpos( $html, '>', $at + 1 );
				if ( false === $closer_at ) {
					$this->parser_state = self::STATE_INCOMPLETE_INPUT;

					return false;
				}

				$this->parser_state         = self::STATE_COMMENT;
				$this->comment_type         = self::COMMENT_AS_INVALID_HTML;
				$this->token_length         = $closer_at + 1 - $this->token_starts_at;
				$this->text_starts_at       = $this->token_starts_at + 2;
				$this->text_length          = $closer_at - $this->text_starts_at;
				$this->bytes_already_parsed = $closer_at + 1;

				/*
				 * Identify nodes that would be CDATA if HTML had CDATA sections.
				 *
				 * This section must occur after identifying the bogus comment end
				 * because in an HTML parser it will span to the nearest `>`, even
				 * if there's no `]]>` as would be required in an XML document. It
				 * is therefore not possible to parse a CDATA section containing
				 * a `>` in the HTML syntax.
				 *
				 * Inside foreign elements there is a discrepancy between browsers
				 * and the specification on this.
				 *
				 * @todo Track whether the Tag Processor is inside a foreign element
				 *       and require the proper closing `]]>` in those cases.
				 */
				if (
					$this->token_length >= 10 &&
					'[' === $html[ $this->token_starts_at + 2 ] &&
					'C' === $html[ $this->token_starts_at + 3 ] &&
					'D' === $html[ $this->token_starts_at + 4 ] &&
					'A' === $html[ $this->token_starts_at + 5 ] &&
					'T' === $html[ $this->token_starts_at + 6 ] &&
					'A' === $html[ $this->token_starts_at + 7 ] &&
					'[' === $html[ $this->token_starts_at + 8 ] &&
					']' === $html[ $closer_at - 1 ] &&
					']' === $html[ $closer_at - 2 ]
				) {
					$this->parser_state    = self::STATE_COMMENT;
					$this->comment_type    = self::COMMENT_AS_CDATA_LOOKALIKE;
					$this->text_starts_at += 7;
					$this->text_length    -= 9;
				}

				return true;
			}

			/*
			 * </> is a missing end tag name, which is ignored.
			 *
			 * This was also known as the "presumptuous empty tag"
			 * in early discussions as it was proposed to close
			 * the nearest previous opening tag.
			 *
			 * See https://html.spec.whatwg.org/#parse-error-missing-end-tag-name
			 */
			if ( '>' === $html[ $at + 1 ] ) {
				// `<>` is interpreted as plaintext.
				if ( ! $this->is_closing_tag ) {
					++$at;
					continue;
				}

				$this->parser_state         = self::STATE_PRESUMPTUOUS_TAG;
				$this->token_length         = $at + 2 - $this->token_starts_at;
				$this->bytes_already_parsed = $at + 2;
				return true;
			}

			/**
			 * `<?` transitions to the processing instruction open state.
			 *
			 * A processing instruction whose target starts with an ASCII letter or `_`,
			 * continues with ASCII alphanumerics, `-`, or `_`, and is not an ASCII
			 * case-insensitive match for `xml` or `xml-stylesheet` produces a processing
			 * instruction node. Anything else transitions to the bogus comment state.
			 *
			 * Both forms end at the nearest `>`; a processing instruction cannot
			 * contain one in the HTML syntax.
			 *
			 * @link https://html.spec.whatwg.org/multipage/parsing.html#processing-instruction-open-state
			 */
			if ( ! $this->is_closing_tag && '?' === $html[ $at + 1 ] ) {
				$closer_at = strpos( $html, '>', $at + 2 );
				if ( false === $closer_at ) {
					$this->parser_state = self::STATE_INCOMPLETE_INPUT;

					return false;
				}

				$target_at     = $at + 2;
				$target_length = 0;
				$first_char    = $html[ $target_at ];
				if (
					( 'a' <= $first_char && 'z' >= $first_char ) ||
					( 'A' <= $first_char && 'Z' >= $first_char ) ||
					'_' === $first_char
				) {
					$target_length = 1 + strspn( $html, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-', $target_at + 1 );
				}

				/*
				 * In the processing instruction target state, only whitespace, `?`,
				 * or `>` may terminate the target; any other character converts the
				 * token into a bogus comment.
				 *
				 * The `xml` and `xml-stylesheet` targets are reserved and disallowed;
				 * they also convert the token into a bogus comment.
				 */
				$is_valid_pi = (
					0 !== $target_length &&
					false !== strpos( " \t\f\r\n?>", $html[ $target_at + $target_length ] ) &&
					! ( 3 === $target_length && 0 === substr_compare( $html, 'xml', $target_at, 3, true ) ) &&
					! ( 14 === $target_length && 0 === substr_compare( $html, 'xml-stylesheet', $target_at, 14, true ) )
				);

				if ( $is_valid_pi ) {
					/*
					 * The processing instruction data starts after any whitespace
					 * following the target and ends at the `>`. When the token is
					 * closed by `?>`, that final `?` is not part of the data.
					 */
					$data_at  = $target_at + $target_length;
					$data_at += strspn( $html, " \t\f\r\n", $data_at );

					$data_length = $closer_at - $data_at;
					if ( $data_length > 0 && '?' === $html[ $closer_at - 1 ] ) {
						--$data_length;
					}

					$this->parser_state         = self::STATE_PROCESSING_INSTRUCTION;
					$this->tag_name_starts_at   = $target_at;
					$this->tag_name_length      = $target_length;
					$this->token_length         = $closer_at + 1 - $this->token_starts_at;
					$this->text_starts_at       = $data_at;
					$this->text_length          = $data_length;
					$this->bytes_already_parsed = $closer_at + 1;
					return true;
				}

				$this->parser_state         = self::STATE_COMMENT;
				$this->comment_type         = self::COMMENT_AS_INVALID_HTML;
				$this->token_length         = $closer_at + 1 - $this->token_starts_at;
				$this->text_starts_at       = $this->token_starts_at + 2;
				$this->text_length          = $closer_at - $this->text_starts_at;
				$this->bytes_already_parsed = $closer_at + 1;

				/*
				 * Identify an XML-like Processing Instruction node.
				 *
				 * HTML and XML processing instructions have different parsing rules.
				 * The HTML API recognizes XML-like processing instructions that are
				 * _not_ HTML processing instructions. The HTML standard transforms
				 * them to "bogus comments," represented by the HTML API as comments
				 * with the `COMMENT_AS_PI_NODE_LOOKALIKE` type. This includes the
				 * special targets `xml` and `xml-stylesheet` which are reserved
				 * targets not allowed in HTML processing instructions.
				 *
				 * > NameStartChar ::= ":" | [A-Z] | "_" | [a-z] | [#xC0-#xD6] | [#xD8-#xF6] | [#xF8-#x2FF] |
				 *                     [#x370-#x37D] | [#x37F-#x1FFF] | [#x200C-#x200D] | [#x2070-#x218F] |
				 *                     [#x2C00-#x2FEF] | [#x3001-#xD7FF] | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] |
				 *                     [#x10000-#xEFFFF]
				 * > NameChar      ::= NameStartChar | "-" | "." | [0-9] | #xB7 | [#x0300-#x036F] | [#x203F-#x2040]
				 *
				 * @see https://www.w3.org/TR/2006/REC-xml11-20060816/#NT-PITarget
				 */
				if ( $this->token_length >= 5 && '?' === $html[ $closer_at - 1 ] ) {
					$comment_text     = substr( $html, $this->token_starts_at + 2, $this->token_length - 4 );
					$pi_target_length = strspn( $comment_text, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ:_' );

					if ( 0 < $pi_target_length ) {
						$pi_target_length += strspn( $comment_text, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789:_-.', $pi_target_length );

						$this->comment_type       = self::COMMENT_AS_PI_NODE_LOOKALIKE;
						$this->tag_name_starts_at = $this->token_starts_at + 2;
						$this->tag_name_length    = $pi_target_length;
						$this->text_starts_at    += $pi_target_length;
						$this->text_length       -= $pi_target_length + 1;
					}
				}

				return true;
			}

			/*
			 * If a non-alpha starts the tag name in a tag closer it's a comment.
			 * Find the first `>`, which closes the comment.
			 *
			 * This parser classifies these particular comments as special "funky comments"
			 * which are made available for further processing.
			 *
			 * See https://html.spec.whatwg.org/#parse-error-invalid-first-character-of-tag-name
			 */
			if ( $this->is_closing_tag ) {
				// No chance of finding a closer.
				if ( $at + 3 > $doc_length ) {
					$this->parser_state = self::STATE_INCOMPLETE_INPUT;

					return false;
				}

				$closer_at = strpos( $html, '>', $at + 2 );
				if ( false === $closer_at ) {
					$this->parser_state = self::STATE_INCOMPLETE_INPUT;

					return false;
				}

				$this->parser_state         = self::STATE_FUNKY_COMMENT;
				$this->token_length         = $closer_at + 1 - $this->token_starts_at;
				$this->text_starts_at       = $this->token_starts_at + 2;
				$this->text_length          = $closer_at - $this->text_starts_at;
				$this->bytes_already_parsed = $closer_at + 1;
				return true;
			}

			++$at;
		}

		/*
		 * This does not imply an incomplete parse; it indicates that there
		 * can be nothing left in the document other than a #text node.
		 */
		$this->parser_state         = self::STATE_TEXT_NODE;
		$this->token_starts_at      = $was_at;
		$this->token_length         = $doc_length - $was_at;
		$this->text_starts_at       = $was_at;
		$this->text_length          = $this->token_length;
		$this->bytes_already_parsed = $doc_length;
		return true;
	}

	/**
	 * Parses the next attribute.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @return bool Whether an attribute was found before the end of the document.
	 */
	private function parse_next_attribute(): bool {
		$doc_length = strlen( $this->html );

		// Skip whitespace and slashes.
		$skipped_length              = strspn( $this->html, " \t\f\r\n/", $this->bytes_already_parsed );
		$this->bytes_already_parsed += $skipped_length;
		if ( $this->bytes_already_parsed >= $doc_length ) {
			$this->parser_state = self::STATE_INCOMPLETE_INPUT;

			return false;
		}

		/**
		 * This block serves two purposes:
		 *
		 * - A fast path for common tag-ending `>`.
		 * - A check for the self-closing flag which must appear as `/>`.
		 *
		 * In a tag like `<g attr=/>`, `/` is the attribute value, not a self-closing
		 * flag. When it appears in this form, the parser has already consumed the
		 * attribute value, `$skipped_length` is 0, and this checks below correctly
		 * identify whether there is a self-closing flag.
		 *
		 * Note: Both start and end tags may have the self-closing flag.
		 */
		if ( '>' === $this->html[ $this->bytes_already_parsed ] ) {
			if ( $skipped_length > 0 && '/' === $this->html[ $this->bytes_already_parsed - 1 ] ) {
				$this->has_self_closing_flag = true;
			}
			return false;
		}

		/*
		 * Treat the equal sign as a part of the attribute
		 * name if it is the first encountered byte.
		 *
		 * @see https://html.spec.whatwg.org/multipage/parsing.html#before-attribute-name-state
		 */
		$name_length = '=' === $this->html[ $this->bytes_already_parsed ]
			? 1 + strcspn( $this->html, "=/> \t\f\r\n", $this->bytes_already_parsed + 1 )
			: strcspn( $this->html, "=/> \t\f\r\n", $this->bytes_already_parsed );

		// No attribute, just tag closer.
		if ( 0 === $name_length || $this->bytes_already_parsed + $name_length >= $doc_length ) {
			return false;
		}

		$attribute_start             = $this->bytes_already_parsed;
		$attribute_name              = substr( $this->html, $attribute_start, $name_length );
		$this->bytes_already_parsed += $name_length;
		if ( $this->bytes_already_parsed >= $doc_length ) {
			$this->parser_state = self::STATE_INCOMPLETE_INPUT;

			return false;
		}

		$this->skip_whitespace();
		if ( $this->bytes_already_parsed >= $doc_length ) {
			$this->parser_state = self::STATE_INCOMPLETE_INPUT;

			return false;
		}

		$has_value = '=' === $this->html[ $this->bytes_already_parsed ];
		if ( $has_value ) {
			++$this->bytes_already_parsed;
			$this->skip_whitespace();
			if ( $this->bytes_already_parsed >= $doc_length ) {
				$this->parser_state = self::STATE_INCOMPLETE_INPUT;

				return false;
			}

			switch ( $this->html[ $this->bytes_already_parsed ] ) {
				case "'":
				case '"':
					$quote                      = $this->html[ $this->bytes_already_parsed ];
					$value_start                = $this->bytes_already_parsed + 1;
					$end_quote_at               = strpos( $this->html, $quote, $value_start );
					$end_quote_at               = false === $end_quote_at ? $doc_length : $end_quote_at;
					$value_length               = $end_quote_at - $value_start;
					$attribute_end              = $end_quote_at + 1;
					$this->bytes_already_parsed = $attribute_end;
					break;

				default:
					$value_start                = $this->bytes_already_parsed;
					$value_length               = strcspn( $this->html, "> \t\f\r\n", $value_start );
					$attribute_end              = $value_start + $value_length;
					$this->bytes_already_parsed = $attribute_end;
			}
		} else {
			$value_start   = $this->bytes_already_parsed;
			$value_length  = 0;
			$attribute_end = $attribute_start + $name_length;
		}

		if ( $attribute_end >= $doc_length ) {
			$this->parser_state = self::STATE_INCOMPLETE_INPUT;

			return false;
		}

		if ( $this->is_closing_tag ) {
			return true;
		}

		/*
		 * > There must never be two or more attributes on
		 * > the same start tag whose names are an ASCII
		 * > case-insensitive match for each other.
		 *     - HTML 5 spec
		 *
		 * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
		 *
		 * The tokenizer replaces U+0000 NULL bytes in
		 * attribute names with U+FFFD.
		 *
		 * @see https://html.spec.whatwg.org/#attribute-name-state
		 */
		$comparable_name = strtolower( str_replace( "\x00", "\u{FFFD}", $attribute_name ) );

		// If an attribute is listed many times, only use the first declaration and ignore the rest.
		if ( ! isset( $this->attributes[ $comparable_name ] ) ) {
			$this->attributes[ $comparable_name ] = new WP_HTML_Attribute_Token(
				$attribute_name,
				$value_start,
				$value_length,
				$attribute_start,
				$attribute_end - $attribute_start,
				! $has_value
			);

			return true;
		}

		/*
		 * Track the duplicate attributes so if we remove it, all disappear together.
		 *
		 * While `$this->duplicated_attributes` could always be stored as an `array()`,
		 * which would simplify the logic here, storing a `null` and only allocating
		 * an array when encountering duplicates avoids needless allocations in the
		 * normative case of parsing tags with no duplicate attributes.
		 */
		$duplicate_span = new WP_HTML_Span( $attribute_start, $attribute_end - $attribute_start );
		if ( null === $this->duplicate_attributes ) {
			$this->duplicate_attributes = array( $comparable_name => array( $duplicate_span ) );
		} elseif ( ! isset( $this->duplicate_attributes[ $comparable_name ] ) ) {
			$this->duplicate_attributes[ $comparable_name ] = array( $duplicate_span );
		} else {
			$this->duplicate_attributes[ $comparable_name ][] = $duplicate_span;
		}

		return true;
	}

	/**
	 * Move the internal cursor past any immediate successive whitespace.
	 *
	 * @since 6.2.0
	 * @ignore
	 */
	private function skip_whitespace() {
		$this->bytes_already_parsed += strspn( $this->html, " \t\f\r\n", $this->bytes_already_parsed );
	}

	/**
	 * Applies attribute updates and cleans up once a tag is fully parsed.
	 *
	 * @since 6.2.0
	 * @ignore
	 */
	private function after_tag() {
		/*
		 * There could be lexical updates enqueued for an attribute that
		 * also exists on the next tag. In order to avoid conflating the
		 * attributes across the two tags, lexical updates with names
		 * need to be flushed to raw lexical updates.
		 */
		$this->class_name_updates_to_attributes_updates();

		/*
		 * Purge updates if there are too many. The actual count isn't
		 * scientific, but a few values from 100 to a few thousand were
		 * tests to find a practically-useful limit.
		 *
		 * If the update queue grows too big, then the Tag Processor
		 * will spend more time iterating through them and lose the
		 * efficiency gains of deferring applying them.
		 */
		if ( 1000 < count( $this->lexical_updates ) ) {
			$this->get_updated_html();
		}

		foreach ( $this->lexical_updates as $name => $update ) {
			/*
			 * Any updates appearing after the cursor should be applied
			 * before proceeding, otherwise they may be overlooked.
			 */
			if ( $update->start >= $this->bytes_already_parsed ) {
				$this->get_updated_html();
				break;
			}

			if ( is_int( $name ) ) {
				continue;
			}

			$this->lexical_updates[] = $update;
			unset( $this->lexical_updates[ $name ] );
		}

		$this->token_starts_at          = null;
		$this->token_length             = null;
		$this->has_self_closing_flag    = false;
		$this->tag_name_starts_at       = null;
		$this->tag_name_length          = null;
		$this->text_starts_at           = 0;
		$this->text_length              = 0;
		$this->is_closing_tag           = null;
		$this->attributes               = array();
		$this->comment_type             = null;
		$this->text_node_classification = self::TEXT_IS_GENERIC;
		$this->duplicate_attributes     = null;
	}

	/**
	 * Converts class name updates into tag attributes updates
	 * (they are accumulated in different data formats for performance).
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @see WP_HTML_Tag_Processor::$lexical_updates
	 * @see WP_HTML_Tag_Processor::$classname_updates
	 */
	private function class_name_updates_to_attributes_updates() {
		if ( count( $this->classname_updates ) === 0 ) {
			return;
		}

		$existing_class = $this->get_enqueued_attribute_value( 'class' );
		if ( null === $existing_class || true === $existing_class ) {
			$existing_class = '';
		}

		if ( false === $existing_class && isset( $this->attributes['class'] ) ) {
			$existing_class = $this->get_decoded_attribute_value( $this->attributes['class'] );
		}

		if ( false === $existing_class ) {
			$existing_class = '';
		}

		/**
		 * Updated "class" attribute value.
		 *
		 * This is incrementally built while scanning through the existing class
		 * attribute, skipping removed classes on the way, and then appending
		 * added classes at the end. Only when finished processing will the
		 * value contain the final new value.

		 * @var string $class
		 */
		$class = '';

		/**
		 * Tracks the cursor position in the existing
		 * class attribute value while parsing.
		 *
		 * @var int $at
		 */
		$at = 0;

		/**
		 * Indicates if there's any need to modify the existing class attribute.
		 *
		 * If a call to `add_class()` and `remove_class()` wouldn't impact
		 * the `class` attribute value then there's no need to rebuild it.
		 * For example, when adding a class that's already present or
		 * removing one that isn't.
		 *
		 * This flag enables a performance optimization when none of the enqueued
		 * class updates would impact the `class` attribute; namely, that the
		 * processor can continue without modifying the input document, as if
		 * none of the `add_class()` or `remove_class()` calls had been made.
		 *
		 * This flag is set upon the first change that requires a string update.
		 *
		 * @var bool $modified
		 */
		$modified = false;

		$seen      = array();
		$to_remove = array();
		$is_quirks = self::QUIRKS_MODE === $this->compat_mode;
		if ( $is_quirks ) {
			foreach ( $this->classname_updates as $updated_name => $action ) {
				if ( self::REMOVE_CLASS === $action ) {
					$to_remove[] = strtolower( $updated_name );
				}
			}
		} else {
			foreach ( $this->classname_updates as $updated_name => $action ) {
				if ( self::REMOVE_CLASS === $action ) {
					$to_remove[] = $updated_name;
				}
			}
		}

		// Remove unwanted classes by only copying the new ones.
		$existing_class_length = strlen( $existing_class );
		while ( $at < $existing_class_length ) {
			// Skip to the first non-whitespace character.
			$ws_at     = $at;
			$ws_length = strspn( $existing_class, " \t\f\r\n", $ws_at );
			$at       += $ws_length;

			// Capture the class name – it's everything until the next whitespace.
			$name_length = strcspn( $existing_class, " \t\f\r\n", $at );
			if ( 0 === $name_length ) {
				// If no more class names are found then that's the end.
				break;
			}

			$name                  = substr( $existing_class, $at, $name_length );
			$comparable_class_name = $is_quirks ? strtolower( $name ) : $name;
			$at                   += $name_length;

			// If this class is marked for removal, remove it and move on to the next one.
			if ( in_array( $comparable_class_name, $to_remove, true ) ) {
				$modified = true;
				continue;
			}

			// If a class has already been seen then skip it; it should not be added twice.
			if ( in_array( $comparable_class_name, $seen, true ) ) {
				continue;
			}

			$seen[] = $comparable_class_name;

			/*
			 * Otherwise, append it to the new "class" attribute value.
			 *
			 * There are options for handling whitespace between tags.
			 * Preserving the existing whitespace produces fewer changes
			 * to the HTML content and should clarify the before/after
			 * content when debugging the modified output.
			 *
			 * This approach contrasts normalizing the inter-class
			 * whitespace to a single space, which might appear cleaner
			 * in the output HTML but produce a noisier change.
			 */
			if ( '' !== $class ) {
				$class .= substr( $existing_class, $ws_at, $ws_length );
			}
			$class .= $name;
		}

		// Add new classes by appending those which haven't already been seen.
		foreach ( $this->classname_updates as $name => $operation ) {
			$comparable_name = $is_quirks ? strtolower( $name ) : $name;
			if ( self::ADD_CLASS === $operation && ! in_array( $comparable_name, $seen, true ) ) {
				$modified = true;

				$class .= strlen( $class ) > 0 ? ' ' : '';
				$class .= $name;
			}
		}

		$this->classname_updates = array();
		if ( ! $modified ) {
			return;
		}

		if ( strlen( $class ) > 0 ) {
			$this->set_attribute( 'class', $class );
		} else {
			$this->remove_attribute( 'class' );
		}
	}

	/**
	 * Applies attribute updates to HTML document.
	 *
	 * @since 6.2.0
	 * @since 6.2.1 Accumulates shift for internal cursor and passed pointer.
	 * @since 6.3.0 Invalidate any bookmarks whose targets are overwritten.
	 * @ignore
	 *
	 * @param int $shift_this_point Accumulate and return shift for this position.
	 * @return int How many bytes the given pointer moved in response to the updates.
	 */
	private function apply_attributes_updates( int $shift_this_point ): int {
		if ( ! count( $this->lexical_updates ) ) {
			return 0;
		}

		$accumulated_shift_for_given_point = 0;

		/*
		 * Attribute updates can be enqueued in any order but updates
		 * to the document must occur in lexical order; that is, each
		 * replacement must be made before all others which follow it
		 * at later string indices in the input document.
		 *
		 * Sorting avoids making out-of-order replacements which
		 * can lead to mangled output, partially-duplicated
		 * attributes, and overwritten attributes.
		 */
		usort( $this->lexical_updates, array( self::class, 'sort_start_ascending' ) );

		$bytes_already_copied = 0;
		$output_buffer        = '';
		foreach ( $this->lexical_updates as $diff ) {
			$shift = strlen( $diff->text ) - $diff->length;

			// Adjust the cursor position by however much an update affects it.
			if ( $diff->start < $this->bytes_already_parsed ) {
				$this->bytes_already_parsed += $shift;
			}

			// Accumulate shift of the given pointer within this function call.
			if ( $diff->start < $shift_this_point ) {
				$accumulated_shift_for_given_point += $shift;
			}

			$output_buffer       .= substr( $this->html, $bytes_already_copied, $diff->start - $bytes_already_copied );
			$output_buffer       .= $diff->text;
			$bytes_already_copied = $diff->start + $diff->length;
		}

		$this->html = $output_buffer . substr( $this->html, $bytes_already_copied );

		/*
		 * Adjust bookmark locations to account for how the text
		 * replacements adjust offsets in the input document.
		 */
		foreach ( $this->bookmarks as $bookmark_name => $bookmark ) {
			$bookmark_end = $bookmark->start + $bookmark->length;

			/*
			 * Each lexical update which appears before the bookmark's endpoints
			 * might shift the offsets for those endpoints. Loop through each change
			 * and accumulate the total shift for each bookmark, then apply that
			 * shift after tallying the full delta.
			 */
			$head_delta = 0;
			$tail_delta = 0;

			foreach ( $this->lexical_updates as $diff ) {
				$diff_end = $diff->start + $diff->length;

				if ( $bookmark->start < $diff->start && $bookmark_end < $diff->start ) {
					break;
				}

				if ( $bookmark->start >= $diff->start && $bookmark_end < $diff_end ) {
					$this->release_bookmark( $bookmark_name );
					continue 2;
				}

				$delta = strlen( $diff->text ) - $diff->length;

				if ( $bookmark->start >= $diff->start ) {
					$head_delta += $delta;
				}

				if ( $bookmark_end >= $diff_end ) {
					$tail_delta += $delta;
				}
			}

			$bookmark->start  += $head_delta;
			$bookmark->length += $tail_delta - $head_delta;
		}

		$this->lexical_updates = array();

		return $accumulated_shift_for_given_point;
	}

	/**
	 * Checks whether a bookmark with the given name exists.
	 *
	 * @since 6.3.0
	 *
	 * @param string $bookmark_name Name to identify a bookmark that potentially exists.
	 * @return bool Whether that bookmark exists.
	 */
	public function has_bookmark( $bookmark_name ): bool {
		return array_key_exists( $bookmark_name, $this->bookmarks );
	}

	/**
	 * Move the internal cursor in the Tag Processor to a given bookmark's location.
	 *
	 * In order to prevent accidental infinite loops, there's a
	 * maximum limit on the number of times seek() can be called.
	 *
	 * @since 6.2.0
	 *
	 * @param string $bookmark_name Jump to the place in the document identified by this bookmark name.
	 * @return bool Whether the internal cursor was successfully moved to the bookmark's location.
	 */
	public function seek( $bookmark_name ): bool {
		if ( ! array_key_exists( $bookmark_name, $this->bookmarks ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'Unknown bookmark name.' ),
				'6.2.0'
			);
			return false;
		}

		$existing_bookmark = $this->bookmarks[ $bookmark_name ];

		if (
			$this->token_starts_at === $existing_bookmark->start &&
			$this->token_length === $existing_bookmark->length
		) {
			return true;
		}

		if ( ++$this->seek_count > static::MAX_SEEK_OPS ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'Too many calls to seek() - this can lead to performance issues.' ),
				'6.2.0'
			);
			return false;
		}

		// Flush out any pending updates to the document.
		$this->get_updated_html();

		// Point this tag processor before the sought tag opener and consume it.
		$this->bytes_already_parsed = $this->bookmarks[ $bookmark_name ]->start;
		$this->parser_state         = self::STATE_READY;
		return $this->next_token();
	}

	/**
	 * Compare two WP_HTML_Text_Replacement objects.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @param WP_HTML_Text_Replacement $a First attribute update.
	 * @param WP_HTML_Text_Replacement $b Second attribute update.
	 * @return int Comparison value for string order.
	 */
	private static function sort_start_ascending( WP_HTML_Text_Replacement $a, WP_HTML_Text_Replacement $b ): int {
		$by_start = $a->start - $b->start;
		if ( 0 !== $by_start ) {
			return $by_start;
		}

		$by_text = isset( $a->text, $b->text ) ? strcmp( $a->text, $b->text ) : 0;
		if ( 0 !== $by_text ) {
			return $by_text;
		}

		/*
		 * This code should be unreachable, because it implies the two replacements
		 * start at the same location and contain the same text.
		 */
		return $a->length - $b->length;
	}

	/**
	 * Return the enqueued value for a given attribute, if one exists.
	 *
	 * Enqueued updates can take different data types:
	 *  - If an update is enqueued and is boolean, the return will be `true`
	 *  - If an update is otherwise enqueued, the return will be the string value of that update.
	 *  - If an attribute is enqueued to be removed, the return will be `null` to indicate that.
	 *  - If no updates are enqueued, the return will be `false` to differentiate from "removed."
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @param string $comparable_name The attribute name in its comparable form.
	 * @return string|boolean|null Value of enqueued update if present, otherwise false.
	 */
	private function get_enqueued_attribute_value( string $comparable_name ) {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return false;
		}

		if ( ! isset( $this->lexical_updates[ $comparable_name ] ) ) {
			return false;
		}

		$enqueued_text = $this->lexical_updates[ $comparable_name ]->text;

		// Removed attributes erase the entire span.
		if ( '' === $enqueued_text ) {
			return null;
		}

		/*
		 * Boolean attribute updates are just the attribute name without a corresponding value.
		 *
		 * This value might differ from the given comparable name in that there could be leading
		 * or trailing whitespace, and that the casing follows the name given in `set_attribute`.
		 *
		 * Example:
		 *
		 *     $p->set_attribute( 'data-TEST-id', 'update' );
		 *     'update' === $p->get_enqueued_attribute_value( 'data-test-id' );
		 *
		 * Detect this difference based on the absence of the `=`, which _must_ exist in any
		 * attribute containing a value, e.g. `<input type="text" enabled />`.
		 *                                            ¹           ²
		 *                                       1. Attribute with a string value.
		 *                                       2. Boolean attribute whose value is `true`.
		 */
		$equals_at = strpos( $enqueued_text, '=' );
		if ( false === $equals_at ) {
			return true;
		}

		/*
		 * Finally, a normal update's value will appear after the `=` and
		 * be double-quoted, as performed incidentally by `set_attribute`.
		 *
		 * e.g. `type="text"`
		 *           ¹²    ³
		 *        1. Equals is here.
		 *        2. Double-quoting starts one after the equals sign.
		 *        3. Double-quoting ends at the last character in the update.
		 */
		$enqueued_value = substr( $enqueued_text, $equals_at + 2, -1 );
		return WP_HTML_Decoder::decode_attribute( $enqueued_value );
	}

	/**
	 * Returns the value of a requested attribute from a matched tag opener if that attribute exists.
	 *
	 * Example:
	 *
	 *     $p = new WP_HTML_Tag_Processor( '<div enabled class="test" data-test-id="14">Test</div>' );
	 *     $p->next_tag( array( 'class_name' => 'test' ) ) === true;
	 *     $p->get_attribute( 'data-test-id' ) === '14';
	 *     $p->get_attribute( 'enabled' ) === true;
	 *     $p->get_attribute( 'aria-label' ) === null;
	 *
	 *     $p->next_tag() === false;
	 *     $p->get_attribute( 'class' ) === null;
	 *
	 * @since 6.2.0
	 *
	 * @param string $name Name of attribute whose value is requested.
	 * @return string|true|null Value of attribute or `null` if not available. Boolean attributes return `true`.
	 */
	public function get_attribute( $name ) {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return null;
		}

		$comparable = strtolower( $name );

		/*
		 * For every attribute other than `class` it's possible to perform a quick check if
		 * there's an enqueued lexical update whose value takes priority over what's found in
		 * the input document.
		 *
		 * The `class` attribute is special though because of the exposed helpers `add_class`
		 * and `remove_class`. These form a builder for the `class` attribute, so an additional
		 * check for enqueued class changes is required in addition to the check for any enqueued
		 * attribute values. If any exist, those enqueued class changes must first be flushed out
		 * into an attribute value update.
		 */
		if ( 'class' === $comparable ) {
			$this->class_name_updates_to_attributes_updates();
		}

		// Return any enqueued attribute value updates if they exist.
		$enqueued_value = $this->get_enqueued_attribute_value( $comparable );
		if ( false !== $enqueued_value ) {
			return $enqueued_value;
		}

		if ( ! isset( $this->attributes[ $comparable ] ) ) {
			return null;
		}

		$attribute = $this->attributes[ $comparable ];

		/*
		 * This flag distinguishes an attribute with no value
		 * from an attribute with an empty string value. For
		 * unquoted attributes this could look very similar.
		 * It refers to whether an `=` follows the name.
		 *
		 * e.g. <div boolean-attribute empty-attribute=></div>
		 *           ¹                 ²
		 *        1. Attribute `boolean-attribute` is `true`.
		 *        2. Attribute `empty-attribute` is `""`.
		 */
		if ( true === $attribute->is_true ) {
			return true;
		}

		return $this->get_decoded_attribute_value( $attribute );
	}

	/**
	 * Decode an attribute value from source.
	 *
	 * This method applies the following transformations that the processor defers:
	 *   - Normalize newlines  (input stream preprocessing)
	 *   - Replace NULL bytes (tokenization)
	 *   - Decode character references (tokenization)
	 *
	 * @since 7.1.0
	 * @ignore
	 *
	 * @param WP_HTML_Attribute_Token $attribute Attribute token from the input document.
	 * @return string Decoded attribute value.
	 */
	private function get_decoded_attribute_value( WP_HTML_Attribute_Token $attribute ): string {
		$raw_value = substr( $this->html, $attribute->value_starts_at, $attribute->value_length );
		$raw_value = str_replace( "\r\n", "\n", $raw_value );
		$raw_value = str_replace( "\r", "\n", $raw_value );
		$raw_value = str_replace( "\x00", "\u{FFFD}", $raw_value );
		return WP_HTML_Decoder::decode_attribute( $raw_value );
	}

	/**
	 * Gets lowercase names of all attributes matching a given prefix in the current tag.
	 *
	 * Note that matching is case-insensitive. This is in accordance with the spec:
	 *
	 * > There must never be two or more attributes on
	 * > the same start tag whose names are an ASCII
	 * > case-insensitive match for each other.
	 *     - HTML 5 spec
	 *
	 * Example:
	 *
	 *     $p = new WP_HTML_Tag_Processor( '<div data-ENABLED class="test" DATA-test-id="14">Test</div>' );
	 *     $p->next_tag( array( 'class_name' => 'test' ) ) === true;
	 *     $p->get_attribute_names_with_prefix( 'data-' ) === array( 'data-enabled', 'data-test-id' );
	 *
	 *     $p->next_tag() === false;
	 *     $p->get_attribute_names_with_prefix( 'data-' ) === null;
	 *
	 * @since 6.2.0
	 *
	 * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
	 *
	 * @param string $prefix Prefix of requested attribute names.
	 * @return array|null List of attribute names, or `null` when no tag opener is matched.
	 * @phpstan-return list<non-empty-string>|null
	 */
	public function get_attribute_names_with_prefix( $prefix ) {
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			$this->is_closing_tag
		) {
			return null;
		}

		$comparable = strtolower( $prefix );

		/*
		 * For the `class` attribute, ensure that enqueued class changes from
		 * `add_class` and `remove_class` are flushed into attribute updates.
		 */
		$has_class = isset( $this->attributes['class'] );
		if ( '' === $comparable || str_starts_with( 'class', $comparable ) ) {
			foreach ( $this->classname_updates as $update ) {
				if (
					( $has_class && self::REMOVE_CLASS === $update ) ||
					( ! $has_class && self::ADD_CLASS === $update )
				) {
					$this->class_name_updates_to_attributes_updates();
					break;
				}
			}
		}

		$additions = array();
		$removals  = array();
		foreach ( $this->lexical_updates as $update_name => $update ) {
			if ( is_int( $update_name ) || 'modifiable text' === $update_name ) {
				continue;
			}

			if ( '' === $update->text ) {
				$removals[ $update_name ] = true;
			} elseif ( ! isset( $this->attributes[ $update_name ] ) && str_starts_with( $update_name, $comparable ) ) {
				$additions[] = $update_name;
			}
		}

		$matches = array();
		foreach ( array_keys( $this->attributes ) as $attr_name ) {
			if ( str_starts_with( $attr_name, $comparable ) && ! isset( $removals[ $attr_name ] ) ) {
				$matches[] = $attr_name;
			}
		}

		return empty( $additions ) ? $matches : array_merge( $additions, $matches );
	}

	/**
	 * Returns the namespace of the matched token.
	 *
	 * @since 6.7.0
	 *
	 * @return string One of 'html', 'math', or 'svg'.
	 */
	public function get_namespace(): string {
		return $this->parsing_namespace;
	}

	/**
	 * Returns the uppercase name of the matched tag.
	 *
	 * Example:
	 *
	 *     $p = new WP_HTML_Tag_Processor( '<div class="test">Test</div>' );
	 *     $p->next_tag() === true;
	 *     $p->get_tag() === 'DIV';
	 *
	 *     $p->next_tag() === false;
	 *     $p->get_tag() === null;
	 *
	 * @since 6.2.0
	 *
	 * @return string|null Name of currently matched tag in input HTML, or `null` if none found.
	 */
	public function get_tag() {
		if ( null === $this->tag_name_starts_at ) {
			return null;
		}

		$tag_name = str_replace( "\x00", "\u{FFFD}", substr( $this->html, $this->tag_name_starts_at, $this->tag_name_length ) );

		if ( self::STATE_MATCHED_TAG === $this->parser_state ) {
			return strtoupper( $tag_name );
		}

		/*
		 * Processing instruction targets are case-sensitive
		 * and returned as they appear in the input HTML.
		 */
		if ( self::STATE_PROCESSING_INSTRUCTION === $this->parser_state ) {
			return $tag_name;
		}

		if (
			self::STATE_COMMENT === $this->parser_state &&
			self::COMMENT_AS_PI_NODE_LOOKALIKE === $this->get_comment_type()
		) {
			return $tag_name;
		}

		return null;
	}

	/**
	 * Returns the adjusted tag name for a given token, taking into
	 * account the current parsing context, whether HTML, SVG, or MathML.
	 *
	 * @since 6.7.0
	 *
	 * @return string|null Name of current tag name.
	 */
	public function get_qualified_tag_name() {
		$tag_name = $this->get_tag();
		if ( null === $tag_name ) {
			return null;
		}

		if ( 'html' === $this->get_namespace() ) {
			return $tag_name;
		}

		$lower_tag_name = strtolower( $tag_name );
		if ( 'math' === $this->get_namespace() ) {
			return $lower_tag_name;
		}

		if ( 'svg' === $this->get_namespace() ) {
			switch ( $lower_tag_name ) {
				case 'altglyph':
					return 'altGlyph';

				case 'altglyphdef':
					return 'altGlyphDef';

				case 'altglyphitem':
					return 'altGlyphItem';

				case 'animatecolor':
					return 'animateColor';

				case 'animatemotion':
					return 'animateMotion';

				case 'animatetransform':
					return 'animateTransform';

				case 'clippath':
					return 'clipPath';

				case 'feblend':
					return 'feBlend';

				case 'fecolormatrix':
					return 'feColorMatrix';

				case 'fecomponenttransfer':
					return 'feComponentTransfer';

				case 'fecomposite':
					return 'feComposite';

				case 'feconvolvematrix':
					return 'feConvolveMatrix';

				case 'fediffuselighting':
					return 'feDiffuseLighting';

				case 'fedisplacementmap':
					return 'feDisplacementMap';

				case 'fedistantlight':
					return 'feDistantLight';

				case 'fedropshadow':
					return 'feDropShadow';

				case 'feflood':
					return 'feFlood';

				case 'fefunca':
					return 'feFuncA';

				case 'fefuncb':
					return 'feFuncB';

				case 'fefuncg':
					return 'feFuncG';

				case 'fefuncr':
					return 'feFuncR';

				case 'fegaussianblur':
					return 'feGaussianBlur';

				case 'feimage':
					return 'feImage';

				case 'femerge':
					return 'feMerge';

				case 'femergenode':
					return 'feMergeNode';

				case 'femorphology':
					return 'feMorphology';

				case 'feoffset':
					return 'feOffset';

				case 'fepointlight':
					return 'fePointLight';

				case 'fespecularlighting':
					return 'feSpecularLighting';

				case 'fespotlight':
					return 'feSpotLight';

				case 'fetile':
					return 'feTile';

				case 'feturbulence':
					return 'feTurbulence';

				case 'foreignobject':
					return 'foreignObject';

				case 'glyphref':
					return 'glyphRef';

				case 'lineargradient':
					return 'linearGradient';

				case 'radialgradient':
					return 'radialGradient';

				case 'textpath':
					return 'textPath';

				default:
					return $lower_tag_name;
			}
		}

		// This unnecessary return prevents tools from inaccurately reporting type errors.
		return $tag_name;
	}

	/**
	 * Returns the adjusted attribute name for a given attribute, taking into
	 * account the current parsing context, whether HTML, SVG, or MathML.
	 *
	 * In SVG and MathML contexts, adjusted foreign attributes with a namespace
	 * prefix use a space between the prefix and local name. For example,
	 * `xlink:href` is returned as `xlink href`, while the unprefixed `xmlns`
	 * attribute is returned as `xmlns`. Non-adjusted attributes with a colon in
	 * their name, such as `foo:bar`, are returned unchanged.
	 *
	 * @since 6.7.0
	 *
	 * @param string $attribute_name Which attribute to adjust.
	 *
	 * @return string|null
	 */
	public function get_qualified_attribute_name( $attribute_name ) {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return null;
		}

		$namespace  = $this->get_namespace();
		$lower_name = strtolower( $attribute_name );

		if ( 'math' === $namespace && 'definitionurl' === $lower_name ) {
			return 'definitionURL';
		}

		if ( 'svg' === $this->get_namespace() ) {
			switch ( $lower_name ) {
				case 'attributename':
					return 'attributeName';

				case 'attributetype':
					return 'attributeType';

				case 'basefrequency':
					return 'baseFrequency';

				case 'baseprofile':
					return 'baseProfile';

				case 'calcmode':
					return 'calcMode';

				case 'clippathunits':
					return 'clipPathUnits';

				case 'diffuseconstant':
					return 'diffuseConstant';

				case 'edgemode':
					return 'edgeMode';

				case 'filterunits':
					return 'filterUnits';

				case 'glyphref':
					return 'glyphRef';

				case 'gradienttransform':
					return 'gradientTransform';

				case 'gradientunits':
					return 'gradientUnits';

				case 'kernelmatrix':
					return 'kernelMatrix';

				case 'kernelunitlength':
					return 'kernelUnitLength';

				case 'keypoints':
					return 'keyPoints';

				case 'keysplines':
					return 'keySplines';

				case 'keytimes':
					return 'keyTimes';

				case 'lengthadjust':
					return 'lengthAdjust';

				case 'limitingconeangle':
					return 'limitingConeAngle';

				case 'markerheight':
					return 'markerHeight';

				case 'markerunits':
					return 'markerUnits';

				case 'markerwidth':
					return 'markerWidth';

				case 'maskcontentunits':
					return 'maskContentUnits';

				case 'maskunits':
					return 'maskUnits';

				case 'numoctaves':
					return 'numOctaves';

				case 'pathlength':
					return 'pathLength';

				case 'patterncontentunits':
					return 'patternContentUnits';

				case 'patterntransform':
					return 'patternTransform';

				case 'patternunits':
					return 'patternUnits';

				case 'pointsatx':
					return 'pointsAtX';

				case 'pointsaty':
					return 'pointsAtY';

				case 'pointsatz':
					return 'pointsAtZ';

				case 'preservealpha':
					return 'preserveAlpha';

				case 'preserveaspectratio':
					return 'preserveAspectRatio';

				case 'primitiveunits':
					return 'primitiveUnits';

				case 'refx':
					return 'refX';

				case 'refy':
					return 'refY';

				case 'repeatcount':
					return 'repeatCount';

				case 'repeatdur':
					return 'repeatDur';

				case 'requiredextensions':
					return 'requiredExtensions';

				case 'requiredfeatures':
					return 'requiredFeatures';

				case 'specularconstant':
					return 'specularConstant';

				case 'specularexponent':
					return 'specularExponent';

				case 'spreadmethod':
					return 'spreadMethod';

				case 'startoffset':
					return 'startOffset';

				case 'stddeviation':
					return 'stdDeviation';

				case 'stitchtiles':
					return 'stitchTiles';

				case 'surfacescale':
					return 'surfaceScale';

				case 'systemlanguage':
					return 'systemLanguage';

				case 'tablevalues':
					return 'tableValues';

				case 'targetx':
					return 'targetX';

				case 'targety':
					return 'targetY';

				case 'textlength':
					return 'textLength';

				case 'viewbox':
					return 'viewBox';

				case 'viewtarget':
					return 'viewTarget';

				case 'xchannelselector':
					return 'xChannelSelector';

				case 'ychannelselector':
					return 'yChannelSelector';

				case 'zoomandpan':
					return 'zoomAndPan';
			}
		}

		if ( 'html' !== $namespace ) {
			switch ( $lower_name ) {
				case 'xlink:actuate':
					return 'xlink actuate';

				case 'xlink:arcrole':
					return 'xlink arcrole';

				case 'xlink:href':
					return 'xlink href';

				case 'xlink:role':
					return 'xlink role';

				case 'xlink:show':
					return 'xlink show';

				case 'xlink:title':
					return 'xlink title';

				case 'xlink:type':
					return 'xlink type';

				case 'xml:lang':
					return 'xml lang';

				case 'xml:space':
					return 'xml space';

				case 'xmlns':
					return 'xmlns';

				case 'xmlns:xlink':
					return 'xmlns xlink';
			}
		}

		return $attribute_name;
	}

	/**
	 * Indicates if the currently matched tag contains the self-closing flag.
	 *
	 * No HTML elements ought to have the self-closing flag and for those, the self-closing
	 * flag will be ignored. For void elements this is benign because they "self close"
	 * automatically. For non-void HTML elements though problems will appear if someone
	 * intends to use a self-closing element in place of that element with an empty body.
	 * For HTML foreign elements and custom elements the self-closing flag determines if
	 * they self-close or not.
	 *
	 * This function does not determine if a tag is self-closing,
	 * but only if the self-closing flag is present in the syntax.
	 *
	 * @since 6.3.0
	 *
	 * @return bool Whether the currently matched tag contains the self-closing flag.
	 */
	public function has_self_closing_flag(): bool {
		if ( self::STATE_MATCHED_TAG !== $this->parser_state ) {
			return false;
		}

		return $this->has_self_closing_flag;
	}

	/**
	 * Indicates if the current tag token is a tag closer.
	 *
	 * Example:
	 *
	 *     $p = new WP_HTML_Tag_Processor( '<div></div>' );
	 *     $p->next_tag( array( 'tag_name' => 'div', 'tag_closers' => 'visit' ) );
	 *     $p->is_tag_closer() === false;
	 *
	 *     $p->next_tag( array( 'tag_name' => 'div', 'tag_closers' => 'visit' ) );
	 *     $p->is_tag_closer() === true;
	 *
	 * @since 6.2.0
	 * @since 6.7.0 Reports all BR tags as opening tags.
	 *
	 * @return bool Whether the current tag is a tag closer.
	 */
	public function is_tag_closer(): bool {
		return (
			self::STATE_MATCHED_TAG === $this->parser_state &&
			$this->is_closing_tag &&

			/*
			 * The BR tag can only exist as an opening tag. If something like `</br>`
			 * appears then the HTML parser will treat it as an opening tag with no
			 * attributes. The BR tag is unique in this way.
			 *
			 * @see https://html.spec.whatwg.org/#parsing-main-inbody
			 */
			'BR' !== $this->get_tag()
		);
	}

	/**
	 * Indicates the kind of matched token, if any.
	 *
	 * This differs from `get_token_name()` in that it always
	 * returns a static string indicating the type, whereas
	 * `get_token_name()` may return values derived from the
	 * token itself, such as a tag name or processing
	 * instruction tag.
	 *
	 * Possible values:
	 *  - `#tag` when matched on a tag.
	 *  - `#text` when matched on a text node.
	 *  - `#cdata-section` when matched on a CDATA node.
	 *  - `#comment` when matched on a comment.
	 *  - `#doctype` when matched on a DOCTYPE declaration.
	 *  - `#presumptuous-tag` when matched on an empty tag closer.
	 *  - `#funky-comment` when matched on a funky comment.
	 *  - `#processing-instruction` when matched on a processing instruction.
	 *
	 * @since 6.5.0
	 * @since 7.1.0 Recognizes processing instructions.
	 *
	 * @return string|null What kind of token is matched, or null.
	 * @phpstan-return '#tag'|'#text'|'#cdata-section'|'#comment'|'#doctype'|'#presumptuous-tag'|'#funky-comment'|'#processing-instruction'|null
	 */
	public function get_token_type() {
		switch ( $this->parser_state ) {
			case self::STATE_MATCHED_TAG:
				return '#tag';

			case self::STATE_DOCTYPE:
				return '#doctype';

			default:
				return $this->get_token_name();
		}
	}

	/**
	 * Returns the node name represented by the token.
	 *
	 * This matches the DOM API value `nodeName`. Some values
	 * are static, such as `#text` for a text node, while others
	 * are dynamically generated from the token itself.
	 *
	 * Dynamic names:
	 *  - Uppercase tag name for tag matches.
	 *  - `html` for DOCTYPE declarations.
	 *
	 * Note that if the Tag Processor is not matched on a token
	 * then this function will return `null`, either because it
	 * hasn't yet found a token or because it reached the end
	 * of the document without matching a token.
	 *
	 * @since 6.5.0
	 *
	 * @return string|null Name of the matched token.
	 */
	public function get_token_name() {
		switch ( $this->parser_state ) {
			case self::STATE_MATCHED_TAG:
				return $this->get_tag();

			case self::STATE_TEXT_NODE:
				return '#text';

			case self::STATE_CDATA_NODE:
				return '#cdata-section';

			case self::STATE_COMMENT:
				return '#comment';

			case self::STATE_DOCTYPE:
				return 'html';

			case self::STATE_PRESUMPTUOUS_TAG:
				return '#presumptuous-tag';

			case self::STATE_FUNKY_COMMENT:
				return '#funky-comment';

			case self::STATE_PROCESSING_INSTRUCTION:
				return '#processing-instruction';
		}

		return null;
	}

	/**
	 * Indicates what kind of comment produced the comment node.
	 *
	 * Because there are different kinds of HTML syntax which produce
	 * comments, the Tag Processor tracks and exposes this as a type
	 * for the comment. Nominally only regular HTML comments exist as
	 * they are commonly known, but a number of unrelated syntax errors
	 * also produce comments.
	 *
	 * @see self::COMMENT_AS_ABRUPTLY_CLOSED_COMMENT
	 * @see self::COMMENT_AS_CDATA_LOOKALIKE
	 * @see self::COMMENT_AS_INVALID_HTML
	 * @see self::COMMENT_AS_HTML_COMMENT
	 * @see self::COMMENT_AS_PI_NODE_LOOKALIKE
	 *
	 * @since 6.5.0
	 *
	 * @return string|null
	 */
	public function get_comment_type() {
		if ( self::STATE_COMMENT !== $this->parser_state ) {
			return null;
		}

		return $this->comment_type;
	}

	/**
	 * Returns the text of a matched comment or null if not on a comment type node.
	 *
	 * This method returns the entire text content of a comment node as it
	 * would appear in the browser.
	 *
	 * This differs from {@see ::get_modifiable_text()} in that certain comment
	 * types in the HTML API cannot allow their entire comment text content to
	 * be modified. Namely, "bogus comments" of the form `<?xml not allowed in html>`
	 * will create a comment whose text content starts with `?`. Note that if
	 * that character were modified, it would be possible to change the node
	 * type.
	 *
	 * @since 6.7.0
	 *
	 * @return string|null The comment text as it would appear in the browser or null
	 *                     if not on a comment type node.
	 */
	public function get_full_comment_text() {
		if ( self::STATE_FUNKY_COMMENT === $this->parser_state ) {
			return $this->get_modifiable_text();
		}

		if ( self::STATE_COMMENT !== $this->parser_state ) {
			return null;
		}

		switch ( $this->get_comment_type() ) {
			case self::COMMENT_AS_HTML_COMMENT:
			case self::COMMENT_AS_ABRUPTLY_CLOSED_COMMENT:
				return $this->get_modifiable_text();

			case self::COMMENT_AS_CDATA_LOOKALIKE:
				return "[CDATA[{$this->get_modifiable_text()}]]";

			case self::COMMENT_AS_PI_NODE_LOOKALIKE:
				return "?{$this->get_tag()}{$this->get_modifiable_text()}?";

			/*
			 * This represents "bogus comments state" from HTML tokenization.
			 * This can be entered by `<?` or `<!`, where `?` is included in
			 * the comment text but `!` is not.
			 */
			case self::COMMENT_AS_INVALID_HTML:
				$preceding_character = $this->html[ $this->text_starts_at - 1 ];
				$comment_start       = '?' === $preceding_character ? '?' : '';
				return "{$comment_start}{$this->get_modifiable_text()}";
		}

		return null;
	}

	/**
	 * Subdivides a matched text node, splitting NULL byte sequences and decoded whitespace as
	 * distinct nodes prefixes.
	 *
	 * Note that once anything that's neither a NULL byte nor decoded whitespace is
	 * encountered, then the remainder of the text node is left intact as generic text.
	 *
	 *  - The HTML Processor uses this to apply distinct rules for different kinds of text.
	 *  - Inter-element whitespace can be detected and skipped with this method.
	 *
	 * Text nodes aren't eagerly subdivided because there's no need to split them unless
	 * decisions are being made on NULL byte sequences or whitespace-only text.
	 *
	 * Example:
	 *
	 *     $processor = new WP_HTML_Tag_Processor( "\x00Apples & Oranges" );
	 *     true  === $processor->next_token();                   // Text is "Apples & Oranges".
	 *     true  === $processor->subdivide_text_appropriately(); // Text is "".
	 *     true  === $processor->next_token();                   // Text is "Apples & Oranges".
	 *     false === $processor->subdivide_text_appropriately();
	 *
	 *     $processor = new WP_HTML_Tag_Processor( "&#xD; \r\n\tMore" );
	 *     true  === $processor->next_token();                   // Text is "␍ ␊␉More".
	 *     true  === $processor->subdivide_text_appropriately(); // Text is "␍ ␊␉".
	 *     true  === $processor->next_token();                   // Text is "More".
	 *     false === $processor->subdivide_text_appropriately();
	 *
	 * @since 6.7.0
	 *
	 * @return bool Whether the text node was subdivided.
	 */
	public function subdivide_text_appropriately(): bool {
		if ( self::STATE_TEXT_NODE !== $this->parser_state ) {
			return false;
		}

		$this->text_node_classification = self::TEXT_IS_GENERIC;

		/*
		 * NULL bytes are treated categorically different than numeric character
		 * references whose number is zero. `&#x00;` is not the same as `"\x00"`.
		 */
		$leading_nulls = strspn( $this->html, "\x00", $this->text_starts_at, $this->text_length );
		if ( $leading_nulls > 0 ) {
			$this->token_length             = $leading_nulls;
			$this->text_length              = $leading_nulls;
			$this->bytes_already_parsed     = $this->token_starts_at + $leading_nulls;
			$this->text_node_classification = self::TEXT_IS_NULL_SEQUENCE;
			return true;
		}

		/*
		 * Start a decoding loop to determine the point at which the
		 * text subdivides. This entails raw whitespace bytes and any
		 * character reference that decodes to the same.
		 */
		$at  = $this->text_starts_at;
		$end = $this->text_starts_at + $this->text_length;
		while ( $at < $end ) {
			$skipped = strspn( $this->html, " \t\f\r\n", $at, $end - $at );
			$at     += $skipped;

			if ( $at < $end && '&' === $this->html[ $at ] ) {
				$matched_byte_length = null;
				$replacement         = WP_HTML_Decoder::read_character_reference( 'data', $this->html, $at, $matched_byte_length );
				if ( isset( $replacement ) && 1 === strspn( $replacement, " \t\f\r\n" ) ) {
					$at += $matched_byte_length;
					continue;
				}
			}

			break;
		}

		if ( $at > $this->text_starts_at ) {
			$new_length                     = $at - $this->text_starts_at;
			$this->text_length              = $new_length;
			$this->token_length             = $new_length;
			$this->bytes_already_parsed     = $at;
			$this->text_node_classification = self::TEXT_IS_WHITESPACE;
			return true;
		}

		return false;
	}

	/**
	 * Returns the modifiable text for a matched token, or an empty string.
	 *
	 * Modifiable text is text content that may be read and changed without
	 * changing the HTML structure of the document around it. This includes
	 * the contents of `#text` nodes in the HTML as well as the inner
	 * contents of HTML comments, Processing Instructions, and others, even
	 * though these nodes aren't part of a parsed DOM tree. They also contain
	 * the contents of SCRIPT and STYLE tags, of TEXTAREA tags, and of any
	 * other section in an HTML document which cannot contain HTML markup (DATA).
	 *
	 * If a token has no modifiable text then an empty string is returned to
	 * avoid needless crashing or type errors. An empty string does not mean
	 * that a token has modifiable text, and a token with modifiable text may
	 * have an empty string (e.g. a comment with no contents).
	 *
	 * Limitations:
	 *
	 *  - This function will not strip the leading newline appropriately
	 *    after seeking into a LISTING or PRE element. To ensure that the
	 *    newline is treated properly, seek to the LISTING or PRE opening
	 *    tag instead of to the first text node inside the element.
	 *
	 * @since 6.5.0
	 * @since 6.7.0 Replaces NULL bytes (U+0000) and newlines appropriately.
	 *
	 * @return string
	 */
	public function get_modifiable_text(): string {
		$has_enqueued_update = isset( $this->lexical_updates['modifiable text'] );

		if ( ! $has_enqueued_update && ( null === $this->text_starts_at || 0 === $this->text_length ) ) {
			return '';
		}

		$text = $has_enqueued_update
			? $this->lexical_updates['modifiable text']->text
			: substr( $this->html, $this->text_starts_at, $this->text_length );

		/*
		 * An enqueued processing instruction update holds normalized raw
		 * syntax spanning from the end of the target through the end of
		 * the token: a separating space, the data, and the `?>` closer.
		 * The data is found by skipping the leading whitespace and
		 * dropping the two bytes of the closer.
		 *
		 * @see WP_HTML_Tag_Processor::set_modifiable_text()
		 */
		if ( $has_enqueued_update && self::STATE_PROCESSING_INSTRUCTION === $this->parser_state ) {
			$text = substr( $text, strspn( $text, " \t\f\r\n" ), -2 );
		}

		/*
		 * Pre-processing the input stream would normally happen before
		 * any parsing is done, but deferring it means it's possible to
		 * skip in most cases. When getting the modifiable text, however
		 * it's important to apply the pre-processing steps, which is
		 * normalizing newlines.
		 *
		 * @see https://html.spec.whatwg.org/#preprocessing-the-input-stream
		 * @see https://infra.spec.whatwg.org/#normalize-newlines
		 */
		$text = str_replace( "\r\n", "\n", $text );
		$text = str_replace( "\r", "\n", $text );

		// Comment and processing instruction data is not decoded.
		if (
			self::STATE_CDATA_NODE === $this->parser_state ||
			self::STATE_COMMENT === $this->parser_state ||
			self::STATE_DOCTYPE === $this->parser_state ||
			self::STATE_FUNKY_COMMENT === $this->parser_state ||
			self::STATE_PROCESSING_INSTRUCTION === $this->parser_state
		) {
			return str_replace( "\x00", "\u{FFFD}", $text );
		}

		$tag_name = $this->get_token_name();
		if (
			// Script data is not decoded.
			'SCRIPT' === $tag_name ||

			// RAWTEXT data is not decoded.
			'IFRAME' === $tag_name ||
			'NOEMBED' === $tag_name ||
			'NOFRAMES' === $tag_name ||
			'STYLE' === $tag_name ||
			'XMP' === $tag_name
		) {
			return str_replace( "\x00", "\u{FFFD}", $text );
		}

		$decoded = WP_HTML_Decoder::decode_text_node( $text );

		/*
		 * Skip the first line feed after LISTING, PRE, and TEXTAREA opening tags.
		 *
		 * Note that this first newline may come in the form of a character
		 * reference, such as `&#x0a;`, and so it's important to perform
		 * this transformation only after decoding the raw text content.
		 */
		if (
			( "\n" === ( $decoded[0] ?? '' ) ) &&
			( ( $this->skip_newline_at === $this->token_starts_at && '#text' === $tag_name ) || 'TEXTAREA' === $tag_name )
		) {
			$decoded = substr( $decoded, 1 );
		}

		/*
		 * Only in normative text nodes does the NULL byte (U+0000) get removed.
		 * In all other contexts it's replaced by the replacement character (U+FFFD)
		 * for security reasons (to avoid joining together strings that were safe
		 * when separated, but not when joined).
		 *
		 * @todo Inside HTML integration points and MathML integration points, the
		 *       text is processed according to the insertion mode, not according
		 *       to the foreign content rules. This should strip the NULL bytes.
		 */
		return ( '#text' === $tag_name && 'html' === $this->get_namespace() )
			? str_replace( "\x00", '', $decoded )
			: str_replace( "\x00", "\u{FFFD}", $decoded );
	}

	/**
	 * Sets the modifiable text for the matched token, if matched.
	 *
	 * Modifiable text is text content that may be read and changed without
	 * changing the HTML structure of the document around it. This includes
	 * the contents of `#text` nodes in the HTML as well as the inner
	 * contents of HTML comments, Processing Instructions, and others, even
	 * though these nodes aren't part of a parsed DOM tree. They also contain
	 * the contents of SCRIPT and STYLE tags, of TEXTAREA tags, and of any
	 * other section in an HTML document which cannot contain HTML markup (DATA).
	 *
	 * Not all modifiable text may be set by this method, and not all content
	 * may be set as modifiable text. In the case that this fails it will return
	 * `false` indicating as much. For instance, if the contents of a SCRIPT
	 * element are neither JavaScript nor JSON, it’s not possible to guarantee
	 * that escaping strings like `</script>` won’t break the script; in these
	 * cases, updates will be rejected and it’s up to calling code to perform
	 * language-specific escaping or workarounds. Similarly, it will not allow
	 * setting content into a comment which would prematurely terminate the comment,
	 * or processing instruction data which cannot be represented: data containing
	 * a `>`, which would prematurely terminate the processing instruction, or data
	 * with leading whitespace, which is indistinguishable from the whitespace
	 * separating the data from its target.
	 *
	 * Example:
	 *
	 *     // Add a preface to all STYLE contents.
	 *     while ( $processor->next_tag( 'STYLE' ) ) {
	 *         $style = $processor->get_modifiable_text();
	 *         $processor->set_modifiable_text( "// Made with love on the World Wide Web\n{$style}" );
	 *     }
	 *
	 *     // Replace smiley text with Emoji smilies.
	 *     while ( $processor->next_token() ) {
	 *         if ( '#text' !== $processor->get_token_name() ) {
	 *             continue;
	 *         }
	 *
	 *         $chunk = $processor->get_modifiable_text();
	 *         if ( ! str_contains( $chunk, ':)' ) ) {
	 *             continue;
	 *         }
	 *
	 *         $processor->set_modifiable_text( str_replace( ':)', '🙂', $chunk ) );
	 *     }
	 *
	 * This function handles all necessary HTML encoding. Provide normal, unescaped string values.
	 * The HTML API will encode the strings appropriately so that the browser will interpret them
	 * as the intended value.
	 *
	 * Example:
	 *
	 *     // Renders as “Eggs & Milk” in a browser, encoded as `<p>Eggs &amp; Milk</p>`.
	 *     $processor->set_modifiable_text( 'Eggs & Milk' );
	 *
	 *     // Renders as “Eggs &amp; Milk” in a browser, encoded as `<p>Eggs &amp;amp; Milk</p>`.
	 *     $processor->set_modifiable_text( 'Eggs &amp; Milk' );
	 *
	 * @since 6.7.0
	 * @since 6.9.0 Escapes all character references instead of trying to avoid double-escaping.
	 * @since 7.1.0 Supports setting processing instruction data.
	 *
	 * @param string $plaintext_content New text content to represent in the matched token.
	 * @return bool Whether the text was able to update.
	 */
	public function set_modifiable_text( string $plaintext_content ): bool {
		if ( self::STATE_TEXT_NODE === $this->parser_state ) {
			$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
				$this->text_starts_at,
				$this->text_length,
				strtr(
					$plaintext_content,
					array(
						'<' => '&lt;',
						'>' => '&gt;',
						'&' => '&amp;',
						'"' => '&quot;',
						"'" => '&apos;',
					)
				)
			);

			return true;
		}

		// Comment data is not encoded.
		if (
			self::STATE_COMMENT === $this->parser_state &&
			self::COMMENT_AS_HTML_COMMENT === $this->comment_type
		) {
			// Check if the text could close the comment.
			if ( 1 === preg_match( '/--!?>/', $plaintext_content ) ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Comment text cannot contain a comment closer.' ),
					'7.1.0'
				);
				return false;
			}

			$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
				$this->text_starts_at,
				$this->text_length,
				$plaintext_content
			);

			return true;
		}

		// Processing instruction data is not encoded.
		if ( self::STATE_PROCESSING_INSTRUCTION === $this->parser_state ) {
			/*
			 * A processing instruction ends at the first `>` in its
			 * raw syntax: data containing one cannot be represented.
			 */
			if ( str_contains( $plaintext_content, '>' ) ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Processing instruction data cannot contain ">".' ),
					'7.1.0'
				);
				return false;
			}

			/*
			 * All whitespace between the target and the data is skipped when
			 * parsing: data with leading whitespace cannot be represented.
			 */
			if ( 0 !== strspn( $plaintext_content, " \t\f\r\n" ) ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Processing instruction data cannot start with whitespace. Try ltrim( $plaintext_content, " \t\f\r\n" ).' ),
					'7.1.0'
				);
				return false;
			}

			/**
			 * A single replacement spans from the end of the target through
			 * the end of the token, normalizing the raw syntax for that
			 * region into a fixed form: a separating space, the data, and
			 * the `?>` closer.
			 *
			 * {@see self::get_modifiable_text()} performs necessary parsing to
			 * return the correct processing instruction data based
			 * on the modifiable text lexical update.
			 */
			$data_at = $this->tag_name_starts_at + $this->tag_name_length;

			$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
				$data_at,
				$this->token_starts_at + $this->token_length - $data_at,
				" {$plaintext_content}?>"
			);

			return true;
		}

		/*
		 * The rest of this function handles modifiable text for special "atomic" HTML elements.
		 * Only tags in the HTML namespace should be processed.
		 */
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			'html' !== $this->get_namespace()
		) {
			_doing_it_wrong(
				__METHOD__,
				__( 'This token does not support setting modifiable text.' ),
				'7.1.0'
			);
			return false;
		}

		switch ( $this->get_tag() ) {
			case 'SCRIPT':
				$script_content_type = $this->get_script_content_type();

				switch ( $script_content_type ) {
					case 'javascript':
					case 'json':
						$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
							$this->text_starts_at,
							$this->text_length,
							self::escape_javascript_script_contents( $plaintext_content )
						);
						return true;
				}

				/*
				 * If the script’s content type isn’t recognized and understandable then it’s
				 * impossible to guarantee that escaping the content won’t cause runtime breakage.
				 * For instance, if the script content type were PHP code then escaping with
				 * `\u0073` would not be met by unescaping; rather, it could result in corrupted
				 * data or even syntax errors.
				 *
				 * Because of this, content which could potentially modify the SCRIPT tag’s
				 * HTML structure is rejected here. It’s the responsibility of calling code to
				 * perform whatever semantic escaping is necessary to avoid problematic strings.
				 */
				if (
					false !== stripos( $plaintext_content, '<script' ) ||
					false !== stripos( $plaintext_content, '</script' )
				) {
					_doing_it_wrong(
						__METHOD__,
						__( 'SCRIPT text with an unrecognized content type cannot contain a SCRIPT tag. Apply the escaping appropriate for the content type.' ),
						'7.1.0'
					);
					return false;
				}
				$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
					$this->text_starts_at,
					$this->text_length,
					$plaintext_content
				);
				return true;

			case 'IFRAME':
			case 'NOEMBED':
			case 'NOFRAMES':
			case 'XMP':
				$tag_name = $this->get_tag();
				if ( false !== stripos( $plaintext_content, "</{$tag_name}" ) ) {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: %s: HTML tag name. */
							__( '%s text cannot contain its own closing tag.' ),
							$tag_name
						),
						'7.1.0'
					);
					return false;
				}

				$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
					$this->text_starts_at,
					$this->text_length,
					$plaintext_content
				);

				return true;

			case 'STYLE':
				$plaintext_content = preg_replace_callback(
					'~</(?P<TAG_NAME>style)~i',
					static function ( $tag_match ) {
						return "\\3c\\2f{$tag_match['TAG_NAME']}";
					},
					$plaintext_content
				);

				$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
					$this->text_starts_at,
					$this->text_length,
					$plaintext_content
				);

				return true;

			case 'TEXTAREA':
			case 'TITLE':
				$plaintext_content = preg_replace_callback(
					"~</(?P<TAG_NAME>{$this->get_tag()})~i",
					static function ( $tag_match ) {
						return "&lt;/{$tag_match['TAG_NAME']}";
					},
					$plaintext_content
				);

				/*
				 * HTML ignores a single leading newline in this context. If a leading newline
				 * is intended, preserve it by adding an extra newline.
				 */
				if (
					'TEXTAREA' === $this->get_tag() &&
					1 === strspn( $plaintext_content, "\n\r", 0, 1 )
				) {
					$plaintext_content = "\n{$plaintext_content}";
				}

				/*
				 * These don't _need_ to be escaped, but since they are decoded it's
				 * safe to leave them escaped and this can prevent other code from
				 * naively detecting tags within the contents.
				 *
				 * @todo It would be useful to prefix a multiline replacement text
				 *       with a newline, but not necessary. This is for aesthetics.
				 */
				$this->lexical_updates['modifiable text'] = new WP_HTML_Text_Replacement(
					$this->text_starts_at,
					$this->text_length,
					$plaintext_content
				);

				return true;
		}

		_doing_it_wrong(
			__METHOD__,
			__( 'This tag does not support setting modifiable text.' ),
			'7.1.0'
		);
		return false;
	}

	/**
	 * Returns the content type of the currently-matched HTML SCRIPT tag, if matched and
	 * recognized, otherwise returns `null` to indicate an unrecognized content type.
	 *
	 * An HTML SCRIPT tag is a normal SCRIPT tag, but there can be SCRIPT elements inside
	 * SVG and MathML elements as well, and these have different parsing rules than those
	 * in general HTML. For this reason, no content-type inference is performed on those.
	 *
	 * Note! This concept is related but distinct from the MIME type of the script.
	 * Parsing MUST match the specific algorithm in the HTML specification, which
	 * relies on exact string comparison in some cases. MIME type decoding may be
	 * performed on SVG or MathML SCRIPT tags.
	 *
	 * Only 'javascript' and 'json' content types are currently recognized.
	 *
	 * @see https://html.spec.whatwg.org/multipage/scripting.html#prepare-the-script-element
	 *
	 * @since 7.0.0
	 * @ignore
	 *
	 * @return 'javascript'|'json'|null Type of script element content if matched and recognized.
	 */
	private function get_script_content_type() {
		// SVG and MathML SCRIPT elements are not recognized.
		if ( 'SCRIPT' !== $this->get_tag() || $this->get_namespace() !== 'html' ) {
			return null;
		}

		/*
		 * > If any of the following are true:
		 * >   - el has a type attribute whose value is the empty string;
		 * >   - el has no type attribute but it has a language attribute and that attribute's
		 * >     value is the empty string; or
		 * >   - el has neither a type attribute nor a language attribute,
		 * > then let the script block's type string for this script element be "text/javascript".
		 */
		$type = $this->get_attribute( 'type' );
		$lang = $this->get_attribute( 'language' );

		if ( true === $type || '' === $type ) {
			return 'javascript';
		}

		if ( null === $type && ( null === $lang || true === $lang || '' === $lang ) ) {
			return 'javascript';
		}

		/*
		 * > Otherwise, if el has a type attribute, then let the script block's type string be
		 * > the value of that attribute with leading and trailing ASCII whitespace stripped.
		 * > Otherwise, el has a non-empty language attribute; let the script block's type string
		 * > be the concatenation of "text/" and the value of el's language attribute.
		 */
		$type_string = is_string( $type ) ? trim( $type, " \t\f\r\n" ) : "text/{$lang}";

		// All matches are ASCII case-insensitive; eagerly lower-case for comparison.
		$type_string = strtolower( $type_string );

		/*
		 * > If the script block's type string is a JavaScript MIME type essence match, then
		 * > set el's type to "classic".
		 *
		 * > A string is a JavaScript MIME type essence match if it is an ASCII case-insensitive
		 * > match for one of the JavaScript MIME type essence strings.
		 *
		 * > A JavaScript MIME type is any MIME type whose essence is one of the following:
		 * >
		 * > - application/ecmascript
		 * > - application/javascript
		 * > - application/x-ecmascript
		 * > - application/x-javascript
		 * > - text/ecmascript
		 * > - text/javascript
		 * > - text/javascript1.0
		 * > - text/javascript1.1
		 * > - text/javascript1.2
		 * > - text/javascript1.3
		 * > - text/javascript1.4
		 * > - text/javascript1.5
		 * > - text/jscript
		 * > - text/livescript
		 * > - text/x-ecmascript
		 * > - text/x-javascript
		 *
		 * @see https://mimesniff.spec.whatwg.org/#javascript-mime-type-essence-match
		 * @see https://mimesniff.spec.whatwg.org/#javascript-mime-type
		 */
		switch ( $type_string ) {
			case 'application/ecmascript':
			case 'application/javascript':
			case 'application/x-ecmascript':
			case 'application/x-javascript':
			case 'text/ecmascript':
			case 'text/javascript':
			case 'text/javascript1.0':
			case 'text/javascript1.1':
			case 'text/javascript1.2':
			case 'text/javascript1.3':
			case 'text/javascript1.4':
			case 'text/javascript1.5':
			case 'text/jscript':
			case 'text/livescript':
			case 'text/x-ecmascript':
			case 'text/x-javascript':
				return 'javascript';

			/*
			 * > Otherwise, if the script block's type string is an ASCII case-insensitive match for
			 * > the string "module", then set el's type to "module".
			 *
			 * A module is evaluated as JavaScript.
			 */
			case 'module':
				return 'javascript';

			/*
			 * > Otherwise, if the script block's type string is an ASCII case-insensitive match for the string "importmap", then set el's type to "importmap".
			 * > Otherwise, if the script block's type string is an ASCII case-insensitive match for the string "speculationrules", then set el's type to "speculationrules".
			 *
			 * These conditions indicate JSON content.
			 */
			case 'importmap':
			case 'speculationrules':
				return 'json';

			/** @todo Rely on a full MIME parser for determining JSON content. */
			case 'application/json':
			case 'text/json':
				return 'json';
		}

		/*
		 * > Otherwise, return. (No script is executed, and el's type is left as null.)
		 */
		return null;
	}

	/**
	 * Escape JavaScript and JSON script tag contents.
	 *
	 * Ensure that the script contents cannot modify the HTML structure or break out
	 * of its containing SCRIPT element. JavaScript and JSON may both be escaped with
	 * the same rules, even though there are additional escaping measures available
	 * to JavaScript source code which aren’t applicable to serialized JSON data.
	 *
	 * A simple method safely escapes all content except for a few extremely rare and
	 * unlikely exceptions: prevent the appearance of `<script` and `</script` within
	 * the contents by replacing the first letter of the tag name with a Unicode escape.
	 *
	 * Example:
	 *
	 *     $plaintext = '<script>document.write( "A </script> closes a script." );</script>';
	 *     $escaped   = '<script>document.write( "A </\u0073cript> closes a script." );</script>';
	 *
	 * This works because of how parsing changes after encountering an opening SCRIPT
	 * tag. The actual parsing comprises a complicated state machine, the result of
	 * legacy behaviors and diverse browser support. However, without these two strings
	 * in the script contents, two key things are ensured: `</script>` cannot appear to
	 * prematurely close the tag, and the problematic double-escaped state becomes
	 * unreachable. A JavaScript engine or JSON decoder will then decode the Unicode
	 * escape (`\u0073`) back into its original plaintext value, but only after having
	 * been safely extracted from the HTML.
	 *
	 * While it may seem tempting to replace the `<` character instead, doing so would
	 * break JavaScript syntax. The `<` character is used in comparison operators and
	 * other JavaScript syntax; replacing it would break valid JavaScript. Replacing
	 * only the `s` in `<script` and `</script` avoids modifying JavaScript syntax.
	 *
	 * ### Exceptions
	 *
	 * This _should_ work everywhere, but there are some extreme exceptions.
	 *
	 *  - Comments.
	 *  - Tagged templates, such as `String.raw()`, which provide access to “raw” strings.
	 *  - The `source` property of a RegExp object.
	 *
	 * Each of these exceptions appear at the source code level, not at the semantic or
	 * evaluation level. Normal JavaScript will remain semantically equivalent after escaping,
	 * but any JavaScript which analyzes the raw source code will see potentially-different
	 * values.
	 *
	 * #### Comments
	 *
	 * Comments are never unescaped because they aren’t parsed by the JavaScript engine.
	 * When viewing the source in a browser’s developer tools, the comments will retain
	 * their escaped text.
	 *
	 * Example:
	 *
	 *     // A comment: "</script>"
	 *         …becomes…
	 *     // A comment: "</\u0073cript>"
	 *
	 * #### Tagged templates.
	 *
	 * Tagged templates “enable the embedding of arbitrary string content, where escape
	 * sequences may follow a different syntax.” For example, they can aid representing
	 * a RegExp pattern or LaTex snippet within a JavaScript string, where the string
	 * escape characters might get noisy and distracting.
	 *
	 * Example:
	 *
	 *     console.log( 'A \notin B' );           // Prints a newline because of the "\n".
	 *     console.log( 'A \\notin B' );          // Prints "A \notin B".
	 *     console.log( String.raw`A \notin B` ); // Prints "A \notin B".
	 *
	 * This means that if `<script` transforms into `<\u0073cript` _inside_ a raw string
	 * or tagged template literal which relies on its `.raw` property, the output of the
	 * code will be different after escaping.
	 *
	 * Example:
	 *
	 *     console.log( String.raw`</script>` );      // Prematurely closes the SCRIPT element.
	 *     console.log( String.raw`</\u0073cript>` ); // Prints "</\u0073cript".
	 *
	 * #### RegExp sources.
	 *
	 * The RegExp object exposes its raw source in a similar way to how tagged templates and raw
	 * strings do. Thankfully, because escape sequences are decoded when compiling the pattern,
	 * escaped RegExp patterns will match the same way as the plaintext sequences would.
	 *
	 * Example:
	 *
	 *     true === /<script>/.test( '<script>' );
	 *     true === /<\u0073cript>/.test( '<script>' );
	 *
	 * However, as with raw strings, any code which reads the source will see the escaped value
	 * instead of the decoded one.
	 *
	 * Example:
	 *
	 *     console.log( /<script>/.source );      // Prints "<script>".
	 *     console.log( /<\u0073cript>/.source ); // Prints "<\u0073cript>".
	 *
	 * #### Unsupported escaping.
	 *
	 * It is not possible to properly represent every possible JavaScript source file
	 * inside a SCRIPT element. As with CSS stylesheets, SVG images, and MathML, the
	 * only 100% reliable way to represent all possible inputs is to link to external
	 * files of the given content-type.
	 *
	 * In some cases it’s possible to manually prevent escaping issues. These are not
	 * automatically handled by this function because doing so would require a full
	 * JavaScript tokenizer. Consider the following example listing various ways to
	 * manually escape a closing script tag.
	 *
	 * Example:
	 *
	 *     console.log( String.raw`</script>` );                // !!UNSAFE!! Will be escaped.
	 *     console.log( String.raw`</\u0073cript>` );           // "</\u0073cript>"
	 *     console.log( String.raw`</scr` + String.raw`ipt>` ); // "</script>"
	 *     console.log( String.raw`</${"script"}>` );           // "</script>"
	 *     console.log( '</scr' + 'ipt>' );                     // "</script>"
	 *     console.log( "\x3C/script>" );                       // "</script>"
	 *     console.log( "<\/script>" );                         // "</script>"
	 *
	 * The following graph is a simplified interpretation of how HTML interprets the contents
	 * of a SCRIPT tag and identifies the closing tag. It is useful to understand what text
	 * is dangerous inside of a SCRIPT tag and why different approaches to escaping work.
	 *
	 *                                 Open script
	 *                                     │
	 *                                     ▼
	 *                  ╔═════════════════════════════════════════╗   <!--(…)>
	 *                  ║                                         ║   (all dashes)
	 *                  ║                 script                  ╟────────────────╮
	 *                  ║                  data                   ║                │
	 *      ╭───────────╢                                         ║ ◀──────────────╯
	 *      │           ╚═╤═══════════════════════════════════════╝
	 *      │             │               ▲                    ▲
	 *      │             │ <!--          │ -->                ╰─────╮
	 *      │             ▼               │                          │
	 *      │           ┌─────────────────┴───────────────────────┐  │
	 *      │ </script¹ │                 escaped                 │  │
	 *      │           └─┬─────────────────────────────┬─────────┘  │
	 *      │             │               ▲             │            │ -->
	 *      │             │ </script¹     │ </script¹   │ <script¹   │
	 *      │             ▼               │             ▼            │
	 *      │           ╔══════════════╗  │           ┌───────────┐  │
	 *      │           ║ Close script ║  │           │  double   │  │
	 *      ╰──────────▶║              ║  ╰───────────┤  escaped  ├──╯
	 *                  ╚══════════════╝              └───────────┘
	 *
	 *           ¹ = Case insensitive 'script' followed by one of ' \t\f\r\n/>', known
	 *               as “tag-name-terminating characters.” This sequence forms the start
	 *               of what could be a SCRIPT opening or closing tag.
	 *
	 * @see https://html.spec.whatwg.org/#restrictions-for-contents-of-script-elements
	 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Template_literals#specifications
	 * @see wp_html_api_script_element_escaping_diagram_source()
	 *
	 * @since 7.0.0
	 * @ignore
	 *
	 * @param string $sourcecode Raw contents intended to be serialized into an HTML SCRIPT element.
	 * @return string Escaped form of input contents which will not lead to premature closing of the containing SCRIPT element.
	 */
	private static function escape_javascript_script_contents( string $sourcecode ): string {
		$at      = 0;
		$was_at  = 0;
		$end     = strlen( $sourcecode );
		$escaped = '';

		/*
		 * Replace all instances of the ASCII case-insensitive match of "<script"
		 * and "</script", when followed by whitespace or "/" or ">", by using a
		 * character replacement for the "s" (or the "S").
		 */
		while ( $at < $end ) {
			$tag_at = strpos( $sourcecode, '<', $at );
			if ( false === $tag_at ) {
				break;
			}

			$tag_name_at       = $tag_at + 1;
			$has_closing_slash = $tag_name_at < $end && '/' === $sourcecode[ $tag_name_at ];
			$tag_name_at      += $has_closing_slash ? 1 : 0;

			if ( 0 !== substr_compare( $sourcecode, 'script', $tag_name_at, 6, true ) ) {
				$at = $tag_at + 1;
				continue;
			}

			if ( 1 !== strspn( $sourcecode, " \t\f\r\n/>", $tag_name_at + 6, 1 ) ) {
				$at = $tag_name_at + 6;
				continue;
			}

			$escaped .= substr( $sourcecode, $was_at, $tag_name_at - $was_at );
			$escaped .= 's' === $sourcecode[ $tag_name_at ] ? '\u0073' : '\u0053';
			$was_at   = $tag_name_at + 1;
			$at       = $tag_name_at + 7;
		}

		if ( '' === $escaped ) {
			return $sourcecode;
		}

		if ( $was_at < $end ) {
			$escaped .= substr( $sourcecode, $was_at );
		}

		return $escaped;
	}

	/**
	 * Updates or creates a new attribute on the currently matched tag with the passed value.
	 *
	 * This function handles all necessary HTML encoding. Provide normal, unescaped string values.
	 * The HTML API will encode the strings appropriately so that the browser will interpret them
	 * as the intended value.
	 *
	 * Example:
	 *
	 *     // Renders “Eggs & Milk” in a browser, encoded as `<abbr title="Eggs &amp; Milk">`.
	 *     $processor->set_attribute( 'title', 'Eggs & Milk' );
	 *
	 *     // Renders “Eggs &amp; Milk” in a browser, encoded as `<abbr title="Eggs &amp;amp; Milk">`.
	 *     $processor->set_attribute( 'title', 'Eggs &amp; Milk' );
	 *
	 *     // Renders `true` as `<abbr title>`.
	 *     $processor->set_attribute( 'title', true );
	 *
	 *     // Renders without the attribute for `false` as `<abbr>`.
	 *     $processor->set_attribute( 'title', false );
	 *
	 * Special handling is provided for boolean attribute values:
	 *  - When `true` is passed as the value, then only the attribute name is added to the tag.
	 *  - When `false` is passed, the attribute gets removed if it existed before.
	 *
	 * @since 6.2.0
	 * @since 6.2.1 Fix: Only create a single update for multiple calls with case-variant attribute names.
	 * @since 6.9.0 Escapes all character references instead of trying to avoid double-escaping.
	 *
	 * @param string      $name  The attribute name to target.
	 * @param string|bool $value The new attribute value.
	 * @return bool Whether an attribute value was set.
	 */
	public function set_attribute( $name, $value ): bool {
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			$this->is_closing_tag
		) {
			return false;
		}

		/*
		 * WordPress rejects more characters than are strictly forbidden
		 * in HTML5. This is to prevent additional security risks deeper
		 * in the WordPress and plugin stack. Specifically the
		 * less-than (<) greater-than (>) and ampersand (&) aren't allowed.
		 *
		 * The use of a PCRE match enables looking for specific Unicode
		 * code points without writing a UTF-8 decoder. Whereas scanning
		 * for one-byte characters is trivial (with `strcspn`), scanning
		 * for the longer byte sequences would be more complicated. Given
		 * that this shouldn't be in the hot path for execution, it's a
		 * reasonable compromise in efficiency without introducing a
		 * noticeable impact on the overall system.
		 *
		 * @see https://html.spec.whatwg.org/#attributes-2
		 *
		 * @todo As the only regex pattern maybe we should take it out?
		 *       Are Unicode patterns available broadly in Core?
		 */
		if (
			0 === strlen( $name ) ||
			// HTML noncharacters.
			preg_match(
				'~' .
				// Control characters.
				'[\x00-\x1F]|' .
				// Syntax-like characters.
				'["\'>&</ =]|' .
				// U+FDD0–U+FDEF.
                '\xEF\xB7[\x90-\xAF]|' .
                // U+nFFFE and U+nFFFF, for planes 0–16.
				'(?:\xEF|\xF0[\x9F\xAF\xBF]|[\xF1-\xF3][\x8F\x9F\xAF\xBF]|\xF4\x8F)\xBF[\xBE\xBF]' .
				'~S',
				$name
			)
		) {
			_doing_it_wrong(
				__METHOD__,
				__( 'Invalid attribute name.' ),
				'6.2.0'
			);

			return false;
		}

		/*
		 * > The values "true" and "false" are not allowed on boolean attributes.
		 * > To represent a false value, the attribute has to be omitted altogether.
		 *     - HTML5 spec, https://html.spec.whatwg.org/#boolean-attributes
		 */
		if ( false === $value ) {
			return $this->remove_attribute( $name );
		}

		if ( true === $value ) {
			$updated_attribute = $name;
		} else {
			$comparable_name = strtolower( $name );

			/**
			 * Escape attribute values appropriately.
			 *
			 * @see https://html.spec.whatwg.org/#attributes-3
			 */
			$escaped_new_value = in_array( $comparable_name, wp_kses_uri_attributes(), true )
				? esc_url( $value )
				: strtr(
					$value,
					array(
						'<' => '&lt;',
						'>' => '&gt;',
						'&' => '&amp;',
						'"' => '&quot;',
						"'" => '&apos;',
					)
				);

			// If the escaping functions wiped out the update, reject it and indicate it was rejected.
			if ( '' === $escaped_new_value && '' !== $value ) {
				return false;
			}

			$updated_attribute = "{$name}=\"{$escaped_new_value}\"";
		}

		/*
		 * > There must never be two or more attributes on
		 * > the same start tag whose names are an ASCII
		 * > case-insensitive match for each other.
		 *     - HTML 5 spec
		 *
		 * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
		 */
		$comparable_name = strtolower( $name );

		if ( isset( $this->attributes[ $comparable_name ] ) ) {
			/*
			 * Update an existing attribute.
			 *
			 * Example – set attribute id to "new" in <div id="initial_id" />:
			 *
			 *     <div id="initial_id"/>
			 *          ^-------------^
			 *          start         end
			 *     replacement: `id="new"`
			 *
			 *     Result: <div id="new"/>
			 */
			$existing_attribute                        = $this->attributes[ $comparable_name ];
			$this->lexical_updates[ $comparable_name ] = new WP_HTML_Text_Replacement(
				$existing_attribute->start,
				$existing_attribute->length,
				$updated_attribute
			);
		} else {
			/*
			 * Create a new attribute at the tag's name end.
			 *
			 * Example – add attribute id="new" to <div />:
			 *
			 *     <div/>
			 *         ^
			 *         start and end
			 *     replacement: ` id="new"`
			 *
			 *     Result: <div id="new"/>
			 */
			$this->lexical_updates[ $comparable_name ] = new WP_HTML_Text_Replacement(
				$this->tag_name_starts_at + $this->tag_name_length,
				0,
				' ' . $updated_attribute
			);
		}

		/*
		 * Any calls to update the `class` attribute directly should wipe out any
		 * enqueued class changes from `add_class` and `remove_class`.
		 */
		if ( 'class' === $comparable_name && ! empty( $this->classname_updates ) ) {
			$this->classname_updates = array();
		}

		return true;
	}

	/**
	 * Remove an attribute from the currently-matched tag.
	 *
	 * @since 6.2.0
	 *
	 * @param string $name The attribute name to remove.
	 * @return bool Whether an attribute was removed.
	 */
	public function remove_attribute( $name ): bool {
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			$this->is_closing_tag
		) {
			return false;
		}

		/*
		 * > There must never be two or more attributes on
		 * > the same start tag whose names are an ASCII
		 * > case-insensitive match for each other.
		 *     - HTML 5 spec
		 *
		 * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
		 */
		$name = strtolower( $name );

		/*
		 * Any calls to update the `class` attribute directly should wipe out any
		 * enqueued class changes from `add_class` and `remove_class`.
		 */
		if ( 'class' === $name && count( $this->classname_updates ) !== 0 ) {
			$this->classname_updates = array();
		}

		/*
		 * If updating an attribute that didn't exist in the input
		 * document, then remove the enqueued update and move on.
		 *
		 * For example, this might occur when calling `remove_attribute()`
		 * after calling `set_attribute()` for the same attribute
		 * and when that attribute wasn't originally present.
		 */
		if ( ! isset( $this->attributes[ $name ] ) ) {
			if ( isset( $this->lexical_updates[ $name ] ) ) {
				unset( $this->lexical_updates[ $name ] );
			}
			return false;
		}

		/*
		 * Removes an existing tag attribute.
		 *
		 * Example – remove the attribute id from <div id="main"/>:
		 *    <div id="initial_id"/>
		 *         ^-------------^
		 *         start         end
		 *    replacement: ``
		 *
		 *    Result: <div />
		 */
		$this->lexical_updates[ $name ] = new WP_HTML_Text_Replacement(
			$this->attributes[ $name ]->start,
			$this->attributes[ $name ]->length,
			''
		);

		// Removes any duplicated attributes if they were also present.
		foreach ( $this->duplicate_attributes[ $name ] ?? array() as $attribute_token ) {
			$this->lexical_updates[] = new WP_HTML_Text_Replacement(
				$attribute_token->start,
				$attribute_token->length,
				''
			);
		}

		return true;
	}

	/**
	 * Adds a new class name to the currently matched tag.
	 *
	 * @since 6.2.0
	 *
	 * @param string $class_name The class name to add.
	 * @return bool Whether the class was set to be added.
	 */
	public function add_class( $class_name ): bool {
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			$this->is_closing_tag
		) {
			return false;
		}

		if ( self::QUIRKS_MODE !== $this->compat_mode ) {
			$this->classname_updates[ $class_name ] = self::ADD_CLASS;
			return true;
		}

		/*
		 * Because class names are matched ASCII-case-insensitively in quirks mode,
		 * this needs to see if a case variant of the given class name is already
		 * enqueued and update that existing entry, if so. This picks the casing of
		 * the first-provided class name for all lexical variations.
		 */
		$class_name_length = strlen( $class_name );
		foreach ( $this->classname_updates as $updated_name => $action ) {
			if (
				strlen( $updated_name ) === $class_name_length &&
				0 === substr_compare( $updated_name, $class_name, 0, $class_name_length, true )
			) {
				$this->classname_updates[ $updated_name ] = self::ADD_CLASS;
				return true;
			}
		}

		$this->classname_updates[ $class_name ] = self::ADD_CLASS;
		return true;
	}

	/**
	 * Removes a class name from the currently matched tag.
	 *
	 * @since 6.2.0
	 *
	 * @param string $class_name The class name to remove.
	 * @return bool Whether the class was set to be removed.
	 */
	public function remove_class( $class_name ): bool {
		if (
			self::STATE_MATCHED_TAG !== $this->parser_state ||
			$this->is_closing_tag
		) {
			return false;
		}

		if ( self::QUIRKS_MODE !== $this->compat_mode ) {
			$this->classname_updates[ $class_name ] = self::REMOVE_CLASS;
			return true;
		}

		/*
		 * Because class names are matched ASCII-case-insensitively in quirks mode,
		 * this needs to see if a case variant of the given class name is already
		 * enqueued and update that existing entry, if so. This picks the casing of
		 * the first-provided class name for all lexical variations.
		 */
		$class_name_length = strlen( $class_name );
		foreach ( $this->classname_updates as $updated_name => $action ) {
			if (
				strlen( $updated_name ) === $class_name_length &&
				0 === substr_compare( $updated_name, $class_name, 0, $class_name_length, true )
			) {
				$this->classname_updates[ $updated_name ] = self::REMOVE_CLASS;
				return true;
			}
		}

		$this->classname_updates[ $class_name ] = self::REMOVE_CLASS;
		return true;
	}

	/**
	 * Returns the string representation of the HTML Tag Processor.
	 *
	 * @since 6.2.0
	 *
	 * @see WP_HTML_Tag_Processor::get_updated_html()
	 *
	 * @return string The processed HTML.
	 */
	public function __toString(): string {
		return $this->get_updated_html();
	}

	/**
	 * Returns the string representation of the HTML Tag Processor.
	 *
	 * @since 6.2.0
	 * @since 6.2.1 Shifts the internal cursor corresponding to the applied updates.
	 * @since 6.4.0 No longer calls subclass method `next_tag()` after updating HTML.
	 *
	 * @return string The processed HTML.
	 */
	public function get_updated_html(): string {
		$requires_no_updating = 0 === count( $this->classname_updates ) && 0 === count( $this->lexical_updates );

		/*
		 * When there is nothing more to update and nothing has already been
		 * updated, return the original document and avoid a string copy.
		 */
		if ( $requires_no_updating ) {
			return $this->html;
		}

		/*
		 * Keep track of the position right before the current tag. This will
		 * be necessary for reparsing the current tag after updating the HTML.
		 */
		$before_current_tag = $this->token_starts_at ?? 0;

		/*
		 * 1. Apply the enqueued edits and update all the pointers to reflect those changes.
		 */
		$this->class_name_updates_to_attributes_updates();
		$before_current_tag += $this->apply_attributes_updates( $before_current_tag );

		/*
		 * 2. Rewind to before the current tag and reparse to get updated attributes.
		 *
		 * At this point the internal cursor points to the end of the tag name.
		 * Rewind before the tag name starts so that it's as if the cursor didn't
		 * move; a call to `next_tag()` will reparse the recently-updated attributes
		 * and additional calls to modify the attributes will apply at this same
		 * location, but in order to avoid issues with subclasses that might add
		 * behaviors to `next_tag()`, the internal methods should be called here
		 * instead.
		 *
		 * It's important to note that in this specific place there will be no change
		 * because the processor was already at a tag when this was called and it's
		 * rewinding only to the beginning of this very tag before reprocessing it
		 * and its attributes.
		 *
		 * <p>Previous HTML<em>More HTML</em></p>
		 *                 ↑  │ back up by the length of the tag name plus the opening <
		 *                 └←─┘ back up by strlen("em") + 1 ==> 3
		 */
		$this->bytes_already_parsed = $before_current_tag;
		$this->base_class_next_token();

		return $this->html;
	}

	/**
	 * Parses tag query input into internal search criteria.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @param array|string|null $query {
	 *     Optional. Which tag name to find, having which class, etc. Default is to find any tag.
	 *
	 *     @type string|null $tag_name     Which tag to find, or `null` for "any tag."
	 *     @type int|null    $match_offset Find the Nth tag matching all search criteria.
	 *                                     1 for "first" tag, 3 for "third," etc.
	 *                                     Defaults to first tag.
	 *     @type string|null $class_name   Tag must contain this class name to match.
	 *     @type string      $tag_closers  "visit" or "skip": whether to stop on tag closers, e.g. </div>.
	 * }
	 */
	private function parse_query( $query ) {
		if ( null !== $query && $query === $this->last_query ) {
			return;
		}

		$this->last_query          = $query;
		$this->sought_tag_name     = null;
		$this->sought_class_name   = null;
		$this->sought_match_offset = 1;
		$this->stop_on_tag_closers = false;

		// A single string value means "find the tag of this name".
		if ( is_string( $query ) ) {
			$this->sought_tag_name = $query;
			return;
		}

		// An empty query parameter applies no restrictions on the search.
		if ( null === $query ) {
			return;
		}

		// If not using the string interface, an associative array is required.
		if ( ! is_array( $query ) ) {
			_doing_it_wrong(
				__METHOD__,
				__( 'The query argument must be an array or a tag name.' ),
				'6.2.0'
			);
			return;
		}

		if ( isset( $query['tag_name'] ) && is_string( $query['tag_name'] ) ) {
			$this->sought_tag_name = $query['tag_name'];
		}

		if ( isset( $query['class_name'] ) && is_string( $query['class_name'] ) ) {
			$this->sought_class_name = $query['class_name'];
		}

		if ( isset( $query['match_offset'] ) && is_int( $query['match_offset'] ) && 0 < $query['match_offset'] ) {
			$this->sought_match_offset = $query['match_offset'];
		}

		if ( isset( $query['tag_closers'] ) ) {
			$this->stop_on_tag_closers = 'visit' === $query['tag_closers'];
		}
	}


	/**
	 * Checks whether a given tag and its attributes match the search criteria.
	 *
	 * @since 6.2.0
	 * @ignore
	 *
	 * @return bool Whether the given tag and its attribute match the search criteria.
	 */
	private function matches(): bool {
		if ( $this->is_closing_tag && ! $this->stop_on_tag_closers ) {
			return false;
		}

		// Does the tag name match the requested tag name in a case-insensitive manner?
		if ( isset( $this->sought_tag_name ) ) {
			$tag_name = $this->get_tag();
			if (
				strlen( $this->sought_tag_name ) !== strlen( $tag_name ) ||
				0 !== substr_compare( $tag_name, $this->sought_tag_name, 0, null, true )
			) {
				return false;
			}
		}

		if ( null !== $this->sought_class_name && ! $this->has_class( $this->sought_class_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets DOCTYPE declaration info from a DOCTYPE token.
	 *
	 * DOCTYPE tokens may appear in many places in an HTML document. In most places, they are
	 * simply ignored. The main parsing functions find the basic shape of DOCTYPE tokens but
	 * do not perform detailed parsing.
	 *
	 * This method can be called to perform a full parse of the DOCTYPE token and retrieve
	 * its information.
	 *
	 * @return WP_HTML_Doctype_Info|null The DOCTYPE declaration information or `null` if not
	 *                                   currently at a DOCTYPE node.
	 */
	public function get_doctype_info() {
		if ( self::STATE_DOCTYPE !== $this->parser_state ) {
			return null;
		}

		return WP_HTML_Doctype_Info::from_doctype_token( substr( $this->html, $this->token_starts_at, $this->token_length ) );
	}

	/**
	 * Parser Ready State.
	 *
	 * Indicates that the parser is ready to run and waiting for a state transition.
	 * It may not have started yet, or it may have just finished parsing a token and
	 * is ready to find the next one.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_READY = 'STATE_READY';

	/**
	 * Parser Complete State.
	 *
	 * Indicates that the parser has reached the end of the document and there is
	 * nothing left to scan. It finished parsing the last token completely.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_COMPLETE = 'STATE_COMPLETE';

	/**
	 * Parser Incomplete Input State.
	 *
	 * Indicates that the parser has reached the end of the document before finishing
	 * a token. It started parsing a token but there is a possibility that the input
	 * HTML document was truncated in the middle of a token.
	 *
	 * The parser is reset at the start of the incomplete token and has paused. There
	 * is nothing more than can be scanned unless provided a more complete document.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_INCOMPLETE_INPUT = 'STATE_INCOMPLETE_INPUT';

	/**
	 * Parser Matched Tag State.
	 *
	 * Indicates that the parser has found an HTML tag and it's possible to get
	 * the tag name and read or modify its attributes (if it's not a closing tag).
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_MATCHED_TAG = 'STATE_MATCHED_TAG';

	/**
	 * Parser Text Node State.
	 *
	 * Indicates that the parser has found a text node and it's possible
	 * to read and modify that text.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_TEXT_NODE = 'STATE_TEXT_NODE';

	/**
	 * Parser CDATA Node State.
	 *
	 * Indicates that the parser has found a CDATA node and it's possible
	 * to read and modify its modifiable text. Note that in HTML there are
	 * no CDATA nodes outside of foreign content (SVG and MathML). Outside
	 * of foreign content, they are treated as HTML comments.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_CDATA_NODE = 'STATE_CDATA_NODE';

	/**
	 * Indicates that the parser has found an HTML comment and it's
	 * possible to read and modify its modifiable text.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_COMMENT = 'STATE_COMMENT';

	/**
	 * Indicates that the parser has found a DOCTYPE node and it's
	 * possible to read its DOCTYPE information via `get_doctype_info()`.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_DOCTYPE = 'STATE_DOCTYPE';

	/**
	 * Indicates that the parser has found an empty tag closer `</>`.
	 *
	 * Note that in HTML there are no empty tag closers, and they
	 * are ignored. Nonetheless, the Tag Processor still
	 * recognizes them as they appear in the HTML stream.
	 *
	 * These were historically discussed as a "presumptuous tag
	 * closer," which would close the nearest open tag, but were
	 * dismissed in favor of explicitly-closing tags.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_PRESUMPTUOUS_TAG = 'STATE_PRESUMPTUOUS_TAG';

	/**
	 * Indicates that the parser has found a "funky comment"
	 * and it's possible to read and modify its modifiable text.
	 *
	 * Example:
	 *
	 *     </%url>
	 *     </{"wp-bit":"query/post-author"}>
	 *     </2>
	 *
	 * Funky comments are tag closers with invalid tag names. Note
	 * that in HTML these are turned into bogus comments. Nonetheless,
	 * the Tag Processor recognizes them in a stream of HTML and
	 * exposes them for inspection and modification.
	 *
	 * @since 6.5.0
	 *
	 * @access private
	 */
	const STATE_FUNKY_COMMENT = 'STATE_WP_FUNKY';

	/**
	 * Indicates that the parser has found a processing instruction
	 * and it's possible to read its target and data.
	 *
	 * Example:
	 *
	 *     <?wp-bit {"just": "kidding"}>
	 *
	 * Processing instructions with an allowable target are parsed
	 * into processing instruction nodes. The reserved `xml` and
	 * `xml-stylesheet` targets, and targets with characters outside
	 * an ASCII-representable subset, are turned into bogus comments.
	 *
	 * @link https://html.spec.whatwg.org/multipage/parsing.html#processing-instruction-open-state
	 *
	 * @since 7.1.0
	 *
	 * @access private
	 */
	const STATE_PROCESSING_INSTRUCTION = 'STATE_PROCESSING_INSTRUCTION';

	/**
	 * Indicates that a comment was created when encountering abruptly-closed HTML comment.
	 *
	 * Example:
	 *
	 *     <!-->
	 *     <!--->
	 *
	 * @since 6.5.0
	 */
	const COMMENT_AS_ABRUPTLY_CLOSED_COMMENT = 'COMMENT_AS_ABRUPTLY_CLOSED_COMMENT';

	/**
	 * Indicates that a comment would be parsed as a CDATA node,
	 * were HTML to allow CDATA nodes outside of foreign content.
	 *
	 * Example:
	 *
	 *     <![CDATA[This is a CDATA node.]]>
	 *
	 * This is an HTML comment, but it looks like a CDATA node.
	 *
	 * @since 6.5.0
	 */
	const COMMENT_AS_CDATA_LOOKALIKE = 'COMMENT_AS_CDATA_LOOKALIKE';

	/**
	 * Indicates that a comment was created when encountering
	 * normative HTML comment syntax.
	 *
	 * Example:
	 *
	 *     <!-- this is a comment -->
	 *
	 * @since 6.5.0
	 */
	const COMMENT_AS_HTML_COMMENT = 'COMMENT_AS_HTML_COMMENT';

	/**
	 * Indicates that a comment would be parsed as a Processing
	 * Instruction node, were its target allowed within HTML.
	 *
	 * Example:
	 *
	 *     <?xml version="1.0" ?>
	 *     <?wp.like count=5 ?>
	 *
	 * These are HTML comments, but they look like processing
	 * instructions. HTML parses processing instructions with
	 * an allowable target into processing instruction nodes,
	 * but the reserved `xml` and `xml-stylesheet` targets and
	 * XML-valid targets with characters outside of the allowed
	 * set become bogus comments instead.
	 *
	 * @since 6.5.0
	 * @since 7.1.0 Only applies to reserved and XML-specific target names;
	 *              other processing instructions produce their own token.
	 */
	const COMMENT_AS_PI_NODE_LOOKALIKE = 'COMMENT_AS_PI_NODE_LOOKALIKE';

	/**
	 * Indicates that a comment was created when encountering invalid
	 * HTML input, a so-called "bogus comment."
	 *
	 * Example:
	 *
	 *     <?= nothing special ?>
	 *     <!{nothing special}>
	 *
	 * @since 6.5.0
	 */
	const COMMENT_AS_INVALID_HTML = 'COMMENT_AS_INVALID_HTML';

	/**
	 * No-quirks mode document compatibility mode.
	 *
	 * > In no-quirks mode, the behavior is (hopefully) the desired behavior
	 * > described by the modern HTML and CSS specifications.
	 *
	 * @see self::$compat_mode
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Quirks_Mode_and_Standards_Mode
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const NO_QUIRKS_MODE = 'no-quirks-mode';

	/**
	 * Quirks mode document compatibility mode.
	 *
	 * > In quirks mode, layout emulates behavior in Navigator 4 and Internet
	 * > Explorer 5. This is essential in order to support websites that were
	 * > built before the widespread adoption of web standards.
	 *
	 * @see self::$compat_mode
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Quirks_Mode_and_Standards_Mode
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const QUIRKS_MODE = 'quirks-mode';

	/**
	 * Indicates that a span of text may contain any combination of significant
	 * kinds of characters: NULL bytes, whitespace, and others.
	 *
	 * @see self::$text_node_classification
	 * @see self::subdivide_text_appropriately
	 *
	 * @since 6.7.0
	 */
	const TEXT_IS_GENERIC = 'TEXT_IS_GENERIC';

	/**
	 * Indicates that a span of text comprises a sequence only of NULL bytes.
	 *
	 * @see self::$text_node_classification
	 * @see self::subdivide_text_appropriately
	 *
	 * @since 6.7.0
	 */
	const TEXT_IS_NULL_SEQUENCE = 'TEXT_IS_NULL_SEQUENCE';

	/**
	 * Indicates that a span of decoded text comprises only whitespace.
	 *
	 * @see self::$text_node_classification
	 * @see self::subdivide_text_appropriately
	 *
	 * @since 6.7.0
	 */
	const TEXT_IS_WHITESPACE = 'TEXT_IS_WHITESPACE';

	/**
	 * Wakeup magic method.
	 *
	 * @since 6.9.2
	 */
	public function __wakeup() {
		throw new \LogicException( __CLASS__ . ' should never be unserialized' );
	}
}
