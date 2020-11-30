"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockInsertionPoint = function BlockInsertionPoint(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var lineStyle = getStylesFromColorScheme(_style.default.lineStyleAddHere, _style.default.lineStyleAddHereDark);
  var labelStyle = getStylesFromColorScheme(_style.default.labelStyleAddHere, _style.default.labelStyleAddHereDark);
  return (0, _element.createElement)(_reactNative.View, {
    style: _style.default.containerStyleAddHere
  }, (0, _element.createElement)(_reactNative.View, {
    style: lineStyle
  }), (0, _element.createElement)(_reactNative.Text, {
    style: labelStyle
  }, (0, _i18n.__)('ADD BLOCK HERE')), (0, _element.createElement)(_reactNative.View, {
    style: lineStyle
  }));
};

var _default = (0, _compose.withPreferredColorScheme)(BlockInsertionPoint);

exports.default = _default;
//# sourceMappingURL=insertion-point.native.js.map