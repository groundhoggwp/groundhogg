import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { getBlockType, hasBlockSupport } from '@wordpress/blocks';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
export function BlockModeToggle(_ref) {
  var blockType = _ref.blockType,
      mode = _ref.mode,
      onToggleMode = _ref.onToggleMode,
      _ref$small = _ref.small,
      small = _ref$small === void 0 ? false : _ref$small,
      _ref$isCodeEditingEna = _ref.isCodeEditingEnabled,
      isCodeEditingEnabled = _ref$isCodeEditingEna === void 0 ? true : _ref$isCodeEditingEna;

  if (!hasBlockSupport(blockType, 'html', true) || !isCodeEditingEnabled) {
    return null;
  }

  var label = mode === 'visual' ? __('Edit as HTML') : __('Edit visually');
  return createElement(MenuItem, {
    onClick: onToggleMode
  }, !small && label);
}
export default compose([withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock,
      getBlockMode = _select.getBlockMode,
      getSettings = _select.getSettings;

  var block = getBlock(clientId);
  var isCodeEditingEnabled = getSettings().codeEditingEnabled;
  return {
    mode: getBlockMode(clientId),
    blockType: block ? getBlockType(block.name) : null,
    isCodeEditingEnabled: isCodeEditingEnabled
  };
}), withDispatch(function (dispatch, _ref3) {
  var _ref3$onToggle = _ref3.onToggle,
      onToggle = _ref3$onToggle === void 0 ? noop : _ref3$onToggle,
      clientId = _ref3.clientId;
  return {
    onToggleMode: function onToggleMode() {
      dispatch('core/block-editor').toggleBlockMode(clientId);
      onToggle();
    }
  };
})])(BlockModeToggle);
//# sourceMappingURL=block-mode-toggle.js.map