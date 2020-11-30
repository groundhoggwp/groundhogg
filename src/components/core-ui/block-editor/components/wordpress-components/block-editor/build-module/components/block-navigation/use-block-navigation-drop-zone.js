import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

/**
 * WordPress dependencies
 */
import { __unstableUseDropZone as useDropZone } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { getDistanceToNearestEdge } from '../../utils/math';
import useOnBlockDrop from '../use-on-block-drop';
/** @typedef {import('../../utils/math').WPPoint} WPPoint */

/** @typedef {import('@wordpress/element').RefObject} RefObject */

/**
 * The type of a drag event.
 *
 * @typedef {'default'|'file'|'html'} WPDragEventType
 */

/**
 * An array representing data for blocks in the DOM used by drag and drop.
 *
 * @typedef {Object} WPBlockNavigationDropZoneBlocks
 * @property {string}  clientId                        The client id for the block.
 * @property {string}  rootClientId                    The root client id for the block.
 * @property {number}  blockIndex                      The block's index.
 * @property {Element} element                         The DOM element representing the block.
 * @property {number}  innerBlockCount                 The number of inner blocks the block has.
 * @property {boolean} isDraggedBlock                  Whether the block is currently being dragged.
 * @property {boolean} canInsertDraggedBlocksAsSibling Whether the dragged block can be a sibling of this block.
 * @property {boolean} canInsertDraggedBlocksAsChild   Whether the dragged block can be a child of this block.
 */

/**
 * An object containing details of a drop target.
 *
 * @typedef {Object} WPBlockNavigationDropZoneTarget
 * @property {string}                   blockIndex   The insertion index.
 * @property {string}                   rootClientId The root client id for the block.
 * @property {string|undefined}         clientId     The client id for the block.
 * @property {'top'|'bottom'|'inside'}  dropPosition The position relative to the block that the user is dropping to.
 *                                                   'inside' refers to nesting as an inner block.
 */

/**
 * A react hook that returns data about blocks used for computing where a user
 * can drop to when dragging and dropping blocks.
 *
 * @param {Object}          ref           A React ref of a containing element for block navigation.
 * @param {WPPoint}         position      The current drag position.
 * @param {WPDragEventType} dragEventType The drag event type.
 *
 * @return {RefObject<WPBlockNavigationDropZoneBlocks>} A React ref containing the blocks data.
 */

function useDropTargetBlocksData(ref, position, dragEventType) {
  var _useSelect = useSelect(function (select) {
    var selectors = select('core/block-editor');
    return {
      canInsertBlocks: selectors.canInsertBlocks,
      getBlockRootClientId: selectors.getBlockRootClientId,
      getBlockIndex: selectors.getBlockIndex,
      getBlockCount: selectors.getBlockCount,
      getDraggedBlockClientIds: selectors.getDraggedBlockClientIds
    };
  }, []),
      getBlockRootClientId = _useSelect.getBlockRootClientId,
      getBlockIndex = _useSelect.getBlockIndex,
      getBlockCount = _useSelect.getBlockCount,
      getDraggedBlockClientIds = _useSelect.getDraggedBlockClientIds,
      canInsertBlocks = _useSelect.canInsertBlocks;

  var blocksData = useRef(); // Compute data about blocks only when the user
  // starts dragging, as determined by `hasPosition`.

  var hasPosition = !!position;
  useEffect(function () {
    if (!ref.current || !hasPosition) {
      return;
    }

    var isBlockDrag = dragEventType === 'default';
    var draggedBlockClientIds = isBlockDrag ? getDraggedBlockClientIds() : undefined;
    var blockElements = Array.from(ref.current.querySelectorAll('[data-block]'));
    blocksData.current = blockElements.map(function (blockElement) {
      var clientId = blockElement.dataset.block;
      var rootClientId = getBlockRootClientId(clientId);
      return {
        clientId: clientId,
        rootClientId: rootClientId,
        blockIndex: getBlockIndex(clientId, rootClientId),
        element: blockElement,
        isDraggedBlock: isBlockDrag ? draggedBlockClientIds.includes(clientId) : false,
        innerBlockCount: getBlockCount(clientId),
        canInsertDraggedBlocksAsSibling: isBlockDrag ? canInsertBlocks(draggedBlockClientIds, rootClientId) : true,
        canInsertDraggedBlocksAsChild: isBlockDrag ? canInsertBlocks(draggedBlockClientIds, clientId) : true
      };
    });
  }, [// `ref` shouldn't actually change during a drag operation, but
  // is specified for completeness as it's used within the hook.
  ref, hasPosition, dragEventType, canInsertBlocks, getBlockCount, getBlockIndex, getBlockRootClientId, getDraggedBlockClientIds]);
  return blocksData;
}
/**
 * Is the point contained by the rectangle.
 *
 * @param {WPPoint} point The point.
 * @param {DOMRect} rect  The rectangle.
 *
 * @return {boolean} True if the point is contained by the rectangle, false otherwise.
 */


function isPointContainedByRect(point, rect) {
  return rect.left <= point.x && rect.right >= point.x && rect.top <= point.y && rect.bottom >= point.y;
}
/**
 * Determines whether the user positioning the dragged block to nest as an
 * inner block.
 *
 * Presently this is determined by whether the cursor is on the right hand side
 * of the block.
 *
 * @param {WPPoint} point The point representing the cursor position when dragging.
 * @param {DOMRect} rect  The rectangle.
 */


function isNestingGesture(point, rect) {
  var blockCenterX = rect.left + rect.width / 2;
  return point.x > blockCenterX;
} // Block navigation is always a vertical list, so only allow dropping
// to the above or below a block.


var ALLOWED_DROP_EDGES = ['top', 'bottom'];
/**
 * Given blocks data and the cursor position, compute the drop target.
 *
 * @param {WPBlockNavigationDropZoneBlocks} blocksData Data about the blocks in block navigation.
 * @param {WPPoint} position The point representing the cursor position when dragging.
 *
 * @return {WPBlockNavigationDropZoneTarget} An object containing data about the drop target.
 */

function getBlockNavigationDropTarget(blocksData, position) {
  var candidateEdge;
  var candidateBlockData;
  var candidateDistance;
  var candidateRect;

  var _iterator = _createForOfIteratorHelper(blocksData),
      _step;

  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var blockData = _step.value;

      if (blockData.isDraggedBlock) {
        continue;
      }

      var rect = blockData.element.getBoundingClientRect();

      var _getDistanceToNearest = getDistanceToNearestEdge(position, rect, ALLOWED_DROP_EDGES),
          _getDistanceToNearest2 = _slicedToArray(_getDistanceToNearest, 2),
          distance = _getDistanceToNearest2[0],
          edge = _getDistanceToNearest2[1];

      var isCursorWithinBlock = isPointContainedByRect(position, rect);

      if (candidateDistance === undefined || distance < candidateDistance || isCursorWithinBlock) {
        candidateDistance = distance;
        var index = blocksData.indexOf(blockData);
        var previousBlockData = blocksData[index - 1]; // If dragging near the top of a block and the preceding block
        // is at the same level, use the preceding block as the candidate
        // instead, as later it makes determining a nesting drop easier.

        if (edge === 'top' && previousBlockData && previousBlockData.rootClientId === blockData.rootClientId && !previousBlockData.isDraggedBlock) {
          candidateBlockData = previousBlockData;
          candidateEdge = 'bottom';
          candidateRect = previousBlockData.element.getBoundingClientRect();
        } else {
          candidateBlockData = blockData;
          candidateEdge = edge;
          candidateRect = rect;
        } // If the mouse position is within the block, break early
        // as the user would intend to drop either before or after
        // this block.
        //
        // This solves an issue where some rows in the block navigation
        // tree overlap slightly due to sub-pixel rendering.


        if (isCursorWithinBlock) {
          break;
        }
      }
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }

  if (!candidateBlockData) {
    return;
  }

  var isDraggingBelow = candidateEdge === 'bottom'; // If the user is dragging towards the bottom of the block check whether
  // they might be trying to nest the block as a child.
  // If the block already has inner blocks, this should always be treated
  // as nesting since the next block in the tree will be the first child.

  if (isDraggingBelow && candidateBlockData.canInsertDraggedBlocksAsChild && (candidateBlockData.innerBlockCount > 0 || isNestingGesture(position, candidateRect))) {
    return {
      rootClientId: candidateBlockData.clientId,
      blockIndex: 0,
      dropPosition: 'inside'
    };
  } // If dropping as a sibling, but block cannot be inserted in
  // this context, return early.


  if (!candidateBlockData.canInsertDraggedBlocksAsSibling) {
    return;
  }

  var offset = isDraggingBelow ? 1 : 0;
  return {
    rootClientId: candidateBlockData.rootClientId,
    clientId: candidateBlockData.clientId,
    blockIndex: candidateBlockData.blockIndex + offset,
    dropPosition: candidateEdge
  };
}
/**
 * A react hook for implementing a drop zone in block navigation.
 *
 * @param {Object} ref A React ref of a containing element for block navigation.
 *
 * @return {WPBlockNavigationDropZoneTarget} The drop target.
 */


export default function useBlockNavigationDropZone(ref) {
  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      _useState2$ = _useState2[0],
      target = _useState2$ === void 0 ? {} : _useState2$,
      setTarget = _useState2[1];

  var targetRootClientId = target.rootClientId,
      targetBlockIndex = target.blockIndex;
  var dropEventHandlers = useOnBlockDrop(targetRootClientId, targetBlockIndex);

  var _useDropZone = useDropZone(_objectSpread({
    element: ref,
    withPosition: true
  }, dropEventHandlers)),
      position = _useDropZone.position,
      dragEventType = _useDropZone.type;

  var blocksData = useDropTargetBlocksData(ref, position, dragEventType); // Calculate the drop target based on the drag position.

  useEffect(function () {
    if (position) {
      var newTarget = getBlockNavigationDropTarget(blocksData.current, position);

      if (newTarget) {
        setTarget(newTarget);
      }
    }
  }, [blocksData, position]);

  if (position) {
    return target;
  }
}
//# sourceMappingURL=use-block-navigation-drop-zone.js.map