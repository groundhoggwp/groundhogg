import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { Caption, RichText } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import styles from './styles.scss';

var BlockCaption = function BlockCaption(_ref) {
  var accessible = _ref.accessible,
      accessibilityLabelCreator = _ref.accessibilityLabelCreator,
      onBlur = _ref.onBlur,
      onChange = _ref.onChange,
      onFocus = _ref.onFocus,
      isSelected = _ref.isSelected,
      shouldDisplay = _ref.shouldDisplay,
      text = _ref.text,
      insertBlocksAfter = _ref.insertBlocksAfter;
  return createElement(View, {
    style: [styles.container, shouldDisplay && styles.padding]
  }, createElement(Caption, {
    accessibilityLabelCreator: accessibilityLabelCreator,
    accessible: accessible,
    isSelected: isSelected,
    onBlur: onBlur,
    onChange: onChange,
    onFocus: onFocus,
    shouldDisplay: shouldDisplay,
    value: text,
    insertBlocksAfter: insertBlocksAfter
  }));
};

export default compose([withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockAttributes = _select.getBlockAttributes,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var _ref3 = getBlockAttributes(clientId) || {},
      caption = _ref3.caption;

  var isBlockSelected = getSelectedBlockClientId() === clientId; // We'll render the caption so that the soft keyboard is not forced to close on Android
  // but still hide it by setting its display style to none. See wordpress-mobile/gutenberg-mobile#1221

  var shouldDisplay = !RichText.isEmpty(caption) > 0 || isBlockSelected;
  return {
    shouldDisplay: shouldDisplay,
    text: caption
  };
}), withDispatch(function (dispatch, _ref4) {
  var clientId = _ref4.clientId;

  var _dispatch = dispatch('core/block-editor'),
      updateBlockAttributes = _dispatch.updateBlockAttributes;

  return {
    onChange: function onChange(caption) {
      updateBlockAttributes(clientId, {
        caption: caption
      });
    }
  };
})])(BlockCaption);
//# sourceMappingURL=index.native.js.map