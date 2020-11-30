"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.pasteHandler = pasteHandler;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _dom = require("@wordpress/dom");

var _factory = require("../factory");

var _registration = require("../registration");

var _serializer = require("../serializer");

var _parser = require("../parser");

var _normaliseBlocks = _interopRequireDefault(require("./normalise-blocks"));

var _specialCommentConverter = _interopRequireDefault(require("./special-comment-converter"));

var _commentRemover = _interopRequireDefault(require("./comment-remover"));

var _isInlineContent = _interopRequireDefault(require("./is-inline-content"));

var _phrasingContentReducer = _interopRequireDefault(require("./phrasing-content-reducer"));

var _headRemover = _interopRequireDefault(require("./head-remover"));

var _msListConverter = _interopRequireDefault(require("./ms-list-converter"));

var _listReducer = _interopRequireDefault(require("./list-reducer"));

var _imageCorrector = _interopRequireDefault(require("./image-corrector"));

var _blockquoteNormaliser = _interopRequireDefault(require("./blockquote-normaliser"));

var _figureContentReducer = _interopRequireDefault(require("./figure-content-reducer"));

var _shortcodeConverter = _interopRequireDefault(require("./shortcode-converter"));

var _markdownConverter = _interopRequireDefault(require("./markdown-converter"));

var _iframeRemover = _interopRequireDefault(require("./iframe-remover"));

var _googleDocsUidRemover = _interopRequireDefault(require("./google-docs-uid-remover"));

var _htmlFormattingRemover = _interopRequireDefault(require("./html-formatting-remover"));

var _brRemover = _interopRequireDefault(require("./br-remover"));

var _utils = require("./utils");

var _emptyParagraphRemover = _interopRequireDefault(require("./empty-paragraph-remover"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Browser dependencies
 */
var _window = window,
    console = _window.console;
/**
 * Filters HTML to only contain phrasing content.
 *
 * @param {string} HTML The HTML to filter.
 *
 * @return {string} HTML only containing phrasing content.
 */

function filterInlineHTML(HTML) {
  HTML = (0, _utils.deepFilterHTML)(HTML, [_googleDocsUidRemover.default, _phrasingContentReducer.default, _commentRemover.default]);
  HTML = (0, _dom.removeInvalidHTML)(HTML, (0, _dom.getPhrasingContentSchema)('paste'), {
    inline: true
  });
  HTML = (0, _utils.deepFilterHTML)(HTML, [_htmlFormattingRemover.default, _brRemover.default]); // Allows us to ask for this information when we get a report.

  console.log('Processed inline HTML:\n\n', HTML);
  return HTML;
}

function getRawTransformations() {
  return (0, _lodash.filter)((0, _factory.getBlockTransforms)('from'), {
    type: 'raw'
  }).map(function (transform) {
    return transform.isMatch ? transform : _objectSpread({}, transform, {
      isMatch: function isMatch(node) {
        return transform.selector && node.matches(transform.selector);
      }
    });
  });
}
/**
 * Converts HTML directly to blocks. Looks for a matching transform for each
 * top-level tag. The HTML should be filtered to not have any text between
 * top-level tags and formatted in a way that blocks can handle the HTML.
 *
 * @param  {Object} $1               Named parameters.
 * @param  {string} $1.html          HTML to convert.
 * @param  {Array}  $1.rawTransforms Transforms that can be used.
 *
 * @return {Array} An array of blocks.
 */


function htmlToBlocks(_ref) {
  var html = _ref.html,
      rawTransforms = _ref.rawTransforms;
  var doc = document.implementation.createHTMLDocument('');
  doc.body.innerHTML = html;
  return Array.from(doc.body.children).map(function (node) {
    var rawTransform = (0, _factory.findTransform)(rawTransforms, function (_ref2) {
      var isMatch = _ref2.isMatch;
      return isMatch(node);
    });

    if (!rawTransform) {
      return (0, _factory.createBlock)( // Should not be hardcoded.
      'core/html', (0, _parser.getBlockAttributes)('core/html', node.outerHTML));
    }

    var transform = rawTransform.transform,
        blockName = rawTransform.blockName;

    if (transform) {
      return transform(node);
    }

    return (0, _factory.createBlock)(blockName, (0, _parser.getBlockAttributes)(blockName, node.outerHTML));
  });
}
/**
 * Converts an HTML string to known blocks. Strips everything else.
 *
 * @param {Object}  options
 * @param {string}  [options.HTML]      The HTML to convert.
 * @param {string}  [options.plainText] Plain text version.
 * @param {string}  [options.mode]      Handle content as blocks or inline content.
 *                                      * 'AUTO': Decide based on the content passed.
 *                                      * 'INLINE': Always handle as inline content, and return string.
 *                                      * 'BLOCKS': Always handle as blocks, and return array of blocks.
 * @param {Array}   [options.tagName]   The tag into which content will be inserted.
 *
 * @return {Array|string} A list of blocks or a string, depending on `handlerMode`.
 */


function pasteHandler(_ref3) {
  var _ref3$HTML = _ref3.HTML,
      HTML = _ref3$HTML === void 0 ? '' : _ref3$HTML,
      _ref3$plainText = _ref3.plainText,
      plainText = _ref3$plainText === void 0 ? '' : _ref3$plainText,
      _ref3$mode = _ref3.mode,
      mode = _ref3$mode === void 0 ? 'AUTO' : _ref3$mode,
      tagName = _ref3.tagName;
  // First of all, strip any meta tags.
  HTML = HTML.replace(/<meta[^>]+>/g, ''); // Strip Windows markers.

  HTML = HTML.replace(/^\s*<html[^>]*>\s*<body[^>]*>(?:\s*<!--\s*StartFragment\s*-->)?/i, '');
  HTML = HTML.replace(/(?:<!--\s*EndFragment\s*-->\s*)?<\/body>\s*<\/html>\s*$/i, ''); // If we detect block delimiters in HTML, parse entirely as blocks.

  if (mode !== 'INLINE') {
    // Check plain text if there is no HTML.
    var content = HTML ? HTML : plainText;

    if (content.indexOf('<!-- wp:') !== -1) {
      return (0, _parser.parseWithGrammar)(content);
    }
  } // Normalize unicode to use composed characters.
  // This is unsupported in IE 11 but it's a nice-to-have feature, not mandatory.
  // Not normalizing the content will only affect older browsers and won't
  // entirely break the app.
  // See: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/normalize
  // See: https://core.trac.wordpress.org/ticket/30130
  // See: https://github.com/WordPress/gutenberg/pull/6983#pullrequestreview-125151075


  if (String.prototype.normalize) {
    HTML = HTML.normalize();
  } // Parse Markdown (and encoded HTML) if:
  // * There is a plain text version.
  // * There is no HTML version, or it has no formatting.


  if (plainText && (!HTML || (0, _utils.isPlain)(HTML))) {
    HTML = (0, _markdownConverter.default)(plainText); // Switch to inline mode if:
    // * The current mode is AUTO.
    // * The original plain text had no line breaks.
    // * The original plain text was not an HTML paragraph.
    // * The converted text is just a paragraph.

    if (mode === 'AUTO' && plainText.indexOf('\n') === -1 && plainText.indexOf('<p>') !== 0 && HTML.indexOf('<p>') === 0) {
      mode = 'INLINE';
    }
  }

  if (mode === 'INLINE') {
    return filterInlineHTML(HTML);
  } // An array of HTML strings and block objects. The blocks replace matched
  // shortcodes.


  var pieces = (0, _shortcodeConverter.default)(HTML); // The call to shortcodeConverter will always return more than one element
  // if shortcodes are matched. The reason is when shortcodes are matched
  // empty HTML strings are included.

  var hasShortcodes = pieces.length > 1;

  if (mode === 'AUTO' && !hasShortcodes && (0, _isInlineContent.default)(HTML, tagName)) {
    return filterInlineHTML(HTML);
  }

  var rawTransforms = getRawTransformations();
  var phrasingContentSchema = (0, _dom.getPhrasingContentSchema)('paste');
  var blockContentSchema = (0, _utils.getBlockContentSchema)(rawTransforms, phrasingContentSchema, true);
  var blocks = (0, _lodash.compact)((0, _lodash.flatMap)(pieces, function (piece) {
    // Already a block from shortcode.
    if (typeof piece !== 'string') {
      return piece;
    }

    var filters = [_googleDocsUidRemover.default, _msListConverter.default, _headRemover.default, _listReducer.default, _imageCorrector.default, _phrasingContentReducer.default, _specialCommentConverter.default, _commentRemover.default, _iframeRemover.default, _figureContentReducer.default, _blockquoteNormaliser.default];

    var schema = _objectSpread({}, blockContentSchema, {}, phrasingContentSchema);

    piece = (0, _utils.deepFilterHTML)(piece, filters, blockContentSchema);
    piece = (0, _dom.removeInvalidHTML)(piece, schema);
    piece = (0, _normaliseBlocks.default)(piece);
    piece = (0, _utils.deepFilterHTML)(piece, [_htmlFormattingRemover.default, _brRemover.default, _emptyParagraphRemover.default], blockContentSchema); // Allows us to ask for this information when we get a report.

    console.log('Processed HTML piece:\n\n', piece);
    return htmlToBlocks({
      html: piece,
      rawTransforms: rawTransforms
    });
  })); // If we're allowed to return inline content, and there is only one inlineable block,
  // and the original plain text content does not have any line breaks, then
  // treat it as inline paste.

  if (mode === 'AUTO' && blocks.length === 1 && (0, _registration.hasBlockSupport)(blocks[0].name, '__unstablePasteTextInline', false)) {
    var trimmedPlainText = plainText.trim();

    if (trimmedPlainText !== '' && trimmedPlainText.indexOf('\n') === -1) {
      return (0, _dom.removeInvalidHTML)((0, _serializer.getBlockContent)(blocks[0]), phrasingContentSchema);
    }
  }

  return blocks;
}
//# sourceMappingURL=paste-handler.js.map