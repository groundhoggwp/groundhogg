import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { withPreferredColorScheme } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */

import { Platform, Text, TouchableOpacity } from 'react-native';
/**
 * Internal dependencies
 */

import Icon from '../../icon';
import closeIcon from './close-icon';
import styles from './button.scss';
var ICON_SIZE = 24;
var Button = withPreferredColorScheme(function (_ref) {
  var icon = _ref.icon,
      onPress = _ref.onPress,
      title = _ref.title,
      isPrimary = _ref.isPrimary,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var titleStyle = getStylesFromColorScheme(styles.title, styles.titleDark);
  return createElement(TouchableOpacity, {
    onPress: onPress
  }, icon ? createElement(Icon, {
    icon: icon,
    size: ICON_SIZE,
    style: styles.icon
  }) : createElement(Text, {
    style: [titleStyle, isPrimary && styles.titlePrimary]
  }, title));
});
Button.displayName = 'ModalHeaderBar.Button';
export { Button };

var CloseButton = function CloseButton(_ref2) {
  var onPress = _ref2.onPress;
  var props = Platform.select({
    ios: {
      title: __('Close')
    },
    android: {
      accessibilityLabel: __('Close'),
      icon: closeIcon
    }
  });
  return createElement(Button, _extends({
    onPress: onPress
  }, props));
};

CloseButton.displayName = 'ModalHeaderBar.CloseButton';
export { CloseButton };
//# sourceMappingURL=button.native.js.map