"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Button = Button;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _components = require("@wordpress/components");

var _galleryImageStyle = _interopRequireDefault(require("./gallery-image-style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Button(props) {
  var icon = props.icon,
      _props$iconSize = props.iconSize,
      iconSize = _props$iconSize === void 0 ? 24 : _props$iconSize,
      onClick = props.onClick,
      disabled = props.disabled,
      ariaDisabled = props['aria-disabled'],
      _props$accessibilityL = props.accessibilityLabel,
      accessibilityLabel = _props$accessibilityL === void 0 ? 'button' : _props$accessibilityL,
      customStyle = props.style;

  var buttonStyle = _reactNative.StyleSheet.compose(_galleryImageStyle.default.buttonActive, customStyle);

  var isDisabled = disabled || ariaDisabled;

  var _ref = isDisabled ? _galleryImageStyle.default.buttonDisabled : _galleryImageStyle.default.button,
      fill = _ref.fill;

  return (0, _element.createElement)(_reactNative.TouchableOpacity, {
    style: buttonStyle,
    activeOpacity: 0.7,
    accessibilityLabel: accessibilityLabel,
    accessibilityRole: 'button',
    onPress: onClick,
    disabled: isDisabled
  }, (0, _element.createElement)(_components.Icon, {
    icon: icon,
    fill: fill,
    size: iconSize
  }));
}

var _default = Button;
exports.default = _default;
//# sourceMappingURL=gallery-button.native.js.map