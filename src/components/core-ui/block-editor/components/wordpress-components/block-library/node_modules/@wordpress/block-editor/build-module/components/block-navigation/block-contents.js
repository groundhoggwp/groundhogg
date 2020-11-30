import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { useBlockNavigationContext } from './context';
import BlockNavigationBlockSlot from './block-slot';
import BlockNavigationBlockSelectButton from './block-select-button';
import BlockDraggable from '../block-draggable';
var BlockNavigationBlockContents = forwardRef(function (_ref, ref) {
  var onClick = _ref.onClick,
      block = _ref.block,
      isSelected = _ref.isSelected,
      position = _ref.position,
      siblingBlockCount = _ref.siblingBlockCount,
      level = _ref.level,
      props = _objectWithoutProperties(_ref, ["onClick", "block", "isSelected", "position", "siblingBlockCount", "level"]);

  var _useBlockNavigationCo = useBlockNavigationContext(),
      __experimentalFeatures = _useBlockNavigationCo.__experimentalFeatures,
      _useBlockNavigationCo2 = _useBlockNavigationCo.blockDropTarget,
      blockDropTarget = _useBlockNavigationCo2 === void 0 ? {} : _useBlockNavigationCo2;

  var clientId = block.clientId;

  var _useSelect = useSelect(function (select) {
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
  var className = classnames('block-editor-block-navigation-block-contents', {
    'is-dropping-before': isDroppingBefore || isBlockMoveTarget,
    'is-dropping-after': isDroppingAfter,
    'is-dropping-to-inner-blocks': isDroppingToInnerBlocks
  });
  return createElement(BlockDraggable, {
    clientIds: [block.clientId],
    elementId: "block-navigation-block-".concat(block.clientId)
  }, function (_ref2) {
    var isDraggable = _ref2.isDraggable,
        onDraggableStart = _ref2.onDraggableStart,
        onDraggableEnd = _ref2.onDraggableEnd;
    return __experimentalFeatures ? createElement(BlockNavigationBlockSlot, _extends({
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
    }, props)) : createElement(BlockNavigationBlockSelectButton, _extends({
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
export default BlockNavigationBlockContents;
//# sourceMappingURL=block-contents.js.map