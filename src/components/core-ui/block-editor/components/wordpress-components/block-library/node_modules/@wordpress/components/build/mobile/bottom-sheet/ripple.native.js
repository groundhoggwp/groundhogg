"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _rippleNative = _interopRequireDefault(require("./ripple.native.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var ANDROID_VERSION_LOLLIPOP = 21;
var ANDROID_VERSION_PIE = 28;

var TouchableRipple = function TouchableRipple(_ref) {
  var style = _ref.style,
      onPress = _ref.onPress,
      disabledProp = _ref.disabled,
      children = _ref.children,
      activeOpacity = _ref.activeOpacity,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      _ref$borderless = _ref.borderless,
      borderless = _ref$borderless === void 0 ? false : _ref$borderless,
      touchableProps = (0, _objectWithoutProperties2.default)(_ref, ["style", "onPress", "disabled", "children", "activeOpacity", "getStylesFromColorScheme", "borderless"]);
  var isTouchableNativeSupported = _reactNative.Platform.OS === 'android' && _reactNative.Platform.Version >= ANDROID_VERSION_LOLLIPOP;
  var disabled = disabledProp || !onPress;
  var rippleColor = getStylesFromColorScheme(_rippleNative.default.ripple, _rippleNative.default.rippleDark).backgroundColor;

  if (isTouchableNativeSupported) {
    // A workaround for ripple on Android P is to use useForeground + overflow: 'hidden'
    // https://github.com/facebook/react-native/issues/6480
    var useForeground = _reactNative.Platform.OS === 'android' && _reactNative.Platform.Version >= ANDROID_VERSION_PIE && borderless;
    return (0, _element.createElement)(_reactNative.TouchableNativeFeedback, (0, _extends2.default)({}, touchableProps, {
      onPress: onPress,
      disabled: disabled,
      useForeground: useForeground,
      background: _reactNative.TouchableNativeFeedback.Ripple(rippleColor, borderless)
    }), (0, _element.createElement)(_reactNative.View, {
      style: [borderless && _rippleNative.default.overflow, style]
    }, children));
  }

  return (0, _element.createElement)(_reactNative.TouchableOpacity, (0, _extends2.default)({}, touchableProps, {
    onPress: onPress,
    disabled: disabled,
    activeOpacity: activeOpacity,
    style: style
  }), children);
};

var _default = (0, _compose.withPreferredColorScheme)(TouchableRipple);

exports.default = _default;
//# sourceMappingURL=ripple.native.js.map