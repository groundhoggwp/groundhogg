import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

/**
 * External dependencies
 */
import { castArray } from 'lodash';
/**
 * Insert one or multiple elements into a given position of an array.
 *
 * @param {Array}  array    Source array.
 * @param {*}      elements Elements to insert.
 * @param {number} index    Insert Position.
 *
 * @return {Array}          Result.
 */

export function insertAt(array, elements, index) {
  return [].concat(_toConsumableArray(array.slice(0, index)), _toConsumableArray(castArray(elements)), _toConsumableArray(array.slice(index)));
}
/**
 * Moves an element in an array.
 *
 * @param {Array}  array Source array.
 * @param {number} from  Source index.
 * @param {number} to    Destination index.
 * @param {number} count Number of elements to move.
 *
 * @return {Array}       Result.
 */

export function moveTo(array, from, to) {
  var count = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 1;

  var withoutMovedElements = _toConsumableArray(array);

  withoutMovedElements.splice(from, count);
  return insertAt(withoutMovedElements, array.slice(from, from + count), to);
}
//# sourceMappingURL=array.js.map