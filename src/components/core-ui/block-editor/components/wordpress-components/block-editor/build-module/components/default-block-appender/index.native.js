import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { TouchableWithoutFeedback, View } from 'react-native';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { withSelect, withDispatch } from '@wordpress/data';
import { getDefaultBlockName } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import BlockInsertionPoint from '../block-list/insertion-point';
import styles from './style.scss';
export function DefaultBlockAppender(_ref) {
  var isLocked = _ref.isLocked,
      isVisible = _ref.isVisible,
      onAppend = _ref.onAppend,
      placeholder = _ref.placeholder,
      containerStyle = _ref.containerStyle,
      showSeparator = _ref.showSeparator;

  if (isLocked || !isVisible) {
    return null;
  }

  var value = typeof placeholder === 'string' ? decodeEntities(placeholder) : __('Start writing…');
  return createElement(TouchableWithoutFeedback, {
    onPress: onAppend
  }, createElement(View, {
    style: [styles.blockHolder, showSeparator && containerStyle],
    pointerEvents: "box-only"
  }, showSeparator ? createElement(BlockInsertionPoint, null) : createElement(RichText, {
    placeholder: value,
    onChange: function onChange() {}
  })));
}
export default compose(withSelect(function (select, ownProps) {
  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockName = _select.getBlockName,
      isBlockValid = _select.isBlockValid,
      getTemplateLock = _select.getTemplateLock;

  var isEmpty = !getBlockCount(ownProps.rootClientId);
  var isLastBlockDefault = getBlockName(ownProps.lastBlockClientId) === getDefaultBlockName();
  var isLastBlockValid = isBlockValid(ownProps.lastBlockClientId);
  return {
    isVisible: isEmpty || !isLastBlockDefault || !isLastBlockValid,
    isLocked: !!getTemplateLock(ownProps.rootClientId)
  };
}), withDispatch(function (dispatch, ownProps) {
  var _dispatch = dispatch('core/block-editor'),
      insertDefaultBlock = _dispatch.insertDefaultBlock,
      startTyping = _dispatch.startTyping;

  return {
    onAppend: function onAppend() {
      var rootClientId = ownProps.rootClientId;
      insertDefaultBlock(undefined, rootClientId);
      startTyping();
    }
  };
}))(DefaultBlockAppender);
//# sourceMappingURL=index.native.js.map