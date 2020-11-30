"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.insertAt = insertAt;
exports.moveTo = moveTo;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Insert one or multiple elements into a given position of an array.
 *
 * @param {Array}  array    Source array.
 * @param {*}      elements Elements to insert.
 * @param {number} index    Insert Position.
 *
 * @return {Array}          Result.
 */
function insertAt(array, elements, index) {
  return [].concat((0, _toConsumableArray2.default)(array.slice(0, index)), (0, _toConsumableArray2.default)((0, _lodash.castArray)(elements)), (0, _toConsumableArray2.default)(array.slice(index)));
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


function moveTo(array, from, to) {
  var count = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 1;
  var withoutMovedElements = (0, _toConsumableArray2.default)(array);
  withoutMovedElements.splice(from, count);
  return insertAt(withoutMovedElements, array.slice(from, from + count), to);
}
//# sourceMappingURL=array.js.map