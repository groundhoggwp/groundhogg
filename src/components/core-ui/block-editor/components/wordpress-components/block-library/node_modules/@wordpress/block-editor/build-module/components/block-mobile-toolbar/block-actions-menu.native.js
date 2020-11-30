import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { Platform, findNodeHandle } from 'react-native';
import { partial, first, castArray, last, compact } from 'lodash';
/**
 * WordPress dependencies
 */

import { ToolbarButton, Picker } from '@wordpress/components';
import { getBlockType, getDefaultBlockName, serialize, rawHandler, createBlock, isUnmodifiedDefaultBlock } from '@wordpress/blocks';
import { __, sprintf } from '@wordpress/i18n';
import { withDispatch, withSelect } from '@wordpress/data';
import { withInstanceId, compose } from '@wordpress/compose';
import { moreHorizontalMobile } from '@wordpress/icons';
import { useRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { getMoversSetup } from '../block-mover/mover-description';

var BlockActionsMenu = function BlockActionsMenu(_ref) {
  var onDelete = _ref.onDelete,
      isStackedHorizontally = _ref.isStackedHorizontally,
      wrapBlockSettings = _ref.wrapBlockSettings,
      wrapBlockMover = _ref.wrapBlockMover,
      openGeneralSidebar = _ref.openGeneralSidebar,
      onMoveDown = _ref.onMoveDown,
      onMoveUp = _ref.onMoveUp,
      isFirst = _ref.isFirst,
      isLast = _ref.isLast,
      blockTitle = _ref.blockTitle,
      isEmptyDefaultBlock = _ref.isEmptyDefaultBlock,
      anchorNodeRef = _ref.anchorNodeRef,
      getBlocksByClientId = _ref.getBlocksByClientId,
      selectedBlockClientId = _ref.selectedBlockClientId,
      updateClipboard = _ref.updateClipboard,
      createInfoNotice = _ref.createInfoNotice,
      duplicateBlock = _ref.duplicateBlock,
      removeBlocks = _ref.removeBlocks,
      pasteBlock = _ref.pasteBlock,
      isPasteEnabled = _ref.isPasteEnabled;
  var pickerRef = useRef();
  var moversOptions = {
    keys: ['icon', 'actionTitle']
  };

  var _getMoversSetup = getMoversSetup(isStackedHorizontally, moversOptions),
      _getMoversSetup$actio = _getMoversSetup.actionTitle,
      backwardButtonTitle = _getMoversSetup$actio.backward,
      forwardButtonTitle = _getMoversSetup$actio.forward;

  var deleteOption = {
    id: 'deleteOption',
    label: __('Remove block'),
    value: 'deleteOption',
    separated: true,
    disabled: isEmptyDefaultBlock
  };
  var settingsOption = {
    id: 'settingsOption',
    label: __('Block settings'),
    value: 'settingsOption'
  };
  var backwardButtonOption = {
    id: 'backwardButtonOption',
    label: backwardButtonTitle,
    value: 'backwardButtonOption',
    disabled: isFirst
  };
  var forwardButtonOption = {
    id: 'forwardButtonOption',
    label: forwardButtonTitle,
    value: 'forwardButtonOption',
    disabled: isLast
  };
  var copyButtonOption = {
    id: 'copyButtonOption',
    label: __('Copy block'),
    value: 'copyButtonOption'
  };
  var cutButtonOption = {
    id: 'cutButtonOption',
    label: __('Cut block'),
    value: 'cutButtonOption'
  };
  var pasteButtonOption = {
    id: 'pasteButtonOption',
    label: __('Paste block after'),
    value: 'pasteButtonOption'
  };
  var duplicateButtonOption = {
    id: 'duplicateButtonOption',
    label: __('Duplicate block'),
    value: 'duplicateButtonOption'
  };
  var options = compact([wrapBlockMover && backwardButtonOption, wrapBlockMover && forwardButtonOption, wrapBlockSettings && settingsOption, copyButtonOption, cutButtonOption, isPasteEnabled && pasteButtonOption, duplicateButtonOption, deleteOption]);

  function onPickerSelect(value) {
    switch (value) {
      case deleteOption.value:
        onDelete();
        createInfoNotice( // translators: displayed right after the block is removed.
        __('Block removed'));
        break;

      case settingsOption.value:
        openGeneralSidebar();
        break;

      case forwardButtonOption.value:
        onMoveDown();
        break;

      case backwardButtonOption.value:
        onMoveUp();
        break;

      case copyButtonOption.value:
        var copyBlock = getBlocksByClientId(selectedBlockClientId);
        updateClipboard(serialize(copyBlock));
        createInfoNotice( // translators: displayed right after the block is copied.
        __('Block copied'));
        break;

      case cutButtonOption.value:
        var cutBlock = getBlocksByClientId(selectedBlockClientId);
        updateClipboard(serialize(cutBlock));
        removeBlocks(selectedBlockClientId);
        createInfoNotice( // translators: displayed right after the block is cut.
        __('Block cut'));
        break;

      case pasteButtonOption.value:
        pasteBlock();
        createInfoNotice( // translators: displayed right after the block is pasted.
        __('Block pasted'));
        break;

      case duplicateButtonOption.value:
        duplicateBlock();
        createInfoNotice( // translators: displayed right after the block is duplicated.
        __('Block duplicated'));
        break;
    }
  }

  function onPickerPresent() {
    if (pickerRef.current) {
      pickerRef.current.presentPicker();
    }
  }

  var disabledButtonIndices = options.map(function (option, index) {
    return option.disabled && index + 1;
  }).filter(Boolean);
  var accessibilityHint = Platform.OS === 'ios' ? __('Double tap to open Action Sheet with available options') : __('Double tap to open Bottom Sheet with available options');
  return createElement(Fragment, null, createElement(ToolbarButton, {
    title: __('Open Block Actions Menu'),
    onClick: onPickerPresent,
    icon: moreHorizontalMobile,
    extraProps: {
      hint: accessibilityHint
    }
  }), createElement(Picker, {
    ref: pickerRef,
    options: options,
    onChange: onPickerSelect,
    destructiveButtonIndex: options.length,
    disabledButtonIndices: disabledButtonIndices,
    hideCancelButton: Platform.OS !== 'ios',
    leftAlign: true,
    anchor: anchorNodeRef ? findNodeHandle(anchorNodeRef) : undefined // translators: %s: block title e.g: "Paragraph".
    ,
    title: sprintf(__('%s block options'), blockTitle)
  }));
};

export default compose(withSelect(function (select, _ref2) {
  var clientIds = _ref2.clientIds;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockOrder = _select.getBlockOrder,
      getBlockName = _select.getBlockName,
      getBlock = _select.getBlock,
      getBlocksByClientId = _select.getBlocksByClientId,
      getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
      canInsertBlockType = _select.canInsertBlockType;

  var _select2 = select('core/editor'),
      getClipboard = _select2.getClipboard;

  var normalizedClientIds = castArray(clientIds);
  var block = getBlock(normalizedClientIds);
  var blockName = getBlockName(normalizedClientIds);
  var blockType = getBlockType(blockName);
  var blockTitle = blockType.title;
  var firstClientId = first(normalizedClientIds);
  var rootClientId = getBlockRootClientId(firstClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex(last(normalizedClientIds), rootClientId);
  var isDefaultBlock = blockName === getDefaultBlockName();
  var isEmptyContent = block.attributes.content === '';
  var isExactlyOneBlock = blockOrder.length === 1;
  var isEmptyDefaultBlock = isExactlyOneBlock && isDefaultBlock && isEmptyContent;
  var clipboard = getClipboard();
  var clipboardBlock = clipboard && rawHandler({
    HTML: clipboard
  })[0];
  var isPasteEnabled = clipboardBlock && canInsertBlockType(clipboardBlock.name, rootClientId);
  return {
    isFirst: firstIndex === 0,
    isLast: lastIndex === blockOrder.length - 1,
    rootClientId: rootClientId,
    blockTitle: blockTitle,
    isEmptyDefaultBlock: isEmptyDefaultBlock,
    getBlocksByClientId: getBlocksByClientId,
    selectedBlockClientId: getSelectedBlockClientIds(),
    currentIndex: firstIndex,
    isPasteEnabled: isPasteEnabled,
    clipboardBlock: clipboardBlock
  };
}), withDispatch(function (dispatch, _ref3, _ref4) {
  var clientIds = _ref3.clientIds,
      rootClientId = _ref3.rootClientId,
      currentIndex = _ref3.currentIndex,
      clipboardBlock = _ref3.clipboardBlock;
  var select = _ref4.select;

  var _dispatch = dispatch('core/block-editor'),
      moveBlocksDown = _dispatch.moveBlocksDown,
      moveBlocksUp = _dispatch.moveBlocksUp,
      duplicateBlocks = _dispatch.duplicateBlocks,
      removeBlocks = _dispatch.removeBlocks,
      insertBlock = _dispatch.insertBlock,
      replaceBlocks = _dispatch.replaceBlocks;

  var _dispatch2 = dispatch('core/edit-post'),
      _openGeneralSidebar = _dispatch2.openGeneralSidebar;

  var _dispatch3 = dispatch('core/editor'),
      updateClipboard = _dispatch3.updateClipboard,
      createInfoNotice = _dispatch3.createInfoNotice;

  var _select3 = select('core/block-editor'),
      getBlockSelectionEnd = _select3.getBlockSelectionEnd,
      getBlock = _select3.getBlock;

  return {
    onMoveDown: partial(moveBlocksDown, clientIds, rootClientId),
    onMoveUp: partial(moveBlocksUp, clientIds, rootClientId),
    openGeneralSidebar: function openGeneralSidebar() {
      return _openGeneralSidebar('edit-post/block');
    },
    updateClipboard: updateClipboard,
    createInfoNotice: createInfoNotice,
    duplicateBlock: function duplicateBlock() {
      return duplicateBlocks(clientIds);
    },
    removeBlocks: removeBlocks,
    pasteBlock: function pasteBlock() {
      var canReplaceBlock = isUnmodifiedDefaultBlock(getBlock(getBlockSelectionEnd()));

      if (!canReplaceBlock) {
        var insertedBlock = createBlock(clipboardBlock.name, clipboardBlock.attributes, clipboardBlock.innerBlocks);
        insertBlock(insertedBlock, currentIndex + 1, rootClientId);
      } else {
        replaceBlocks(clientIds, clipboardBlock);
      }
    }
  };
}), withInstanceId)(BlockActionsMenu);
//# sourceMappingURL=block-actions-menu.native.js.map