import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';

var ToolbarGroupContainer = function ToolbarGroupContainer(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      passedStyle = _ref.passedStyle,
      children = _ref.children;
  return createElement(View, {
    style: [getStylesFromColorScheme(styles.container, styles.containerDark), passedStyle]
  }, children);
};

export default withPreferredColorScheme(ToolbarGroupContainer);
//# sourceMappingURL=toolbar-group-container.native.js.map