"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _moverDescription = require("../block-mover/mover-description");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
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
  var pickerRef = (0, _element.useRef)();
  var moversOptions = {
    keys: ['icon', 'actionTitle']
  };

  var _getMoversSetup = (0, _moverDescription.getMoversSetup)(isStackedHorizontally, moversOptions),
      _getMoversSetup$actio = _getMoversSetup.actionTitle,
      backwardButtonTitle = _getMoversSetup$actio.backward,
      forwardButtonTitle = _getMoversSetup$actio.forward;

  var deleteOption = {
    id: 'deleteOption',
    label: (0, _i18n.__)('Remove block'),
    value: 'deleteOption',
    separated: true,
    disabled: isEmptyDefaultBlock
  };
  var settingsOption = {
    id: 'settingsOption',
    label: (0, _i18n.__)('Block settings'),
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
    label: (0, _i18n.__)('Copy block'),
    value: 'copyButtonOption'
  };
  var cutButtonOption = {
    id: 'cutButtonOption',
    label: (0, _i18n.__)('Cut block'),
    value: 'cutButtonOption'
  };
  var pasteButtonOption = {
    id: 'pasteButtonOption',
    label: (0, _i18n.__)('Paste block after'),
    value: 'pasteButtonOption'
  };
  var duplicateButtonOption = {
    id: 'duplicateButtonOption',
    label: (0, _i18n.__)('Duplicate block'),
    value: 'duplicateButtonOption'
  };
  var options = (0, _lodash.compact)([wrapBlockMover && backwardButtonOption, wrapBlockMover && forwardButtonOption, wrapBlockSettings && settingsOption, copyButtonOption, cutButtonOption, isPasteEnabled && pasteButtonOption, duplicateButtonOption, deleteOption]);

  function onPickerSelect(value) {
    switch (value) {
      case deleteOption.value:
        onDelete();
        createInfoNotice( // translators: displayed right after the block is removed.
        (0, _i18n.__)('Block removed'));
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
        updateClipboard((0, _blocks.serialize)(copyBlock));
        createInfoNotice( // translators: displayed right after the block is copied.
        (0, _i18n.__)('Block copied'));
        break;

      case cutButtonOption.value:
        var cutBlock = getBlocksByClientId(selectedBlockClientId);
        updateClipboard((0, _blocks.serialize)(cutBlock));
        removeBlocks(selectedBlockClientId);
        createInfoNotice( // translators: displayed right after the block is cut.
        (0, _i18n.__)('Block cut'));
        break;

      case pasteButtonOption.value:
        pasteBlock();
        createInfoNotice( // translators: displayed right after the block is pasted.
        (0, _i18n.__)('Block pasted'));
        break;

      case duplicateButtonOption.value:
        duplicateBlock();
        createInfoNotice( // translators: displayed right after the block is duplicated.
        (0, _i18n.__)('Block duplicated'));
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
  var accessibilityHint = _reactNative.Platform.OS === 'ios' ? (0, _i18n.__)('Double tap to open Action Sheet with available options') : (0, _i18n.__)('Double tap to open Bottom Sheet with available options');
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Open Block Actions Menu'),
    onClick: onPickerPresent,
    icon: _icons.moreHorizontalMobile,
    extraProps: {
      hint: accessibilityHint
    }
  }), (0, _element.createElement)(_components.Picker, {
    ref: pickerRef,
    options: options,
    onChange: onPickerSelect,
    destructiveButtonIndex: options.length,
    disabledButtonIndices: disabledButtonIndices,
    hideCancelButton: _reactNative.Platform.OS !== 'ios',
    leftAlign: true,
    anchor: anchorNodeRef ? (0, _reactNative.findNodeHandle)(anchorNodeRef) : undefined // translators: %s: block title e.g: "Paragraph".
    ,
    title: (0, _i18n.sprintf)((0, _i18n.__)('%s block options'), blockTitle)
  }));
};

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref2) {
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

  var normalizedClientIds = (0, _lodash.castArray)(clientIds);
  var block = getBlock(normalizedClientIds);
  var blockName = getBlockName(normalizedClientIds);
  var blockType = (0, _blocks.getBlockType)(blockName);
  var blockTitle = blockType.title;
  var firstClientId = (0, _lodash.first)(normalizedClientIds);
  var rootClientId = getBlockRootClientId(firstClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex((0, _lodash.last)(normalizedClientIds), rootClientId);
  var isDefaultBlock = blockName === (0, _blocks.getDefaultBlockName)();
  var isEmptyContent = block.attributes.content === '';
  var isExactlyOneBlock = blockOrder.length === 1;
  var isEmptyDefaultBlock = isExactlyOneBlock && isDefaultBlock && isEmptyContent;
  var clipboard = getClipboard();
  var clipboardBlock = clipboard && (0, _blocks.rawHandler)({
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
}), (0, _data.withDispatch)(function (dispatch, _ref3, _ref4) {
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
    onMoveDown: (0, _lodash.partial)(moveBlocksDown, clientIds, rootClientId),
    onMoveUp: (0, _lodash.partial)(moveBlocksUp, clientIds, rootClientId),
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
      var canReplaceBlock = (0, _blocks.isUnmodifiedDefaultBlock)(getBlock(getBlockSelectionEnd()));

      if (!canReplaceBlock) {
        var insertedBlock = (0, _blocks.createBlock)(clipboardBlock.name, clipboardBlock.attributes, clipboardBlock.innerBlocks);
        insertBlock(insertedBlock, currentIndex + 1, rootClientId);
      } else {
        replaceBlocks(clientIds, clipboardBlock);
      }
    }
  };
}), _compose.withInstanceId)(BlockActionsMenu);

exports.default = _default;
//# sourceMappingURL=block-actions-menu.native.js.map