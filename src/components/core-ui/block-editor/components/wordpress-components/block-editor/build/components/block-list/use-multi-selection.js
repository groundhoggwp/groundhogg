"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useMultiSelection;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _dom = require("../../utils/dom");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

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

function useMultiSelection(ref) {
  var _useSelect = (0, _data.useSelect)(selector, []),
      isSelectionEnabled = _useSelect.isSelectionEnabled,
      isMultiSelecting = _useSelect.isMultiSelecting,
      multiSelectedBlockClientIds = _useSelect.multiSelectedBlockClientIds,
      hasMultiSelection = _useSelect.hasMultiSelection,
      getBlockParents = _useSelect.getBlockParents,
      selectedBlockClientId = _useSelect.selectedBlockClientId;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      startMultiSelect = _useDispatch.startMultiSelect,
      stopMultiSelect = _useDispatch.stopMultiSelect,
      multiSelect = _useDispatch.multiSelect,
      selectBlock = _useDispatch.selectBlock;

  var rafId = (0, _element.useRef)();
  var startClientId = (0, _element.useRef)();
  var anchorElement = (0, _element.useRef)();
  /**
   * When the component updates, and there is multi selection, we need to
   * select the entire block contents.
   */

  (0, _element.useEffect)(function () {
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;

    if (!hasMultiSelection || isMultiSelecting) {
      if (!selectedBlockClientId || isMultiSelecting) {
        return;
      }

      var _selection = defaultView.getSelection();

      if (_selection.rangeCount && !_selection.isCollapsed) {
        var blockNode = (0, _dom.getBlockDOMNode)(selectedBlockClientId);

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
    var startNode = (0, _dom.getBlockDOMNode)(start);
    var endNode = (0, _dom.getBlockDOMNode)(end);
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
  var onSelectionChange = (0, _element.useCallback)(function (_ref) {
    var isSelectionEnd = _ref.isSelectionEnd;
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    var selection = defaultView.getSelection(); // If no selection is found, end multi selection and enable all rich
    // text areas.

    if (!selection.rangeCount || selection.isCollapsed) {
      toggleRichText(ref.current, true);
      return;
    }

    var clientId = (0, _dom.getBlockClientId)(selection.focusNode);
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
      var startPath = [].concat((0, _toConsumableArray2.default)(getBlockParents(startClientId.current)), [startClientId.current]);
      var endPath = [].concat((0, _toConsumableArray2.default)(getBlockParents(clientId)), [clientId]);
      var depth = Math.min(startPath.length, endPath.length) - 1;
      multiSelect(startPath[depth], endPath[depth]);
    }
  }, [selectBlock, getBlockParents, multiSelect]);
  /**
   * Handles a mouseup event to end the current mouse multi-selection.
   */

  var onSelectionEnd = (0, _element.useCallback)(function () {
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

  (0, _element.useEffect)(function () {
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

  return (0, _element.useCallback)(function (clientId) {
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