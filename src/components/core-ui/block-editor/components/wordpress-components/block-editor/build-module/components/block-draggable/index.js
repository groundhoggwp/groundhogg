import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Draggable } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import BlockDraggableChip from './draggable-chip';
import useScrollWhenDragging from './use-scroll-when-dragging';

var BlockDraggable = function BlockDraggable(_ref) {
  var children = _ref.children,
      clientIds = _ref.clientIds,
      cloneClassname = _ref.cloneClassname,
      _onDragStart = _ref.onDragStart,
      _onDragEnd = _ref.onDragEnd,
      elementId = _ref.elementId;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockRootClientId = _select.getBlockRootClientId,
        getTemplateLock = _select.getTemplateLock;

    var rootClientId = getBlockRootClientId(clientIds[0]);
    var templateLock = rootClientId ? getTemplateLock(rootClientId) : null;
    return {
      srcRootClientId: rootClientId,
      isDraggable: 'all' !== templateLock
    };
  }, [clientIds]),
      srcRootClientId = _useSelect.srcRootClientId,
      isDraggable = _useSelect.isDraggable;

  var isDragging = useRef(false);

  var _useScrollWhenDraggin = useScrollWhenDragging(),
      _useScrollWhenDraggin2 = _slicedToArray(_useScrollWhenDraggin, 3),
      startScrolling = _useScrollWhenDraggin2[0],
      scrollOnDragOver = _useScrollWhenDraggin2[1],
      stopScrolling = _useScrollWhenDraggin2[2];

  var _useDispatch = useDispatch('core/block-editor'),
      startDraggingBlocks = _useDispatch.startDraggingBlocks,
      stopDraggingBlocks = _useDispatch.stopDraggingBlocks; // Stop dragging blocks if the block draggable is unmounted


  useEffect(function () {
    return function () {
      if (isDragging.current) {
        stopDraggingBlocks();
      }
    };
  }, []);

  if (!isDraggable) {
    return children({
      isDraggable: false
    });
  }

  var transferData = {
    type: 'block',
    srcClientIds: clientIds,
    srcRootClientId: srcRootClientId
  };
  return createElement(Draggable, {
    cloneClassname: cloneClassname,
    elementId: elementId || "block-".concat(clientIds[0]),
    transferData: transferData,
    onDragStart: function onDragStart(event) {
      startDraggingBlocks(clientIds);
      isDragging.current = true;
      startScrolling(event);

      if (_onDragStart) {
        _onDragStart();
      }
    },
    onDragOver: scrollOnDragOver,
    onDragEnd: function onDragEnd() {
      stopDraggingBlocks();
      isDragging.current = false;
      stopScrolling();

      if (_onDragEnd) {
        _onDragEnd();
      }
    },
    __experimentalDragComponent: createElement(BlockDraggableChip, {
      clientIds: clientIds
    })
  }, function (_ref2) {
    var onDraggableStart = _ref2.onDraggableStart,
        onDraggableEnd = _ref2.onDraggableEnd;
    return children({
      isDraggable: true,
      onDraggableStart: onDraggableStart,
      onDraggableEnd: onDraggableEnd
    });
  });
};

export default BlockDraggable;
//# sourceMappingURL=index.js.map