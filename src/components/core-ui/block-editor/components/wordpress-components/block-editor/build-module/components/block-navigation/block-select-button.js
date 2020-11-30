import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __experimentalGetBlockLabel as getBlockLabel, getBlockType } from '@wordpress/blocks';
import { Button, VisuallyHidden } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { forwardRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
import { getBlockPositionDescription } from './utils';

function BlockNavigationBlockSelectButton(_ref, ref) {
  var className = _ref.className,
      block = _ref.block,
      isSelected = _ref.isSelected,
      onClick = _ref.onClick,
      position = _ref.position,
      siblingBlockCount = _ref.siblingBlockCount,
      level = _ref.level,
      tabIndex = _ref.tabIndex,
      onFocus = _ref.onFocus,
      onDragStart = _ref.onDragStart,
      onDragEnd = _ref.onDragEnd,
      draggable = _ref.draggable;
  var name = block.name,
      attributes = block.attributes;
  var blockType = getBlockType(name);
  var blockDisplayName = getBlockLabel(blockType, attributes);
  var instanceId = useInstanceId(BlockNavigationBlockSelectButton);
  var descriptionId = "block-navigation-block-select-button__".concat(instanceId);
  var blockPositionDescription = getBlockPositionDescription(position, siblingBlockCount, level);
  return createElement(Fragment, null, createElement(Button, {
    className: classnames('block-editor-block-navigation-block-select-button', className),
    onClick: onClick,
    "aria-describedby": descriptionId,
    ref: ref,
    tabIndex: tabIndex,
    onFocus: onFocus,
    onDragStart: onDragStart,
    onDragEnd: onDragEnd,
    draggable: draggable
  }, createElement(BlockIcon, {
    icon: blockType.icon,
    showColors: true
  }), blockDisplayName, isSelected && createElement(VisuallyHidden, null, __('(selected block)'))), createElement("div", {
    className: "block-editor-block-navigation-block-select-button__description",
    id: descriptionId
  }, blockPositionDescription));
}

export default forwardRef(BlockNavigationBlockSelectButton);
//# sourceMappingURL=block-select-button.js.map