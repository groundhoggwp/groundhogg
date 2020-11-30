"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.removeNewTabRel = removeNewTabRel;
exports.getUpdatedLinkTargetSettings = getUpdatedLinkTargetSettings;

var _lodash = require("lodash");

var _constants = require("./constants");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function removeNewTabRel(currentRel) {
  var newRel = currentRel;

  if (currentRel !== undefined && !(0, _lodash.isEmpty)(newRel)) {
    if (!(0, _lodash.isEmpty)(newRel)) {
      (0, _lodash.each)(_constants.NEW_TAB_REL, function (relVal) {
        var regExp = new RegExp('\\b' + relVal + '\\b', 'gi');
        newRel = newRel.replace(regExp, '');
      }); // Only trim if NEW_TAB_REL values was replaced.

      if (newRel !== currentRel) {
        newRel = newRel.trim();
      }

      if ((0, _lodash.isEmpty)(newRel)) {
        newRel = undefined;
      }
    }
  }

  return newRel;
}
/**
 * Helper to get the link target settings to be stored.
 *
 * @param {boolean} value         The new link target value.
 * @param {Object} attributes     Block attributes.
 * @param {Object} attributes.rel Image block's rel attribute.
 *
 * @return {Object} Updated link target settings.
 */


function getUpdatedLinkTargetSettings(value, _ref) {
  var rel = _ref.rel;
  var linkTarget = value ? '_blank' : undefined;
  var updatedRel;

  if (!linkTarget && !rel) {
    updatedRel = undefined;
  } else {
    updatedRel = removeNewTabRel(rel);
  }

  return {
    linkTarget: linkTarget,
    rel: updatedRel
  };
}
//# sourceMappingURL=utils.js.map