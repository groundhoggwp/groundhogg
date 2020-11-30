import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { getBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { Flex, FlexItem } from '@wordpress/components';
import { dragHandle } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
export default function BlockDraggableChip(_ref) {
  var clientIds = _ref.clientIds;
  var icon = useSelect(function (select) {
    var _getBlockType;

    if (clientIds.length !== 1) {
      return;
    }

    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName;

    var _clientIds = _slicedToArray(clientIds, 1),
        firstId = _clientIds[0];

    var blockName = getBlockName(firstId);
    return (_getBlockType = getBlockType(blockName)) === null || _getBlockType === void 0 ? void 0 : _getBlockType.icon;
  }, [clientIds]);
  return createElement("div", {
    className: "block-editor-block-draggable-chip-wrapper"
  }, createElement("div", {
    className: "block-editor-block-draggable-chip"
  }, createElement(Flex, {
    justify: "center",
    className: "block-editor-block-draggable-chip__content"
  }, createElement(FlexItem, null, icon ? createElement(BlockIcon, {
    icon: icon
  }) : sprintf(
  /* translators: %d: Number of blocks. */
  _n('%d block', '%d blocks', clientIds.length), clientIds.length)), createElement(FlexItem, null, createElement(BlockIcon, {
    icon: dragHandle
  })))));
}
//# sourceMappingURL=draggable-chip.js.map