"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.transformValue = transformValue;
exports.getItemId = getItemId;
exports.getAlignmentIndex = getAlignmentIndex;
exports.ALIGNMENTS = exports.ALIGNMENT_LABEL = exports.GRID = void 0;

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var GRID = [['top left', 'top center', 'top right'], ['center left', 'center center', 'center right'], ['bottom left', 'bottom center', 'bottom right']]; // Stored as map as i18n __() only accepts strings (not variables)

exports.GRID = GRID;
var ALIGNMENT_LABEL = {
  'top left': (0, _i18n.__)('Top Left'),
  'top center': (0, _i18n.__)('Top Center'),
  'top right': (0, _i18n.__)('Top Right'),
  'center left': (0, _i18n.__)('Center Left'),
  'center center': (0, _i18n.__)('Center Center'),
  'center right': (0, _i18n.__)('Center Right'),
  'bottom left': (0, _i18n.__)('Bottom Left'),
  'bottom center': (0, _i18n.__)('Bottom Center'),
  'bottom right': (0, _i18n.__)('Bottom Right')
}; // Transforms GRID into a flat Array of values

exports.ALIGNMENT_LABEL = ALIGNMENT_LABEL;
var ALIGNMENTS = (0, _lodash.flattenDeep)(GRID);
/**
 * Parses and transforms an incoming value to better match the alignment values
 *
 * @param {string} value An alignment value to parse.
 *
 * @return {string} The parsed value.
 */

exports.ALIGNMENTS = ALIGNMENTS;

function transformValue(value) {
  var nextValue = value === 'center' ? 'center center' : value;
  return nextValue.replace('-', ' ');
}
/**
 * Creates an item ID based on a prefix ID and an alignment value.
 *
 * @param {string} prefixId An ID to prefix.
 * @param {string} value An alignment value.
 *
 * @return {string} The item id.
 */


function getItemId(prefixId, value) {
  var valueId = transformValue(value).replace(' ', '-');
  return "".concat(prefixId, "-").concat(valueId);
}
/**
 * Retrieves the alignment index from a value.
 *
 * @param {string} alignment Value to check.
 *
 * @return {number} The index of a matching alignment.
 */


function getAlignmentIndex() {
  var alignment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'center';
  var item = transformValue(alignment).replace('-', ' ');
  var index = ALIGNMENTS.indexOf(item);
  return index > -1 ? index : undefined;
}
//# sourceMappingURL=utils.js.map