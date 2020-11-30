"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Figure = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _figure = _interopRequireDefault(require("./figure.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var Figure = (0, _compose.withPreferredColorScheme)(function (props) {
  var children = props.children,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var wpPullquoteFigure = getStylesFromColorScheme(_figure.default.light, _figure.default.dark);
  return (0, _element.createElement)(_reactNative.View, {
    style: wpPullquoteFigure
  }, children);
});
exports.Figure = Figure;
//# sourceMappingURL=figure.native.js.map