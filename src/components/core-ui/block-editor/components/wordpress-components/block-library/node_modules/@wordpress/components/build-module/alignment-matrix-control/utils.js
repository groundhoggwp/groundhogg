/**
 * External dependencies
 */
import { flattenDeep } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
export var GRID = [['top left', 'top center', 'top right'], ['center left', 'center center', 'center right'], ['bottom left', 'bottom center', 'bottom right']]; // Stored as map as i18n __() only accepts strings (not variables)

export var ALIGNMENT_LABEL = {
  'top left': __('Top Left'),
  'top center': __('Top Center'),
  'top right': __('Top Right'),
  'center left': __('Center Left'),
  'center center': __('Center Center'),
  'center right': __('Center Right'),
  'bottom left': __('Bottom Left'),
  'bottom center': __('Bottom Center'),
  'bottom right': __('Bottom Right')
}; // Transforms GRID into a flat Array of values

export var ALIGNMENTS = flattenDeep(GRID);
/**
 * Parses and transforms an incoming value to better match the alignment values
 *
 * @param {string} value An alignment value to parse.
 *
 * @return {string} The parsed value.
 */

export function transformValue(value) {
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

export function getItemId(prefixId, value) {
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

export function getAlignmentIndex() {
  var alignment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'center';
  var item = transformValue(alignment).replace('-', ' ');
  var index = ALIGNMENTS.indexOf(item);
  return index > -1 ? index : undefined;
}
//# sourceMappingURL=utils.js.map