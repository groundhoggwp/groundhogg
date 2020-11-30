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

import styles from './bottom-separator-cover.scss';

function BottomSeparatorCover(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  return createElement(View, {
    style: getStylesFromColorScheme(styles.coverSeparator, styles.coverSeparatorDark)
  });
}

export default withPreferredColorScheme(BottomSeparatorCover);
//# sourceMappingURL=bottom-separator-cover.native.js.map