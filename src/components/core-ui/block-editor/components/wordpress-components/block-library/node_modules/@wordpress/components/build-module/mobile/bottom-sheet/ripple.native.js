import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Platform, TouchableOpacity, TouchableNativeFeedback, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import rippleStyles from './ripple.native.scss';
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
      touchableProps = _objectWithoutProperties(_ref, ["style", "onPress", "disabled", "children", "activeOpacity", "getStylesFromColorScheme", "borderless"]);

  var isTouchableNativeSupported = Platform.OS === 'android' && Platform.Version >= ANDROID_VERSION_LOLLIPOP;
  var disabled = disabledProp || !onPress;
  var rippleColor = getStylesFromColorScheme(rippleStyles.ripple, rippleStyles.rippleDark).backgroundColor;

  if (isTouchableNativeSupported) {
    // A workaround for ripple on Android P is to use useForeground + overflow: 'hidden'
    // https://github.com/facebook/react-native/issues/6480
    var useForeground = Platform.OS === 'android' && Platform.Version >= ANDROID_VERSION_PIE && borderless;
    return createElement(TouchableNativeFeedback, _extends({}, touchableProps, {
      onPress: onPress,
      disabled: disabled,
      useForeground: useForeground,
      background: TouchableNativeFeedback.Ripple(rippleColor, borderless)
    }), createElement(View, {
      style: [borderless && rippleStyles.overflow, style]
    }, children));
  }

  return createElement(TouchableOpacity, _extends({}, touchableProps, {
    onPress: onPress,
    disabled: disabled,
    activeOpacity: activeOpacity,
    style: style
  }), children);
};

export default withPreferredColorScheme(TouchableRipple);
//# sourceMappingURL=ripple.native.js.map