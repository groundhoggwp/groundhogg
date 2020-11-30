import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { createBlock } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

var Caption = function Caption(_ref) {
  var accessibilityLabelCreator = _ref.accessibilityLabelCreator,
      accessible = _ref.accessible,
      inlineToolbar = _ref.inlineToolbar,
      isSelected = _ref.isSelected,
      onBlur = _ref.onBlur,
      onChange = _ref.onChange,
      onFocus = _ref.onFocus,
      _ref$placeholder = _ref.placeholder,
      placeholder = _ref$placeholder === void 0 ? __('Write caption…') : _ref$placeholder,
      placeholderTextColor = _ref.placeholderTextColor,
      _ref$shouldDisplay = _ref.shouldDisplay,
      shouldDisplay = _ref$shouldDisplay === void 0 ? true : _ref$shouldDisplay,
      style = _ref.style,
      value = _ref.value,
      _ref$insertBlocksAfte = _ref.insertBlocksAfter,
      insertBlocksAfter = _ref$insertBlocksAfte === void 0 ? function () {} : _ref$insertBlocksAfte;
  return createElement(View, {
    accessibilityLabel: accessibilityLabelCreator ? accessibilityLabelCreator(value) : undefined,
    accessibilityRole: "button",
    accessible: accessible,
    style: {
      flex: 1,
      display: shouldDisplay ? 'flex' : 'none'
    }
  }, createElement(RichText, {
    __unstableMobileNoFocusOnMount: true,
    fontSize: style && style.fontSize ? style.fontSize : 14,
    inlineToolbar: inlineToolbar,
    isSelected: isSelected,
    onBlur: onBlur,
    onChange: onChange,
    placeholder: placeholder,
    placeholderTextColor: placeholderTextColor,
    rootTagsToEliminate: ['p'],
    style: style,
    tagName: "p",
    textAlign: "center",
    underlineColorAndroid: "transparent",
    unstableOnFocus: onFocus,
    value: value,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter(createBlock('core/paragraph'));
    },
    deleteEnter: true
  }));
};

export default Caption;
//# sourceMappingURL=index.native.js.map