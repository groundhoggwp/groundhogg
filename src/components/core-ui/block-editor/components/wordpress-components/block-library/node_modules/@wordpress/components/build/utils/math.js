"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getNumber = getNumber;
exports.add = add;
exports.subtract = subtract;
exports.roundClamp = roundClamp;
exports.roundClampString = roundClampString;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Parses and retrieves a number value.
 *
 * @param {any} value The incoming value.
 *
 * @return {number} The parsed number value.
 */
function getNumber(value) {
  var number = Number(value);
  return isNaN(number) ? 0 : number;
}
/**
 * Safely adds 2 values.
 *
 * @param {number|string} args Values to add together.
 *
 * @return {number} The sum of values.
 */


function add() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  return args.reduce(function (sum, arg) {
    return sum + getNumber(arg);
  }, 0);
}
/**
 * Safely subtracts 2 values.
 *
 * @param {number|string} args Values to subtract together.
 *
 * @return {number} The difference of the 2 values.
 */


function subtract() {
  for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    args[_key2] = arguments[_key2];
  }

  return args.reduce(function (diff, arg, index) {
    var value = getNumber(arg);
    return index === 0 ? value : diff - value;
  });
}
/**
 * Determines the decimal position of a number value.
 *
 * @param {number} value The number to evaluate.
 *
 * @return {number} The number of decimal places.
 */


function getPrecision(value) {
  var split = (value + '').split('.');
  return split[1] !== undefined ? split[1].length : 0;
}
/**
 * Clamps a value based on a min/max range with rounding
 *
 * @param {number} value The value.
 * @param {number} min The minimum range.
 * @param {number} max The maximum range.
 * @param {number} step A multiplier for the value.
 *
 * @return {number} The rounded and clamped value.
 */


function roundClamp() {
  var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
  var min = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : Infinity;
  var max = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : Infinity;
  var step = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 1;
  var baseValue = getNumber(value);
  var stepValue = getNumber(step);
  var precision = getPrecision(step);
  var rounded = Math.round(baseValue / stepValue) * stepValue;
  var clampedValue = (0, _lodash.clamp)(rounded, min, max);
  return precision ? getNumber(clampedValue.toFixed(precision)) : clampedValue;
}
/**
 * Clamps a value based on a min/max range with rounding.
 * Returns a string.
 *
 * @param {any} args Arguments for roundClamp().
 * @property {number} value The value.
 * @property {number} min The minimum range.
 * @property {number} max The maximum range.
 * @property {number} step A multiplier for the value.
 *
 * @return {string} The rounded and clamped value.
 */


function roundClampString() {
  return roundClamp.apply(void 0, arguments).toString();
}
//# sourceMappingURL=math.js.map