"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _context = require("./context");

var _blockSlot = _interopRequireDefault(require("./block-slot"));

var _blockSelectButton = _interopRequireDefault(require("./block-select-button"));

var _blockDraggable = _interopRequireDefault(require("../block-draggable"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockNavigationBlockContents = (0, _element.forwardRef)(function (_ref, ref) {
  var onClick = _ref.onClick,
      block = _ref.block,
      isSelected = _ref.isSelected,
      position = _ref.position,
      siblingBlockCount = _ref.siblingBlockCount,
      level = _ref.level,
      props = (0, _objectWithoutProperties2.default)(_ref, ["onClick", "block", "isSelected", "position", "siblingBlockCount", "level"]);

  var _useBlockNavigationCo = (0, _context.useBlockNavigationContext)(),
      __experimentalFeatures = _useBlockNavigationCo.__experimentalFeatures,
      _useBlockNavigationCo2 = _useBlockNavigationCo.blockDropTarget,
      blockDropTarget = _useBlockNavigationCo2 === void 0 ? {} : _useBlockNavigationCo2;

  var clientId = block.clientId;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockRootClientId = _select.getBlockRootClientId,
        hasBlockMovingClientId = _select.hasBlockMovingClientId,
        getSelectedBlockClientId = _select.getSelectedBlockClientId;

    return {
      rootClientId: getBlockRootClientId(clientId) || '',
      blockMovingClientId: hasBlockMovingClientId(),
      selectedBlockInBlockEditor: getSelectedBlockClientId()
    };
  }, [clientId]),
      rootClientId = _useSelect.rootClientId,
      blockMovingClientId = _useSelect.blockMovingClientId,
      selectedBlockInBlockEditor = _useSelect.selectedBlockInBlockEditor;

  var isBlockMoveTarget = blockMovingClientId && selectedBlockInBlockEditor === clientId;
  var dropTargetRootClientId = blockDropTarget.rootClientId,
      dropTargetClientId = blockDropTarget.clientId,
      dropPosition = blockDropTarget.dropPosition;
  var isDroppingBefore = dropTargetRootClientId === rootClientId && dropTargetClientId === clientId && dropPosition === 'top';
  var isDroppingAfter = dropTargetRootClientId === rootClientId && dropTargetClientId === clientId && dropPosition === 'bottom';
  var isDroppingToInnerBlocks = dropTargetRootClientId === clientId && dropPosition === 'inside';
  var className = (0, _classnames.default)('block-editor-block-navigation-block-contents', {
    'is-dropping-before': isDroppingBefore || isBlockMoveTarget,
    'is-dropping-after': isDroppingAfter,
    'is-dropping-to-inner-blocks': isDroppingToInnerBlocks
  });
  return (0, _element.createElement)(_blockDraggable.default, {
    clientIds: [block.clientId],
    elementId: "block-navigation-block-".concat(block.clientId)
  }, function (_ref2) {
    var isDraggable = _ref2.isDraggable,
        onDraggableStart = _ref2.onDraggableStart,
        onDraggableEnd = _ref2.onDraggableEnd;
    return __experimentalFeatures ? (0, _element.createElement)(_blockSlot.default, (0, _extends2.default)({
      ref: ref,
      className: className,
      block: block,
      onClick: onClick,
      isSelected: isSelected,
      position: position,
      siblingBlockCount: siblingBlockCount,
      level: level,
      draggable: isDraggable && __experimentalFeatures,
      onDragStart: onDraggableStart,
      onDragEnd: onDraggableEnd
    }, props)) : (0, _element.createElement)(_blockSelectButton.default, (0, _extends2.default)({
      ref: ref,
      className: className,
      block: block,
      onClick: onClick,
      isSelected: isSelected,
      position: position,
      siblingBlockCount: siblingBlockCount,
      level: level,
      draggable: isDraggable && __experimentalFeatures,
      onDragStart: onDraggableStart,
      onDragEnd: onDraggableEnd
    }, props));
  });
});
var _default = BlockNavigationBlockContents;
exports.default = _default;
//# sourceMappingURL=block-contents.js.map