"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isLineHeightDefined = isLineHeightDefined;
exports.RESET_VALUE = exports.STEP = exports.BASE_DEFAULT_VALUE = void 0;
var BASE_DEFAULT_VALUE = 1.5;
exports.BASE_DEFAULT_VALUE = BASE_DEFAULT_VALUE;
var STEP = 0.1;
/**
 * There are varying value types within LineHeightControl:
 *
 * {undefined} Initial value. No changes from the user.
 * {string} Input value. Value consumed/outputted by the input. Empty would be ''.
 * {number} Block attribute type. Input value needs to be converted for attribute setting.
 *
 * Note: If the value is undefined, the input requires it to be an empty string ('')
 * in order to be considered "controlled" by props (rather than internal state).
 */

exports.STEP = STEP;
var RESET_VALUE = '';
/**
 * Determines if the lineHeight attribute has been properly defined.
 *
 * @param {any} lineHeight The value to check.
 *
 * @return {boolean} Whether the lineHeight attribute is valid.
 */

exports.RESET_VALUE = RESET_VALUE;

function isLineHeightDefined(lineHeight) {
  return lineHeight !== undefined && lineHeight !== RESET_VALUE;
}
//# sourceMappingURL=utils.js.map