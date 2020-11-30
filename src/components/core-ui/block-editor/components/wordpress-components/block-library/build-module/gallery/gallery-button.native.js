import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { StyleSheet, TouchableOpacity } from 'react-native';
/**
 * WordPress dependencies
 */

import { Icon } from '@wordpress/components';
/**
 * Internal dependencies
 */

import style from './gallery-image-style.scss';
export function Button(props) {
  var icon = props.icon,
      _props$iconSize = props.iconSize,
      iconSize = _props$iconSize === void 0 ? 24 : _props$iconSize,
      onClick = props.onClick,
      disabled = props.disabled,
      ariaDisabled = props['aria-disabled'],
      _props$accessibilityL = props.accessibilityLabel,
      accessibilityLabel = _props$accessibilityL === void 0 ? 'button' : _props$accessibilityL,
      customStyle = props.style;
  var buttonStyle = StyleSheet.compose(style.buttonActive, customStyle);
  var isDisabled = disabled || ariaDisabled;

  var _ref = isDisabled ? style.buttonDisabled : style.button,
      fill = _ref.fill;

  return createElement(TouchableOpacity, {
    style: buttonStyle,
    activeOpacity: 0.7,
    accessibilityLabel: accessibilityLabel,
    accessibilityRole: 'button',
    onPress: onClick,
    disabled: isDisabled
  }, createElement(Icon, {
    icon: icon,
    fill: fill,
    size: iconSize
  }));
}
export default Button;
//# sourceMappingURL=gallery-button.native.js.map