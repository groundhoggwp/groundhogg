"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isAction = isAction;
exports.isActionOfType = isActionOfType;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Returns true if the given object quacks like an action.
 *
 * @param {*} object Object to test
 *
 * @return {boolean}  Whether object is an action.
 */
function isAction(object) {
  return (0, _lodash.isPlainObject)(object) && (0, _lodash.isString)(object.type);
}
/**
 * Returns true if the given object quacks like an action and has a specific
 * action type
 *
 * @param {*}      object       Object to test
 * @param {string} expectedType The expected type for the action.
 *
 * @return {boolean} Whether object is an action and is of specific type.
 */


function isActionOfType(object, expectedType) {
  return isAction(object) && object.type === expectedType;
}
//# sourceMappingURL=is-action.js.map