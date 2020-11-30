"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getColorClassName = getColorClassName;
exports.getMostReadableColor = getMostReadableColor;
exports.getColorObjectByColorValue = exports.getColorObjectByAttributeValues = void 0;

var _lodash = require("lodash");

var _tinycolor = _interopRequireDefault(require("tinycolor2"));

/**
 * External dependencies
 */

/**
 * Provided an array of color objects as set by the theme or by the editor defaults,
 * and the values of the defined color or custom color returns a color object describing the color.
 *
 * @param {Array}   colors       Array of color objects as set by the theme or by the editor defaults.
 * @param {?string} definedColor A string containing the color slug.
 * @param {?string} customColor  A string containing the customColor value.
 *
 * @return {?Object} If definedColor is passed and the name is found in colors,
 *                   the color object exactly as set by the theme or editor defaults is returned.
 *                   Otherwise, an object that just sets the color is defined.
 */
var getColorObjectByAttributeValues = function getColorObjectByAttributeValues(colors, definedColor, customColor) {
  if (definedColor) {
    var colorObj = (0, _lodash.find)(colors, {
      slug: definedColor
    });

    if (colorObj) {
      return colorObj;
    }
  }

  return {
    color: customColor
  };
};
/**
 * Provided an array of color objects as set by the theme or by the editor defaults, and a color value returns the color object matching that value or undefined.
 *
 * @param {Array}   colors      Array of color objects as set by the theme or by the editor defaults.
 * @param {?string} colorValue  A string containing the color value.
 *
 * @return {?Object} Color object included in the colors array whose color property equals colorValue.
 *                   Returns undefined if no color object matches this requirement.
 */


exports.getColorObjectByAttributeValues = getColorObjectByAttributeValues;

var getColorObjectByColorValue = function getColorObjectByColorValue(colors, colorValue) {
  return (0, _lodash.find)(colors, {
    color: colorValue
  });
};
/**
 * Returns a class based on the context a color is being used and its slug.
 *
 * @param {string} colorContextName Context/place where color is being used e.g: background, text etc...
 * @param {string} colorSlug        Slug of the color.
 *
 * @return {?string} String with the class corresponding to the color in the provided context.
 *                   Returns undefined if either colorContextName or colorSlug are not provided.
 */


exports.getColorObjectByColorValue = getColorObjectByColorValue;

function getColorClassName(colorContextName, colorSlug) {
  if (!colorContextName || !colorSlug) {
    return undefined;
  }

  return "has-".concat((0, _lodash.kebabCase)(colorSlug), "-").concat(colorContextName);
}
/**
 * Given an array of color objects and a color value returns the color value of the most readable color in the array.
 *
 * @param {Array}   colors     Array of color objects as set by the theme or by the editor defaults.
 * @param {?string} colorValue A string containing the color value.
 *
 * @return {string} String with the color value of the most readable color.
 */


function getMostReadableColor(colors, colorValue) {
  return _tinycolor.default.mostReadable(colorValue, (0, _lodash.map)(colors, 'color')).toHexString();
}
//# sourceMappingURL=utils.js.map