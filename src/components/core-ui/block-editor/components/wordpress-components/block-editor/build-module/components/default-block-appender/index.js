import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import TextareaAutosize from 'react-autosize-textarea';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { getDefaultBlockName } from '@wordpress/blocks';
import { decodeEntities } from '@wordpress/html-entities';
import { withSelect, withDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import Inserter from '../inserter';
export function DefaultBlockAppender(_ref) {
  var isLocked = _ref.isLocked,
      isVisible = _ref.isVisible,
      onAppend = _ref.onAppend,
      showPrompt = _ref.showPrompt,
      placeholder = _ref.placeholder,
      rootClientId = _ref.rootClientId;

  if (isLocked || !isVisible) {
    return null;
  }

  var value = decodeEntities(placeholder) || __('Start writing or type / to choose a block'); // The appender "button" is in-fact a text field so as to support
  // transitions by WritingFlow occurring by arrow key press. WritingFlow
  // only supports tab transitions into text fields and to the block focus
  // boundary.
  //
  // See: https://github.com/WordPress/gutenberg/issues/4829#issuecomment-374213658
  //
  // If it were ever to be made to be a proper `button` element, it is
  // important to note that `onFocus` alone would not be sufficient to
  // capture click events, notably in Firefox.
  //
  // See: https://gist.github.com/cvrebert/68659d0333a578d75372
  // The wp-block className is important for editor styles.


  return createElement("div", {
    "data-root-client-id": rootClientId || '',
    className: "wp-block block-editor-default-block-appender"
  }, createElement(TextareaAutosize, {
    role: "button",
    "aria-label": __('Add block'),
    className: "block-editor-default-block-appender__content",
    readOnly: true,
    onFocus: onAppend,
    value: showPrompt ? value : ''
  }), createElement(Inserter, {
    rootClientId: rootClientId,
    position: "bottom right",
    isAppender: true,
    __experimentalIsQuick: true
  }));
}
export default compose(withSelect(function (select, ownProps) {
  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockName = _select.getBlockName,
      isBlockValid = _select.isBlockValid,
      getSettings = _select.getSettings,
      getTemplateLock = _select.getTemplateLock;

  var isEmpty = !getBlockCount(ownProps.rootClientId);
  var isLastBlockDefault = getBlockName(ownProps.lastBlockClientId) === getDefaultBlockName();
  var isLastBlockValid = isBlockValid(ownProps.lastBlockClientId);

  var _getSettings = getSettings(),
      bodyPlaceholder = _getSettings.bodyPlaceholder;

  return {
    isVisible: isEmpty || !isLastBlockDefault || !isLastBlockValid,
    showPrompt: isEmpty,
    isLocked: !!getTemplateLock(ownProps.rootClientId),
    placeholder: bodyPlaceholder
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
//# sourceMappingURL=index.js.map