import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { first, last, castArray } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { dragHandle } from '@wordpress/icons';
import { ToolbarGroup, ToolbarItem, Button } from '@wordpress/components';
import { getBlockType } from '@wordpress/blocks';
import { useState } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import { _n } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockDraggable from '../block-draggable';
import { BlockMoverUpButton, BlockMoverDownButton } from './button';

function BlockMover(_ref) {
  var isFirst = _ref.isFirst,
      isLast = _ref.isLast,
      clientIds = _ref.clientIds,
      isLocked = _ref.isLocked,
      isHidden = _ref.isHidden,
      rootClientId = _ref.rootClientId,
      orientation = _ref.orientation,
      hideDragHandle = _ref.hideDragHandle;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isFocused = _useState2[0],
      setIsFocused = _useState2[1];

  var onFocus = function onFocus() {
    return setIsFocused(true);
  };

  var onBlur = function onBlur() {
    return setIsFocused(false);
  };

  if (isLocked || isFirst && isLast && !rootClientId) {
    return null;
  } // We emulate a disabled state because forcefully applying the `disabled`
  // attribute on the buttons while it has focus causes the screen to change
  // to an unfocused state (body as active element) without firing blur on,
  // the rendering parent, leaving it unable to react to focus out.


  return createElement("div", {
    className: classnames('block-editor-block-mover', {
      'is-visible': isFocused || !isHidden,
      'is-horizontal': orientation === 'horizontal'
    })
  }, !hideDragHandle && createElement(BlockDraggable, {
    clientIds: clientIds,
    cloneClassname: "block-editor-block-mover__drag-clone"
  }, function (_ref2) {
    var isDraggable = _ref2.isDraggable,
        onDraggableStart = _ref2.onDraggableStart,
        onDraggableEnd = _ref2.onDraggableEnd;
    return createElement(Button, {
      icon: dragHandle,
      className: "block-editor-block-mover__drag-handle",
      "aria-hidden": "true",
      label: _n('Drag block', 'Drag blocks', clientIds.length) // Should not be able to tab to drag handle as this
      // button can only be used with a pointer device.
      ,
      tabIndex: "-1",
      onDragStart: onDraggableStart,
      onDragEnd: onDraggableEnd,
      draggable: isDraggable
    });
  }), createElement(ToolbarGroup, {
    className: "block-editor-block-mover__move-button-container"
  }, createElement(ToolbarItem, {
    onFocus: onFocus,
    onBlur: onBlur
  }, function (itemProps) {
    return createElement(BlockMoverUpButton, _extends({
      clientIds: clientIds
    }, itemProps));
  }), createElement(ToolbarItem, {
    onFocus: onFocus,
    onBlur: onBlur
  }, function (itemProps) {
    return createElement(BlockMoverDownButton, _extends({
      clientIds: clientIds
    }, itemProps));
  })));
}

export default withSelect(function (select, _ref3) {
  var _getBlockListSettings;

  var clientIds = _ref3.clientIds;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock,
      getBlockIndex = _select.getBlockIndex,
      getBlockListSettings = _select.getBlockListSettings,
      getTemplateLock = _select.getTemplateLock,
      getBlockOrder = _select.getBlockOrder,
      getBlockRootClientId = _select.getBlockRootClientId;

  var normalizedClientIds = castArray(clientIds);
  var firstClientId = first(normalizedClientIds);
  var block = getBlock(firstClientId);
  var rootClientId = getBlockRootClientId(first(normalizedClientIds));
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex(last(normalizedClientIds), rootClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var isFirst = firstIndex === 0;
  var isLast = lastIndex === blockOrder.length - 1;
  return {
    blockType: block ? getBlockType(block.name) : null,
    isLocked: getTemplateLock(rootClientId) === 'all',
    rootClientId: rootClientId,
    firstIndex: firstIndex,
    isFirst: isFirst,
    isLast: isLast,
    orientation: (_getBlockListSettings = getBlockListSettings(rootClientId)) === null || _getBlockListSettings === void 0 ? void 0 : _getBlockListSettings.orientation
  };
})(BlockMover);
//# sourceMappingURL=index.js.map