import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, TouchableOpacity, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';

function Stepper(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      isMaxValue = _ref.isMaxValue,
      isMinValue = _ref.isMinValue,
      onPressInDecrement = _ref.onPressInDecrement,
      onPressInIncrement = _ref.onPressInIncrement,
      onPressOut = _ref.onPressOut,
      value = _ref.value;
  var valueStyle = getStylesFromColorScheme(styles.value, styles.valueTextDark);
  var buttonIconStyle = getStylesFromColorScheme(styles.buttonNoBg, styles.buttonNoBgTextDark);
  return createElement(View, {
    style: styles.container,
    accesibility: false,
    importantForAccessibility: "no-hide-descendants"
  }, createElement(TouchableOpacity, {
    disabled: isMinValue,
    onPressIn: onPressInDecrement,
    onPressOut: onPressOut,
    style: [styles.buttonNoBg, isMinValue ? {
      opacity: 0.4
    } : null]
  }, createElement(Icon, {
    icon: chevronDown,
    size: 18,
    color: buttonIconStyle.color
  })), createElement(Text, {
    style: valueStyle
  }, value), createElement(TouchableOpacity, {
    disabled: isMaxValue,
    onPressIn: onPressInIncrement,
    onPressOut: onPressOut,
    style: [styles.buttonNoBg, isMaxValue ? {
      opacity: 0.4
    } : null]
  }, createElement(Icon, {
    icon: chevronUp,
    size: 18,
    color: buttonIconStyle.color
  })));
}

export default withPreferredColorScheme(Stepper);
//# sourceMappingURL=stepper.android.js.map