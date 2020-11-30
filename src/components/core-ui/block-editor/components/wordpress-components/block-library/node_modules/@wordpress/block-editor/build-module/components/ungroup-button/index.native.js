import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { Toolbar, ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import UngroupIcon from './icon';
export function UngroupButton(_ref) {
  var onConvertFromGroup = _ref.onConvertFromGroup,
      _ref$isUngroupable = _ref.isUngroupable,
      isUngroupable = _ref$isUngroupable === void 0 ? false : _ref$isUngroupable;

  if (!isUngroupable) {
    return null;
  }

  return createElement(Toolbar, null, createElement(ToolbarButton, {
    title: __('Ungroup'),
    icon: UngroupIcon,
    onClick: onConvertFromGroup
  }));
}
export default compose([withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlock = _select.getBlock;

  var _select2 = select('core/blocks'),
      getGroupingBlockName = _select2.getGroupingBlockName;

  var selectedId = getSelectedBlockClientId();
  var selectedBlock = getBlock(selectedId);
  var groupingBlockName = getGroupingBlockName();
  var isUngroupable = selectedBlock && selectedBlock.innerBlocks && !!selectedBlock.innerBlocks.length && selectedBlock.name === groupingBlockName;
  var innerBlocks = isUngroupable ? selectedBlock.innerBlocks : [];
  return {
    isUngroupable: isUngroupable,
    clientId: selectedId,
    innerBlocks: innerBlocks
  };
}), withDispatch(function (dispatch, _ref2) {
  var clientId = _ref2.clientId,
      innerBlocks = _ref2.innerBlocks,
      _ref2$onToggle = _ref2.onToggle,
      onToggle = _ref2$onToggle === void 0 ? noop : _ref2$onToggle;

  var _dispatch = dispatch('core/block-editor'),
      replaceBlocks = _dispatch.replaceBlocks;

  return {
    onConvertFromGroup: function onConvertFromGroup() {
      if (!innerBlocks.length) {
        return;
      }

      replaceBlocks(clientId, innerBlocks);
      onToggle();
    }
  };
})])(UngroupButton);
//# sourceMappingURL=index.native.js.map