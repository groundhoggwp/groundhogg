import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, TouchableOpacity, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
import { Icon, minus, plus } from '@wordpress/icons';
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
  var buttonStyle = getStylesFromColorScheme(styles.button, styles.buttonDark);
  return createElement(View, {
    style: styles.container
  }, createElement(Text, {
    style: valueStyle
  }, value), createElement(TouchableOpacity, {
    disabled: isMinValue,
    onPressIn: onPressInDecrement,
    onPressOut: onPressOut,
    style: [buttonStyle, isMinValue ? {
      opacity: 0.4
    } : null]
  }, createElement(Icon, {
    icon: minus,
    size: 24,
    color: buttonStyle.color
  })), createElement(TouchableOpacity, {
    disabled: isMaxValue,
    onPressIn: onPressInIncrement,
    onPressOut: onPressOut,
    style: [buttonStyle, isMaxValue ? {
      opacity: 0.4
    } : null]
  }, createElement(Icon, {
    icon: plus,
    size: 24,
    color: buttonStyle.color
  })));
}

export default withPreferredColorScheme(Stepper);
//# sourceMappingURL=stepper.ios.js.map