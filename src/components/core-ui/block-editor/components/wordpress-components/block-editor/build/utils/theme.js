"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.validateThemeColors = validateThemeColors;
exports.validateThemeGradients = validateThemeGradients;

var _defaults = require("../store/defaults");

/**
 * Internal dependencies
 */

/**
 * Given an array of theme colors checks colors for validity
 *
 * @param {Array}   colors  The array of theme colors
 *
 * @return {Array} The array of valid theme colors or the default colors
 */
function validateThemeColors(colors) {
  if (colors === undefined) {
    colors = _defaults.SETTINGS_DEFAULTS.colors;
  } else {
    var validColors = colors.filter(function (c) {
      return c.color;
    });

    if (validColors.length === 0) {
      colors = _defaults.SETTINGS_DEFAULTS.colors;
    } else if (validColors.length < colors.length) {
      // Filter out invalid colors
      colors = validColors;
    }
  }

  return colors;
}
/**
 * Given an array of theme gradients checks gradients for validity
 *
 * @param {Array}   gradients  The array of theme gradients
 *
 * @return {Array} The array of valid theme gradients or the default gradients
 */


function validateThemeGradients(gradients) {
  if (gradients === undefined) {
    gradients = _defaults.SETTINGS_DEFAULTS.gradients;
  } else {
    var validGradients = gradients.filter(function (c) {
      return c.gradient;
    });

    if (validGradients.length === 0) {
      gradients = _defaults.SETTINGS_DEFAULTS.gradients;
    } else if (validGradients.length < gradients.length) {
      // Filter out invalid gradients
      gradients = validGradients;
    }
  }

  return gradients;
}
//# sourceMappingURL=theme.js.map