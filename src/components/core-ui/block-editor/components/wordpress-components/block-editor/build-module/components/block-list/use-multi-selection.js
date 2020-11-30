import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

/**
 * WordPress dependencies
 */
import { useEffect, useRef, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { getBlockClientId, getBlockDOMNode } from '../../utils/dom';
/**
 * Returns for the deepest node at the start or end of a container node. Ignores
 * any text nodes that only contain HTML formatting whitespace.
 *
 * @param {Element} node Container to search.
 * @param {string} type 'start' or 'end'.
 */

function getDeepestNode(node, type) {
  var child = type === 'start' ? 'firstChild' : 'lastChild';
  var sibling = type === 'start' ? 'nextSibling' : 'previousSibling';

  while (node[child]) {
    node = node[child];

    while (node.nodeType === node.TEXT_NODE && /^[ \t\n]*$/.test(node.data) && node[sibling]) {
      node = node[sibling];
    }
  }

  return node;
}

function selector(select) {
  var _select = select('core/block-editor'),
      isSelectionEnabled = _select.isSelectionEnabled,
      isMultiSelecting = _select.isMultiSelecting,
      getMultiSelectedBlockClientIds = _select.getMultiSelectedBlockClientIds,
      hasMultiSelection = _select.hasMultiSelection,
      getBlockParents = _select.getBlockParents,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  return {
    isSelectionEnabled: isSelectionEnabled(),
    isMultiSelecting: isMultiSelecting(),
    multiSelectedBlockClientIds: getMultiSelectedBlockClientIds(),
    hasMultiSelection: hasMultiSelection(),
    getBlockParents: getBlockParents,
    selectedBlockClientId: getSelectedBlockClientId()
  };
}

function toggleRichText(container, toggle) {
  Array.from(container.querySelectorAll('.rich-text')).forEach(function (node) {
    if (toggle) {
      node.setAttribute('contenteditable', true);
    } else {
      node.removeAttribute('contenteditable');
    }
  });
}

export default function useMultiSelection(ref) {
  var _useSelect = useSelect(selector, []),
      isSelectionEnabled = _useSelect.isSelectionEnabled,
      isMultiSelecting = _useSelect.isMultiSelecting,
      multiSelectedBlockClientIds = _useSelect.multiSelectedBlockClientIds,
      hasMultiSelection = _useSelect.hasMultiSelection,
      getBlockParents = _useSelect.getBlockParents,
      selectedBlockClientId = _useSelect.selectedBlockClientId;

  var _useDispatch = useDispatch('core/block-editor'),
      startMultiSelect = _useDispatch.startMultiSelect,
      stopMultiSelect = _useDispatch.stopMultiSelect,
      multiSelect = _useDispatch.multiSelect,
      selectBlock = _useDispatch.selectBlock;

  var rafId = useRef();
  var startClientId = useRef();
  var anchorElement = useRef();
  /**
   * When the component updates, and there is multi selection, we need to
   * select the entire block contents.
   */

  useEffect(function () {
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;

    if (!hasMultiSelection || isMultiSelecting) {
      if (!selectedBlockClientId || isMultiSelecting) {
        return;
      }

      var _selection = defaultView.getSelection();

      if (_selection.rangeCount && !_selection.isCollapsed) {
        var blockNode = getBlockDOMNode(selectedBlockClientId);

        var _selection$getRangeAt = _selection.getRangeAt(0),
            startContainer = _selection$getRangeAt.startContainer,
            endContainer = _selection$getRangeAt.endContainer;

        if (!!blockNode && (!blockNode.contains(startContainer) || !blockNode.contains(endContainer))) {
          _selection.removeAllRanges();
        }
      }

      return;
    }

    var length = multiSelectedBlockClientIds.length;

    if (length < 2) {
      return;
    } // These must be in the right DOM order.


    var start = multiSelectedBlockClientIds[0];
    var end = multiSelectedBlockClientIds[length - 1];
    var startNode = getBlockDOMNode(start);
    var endNode = getBlockDOMNode(end);
    var selection = defaultView.getSelection();
    var range = ownerDocument.createRange(); // The most stable way to select the whole block contents is to start
    // and end at the deepest points.

    startNode = getDeepestNode(startNode, 'start');
    endNode = getDeepestNode(endNode, 'end');
    range.setStartBefore(startNode);
    range.setEndAfter(endNode);
    selection.removeAllRanges();
    selection.addRange(range);
  }, [hasMultiSelection, isMultiSelecting, multiSelectedBlockClientIds, selectBlock, selectedBlockClientId]);
  var onSelectionChange = useCallback(function (_ref) {
    var isSelectionEnd = _ref.isSelectionEnd;
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    var selection = defaultView.getSelection(); // If no selection is found, end multi selection and enable all rich
    // text areas.

    if (!selection.rangeCount || selection.isCollapsed) {
      toggleRichText(ref.current, true);
      return;
    }

    var clientId = getBlockClientId(selection.focusNode);
    var isSingularSelection = startClientId.current === clientId;

    if (isSingularSelection) {
      selectBlock(clientId); // If the selection is complete (on mouse up), and no multiple
      // blocks have been selected, set focus back to the anchor element
      // if the anchor element contains the selection. Additionally, rich
      // text elements that were previously disabled can now be enabled
      // again.

      if (isSelectionEnd) {
        toggleRichText(ref.current, true);

        if (selection.rangeCount) {
          var _selection$getRangeAt2 = selection.getRangeAt(0),
              commonAncestorContainer = _selection$getRangeAt2.commonAncestorContainer;

          if (anchorElement.current.contains(commonAncestorContainer)) {
            anchorElement.current.focus();
          }
        }
      }
    } else {
      var startPath = [].concat(_toConsumableArray(getBlockParents(startClientId.current)), [startClientId.current]);
      var endPath = [].concat(_toConsumableArray(getBlockParents(clientId)), [clientId]);
      var depth = Math.min(startPath.length, endPath.length) - 1;
      multiSelect(startPath[depth], endPath[depth]);
    }
  }, [selectBlock, getBlockParents, multiSelect]);
  /**
   * Handles a mouseup event to end the current mouse multi-selection.
   */

  var onSelectionEnd = useCallback(function () {
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    ownerDocument.removeEventListener('selectionchange', onSelectionChange); // Equivalent to attaching the listener once.

    defaultView.removeEventListener('mouseup', onSelectionEnd); // The browser selection won't have updated yet at this point, so wait
    // until the next animation frame to get the browser selection.

    rafId.current = defaultView.requestAnimationFrame(function () {
      onSelectionChange({
        isSelectionEnd: true
      });
      stopMultiSelect();
    });
  }, [onSelectionChange, stopMultiSelect]); // Only clean up when unmounting, these are added and cleaned up elsewhere.

  useEffect(function () {
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    return function () {
      ownerDocument.removeEventListener('selectionchange', onSelectionChange);
      defaultView.removeEventListener('mouseup', onSelectionEnd);
      defaultView.cancelAnimationFrame(rafId.current);
    };
  }, [onSelectionChange, onSelectionEnd]);
  /**
   * Binds event handlers to the document for tracking a pending multi-select
   * in response to a mousedown event occurring in a rendered block.
   */

  return useCallback(function (clientId) {
    if (!isSelectionEnabled) {
      return;
    }

    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    startClientId.current = clientId;
    anchorElement.current = ownerDocument.activeElement;
    startMultiSelect(); // `onSelectionStart` is called after `mousedown` and `mouseleave`
    // (from a block). The selection ends when `mouseup` happens anywhere
    // in the window.

    ownerDocument.addEventListener('selectionchange', onSelectionChange);
    defaultView.addEventListener('mouseup', onSelectionEnd); // Removing the contenteditable attributes within the block editor is
    // essential for selection to work across editable areas. The edible
    // hosts are removed, allowing selection to be extended outside the
    // DOM element. `startMultiSelect` sets a flag in the store so the rich
    // text components are updated, but the rerender may happen very slowly,
    // especially in Safari for the blocks that are asynchonously rendered.
    // To ensure the browser instantly removes the selection boundaries, we
    // remove the contenteditable attributes manually.

    toggleRichText(ref.current, false);
  }, [isSelectionEnabled, startMultiSelect, onSelectionEnd]);
}
//# sourceMappingURL=use-multi-selection.js.map