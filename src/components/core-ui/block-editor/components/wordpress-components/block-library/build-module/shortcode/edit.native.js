import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, Text } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { PlainText } from '@wordpress/block-editor';
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './style.scss';
export function ShortcodeEdit(props) {
  var attributes = props.attributes,
      setAttributes = props.setAttributes,
      onFocus = props.onFocus,
      onBlur = props.onBlur,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var titleStyle = getStylesFromColorScheme(styles.blockTitle, styles.blockTitleDark);
  var shortcodeStyle = getStylesFromColorScheme(styles.blockShortcode, styles.blockShortcodeDark);
  var placeholderStyle = getStylesFromColorScheme(styles.placeholder, styles.placeholderDark);
  return createElement(View, null, createElement(Text, {
    style: titleStyle
  }, __('Shortcode')), createElement(PlainText, {
    value: attributes.text,
    style: shortcodeStyle,
    multiline: true,
    underlineColorAndroid: "transparent",
    onChange: function onChange(text) {
      return setAttributes({
        text: text
      });
    },
    placeholder: __('Add a shortcode…'),
    "aria-label": __('Shortcode'),
    isSelected: props.isSelected,
    onFocus: onFocus,
    onBlur: onBlur,
    autoCorrect: false,
    autoComplete: "off",
    placeholderTextColor: placeholderStyle.color
  }));
}
export default withPreferredColorScheme(ShortcodeEdit);
//# sourceMappingURL=edit.native.js.map