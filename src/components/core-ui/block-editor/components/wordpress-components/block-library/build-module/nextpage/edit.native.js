import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
import Hr from 'react-native-hr';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './editor.scss';
export function NextPageEdit(_ref) {
  var attributes = _ref.attributes,
      isSelected = _ref.isSelected,
      onFocus = _ref.onFocus,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var _attributes$customTex = attributes.customText,
      customText = _attributes$customTex === void 0 ? __('Page break') : _attributes$customTex;
  var accessibilityTitle = attributes.customText || '';
  var accessibilityState = isSelected ? ['selected'] : [];
  var textStyle = getStylesFromColorScheme(styles.nextpageText, styles.nextpageTextDark);
  var lineStyle = getStylesFromColorScheme(styles.nextpageLine, styles.nextpageLineDark);
  return createElement(View, {
    accessible: true,
    accessibilityLabel: sprintf(
    /* translators: accessibility text. %s: Page break text. */
    __('Page break block. %s'), accessibilityTitle),
    accessibilityStates: accessibilityState,
    onAccessibilityTap: onFocus
  }, createElement(Hr, {
    text: customText,
    marginLeft: 0,
    marginRight: 0,
    textStyle: textStyle,
    lineStyle: lineStyle
  }));
}
export default withPreferredColorScheme(NextPageEdit);
//# sourceMappingURL=edit.native.js.map