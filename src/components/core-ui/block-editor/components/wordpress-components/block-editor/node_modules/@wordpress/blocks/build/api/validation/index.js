"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isValidCharacterReference = isValidCharacterReference;
exports.getTextPiecesSplitOnWhitespace = getTextPiecesSplitOnWhitespace;
exports.getTextWithCollapsedWhitespace = getTextWithCollapsedWhitespace;
exports.getMeaningfulAttributePairs = getMeaningfulAttributePairs;
exports.isEquivalentTextTokens = isEquivalentTextTokens;
exports.getNormalizedStyleValue = getNormalizedStyleValue;
exports.getStyleProperties = getStyleProperties;
exports.isEqualTagAttributePairs = isEqualTagAttributePairs;
exports.getNextNonWhitespaceToken = getNextNonWhitespaceToken;
exports.isClosedByToken = isClosedByToken;
exports.isEquivalentHTML = isEquivalentHTML;
exports.getBlockContentValidationResult = getBlockContentValidationResult;
exports.isValidBlockContent = isValidBlockContent;
exports.isEqualTokensOfType = exports.isEqualAttributesOfName = exports.DecodeEntityParser = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _toArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _simpleHtmlTokenizer = require("simple-html-tokenizer");

var _lodash = require("lodash");

var _htmlEntities = require("@wordpress/html-entities");

var _logger = require("./logger");

var _serializer = require("../serializer");

var _utils = require("../utils");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Globally matches any consecutive whitespace
 *
 * @type {RegExp}
 */
var REGEXP_WHITESPACE = /[\t\n\r\v\f ]+/g;
/**
 * Matches a string containing only whitespace
 *
 * @type {RegExp}
 */

var REGEXP_ONLY_WHITESPACE = /^[\t\n\r\v\f ]*$/;
/**
 * Matches a CSS URL type value
 *
 * @type {RegExp}
 */

var REGEXP_STYLE_URL_TYPE = /^url\s*\(['"\s]*(.*?)['"\s]*\)$/;
/**
 * Boolean attributes are attributes whose presence as being assigned is
 * meaningful, even if only empty.
 *
 * See: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attributes
 * Extracted from: https://html.spec.whatwg.org/multipage/indices.html#attributes-3
 *
 * Object.keys( Array.from( document.querySelectorAll( '#attributes-1 > tbody > tr' ) )
 *     .filter( ( tr ) => tr.lastChild.textContent.indexOf( 'Boolean attribute' ) !== -1 )
 *     .reduce( ( result, tr ) => Object.assign( result, {
 *         [ tr.firstChild.textContent.trim() ]: true
 *     } ), {} ) ).sort();
 *
 * @type {Array}
 */

var BOOLEAN_ATTRIBUTES = ['allowfullscreen', 'allowpaymentrequest', 'allowusermedia', 'async', 'autofocus', 'autoplay', 'checked', 'controls', 'default', 'defer', 'disabled', 'download', 'formnovalidate', 'hidden', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'playsinline', 'readonly', 'required', 'reversed', 'selected', 'typemustmatch'];
/**
 * Enumerated attributes are attributes which must be of a specific value form.
 * Like boolean attributes, these are meaningful if specified, even if not of a
 * valid enumerated value.
 *
 * See: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#enumerated-attribute
 * Extracted from: https://html.spec.whatwg.org/multipage/indices.html#attributes-3
 *
 * Object.keys( Array.from( document.querySelectorAll( '#attributes-1 > tbody > tr' ) )
 *     .filter( ( tr ) => /^("(.+?)";?\s*)+/.test( tr.lastChild.textContent.trim() ) )
 *     .reduce( ( result, tr ) => Object.assign( result, {
 *         [ tr.firstChild.textContent.trim() ]: true
 *     } ), {} ) ).sort();
 *
 * @type {Array}
 */

var ENUMERATED_ATTRIBUTES = ['autocapitalize', 'autocomplete', 'charset', 'contenteditable', 'crossorigin', 'decoding', 'dir', 'draggable', 'enctype', 'formenctype', 'formmethod', 'http-equiv', 'inputmode', 'kind', 'method', 'preload', 'scope', 'shape', 'spellcheck', 'translate', 'type', 'wrap'];
/**
 * Meaningful attributes are those who cannot be safely ignored when omitted in
 * one HTML markup string and not another.
 *
 * @type {Array}
 */

var MEANINGFUL_ATTRIBUTES = [].concat(BOOLEAN_ATTRIBUTES, ENUMERATED_ATTRIBUTES);
/**
 * Array of functions which receive a text string on which to apply normalizing
 * behavior for consideration in text token equivalence, carefully ordered from
 * least-to-most expensive operations.
 *
 * @type {Array}
 */

var TEXT_NORMALIZATIONS = [_lodash.identity, getTextWithCollapsedWhitespace];
/**
 * Regular expression matching a named character reference. In lieu of bundling
 * a full set of references, the pattern covers the minimal necessary to test
 * positively against the full set.
 *
 * "The ampersand must be followed by one of the names given in the named
 * character references section, using the same case."
 *
 * Tested aginst "12.5 Named character references":
 *
 * ```
 * const references = Array.from( document.querySelectorAll(
 *     '#named-character-references-table tr[id^=entity-] td:first-child'
 * ) ).map( ( code ) => code.textContent )
 * references.every( ( reference ) => /^[\da-z]+$/i.test( reference ) )
 * ```
 *
 * @see https://html.spec.whatwg.org/multipage/syntax.html#character-references
 * @see https://html.spec.whatwg.org/multipage/named-characters.html#named-character-references
 *
 * @type {RegExp}
 */

var REGEXP_NAMED_CHARACTER_REFERENCE = /^[\da-z]+$/i;
/**
 * Regular expression matching a decimal character reference.
 *
 * "The ampersand must be followed by a U+0023 NUMBER SIGN character (#),
 * followed by one or more ASCII digits, representing a base-ten integer"
 *
 * @see https://html.spec.whatwg.org/multipage/syntax.html#character-references
 *
 * @type {RegExp}
 */

var REGEXP_DECIMAL_CHARACTER_REFERENCE = /^#\d+$/;
/**
 * Regular expression matching a hexadecimal character reference.
 *
 * "The ampersand must be followed by a U+0023 NUMBER SIGN character (#), which
 * must be followed by either a U+0078 LATIN SMALL LETTER X character (x) or a
 * U+0058 LATIN CAPITAL LETTER X character (X), which must then be followed by
 * one or more ASCII hex digits, representing a hexadecimal integer"
 *
 * @see https://html.spec.whatwg.org/multipage/syntax.html#character-references
 *
 * @type {RegExp}
 */

var REGEXP_HEXADECIMAL_CHARACTER_REFERENCE = /^#x[\da-f]+$/i;
/**
 * Returns true if the given string is a valid character reference segment, or
 * false otherwise. The text should be stripped of `&` and `;` demarcations.
 *
 * @param {string} text Text to test.
 *
 * @return {boolean} Whether text is valid character reference.
 */

function isValidCharacterReference(text) {
  return REGEXP_NAMED_CHARACTER_REFERENCE.test(text) || REGEXP_DECIMAL_CHARACTER_REFERENCE.test(text) || REGEXP_HEXADECIMAL_CHARACTER_REFERENCE.test(text);
}
/**
 * Subsitute EntityParser class for `simple-html-tokenizer` which uses the
 * implementation of `decodeEntities` from `html-entities`, in order to avoid
 * bundling a massive named character reference.
 *
 * @see https://github.com/tildeio/simple-html-tokenizer/tree/master/src/entity-parser.ts
 */


var DecodeEntityParser = /*#__PURE__*/function () {
  function DecodeEntityParser() {
    (0, _classCallCheck2.default)(this, DecodeEntityParser);
  }

  (0, _createClass2.default)(DecodeEntityParser, [{
    key: "parse",

    /**
     * Returns a substitute string for an entity string sequence between `&`
     * and `;`, or undefined if no substitution should occur.
     *
     * @param {string} entity Entity fragment discovered in HTML.
     *
     * @return {?string} Entity substitute value.
     */
    value: function parse(entity) {
      if (isValidCharacterReference(entity)) {
        return (0, _htmlEntities.decodeEntities)('&' + entity + ';');
      }
    }
  }]);
  return DecodeEntityParser;
}();
/**
 * Given a specified string, returns an array of strings split by consecutive
 * whitespace, ignoring leading or trailing whitespace.
 *
 * @param {string} text Original text.
 *
 * @return {string[]} Text pieces split on whitespace.
 */


exports.DecodeEntityParser = DecodeEntityParser;

function getTextPiecesSplitOnWhitespace(text) {
  return text.trim().split(REGEXP_WHITESPACE);
}
/**
 * Given a specified string, returns a new trimmed string where all consecutive
 * whitespace is collapsed to a single space.
 *
 * @param {string} text Original text.
 *
 * @return {string} Trimmed text with consecutive whitespace collapsed.
 */


function getTextWithCollapsedWhitespace(text) {
  // This is an overly simplified whitespace comparison. The specification is
  // more prescriptive of whitespace behavior in inline and block contexts.
  //
  // See: https://medium.com/@patrickbrosset/when-does-white-space-matter-in-html-b90e8a7cdd33
  return getTextPiecesSplitOnWhitespace(text).join(' ');
}
/**
 * Returns attribute pairs of the given StartTag token, including only pairs
 * where the value is non-empty or the attribute is a boolean attribute, an
 * enumerated attribute, or a custom data- attribute.
 *
 * @see MEANINGFUL_ATTRIBUTES
 *
 * @param {Object} token StartTag token.
 *
 * @return {Array[]} Attribute pairs.
 */


function getMeaningfulAttributePairs(token) {
  return token.attributes.filter(function (pair) {
    var _pair = (0, _slicedToArray2.default)(pair, 2),
        key = _pair[0],
        value = _pair[1];

    return value || key.indexOf('data-') === 0 || (0, _lodash.includes)(MEANINGFUL_ATTRIBUTES, key);
  });
}
/**
 * Returns true if two text tokens (with `chars` property) are equivalent, or
 * false otherwise.
 *
 * @param {Object} actual   Actual token.
 * @param {Object} expected Expected token.
 * @param {Object} logger   Validation logger object.
 *
 * @return {boolean} Whether two text tokens are equivalent.
 */


function isEquivalentTextTokens(actual, expected) {
  var logger = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : (0, _logger.createLogger)();
  // This function is intentionally written as syntactically "ugly" as a hot
  // path optimization. Text is progressively normalized in order from least-
  // to-most operationally expensive, until the earliest point at which text
  // can be confidently inferred as being equal.
  var actualChars = actual.chars;
  var expectedChars = expected.chars;

  for (var i = 0; i < TEXT_NORMALIZATIONS.length; i++) {
    var normalize = TEXT_NORMALIZATIONS[i];
    actualChars = normalize(actualChars);
    expectedChars = normalize(expectedChars);

    if (actualChars === expectedChars) {
      return true;
    }
  }

  logger.warning('Expected text `%s`, saw `%s`.', expected.chars, actual.chars);
  return false;
}
/**
 * Given a style value, returns a normalized style value for strict equality
 * comparison.
 *
 * @param {string} value Style value.
 *
 * @return {string} Normalized style value.
 */


function getNormalizedStyleValue(value) {
  return value // Normalize URL type to omit whitespace or quotes
  .replace(REGEXP_STYLE_URL_TYPE, 'url($1)');
}
/**
 * Given a style attribute string, returns an object of style properties.
 *
 * @param {string} text Style attribute.
 *
 * @return {Object} Style properties.
 */


function getStyleProperties(text) {
  var pairs = text // Trim ending semicolon (avoid including in split)
  .replace(/;?\s*$/, '') // Split on property assignment
  .split(';') // For each property assignment...
  .map(function (style) {
    // ...split further into key-value pairs
    var _style$split = style.split(':'),
        _style$split2 = (0, _toArray2.default)(_style$split),
        key = _style$split2[0],
        valueParts = _style$split2.slice(1);

    var value = valueParts.join(':');
    return [key.trim(), getNormalizedStyleValue(value.trim())];
  });
  return (0, _lodash.fromPairs)(pairs);
}
/**
 * Attribute-specific equality handlers
 *
 * @type {Object}
 */


var isEqualAttributesOfName = _objectSpread({
  class: function _class(actual, expected) {
    // Class matches if members are the same, even if out of order or
    // superfluous whitespace between.
    return !_lodash.xor.apply(void 0, (0, _toConsumableArray2.default)([actual, expected].map(getTextPiecesSplitOnWhitespace))).length;
  },
  style: function style(actual, expected) {
    return _lodash.isEqual.apply(void 0, (0, _toConsumableArray2.default)([actual, expected].map(getStyleProperties)));
  }
}, (0, _lodash.fromPairs)(BOOLEAN_ATTRIBUTES.map(function (attribute) {
  return [attribute, _lodash.stubTrue];
})));
/**
 * Given two sets of attribute tuples, returns true if the attribute sets are
 * equivalent.
 *
 * @param {Array[]} actual   Actual attributes tuples.
 * @param {Array[]} expected Expected attributes tuples.
 * @param {Object}  logger   Validation logger object.
 *
 * @return {boolean} Whether attributes are equivalent.
 */


exports.isEqualAttributesOfName = isEqualAttributesOfName;

function isEqualTagAttributePairs(actual, expected) {
  var logger = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : (0, _logger.createLogger)();

  // Attributes is tokenized as tuples. Their lengths should match. This also
  // avoids us needing to check both attributes sets, since if A has any keys
  // which do not exist in B, we know the sets to be different.
  if (actual.length !== expected.length) {
    logger.warning('Expected attributes %o, instead saw %o.', expected, actual);
    return false;
  } // Attributes are not guaranteed to occur in the same order. For validating
  // actual attributes, first convert the set of expected attribute values to
  // an object, for lookup by key.


  var expectedAttributes = {};

  for (var i = 0; i < expected.length; i++) {
    expectedAttributes[expected[i][0].toLowerCase()] = expected[i][1];
  }

  for (var _i = 0; _i < actual.length; _i++) {
    var _actual$_i = (0, _slicedToArray2.default)(actual[_i], 2),
        name = _actual$_i[0],
        actualValue = _actual$_i[1];

    var nameLower = name.toLowerCase(); // As noted above, if missing member in B, assume different

    if (!expectedAttributes.hasOwnProperty(nameLower)) {
      logger.warning('Encountered unexpected attribute `%s`.', name);
      return false;
    }

    var expectedValue = expectedAttributes[nameLower];
    var isEqualAttributes = isEqualAttributesOfName[nameLower];

    if (isEqualAttributes) {
      // Defer custom attribute equality handling
      if (!isEqualAttributes(actualValue, expectedValue)) {
        logger.warning('Expected attribute `%s` of value `%s`, saw `%s`.', name, expectedValue, actualValue);
        return false;
      }
    } else if (actualValue !== expectedValue) {
      // Otherwise strict inequality should bail
      logger.warning('Expected attribute `%s` of value `%s`, saw `%s`.', name, expectedValue, actualValue);
      return false;
    }
  }

  return true;
}
/**
 * Token-type-specific equality handlers
 *
 * @type {Object}
 */


var isEqualTokensOfType = {
  StartTag: function StartTag(actual, expected) {
    var logger = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : (0, _logger.createLogger)();

    if (actual.tagName !== expected.tagName && // Optimization: Use short-circuit evaluation to defer case-
    // insensitive check on the assumption that the majority case will
    // have exactly equal tag names.
    actual.tagName.toLowerCase() !== expected.tagName.toLowerCase()) {
      logger.warning('Expected tag name `%s`, instead saw `%s`.', expected.tagName, actual.tagName);
      return false;
    }

    return isEqualTagAttributePairs.apply(void 0, (0, _toConsumableArray2.default)([actual, expected].map(getMeaningfulAttributePairs)).concat([logger]));
  },
  Chars: isEquivalentTextTokens,
  Comment: isEquivalentTextTokens
};
/**
 * Given an array of tokens, returns the first token which is not purely
 * whitespace.
 *
 * Mutates the tokens array.
 *
 * @param {Object[]} tokens Set of tokens to search.
 *
 * @return {Object} Next non-whitespace token.
 */

exports.isEqualTokensOfType = isEqualTokensOfType;

function getNextNonWhitespaceToken(tokens) {
  var token;

  while (token = tokens.shift()) {
    if (token.type !== 'Chars') {
      return token;
    }

    if (!REGEXP_ONLY_WHITESPACE.test(token.chars)) {
      return token;
    }
  }
}
/**
 * Tokenize an HTML string, gracefully handling any errors thrown during
 * underlying tokenization.
 *
 * @param {string} html   HTML string to tokenize.
 * @param {Object} logger Validation logger object.
 *
 * @return {Object[]|null} Array of valid tokenized HTML elements, or null on error
 */


function getHTMLTokens(html) {
  var logger = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : (0, _logger.createLogger)();

  try {
    return new _simpleHtmlTokenizer.Tokenizer(new DecodeEntityParser()).tokenize(html);
  } catch (e) {
    logger.warning('Malformed HTML detected: %s', html);
  }

  return null;
}
/**
 * Returns true if the next HTML token closes the current token.
 *
 * @param {Object} currentToken Current token to compare with.
 * @param {Object|undefined} nextToken Next token to compare against.
 *
 * @return {boolean} true if `nextToken` closes `currentToken`, false otherwise
 */


function isClosedByToken(currentToken, nextToken) {
  // Ensure this is a self closed token
  if (!currentToken.selfClosing) {
    return false;
  } // Check token names and determine if nextToken is the closing tag for currentToken


  if (nextToken && nextToken.tagName === currentToken.tagName && nextToken.type === 'EndTag') {
    return true;
  }

  return false;
}
/**
 * Returns true if the given HTML strings are effectively equivalent, or
 * false otherwise. Invalid HTML is not considered equivalent, even if the
 * strings directly match.
 *
 * @param {string} actual   Actual HTML string.
 * @param {string} expected Expected HTML string.
 * @param {Object} logger   Validation logger object.
 *
 * @return {boolean} Whether HTML strings are equivalent.
 */


function isEquivalentHTML(actual, expected) {
  var logger = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : (0, _logger.createLogger)();

  // Short-circuit if markup is identical.
  if (actual === expected) {
    return true;
  } // Tokenize input content and reserialized save content


  var _map = [actual, expected].map(function (html) {
    return getHTMLTokens(html, logger);
  }),
      _map2 = (0, _slicedToArray2.default)(_map, 2),
      actualTokens = _map2[0],
      expectedTokens = _map2[1]; // If either is malformed then stop comparing - the strings are not equivalent


  if (!actualTokens || !expectedTokens) {
    return false;
  }

  var actualToken, expectedToken;

  while (actualToken = getNextNonWhitespaceToken(actualTokens)) {
    expectedToken = getNextNonWhitespaceToken(expectedTokens); // Inequal if exhausted all expected tokens

    if (!expectedToken) {
      logger.warning('Expected end of content, instead saw %o.', actualToken);
      return false;
    } // Inequal if next non-whitespace token of each set are not same type


    if (actualToken.type !== expectedToken.type) {
      logger.warning('Expected token of type `%s` (%o), instead saw `%s` (%o).', expectedToken.type, expectedToken, actualToken.type, actualToken);
      return false;
    } // Defer custom token type equality handling, otherwise continue and
    // assume as equal


    var isEqualTokens = isEqualTokensOfType[actualToken.type];

    if (isEqualTokens && !isEqualTokens(actualToken, expectedToken, logger)) {
      return false;
    } // Peek at the next tokens (actual and expected) to see if they close
    // a self-closing tag


    if (isClosedByToken(actualToken, expectedTokens[0])) {
      // Consume the next expected token that closes the current actual
      // self-closing token
      getNextNonWhitespaceToken(expectedTokens);
    } else if (isClosedByToken(expectedToken, actualTokens[0])) {
      // Consume the next actual token that closes the current expected
      // self-closing token
      getNextNonWhitespaceToken(actualTokens);
    }
  }

  if (expectedToken = getNextNonWhitespaceToken(expectedTokens)) {
    // If any non-whitespace tokens remain in expected token set, this
    // indicates inequality
    logger.warning('Expected %o, instead saw end of content.', expectedToken);
    return false;
  }

  return true;
}
/**
 * Returns an object with `isValid` property set to `true` if the parsed block
 * is valid given the input content. A block is considered valid if, when serialized
 * with assumed attributes, the content matches the original value. If block is
 * invalid, this function returns all validations issues as well.
 *
 * @param {string|Object} blockTypeOrName      Block type.
 * @param {Object}        attributes           Parsed block attributes.
 * @param {string}        originalBlockContent Original block content.
 * @param {Object}        logger           	   Validation logger object.
 *
 * @return {Object} Whether block is valid and contains validation messages.
 */


function getBlockContentValidationResult(blockTypeOrName, attributes, originalBlockContent) {
  var logger = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : (0, _logger.createQueuedLogger)();
  var blockType = (0, _utils.normalizeBlockType)(blockTypeOrName);
  var generatedBlockContent;

  try {
    generatedBlockContent = (0, _serializer.getSaveContent)(blockType, attributes);
  } catch (error) {
    logger.error('Block validation failed because an error occurred while generating block content:\n\n%s', error.toString());
    return {
      isValid: false,
      validationIssues: logger.getItems()
    };
  }

  var isValid = isEquivalentHTML(originalBlockContent, generatedBlockContent, logger);

  if (!isValid) {
    logger.error('Block validation failed for `%s` (%o).\n\nContent generated by `save` function:\n\n%s\n\nContent retrieved from post body:\n\n%s', blockType.name, blockType, generatedBlockContent, originalBlockContent);
  }

  return {
    isValid: isValid,
    validationIssues: logger.getItems()
  };
}
/**
 * Returns true if the parsed block is valid given the input content. A block
 * is considered valid if, when serialized with assumed attributes, the content
 * matches the original value.
 *
 * Logs to console in development environments when invalid.
 *
 * @param {string|Object} blockTypeOrName      Block type.
 * @param {Object}        attributes           Parsed block attributes.
 * @param {string}        originalBlockContent Original block content.
 *
 * @return {boolean} Whether block is valid.
 */


function isValidBlockContent(blockTypeOrName, attributes, originalBlockContent) {
  var _getBlockContentValid = getBlockContentValidationResult(blockTypeOrName, attributes, originalBlockContent, (0, _logger.createLogger)()),
      isValid = _getBlockContentValid.isValid;

  return isValid;
}
//# sourceMappingURL=index.js.map