import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { flatMap, filter, compact } from 'lodash';
/**
 * WordPress dependencies
 */

import deprecated from '@wordpress/deprecated';
import { getPhrasingContentSchema } from '@wordpress/dom';
/**
 * Internal dependencies
 */

import { createBlock, getBlockTransforms, findTransform } from '../factory';
import { getBlockAttributes, parseWithGrammar } from '../parser';
import normaliseBlocks from './normalise-blocks';
import specialCommentConverter from './special-comment-converter';
import listReducer from './list-reducer';
import blockquoteNormaliser from './blockquote-normaliser';
import figureContentReducer from './figure-content-reducer';
import shortcodeConverter from './shortcode-converter';
import { deepFilterHTML, getBlockContentSchema } from './utils';
export { pasteHandler } from './paste-handler';
export function deprecatedGetPhrasingContentSchema(context) {
  deprecated('wp.blocks.getPhrasingContentSchema', {
    alternative: 'wp.dom.getPhrasingContentSchema'
  });
  return getPhrasingContentSchema(context);
}

function getRawTransformations() {
  return filter(getBlockTransforms('from'), {
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
    var rawTransform = findTransform(rawTransforms, function (_ref2) {
      var isMatch = _ref2.isMatch;
      return isMatch(node);
    });

    if (!rawTransform) {
      return createBlock( // Should not be hardcoded.
      'core/html', getBlockAttributes('core/html', node.outerHTML));
    }

    var transform = rawTransform.transform,
        blockName = rawTransform.blockName;

    if (transform) {
      return transform(node);
    }

    return createBlock(blockName, getBlockAttributes(blockName, node.outerHTML));
  });
}
/**
 * Converts an HTML string to known blocks.
 *
 * @param {Object} $1
 * @param {string} $1.HTML The HTML to convert.
 *
 * @return {Array} A list of blocks.
 */


export function rawHandler(_ref3) {
  var _ref3$HTML = _ref3.HTML,
      HTML = _ref3$HTML === void 0 ? '' : _ref3$HTML;

  // If we detect block delimiters, parse entirely as blocks.
  if (HTML.indexOf('<!-- wp:') !== -1) {
    return parseWithGrammar(HTML);
  } // An array of HTML strings and block objects. The blocks replace matched
  // shortcodes.


  var pieces = shortcodeConverter(HTML);
  var rawTransforms = getRawTransformations();
  var phrasingContentSchema = getPhrasingContentSchema();
  var blockContentSchema = getBlockContentSchema(rawTransforms, phrasingContentSchema);
  return compact(flatMap(pieces, function (piece) {
    // Already a block from shortcode.
    if (typeof piece !== 'string') {
      return piece;
    } // These filters are essential for some blocks to be able to transform
    // from raw HTML. These filters move around some content or add
    // additional tags, they do not remove any content.


    var filters = [// Needed to adjust invalid lists.
    listReducer, // Needed to create more and nextpage blocks.
    specialCommentConverter, // Needed to create media blocks.
    figureContentReducer, // Needed to create the quote block, which cannot handle text
    // without wrapper paragraphs.
    blockquoteNormaliser];
    piece = deepFilterHTML(piece, filters, blockContentSchema);
    piece = normaliseBlocks(piece);
    return htmlToBlocks({
      html: piece,
      rawTransforms: rawTransforms
    });
  }));
}
//# sourceMappingURL=index.js.map