"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.addFormatTypes = addFormatTypes;
exports.removeFormatTypes = removeFormatTypes;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Returns an action object used in signalling that format types have been
 * added.
 *
 * @param {Array|Object} formatTypes Format types received.
 *
 * @return {Object} Action object.
 */
function addFormatTypes(formatTypes) {
  return {
    type: 'ADD_FORMAT_TYPES',
    formatTypes: (0, _lodash.castArray)(formatTypes)
  };
}
/**
 * Returns an action object used to remove a registered format type.
 *
 * @param {string|Array} names Format name.
 *
 * @return {Object} Action object.
 */


function removeFormatTypes(names) {
  return {
    type: 'REMOVE_FORMAT_TYPES',
    names: (0, _lodash.castArray)(names)
  };
}
//# sourceMappingURL=actions.js.map