"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getAllValue = getAllValue;
exports.isValuesMixed = isValuesMixed;
exports.isValuesDefined = isValuesDefined;
exports.DEFAULT_VISUALIZER_VALUES = exports.DEFAULT_VALUES = exports.LABELS = void 0;

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _utils = require("../unit-control/utils");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var LABELS = {
  all: (0, _i18n.__)('All'),
  top: (0, _i18n.__)('Top'),
  bottom: (0, _i18n.__)('Bottom'),
  left: (0, _i18n.__)('Left'),
  right: (0, _i18n.__)('Right'),
  mixed: (0, _i18n.__)('Mixed')
};
exports.LABELS = LABELS;
var DEFAULT_VALUES = {
  top: null,
  right: null,
  bottom: null,
  left: null
};
exports.DEFAULT_VALUES = DEFAULT_VALUES;
var DEFAULT_VISUALIZER_VALUES = {
  top: false,
  right: false,
  bottom: false,
  left: false
};
/**
 * Gets an items with the most occurance within an array
 * https://stackoverflow.com/a/20762713
 *
 * @param {Array<any>} arr Array of items to check.
 * @return {any} The item with the most occurances.
 */

exports.DEFAULT_VISUALIZER_VALUES = DEFAULT_VISUALIZER_VALUES;

function mode(arr) {
  return arr.sort(function (a, b) {
    return arr.filter(function (v) {
      return v === a;
    }).length - arr.filter(function (v) {
      return v === b;
    }).length;
  }).pop();
}
/**
 * Gets the 'all' input value and unit from values data.
 *
 * @param {Object} values Box values.
 * @return {string} A value + unit for the 'all' input.
 */


function getAllValue() {
  var values = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var parsedValues = Object.values(values).map(_utils.parseUnit);
  var allValues = parsedValues.map(function (value) {
    return value[0];
  });
  var allUnits = parsedValues.map(function (value) {
    return value[1];
  });
  var value = allValues.every(function (v) {
    return v === allValues[0];
  }) ? allValues[0] : '';
  var unit = mode(allUnits);
  /**
   * The isNumber check is important. On reset actions, the incoming value
   * may be null or an empty string.
   *
   * Also, the value may also be zero (0), which is considered a valid unit value.
   *
   * isNumber() is more specific for these cases, rather than relying on a
   * simple truthy check.
   */

  var allValue = (0, _lodash.isNumber)(value) ? "".concat(value).concat(unit) : null;
  return allValue;
}
/**
 * Checks to determine if values are mixed.
 *
 * @param {Object} values Box values.
 * @return {boolean} Whether values are mixed.
 */


function isValuesMixed() {
  var values = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var allValue = getAllValue(values);
  var isMixed = isNaN(parseFloat(allValue));
  return isMixed;
}
/**
 * Checks to determine if values are defined.
 *
 * @param {Object} values Box values.
 *
 * @return {boolean} Whether values are mixed.
 */


function isValuesDefined(values) {
  return values !== undefined && !(0, _lodash.isEmpty)(Object.values(values).filter(Boolean));
}
//# sourceMappingURL=utils.js.map