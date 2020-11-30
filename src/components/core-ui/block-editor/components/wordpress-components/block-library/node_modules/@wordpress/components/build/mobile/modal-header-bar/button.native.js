"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.CloseButton = exports.Button = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _reactNative = require("react-native");

var _icon = _interopRequireDefault(require("../../icon"));

var _closeIcon = _interopRequireDefault(require("./close-icon"));

var _button = _interopRequireDefault(require("./button.scss"));

/**
 * WordPress dependencies
 */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var ICON_SIZE = 24;
var Button = (0, _compose.withPreferredColorScheme)(function (_ref) {
  var icon = _ref.icon,
      onPress = _ref.onPress,
      title = _ref.title,
      isPrimary = _ref.isPrimary,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var titleStyle = getStylesFromColorScheme(_button.default.title, _button.default.titleDark);
  return (0, _element.createElement)(_reactNative.TouchableOpacity, {
    onPress: onPress
  }, icon ? (0, _element.createElement)(_icon.default, {
    icon: icon,
    size: ICON_SIZE,
    style: _button.default.icon
  }) : (0, _element.createElement)(_reactNative.Text, {
    style: [titleStyle, isPrimary && _button.default.titlePrimary]
  }, title));
});
exports.Button = Button;
Button.displayName = 'ModalHeaderBar.Button';

var CloseButton = function CloseButton(_ref2) {
  var onPress = _ref2.onPress;

  var props = _reactNative.Platform.select({
    ios: {
      title: (0, _i18n.__)('Close')
    },
    android: {
      accessibilityLabel: (0, _i18n.__)('Close'),
      icon: _closeIcon.default
    }
  });

  return (0, _element.createElement)(Button, (0, _extends2.default)({
    onPress: onPress
  }, props));
};

exports.CloseButton = CloseButton;
CloseButton.displayName = 'ModalHeaderBar.CloseButton';
//# sourceMappingURL=button.native.js.map