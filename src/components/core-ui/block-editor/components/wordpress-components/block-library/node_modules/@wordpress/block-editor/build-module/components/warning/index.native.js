import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, Text } from 'react-native';
/**
 * WordPress dependencies
 */

import { Icon } from '@wordpress/components';
import { withPreferredColorScheme } from '@wordpress/compose';
import { normalizeIconObject } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import styles from './style.scss';

function Warning(_ref) {
  var title = _ref.title,
      message = _ref.message,
      icon = _ref.icon,
      iconClass = _ref.iconClass,
      preferredColorScheme = _ref.preferredColorScheme,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      viewProps = _objectWithoutProperties(_ref, ["title", "message", "icon", "iconClass", "preferredColorScheme", "getStylesFromColorScheme"]);

  icon = icon && normalizeIconObject(icon);
  var internalIconClass = 'warning-icon' + '-' + preferredColorScheme;
  var titleStyle = getStylesFromColorScheme(styles.title, styles.titleDark);
  var messageStyle = getStylesFromColorScheme(styles.message, styles.messageDark);
  return createElement(View, _extends({
    style: getStylesFromColorScheme(styles.container, styles.containerDark)
  }, viewProps), icon && createElement(View, {
    style: styles.icon
  }, createElement(Icon, {
    className: iconClass || internalIconClass,
    icon: icon && icon.src ? icon.src : icon
  })), title && createElement(Text, {
    style: titleStyle
  }, title), message && createElement(Text, {
    style: messageStyle
  }, message));
}

export default withPreferredColorScheme(Warning);
//# sourceMappingURL=index.native.js.map