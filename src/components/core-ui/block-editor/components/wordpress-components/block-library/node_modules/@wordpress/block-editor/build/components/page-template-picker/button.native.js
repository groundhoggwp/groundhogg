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

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var PickerButton = function PickerButton(_ref) {
  var icon = _ref.icon,
      label = _ref.label,
      onPress = _ref.onPress;
  var butonWrapperStyles = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.buttonWrapper, _styles.default.buttonWrapperDark);
  var buttonStyles = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.button, _styles.default.buttonDark);
  var buttonTextStyles = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.buttonText, _styles.default.buttonTextDark);
  return (0, _element.createElement)(_reactNative.TouchableOpacity, {
    accessibilityLabel: label,
    accessibilityHint: (0, _i18n.__)('Double tap to select layout'),
    activeOpacity: 0.7,
    onPress: onPress,
    style: butonWrapperStyles
  }, (0, _element.createElement)(_reactNative.View, {
    style: buttonStyles
  }, (0, _element.createElement)(_reactNative.Text, {
    style: _styles.default.buttonIcon
  }, icon), (0, _element.createElement)(_reactNative.Text, {
    style: buttonTextStyles
  }, label)));
};

var _default = PickerButton;
exports.default = _default;
//# sourceMappingURL=button.native.js.map