"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockActions;

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _copyHandler = require("../copy-handler");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockActions(_ref) {
  var clientIds = _ref.clientIds,
      children = _ref.children,
      updateSelection = _ref.__experimentalUpdateSelection;

  var _useSelect = (0, _data.useSelect)(function (select) {
    return select('core/block-editor');
  }, []),
      canInsertBlockType = _useSelect.canInsertBlockType,
      getBlockRootClientId = _useSelect.getBlockRootClientId,
      getBlocksByClientId = _useSelect.getBlocksByClientId,
      getTemplateLock = _useSelect.getTemplateLock;

  var _useSelect2 = (0, _data.useSelect)(function (select) {
    return select('core/blocks');
  }, []),
      getDefaultBlockName = _useSelect2.getDefaultBlockName,
      getGroupingBlockName = _useSelect2.getGroupingBlockName;

  var blocks = getBlocksByClientId(clientIds);
  var rootClientId = getBlockRootClientId(clientIds[0]);
  var canDuplicate = (0, _lodash.every)(blocks, function (block) {
    return !!block && (0, _blocks.hasBlockSupport)(block.name, 'multiple', true) && canInsertBlockType(block.name, rootClientId);
  });
  var canInsertDefaultBlock = canInsertBlockType(getDefaultBlockName(), rootClientId);

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      removeBlocks = _useDispatch.removeBlocks,
      replaceBlocks = _useDispatch.replaceBlocks,
      duplicateBlocks = _useDispatch.duplicateBlocks,
      insertAfterBlock = _useDispatch.insertAfterBlock,
      insertBeforeBlock = _useDispatch.insertBeforeBlock,
      flashBlock = _useDispatch.flashBlock,
      setBlockMovingClientId = _useDispatch.setBlockMovingClientId,
      setNavigationMode = _useDispatch.setNavigationMode,
      selectBlock = _useDispatch.selectBlock;

  var notifyCopy = (0, _copyHandler.useNotifyCopy)();
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
      insertBeforeBlock((0, _lodash.first)((0, _lodash.castArray)(clientIds)));
    },
    onInsertAfter: function onInsertAfter() {
      insertAfterBlock((0, _lodash.last)((0, _lodash.castArray)(clientIds)));
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

      var newBlocks = (0, _blocks.switchToBlockType)(blocks, groupingBlockName);

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