"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _bottomSeparatorCover = _interopRequireDefault(require("./bottom-separator-cover.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BottomSeparatorCover(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  return (0, _element.createElement)(_reactNative.View, {
    style: getStylesFromColorScheme(_bottomSeparatorCover.default.coverSeparator, _bottomSeparatorCover.default.coverSeparatorDark)
  });
}

var _default = (0, _compose.withPreferredColorScheme)(BottomSeparatorCover);

exports.default = _default;
//# sourceMappingURL=bottom-separator-cover.native.js.map