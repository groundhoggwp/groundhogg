import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Text, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';

var BlockInsertionPoint = function BlockInsertionPoint(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var lineStyle = getStylesFromColorScheme(styles.lineStyleAddHere, styles.lineStyleAddHereDark);
  var labelStyle = getStylesFromColorScheme(styles.labelStyleAddHere, styles.labelStyleAddHereDark);
  return createElement(View, {
    style: styles.containerStyleAddHere
  }, createElement(View, {
    style: lineStyle
  }), createElement(Text, {
    style: labelStyle
  }, __('ADD BLOCK HERE')), createElement(View, {
    style: lineStyle
  }));
};

export default withPreferredColorScheme(BlockInsertionPoint);
//# sourceMappingURL=insertion-point.native.js.map