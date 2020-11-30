import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { createContext, forwardRef, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import useMultiSelection from './use-multi-selection';
import { getBlockClientId } from '../../utils/dom';
import InsertionPoint from './insertion-point';
import BlockPopover from './block-popover';
/** @typedef {import('@wordpress/element').WPSyntheticEvent} WPSyntheticEvent */

export var Context = createContext();
export var BlockNodes = createContext();
export var SetBlockNodes = createContext();

function selector(select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      hasMultiSelection = _select.hasMultiSelection;

  return {
    selectedBlockClientId: getSelectedBlockClientId(),
    hasMultiSelection: hasMultiSelection()
  };
}
/**
 * Prevents default dragging behavior within a block.
 * To do: we must handle this in the future and clean up the drag target.
 * Previously dragging was prevented for multi-selected, but this is no longer
 * needed.
 *
 * @param {WPSyntheticEvent} event Synthetic drag event.
 */


function onDragStart(event) {
  // Ensure we target block content, not block controls.
  if (getBlockClientId(event.target)) {
    event.preventDefault();
  }
}

function RootContainer(_ref, ref) {
  var children = _ref.children,
      className = _ref.className;

  var _useSelect = useSelect(selector, []),
      selectedBlockClientId = _useSelect.selectedBlockClientId,
      hasMultiSelection = _useSelect.hasMultiSelection;

  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var onSelectionStart = useMultiSelection(ref);
  /**
   * Marks the block as selected when focused and not already selected. This
   * specifically handles the case where block does not set focus on its own
   * (via `setFocus`), typically if there is no focusable input in the block.
   *
   * @param {WPSyntheticEvent} event
   */

  function onFocus(event) {
    if (hasMultiSelection) {
      return;
    }

    var clientId = getBlockClientId(event.target);

    if (clientId && clientId !== selectedBlockClientId) {
      selectBlock(clientId);
    }
  }

  var _useState = useState({}),
      _useState2 = _slicedToArray(_useState, 2),
      blockNodes = _useState2[0],
      setBlockNodes = _useState2[1];

  return createElement(InsertionPoint, {
    containerRef: ref
  }, createElement(BlockNodes.Provider, {
    value: blockNodes
  }, createElement(BlockPopover, null), createElement("div", {
    ref: ref,
    className: classnames(className, 'is-root-container'),
    onFocus: onFocus,
    onDragStart: onDragStart
  }, createElement(SetBlockNodes.Provider, {
    value: setBlockNodes
  }, createElement(Context.Provider, {
    value: onSelectionStart
  }, children)))));
}

export default forwardRef(RootContainer);
//# sourceMappingURL=root-container.js.map