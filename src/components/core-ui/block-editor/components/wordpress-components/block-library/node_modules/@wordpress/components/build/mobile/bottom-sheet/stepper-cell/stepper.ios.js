"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

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
function Stepper(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      isMaxValue = _ref.isMaxValue,
      isMinValue = _ref.isMinValue,
      onPressInDecrement = _ref.onPressInDecrement,
      onPressInIncrement = _ref.onPressInIncrement,
      onPressOut = _ref.onPressOut,
      value = _ref.value;
  var valueStyle = getStylesFromColorScheme(_style.default.value, _style.default.valueTextDark);
  var buttonStyle = getStylesFromColorScheme(_style.default.button, _style.default.buttonDark);
  return (0, _element.createElement)(_reactNative.View, {
    style: _style.default.container
  }, (0, _element.createElement)(_reactNative.Text, {
    style: valueStyle
  }, value), (0, _element.createElement)(_reactNative.TouchableOpacity, {
    disabled: isMinValue,
    onPressIn: onPressInDecrement,
    onPressOut: onPressOut,
    style: [buttonStyle, isMinValue ? {
      opacity: 0.4
    } : null]
  }, (0, _element.createElement)(_icons.Icon, {
    icon: _icons.minus,
    size: 24,
    color: buttonStyle.color
  })), (0, _element.createElement)(_reactNative.TouchableOpacity, {
    disabled: isMaxValue,
    onPressIn: onPressInIncrement,
    onPressOut: onPressOut,
    style: [buttonStyle, isMaxValue ? {
      opacity: 0.4
    } : null]
  }, (0, _element.createElement)(_icons.Icon, {
    icon: _icons.plus,
    size: 24,
    color: buttonStyle.color
  })));
}

var _default = (0, _compose.withPreferredColorScheme)(Stepper);

exports.default = _default;
//# sourceMappingURL=stepper.ios.js.map