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

import styles from './figure.scss';
export var Figure = withPreferredColorScheme(function (props) {
  var children = props.children,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var wpPullquoteFigure = getStylesFromColorScheme(styles.light, styles.dark);
  return createElement(View, {
    style: wpPullquoteFigure
  }, children);
});
//# sourceMappingURL=figure.native.js.map