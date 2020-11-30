"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isValueDefined = isValueDefined;
exports.isValueEmpty = isValueEmpty;
exports.getDefinedValue = getDefinedValue;

/**
 * Determines if a value is null or undefined.
 *
 * @param {any} value The value to check.
 * @return {boolean} Whether value is null or undefined.
 */
function isValueDefined(value) {
  return value !== undefined && value !== null;
}
/**
 * Determines if a value is empty, null, or undefined.
 *
 * @param {any} value The value to check.
 * @return {boolean} Whether value is empty.
 */


function isValueEmpty(value) {
  var isEmptyString = value === '';
  return !isValueDefined(value) || isEmptyString;
}
/**
 * Attempts to get a defined/non-null value from a collection of arguments.
 *
 * @param {Array<any>} values Values to derive from.
 * @param {any} fallbackValue Fallback value if there are no defined values.
 * @return {any} A defined value or the fallback value.
 */


function getDefinedValue() {
  var _values$find;

  var values = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var fallbackValue = arguments.length > 1 ? arguments[1] : undefined;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
//# sourceMappingURL=values.js.map