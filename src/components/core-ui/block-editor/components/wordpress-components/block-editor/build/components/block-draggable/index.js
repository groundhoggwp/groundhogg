"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _draggableChip = _interopRequireDefault(require("./draggable-chip"));

var _useScrollWhenDragging = _interopRequireDefault(require("./use-scroll-when-dragging"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockDraggable = function BlockDraggable(_ref) {
  var children = _ref.children,
      clientIds = _ref.clientIds,
      cloneClassname = _ref.cloneClassname,
      _onDragStart = _ref.onDragStart,
      _onDragEnd = _ref.onDragEnd,
      elementId = _ref.elementId;

  var _useSelect = (0, _data.useSelect)(function (select) {
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

  var isDragging = (0, _element.useRef)(false);

  var _useScrollWhenDraggin = (0, _useScrollWhenDragging.default)(),
      _useScrollWhenDraggin2 = (0, _slicedToArray2.default)(_useScrollWhenDraggin, 3),
      startScrolling = _useScrollWhenDraggin2[0],
      scrollOnDragOver = _useScrollWhenDraggin2[1],
      stopScrolling = _useScrollWhenDraggin2[2];

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      startDraggingBlocks = _useDispatch.startDraggingBlocks,
      stopDraggingBlocks = _useDispatch.stopDraggingBlocks; // Stop dragging blocks if the block draggable is unmounted


  (0, _element.useEffect)(function () {
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
  return (0, _element.createElement)(_components.Draggable, {
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
    __experimentalDragComponent: (0, _element.createElement)(_draggableChip.default, {
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

var _default = BlockDraggable;
exports.default = _default;
//# sourceMappingURL=index.js.map