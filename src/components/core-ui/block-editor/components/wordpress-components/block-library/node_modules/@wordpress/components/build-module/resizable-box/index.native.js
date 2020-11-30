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

function ResizableBox(props) {
  var size = props.size,
      _props$showHandle = props.showHandle,
      showHandle = _props$showHandle === void 0 ? true : _props$showHandle,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var height = size.height;
  var defaultStyle = getStylesFromColorScheme(styles.staticSpacer, styles.staticDarkSpacer);
  return createElement(View, {
    style: [defaultStyle, showHandle && styles.selectedSpacer, {
      height: height
    }]
  });
}

export default withPreferredColorScheme(ResizableBox);
//# sourceMappingURL=index.native.js.map