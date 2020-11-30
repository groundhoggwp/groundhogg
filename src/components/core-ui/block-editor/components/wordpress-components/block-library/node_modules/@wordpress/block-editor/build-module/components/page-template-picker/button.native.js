import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { TouchableOpacity, Text, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './styles.scss';

var PickerButton = function PickerButton(_ref) {
  var icon = _ref.icon,
      label = _ref.label,
      onPress = _ref.onPress;
  var butonWrapperStyles = usePreferredColorSchemeStyle(styles.buttonWrapper, styles.buttonWrapperDark);
  var buttonStyles = usePreferredColorSchemeStyle(styles.button, styles.buttonDark);
  var buttonTextStyles = usePreferredColorSchemeStyle(styles.buttonText, styles.buttonTextDark);
  return createElement(TouchableOpacity, {
    accessibilityLabel: label,
    accessibilityHint: __('Double tap to select layout'),
    activeOpacity: 0.7,
    onPress: onPress,
    style: butonWrapperStyles
  }, createElement(View, {
    style: buttonStyles
  }, createElement(Text, {
    style: styles.buttonIcon
  }, icon), createElement(Text, {
    style: buttonTextStyles
  }, label)));
};

export default PickerButton;
//# sourceMappingURL=button.native.js.map