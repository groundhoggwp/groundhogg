"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _editor = _interopRequireDefault(require("./editor.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ColorBackground(_ref) {
  var children = _ref.children,
      borderRadiusValue = _ref.borderRadiusValue,
      backgroundColor = _ref.backgroundColor;
  var isGradient = _components.colorsUtils.isGradient;
  var wrapperStyles = [_editor.default.richTextWrapper, {
    borderRadius: borderRadiusValue,
    backgroundColor: backgroundColor
  }];
  return (0, _element.createElement)(_reactNative.View, {
    style: wrapperStyles
  }, isGradient(backgroundColor) && (0, _element.createElement)(_components.Gradient, {
    gradientValue: backgroundColor,
    angleCenter: {
      x: 0.5,
      y: 0.5
    },
    style: [_editor.default.linearGradient, {
      borderRadius: borderRadiusValue
    }]
  }), children);
}

var _default = ColorBackground;
exports.default = _default;
//# sourceMappingURL=color-background.native.js.map