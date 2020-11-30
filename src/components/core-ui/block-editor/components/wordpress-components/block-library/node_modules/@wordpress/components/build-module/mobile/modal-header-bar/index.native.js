import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
import { Button, CloseButton } from './button';
var ModalHeaderBar = withPreferredColorScheme(function (props) {
  var leftButton = props.leftButton,
      title = props.title,
      subtitle = props.subtitle,
      rightButton = props.rightButton,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var separatorStyle = getStylesFromColorScheme(styles.separator, styles.separatorDark);
  var titleStyle = getStylesFromColorScheme(styles.title, styles.titleDark);
  var subtitleStyle = getStylesFromColorScheme(styles.subtitle, styles.subtitleDark);
  return createElement(View, null, createElement(View, {
    style: [styles.bar, subtitle && styles.subtitleBar]
  }, createElement(View, {
    style: styles.leftContainer
  }, leftButton), createElement(View, {
    style: styles.titleContainer,
    accessibilityRole: "header"
  }, createElement(Text, {
    style: titleStyle
  }, title), subtitle && createElement(Text, {
    style: subtitleStyle
  }, subtitle)), createElement(View, {
    style: styles.rightContainer
  }, rightButton)), createElement(View, {
    style: separatorStyle
  }));
});
ModalHeaderBar.displayName = 'ModalHeaderBar';
ModalHeaderBar.Button = Button;
ModalHeaderBar.CloseButton = CloseButton;
export default ModalHeaderBar;
//# sourceMappingURL=index.native.js.map