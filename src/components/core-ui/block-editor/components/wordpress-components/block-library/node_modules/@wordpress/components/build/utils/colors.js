"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.rgba = rgba;
exports.color = color;

var _lodash = require("lodash");

var _tinycolor = _interopRequireDefault(require("tinycolor2"));

var _colorsValues = require("./colors-values");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Generating a CSS complient rgba() color value.
 *
 * @param {string} hexValue The hex value to convert to rgba().
 * @param {number} alpha The alpha value for opacity.
 * @return {string} The converted rgba() color value.
 *
 * @example
 * rgba( '#000000', 0.5 )
 * // rgba(0, 0, 0, 0.5)
 */
function rgba() {
  var hexValue = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  var alpha = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;

  var _tinycolor$toRgb = (0, _tinycolor.default)(hexValue).toRgb(),
      r = _tinycolor$toRgb.r,
      g = _tinycolor$toRgb.g,
      b = _tinycolor$toRgb.b;

  return "rgba(".concat(r, ", ").concat(g, ", ").concat(b, ", ").concat(alpha, ")");
}
/**
 * Retrieves a color from the color palette.
 *
 * @param {string} value The value to retrieve.
 * @return {string} The color (or fallback, if not found).
 *
 * @example
 * color( 'blue.wordpress.700' )
 * // #00669b
 */


function color(value) {
  var fallbackColor = '#000';
  return (0, _lodash.get)(_colorsValues.COLORS, value, fallbackColor);
}
//# sourceMappingURL=colors.js.map