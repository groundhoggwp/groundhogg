import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { useState, createContext, useMemo, useCallback } from '@wordpress/element';
import { getBlockType, getSaveElement, isReusableBlock, isUnmodifiedDefaultBlock, getUnregisteredTypeHandlerName, hasBlockSupport, getBlockDefaultClassName } from '@wordpress/blocks';
import { withFilters } from '@wordpress/components';
import { withDispatch, withSelect, useSelect, useDispatch } from '@wordpress/data';
import { withViewportMatch } from '@wordpress/viewport';
import { compose, pure, ifCondition } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BlockEdit from '../block-edit';
import BlockInvalidWarning from './block-invalid-warning';
import BlockCrashWarning from './block-crash-warning';
import BlockCrashBoundary from './block-crash-boundary';
import BlockHtml from './block-html';
import { useBlockWrapperProps } from './block-wrapper';
export var BlockListBlockContext = createContext();
/**
 * Merges wrapper props with special handling for classNames and styles.
 *
 * @param {Object} propsA
 * @param {Object} propsB
 *
 * @return {Object} Merged props.
 */

function mergeWrapperProps(propsA, propsB) {
  var newProps = _objectSpread(_objectSpread({}, propsA), propsB);

  if (propsA && propsB && propsA.className && propsB.className) {
    newProps.className = classnames(propsA.className, propsB.className);
  }

  if (propsA && propsB && propsA.style && propsB.style) {
    newProps.style = _objectSpread(_objectSpread({}, propsA.style), propsB.style);
  }

  return newProps;
}

function Block(_ref) {
  var children = _ref.children,
      isHtml = _ref.isHtml,
      props = _objectWithoutProperties(_ref, ["children", "isHtml"]);

  return createElement("div", useBlockWrapperProps(props, {
    __unstableIsHtml: isHtml
  }), children);
}

function BlockListBlock(_ref2) {
  var mode = _ref2.mode,
      isFocusMode = _ref2.isFocusMode,
      isLocked = _ref2.isLocked,
      clientId = _ref2.clientId,
      rootClientId = _ref2.rootClientId,
      isSelected = _ref2.isSelected,
      isMultiSelected = _ref2.isMultiSelected,
      isPartOfMultiSelection = _ref2.isPartOfMultiSelection,
      isFirstMultiSelected = _ref2.isFirstMultiSelected,
      isLastMultiSelected = _ref2.isLastMultiSelected,
      isTypingWithinBlock = _ref2.isTypingWithinBlock,
      isAncestorOfSelectedBlock = _ref2.isAncestorOfSelectedBlock,
      isSelectionEnabled = _ref2.isSelectionEnabled,
      className = _ref2.className,
      name = _ref2.name,
      isValid = _ref2.isValid,
      attributes = _ref2.attributes,
      wrapperProps = _ref2.wrapperProps,
      setAttributes = _ref2.setAttributes,
      onReplace = _ref2.onReplace,
      onInsertBlocksAfter = _ref2.onInsertBlocksAfter,
      onMerge = _ref2.onMerge,
      toggleSelection = _ref2.toggleSelection,
      index = _ref2.index,
      enableAnimation = _ref2.enableAnimation;

  // In addition to withSelect, we should favor using useSelect in this
  // component going forward to avoid leaking new props to the public API
  // (editor.BlockListBlock filter)
  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        isBlockBeingDragged = _select.isBlockBeingDragged,
        isBlockHighlighted = _select.isBlockHighlighted;

    return {
      isDragging: isBlockBeingDragged(clientId),
      isHighlighted: isBlockHighlighted(clientId)
    };
  }, [clientId]),
      isDragging = _useSelect.isDragging,
      isHighlighted = _useSelect.isHighlighted;

  var _useDispatch = useDispatch('core/block-editor'),
      removeBlock = _useDispatch.removeBlock;

  var onRemove = useCallback(function () {
    return removeBlock(clientId);
  }, [clientId]); // Handling the error state

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      hasError = _useState2[0],
      setErrorState = _useState2[1];

  var onBlockError = function onBlockError() {
    return setErrorState(true);
  };

  var blockType = getBlockType(name);
  var lightBlockWrapper = hasBlockSupport(blockType, 'lightBlockWrapper', false);
  var isUnregisteredBlock = name === getUnregisteredTypeHandlerName(); // Determine whether the block has props to apply to the wrapper.

  if (blockType.getEditWrapperProps) {
    wrapperProps = mergeWrapperProps(wrapperProps, blockType.getEditWrapperProps(attributes));
  }

  var generatedClassName = lightBlockWrapper && hasBlockSupport(blockType, 'className', true) ? getBlockDefaultClassName(name) : null;
  var customClassName = lightBlockWrapper ? attributes.className : null;
  var isAligned = wrapperProps && !!wrapperProps['data-align']; // The wp-block className is important for editor styles.
  // Generate the wrapper class names handling the different states of the
  // block.

  var wrapperClassName = classnames(generatedClassName, customClassName, 'block-editor-block-list__block', {
    'wp-block': !isAligned,
    'has-warning': !isValid || !!hasError || isUnregisteredBlock,
    'is-selected': isSelected && !isDragging,
    'is-highlighted': isHighlighted,
    'is-multi-selected': isMultiSelected,
    'is-reusable': isReusableBlock(blockType),
    'is-dragging': isDragging,
    'is-typing': isTypingWithinBlock,
    'is-focused': isFocusMode && (isSelected || isAncestorOfSelectedBlock),
    'is-focus-mode': isFocusMode,
    'has-child-selected': isAncestorOfSelectedBlock && !isDragging
  }, className); // We wrap the BlockEdit component in a div that hides it when editing in
  // HTML mode. This allows us to render all of the ancillary pieces
  // (InspectorControls, etc.) which are inside `BlockEdit` but not
  // `BlockHTML`, even in HTML mode.

  var blockEdit = createElement(BlockEdit, {
    name: name,
    isSelected: isSelected,
    attributes: attributes,
    setAttributes: setAttributes,
    insertBlocksAfter: isLocked ? undefined : onInsertBlocksAfter,
    onReplace: isLocked ? undefined : onReplace,
    onRemove: isLocked ? undefined : onRemove,
    mergeBlocks: isLocked ? undefined : onMerge,
    clientId: clientId,
    isSelectionEnabled: isSelectionEnabled,
    toggleSelection: toggleSelection
  }); // For aligned blocks, provide a wrapper element so the block can be
  // positioned relative to the block column.

  if (isAligned) {
    var alignmentWrapperProps = {
      'data-align': wrapperProps['data-align']
    };
    blockEdit = createElement("div", _extends({
      className: "wp-block"
    }, alignmentWrapperProps), blockEdit);
  }

  var value = {
    clientId: clientId,
    rootClientId: rootClientId,
    isSelected: isSelected,
    isFirstMultiSelected: isFirstMultiSelected,
    isLastMultiSelected: isLastMultiSelected,
    isPartOfMultiSelection: isPartOfMultiSelection,
    enableAnimation: enableAnimation,
    index: index,
    className: wrapperClassName,
    isLocked: isLocked,
    name: name,
    mode: mode,
    blockTitle: blockType.title,
    wrapperProps: omit(wrapperProps, ['data-align'])
  };
  var memoizedValue = useMemo(function () {
    return value;
  }, Object.values(value));
  var block;

  if (!isValid) {
    block = createElement(Block, null, createElement(BlockInvalidWarning, {
      clientId: clientId
    }), createElement("div", null, getSaveElement(blockType, attributes)));
  } else if (mode === 'html') {
    // Render blockEdit so the inspector controls don't disappear.
    // See #8969.
    block = createElement(Fragment, null, createElement("div", {
      style: {
        display: 'none'
      }
    }, blockEdit), createElement(Block, {
      isHtml: true
    }, createElement(BlockHtml, {
      clientId: clientId
    })));
  } else if (lightBlockWrapper) {
    block = blockEdit;
  } else {
    block = createElement(Block, wrapperProps, blockEdit);
  }

  return createElement(BlockListBlockContext.Provider, {
    value: memoizedValue
  }, createElement(BlockCrashBoundary, {
    onError: onBlockError
  }, block), !!hasError && createElement(Block, null, createElement(BlockCrashWarning, null)));
}

var applyWithSelect = withSelect(function (select, _ref3) {
  var clientId = _ref3.clientId,
      rootClientId = _ref3.rootClientId,
      isLargeViewport = _ref3.isLargeViewport;

  var _select2 = select('core/block-editor'),
      isBlockSelected = _select2.isBlockSelected,
      isAncestorMultiSelected = _select2.isAncestorMultiSelected,
      isBlockMultiSelected = _select2.isBlockMultiSelected,
      isFirstMultiSelectedBlock = _select2.isFirstMultiSelectedBlock,
      getLastMultiSelectedBlockClientId = _select2.getLastMultiSelectedBlockClientId,
      isTyping = _select2.isTyping,
      getBlockMode = _select2.getBlockMode,
      isSelectionEnabled = _select2.isSelectionEnabled,
      getSettings = _select2.getSettings,
      hasSelectedInnerBlock = _select2.hasSelectedInnerBlock,
      getTemplateLock = _select2.getTemplateLock,
      __unstableGetBlockWithoutInnerBlocks = _select2.__unstableGetBlockWithoutInnerBlocks,
      getMultiSelectedBlockClientIds = _select2.getMultiSelectedBlockClientIds;

  var block = __unstableGetBlockWithoutInnerBlocks(clientId);

  var isSelected = isBlockSelected(clientId);

  var _getSettings = getSettings(),
      focusMode = _getSettings.focusMode,
      isRTL = _getSettings.isRTL;

  var templateLock = getTemplateLock(rootClientId);
  var checkDeep = true; // "ancestor" is the more appropriate label due to "deep" check

  var isAncestorOfSelectedBlock = hasSelectedInnerBlock(clientId, checkDeep); // The fallback to `{}` is a temporary fix.
  // This function should never be called when a block is not present in
  // the state. It happens now because the order in withSelect rendering
  // is not correct.

  var _ref4 = block || {},
      name = _ref4.name,
      attributes = _ref4.attributes,
      isValid = _ref4.isValid;

  var isFirstMultiSelected = isFirstMultiSelectedBlock(clientId); // Do not add new properties here, use `useSelect` instead to avoid
  // leaking new props to the public API (editor.BlockListBlock filter).

  return {
    isMultiSelected: isBlockMultiSelected(clientId),
    isPartOfMultiSelection: isBlockMultiSelected(clientId) || isAncestorMultiSelected(clientId),
    isFirstMultiSelected: isFirstMultiSelected,
    isLastMultiSelected: getLastMultiSelectedBlockClientId() === clientId,
    multiSelectedClientIds: isFirstMultiSelected ? getMultiSelectedBlockClientIds() : undefined,
    // We only care about this prop when the block is selected
    // Thus to avoid unnecessary rerenders we avoid updating the prop if
    // the block is not selected.
    isTypingWithinBlock: (isSelected || isAncestorOfSelectedBlock) && isTyping(),
    mode: getBlockMode(clientId),
    isSelectionEnabled: isSelectionEnabled(),
    isLocked: !!templateLock,
    isFocusMode: focusMode && isLargeViewport,
    isRTL: isRTL,
    // Users of the editor.BlockListBlock filter used to be able to
    // access the block prop.
    // Ideally these blocks would rely on the clientId prop only.
    // This is kept for backward compatibility reasons.
    block: block,
    name: name,
    attributes: attributes,
    isValid: isValid,
    isSelected: isSelected,
    isAncestorOfSelectedBlock: isAncestorOfSelectedBlock
  };
});
var applyWithDispatch = withDispatch(function (dispatch, ownProps, _ref5) {
  var select = _ref5.select;

  var _dispatch = dispatch('core/block-editor'),
      updateBlockAttributes = _dispatch.updateBlockAttributes,
      insertBlocks = _dispatch.insertBlocks,
      mergeBlocks = _dispatch.mergeBlocks,
      replaceBlocks = _dispatch.replaceBlocks,
      _toggleSelection = _dispatch.toggleSelection,
      __unstableMarkLastChangeAsPersistent = _dispatch.__unstableMarkLastChangeAsPersistent; // Do not add new properties here, use `useDispatch` instead to avoid
  // leaking new props to the public API (editor.BlockListBlock filter).


  return {
    setAttributes: function setAttributes(newAttributes) {
      var clientId = ownProps.clientId,
          isFirstMultiSelected = ownProps.isFirstMultiSelected,
          multiSelectedClientIds = ownProps.multiSelectedClientIds;
      var clientIds = isFirstMultiSelected ? multiSelectedClientIds : [clientId];
      updateBlockAttributes(clientIds, newAttributes);
    },
    onInsertBlocks: function onInsertBlocks(blocks, index) {
      var rootClientId = ownProps.rootClientId;
      insertBlocks(blocks, index, rootClientId);
    },
    onInsertBlocksAfter: function onInsertBlocksAfter(blocks) {
      var clientId = ownProps.clientId,
          rootClientId = ownProps.rootClientId;

      var _select3 = select('core/block-editor'),
          getBlockIndex = _select3.getBlockIndex;

      var index = getBlockIndex(clientId, rootClientId);
      insertBlocks(blocks, index + 1, rootClientId);
    },
    onMerge: function onMerge(forward) {
      var clientId = ownProps.clientId;

      var _select4 = select('core/block-editor'),
          getPreviousBlockClientId = _select4.getPreviousBlockClientId,
          getNextBlockClientId = _select4.getNextBlockClientId;

      if (forward) {
        var nextBlockClientId = getNextBlockClientId(clientId);

        if (nextBlockClientId) {
          mergeBlocks(clientId, nextBlockClientId);
        }
      } else {
        var previousBlockClientId = getPreviousBlockClientId(clientId);

        if (previousBlockClientId) {
          mergeBlocks(previousBlockClientId, clientId);
        }
      }
    },
    onReplace: function onReplace(blocks, indexToSelect, initialPosition) {
      if (blocks.length && !isUnmodifiedDefaultBlock(blocks[blocks.length - 1])) {
        __unstableMarkLastChangeAsPersistent();
      }

      replaceBlocks([ownProps.clientId], blocks, indexToSelect, initialPosition);
    },
    toggleSelection: function toggleSelection(selectionEnabled) {
      _toggleSelection(selectionEnabled);
    }
  };
});
export default compose(pure, withViewportMatch({
  isLargeViewport: 'medium'
}), applyWithSelect, applyWithDispatch, // block is sometimes not mounted at the right time, causing it be undefined
// see issue for more info
// https://github.com/WordPress/gutenberg/issues/17013
ifCondition(function (_ref6) {
  var block = _ref6.block;
  return !!block;
}), withFilters('editor.BlockListBlock'))(BlockListBlock);
//# sourceMappingURL=block.js.map