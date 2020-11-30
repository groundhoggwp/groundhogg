import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { PlainText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

/**
 * Block code style
 */

import styles from './theme.scss'; // Note: styling is applied directly to the (nested) PlainText component. Web-side components
// apply it to the container 'div' but we don't have a proper proposal for cascading styling yet.

export function CodeEdit(props) {
  var attributes = props.attributes,
      setAttributes = props.setAttributes,
      onFocus = props.onFocus,
      onBlur = props.onBlur,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var codeStyle = getStylesFromColorScheme(styles.blockCode, styles.blockCodeDark);
  var placeholderStyle = getStylesFromColorScheme(styles.placeholder, styles.placeholderDark);
  return createElement(View, null, createElement(PlainText, {
    value: attributes.content,
    style: codeStyle,
    multiline: true,
    underlineColorAndroid: "transparent",
    onChange: function onChange(content) {
      return setAttributes({
        content: content
      });
    },
    placeholder: __('Write code…'),
    "aria-label": __('Code'),
    isSelected: props.isSelected,
    onFocus: onFocus,
    onBlur: onBlur,
    placeholderTextColor: placeholderStyle.color
  }));
}
export default withPreferredColorScheme(CodeEdit);
//# sourceMappingURL=edit.native.js.map