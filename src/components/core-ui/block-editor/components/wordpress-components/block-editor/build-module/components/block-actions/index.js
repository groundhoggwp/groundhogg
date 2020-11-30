/**
 * External dependencies
 */
import { castArray, first, last, every } from 'lodash';
/**
 * WordPress dependencies
 */

import { useDispatch, useSelect } from '@wordpress/data';
import { hasBlockSupport, switchToBlockType } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { useNotifyCopy } from '../copy-handler';
export default function BlockActions(_ref) {
  var clientIds = _ref.clientIds,
      children = _ref.children,
      updateSelection = _ref.__experimentalUpdateSelection;

  var _useSelect = useSelect(function (select) {
    return select('core/block-editor');
  }, []),
      canInsertBlockType = _useSelect.canInsertBlockType,
      getBlockRootClientId = _useSelect.getBlockRootClientId,
      getBlocksByClientId = _useSelect.getBlocksByClientId,
      getTemplateLock = _useSelect.getTemplateLock;

  var _useSelect2 = useSelect(function (select) {
    return select('core/blocks');
  }, []),
      getDefaultBlockName = _useSelect2.getDefaultBlockName,
      getGroupingBlockName = _useSelect2.getGroupingBlockName;

  var blocks = getBlocksByClientId(clientIds);
  var rootClientId = getBlockRootClientId(clientIds[0]);
  var canDuplicate = every(blocks, function (block) {
    return !!block && hasBlockSupport(block.name, 'multiple', true) && canInsertBlockType(block.name, rootClientId);
  });
  var canInsertDefaultBlock = canInsertBlockType(getDefaultBlockName(), rootClientId);

  var _useDispatch = useDispatch('core/block-editor'),
      removeBlocks = _useDispatch.removeBlocks,
      replaceBlocks = _useDispatch.replaceBlocks,
      duplicateBlocks = _useDispatch.duplicateBlocks,
      insertAfterBlock = _useDispatch.insertAfterBlock,
      insertBeforeBlock = _useDispatch.insertBeforeBlock,
      flashBlock = _useDispatch.flashBlock,
      setBlockMovingClientId = _useDispatch.setBlockMovingClientId,
      setNavigationMode = _useDispatch.setNavigationMode,
      selectBlock = _useDispatch.selectBlock;

  var notifyCopy = useNotifyCopy();
  return children({
    canDuplicate: canDuplicate,
    canInsertDefaultBlock: canInsertDefaultBlock,
    isLocked: !!getTemplateLock(rootClientId),
    rootClientId: rootClientId,
    blocks: blocks,
    onDuplicate: function onDuplicate() {
      return duplicateBlocks(clientIds, updateSelection);
    },
    onRemove: function onRemove() {
      return removeBlocks(clientIds, updateSelection);
    },
    onInsertBefore: function onInsertBefore() {
      insertBeforeBlock(first(castArray(clientIds)));
    },
    onInsertAfter: function onInsertAfter() {
      insertAfterBlock(last(castArray(clientIds)));
    },
    onMoveTo: function onMoveTo() {
      setNavigationMode(true);
      selectBlock(clientIds[0]);
      setBlockMovingClientId(clientIds[0]);
    },
    onGroup: function onGroup() {
      if (!blocks.length) {
        return;
      }

      var groupingBlockName = getGroupingBlockName(); // Activate the `transform` on `core/group` which does the conversion

      var newBlocks = switchToBlockType(blocks, groupingBlockName);

      if (!newBlocks) {
        return;
      }

      replaceBlocks(clientIds, newBlocks);
    },
    onUngroup: function onUngroup() {
      if (!blocks.length) {
        return;
      }

      var innerBlocks = blocks[0].innerBlocks;

      if (!innerBlocks.length) {
        return;
      }

      replaceBlocks(clientIds, innerBlocks);
    },
    onCopy: function onCopy() {
      var selectedBlockClientIds = blocks.map(function (_ref2) {
        var clientId = _ref2.clientId;
        return clientId;
      });

      if (blocks.length === 1) {
        flashBlock(selectedBlockClientIds[0]);
      }

      notifyCopy('copy', selectedBlockClientIds);
    }
  });
}
//# sourceMappingURL=index.js.map