import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Button, VisuallyHidden } from '@wordpress/components';
import { __experimentalGetBlockLabel as getBlockLabel, getBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
export default function BlockNavigationListItem(_ref) {
  var block = _ref.block,
      onClick = _ref.onClick,
      isSelected = _ref.isSelected,
      WrapperComponent = _ref.wrapperComponent,
      children = _ref.children;
  var blockType = getBlockType(block.name);
  return createElement("div", {
    className: "block-editor-block-navigation__list-item"
  }, createElement(WrapperComponent, {
    className: classnames('block-editor-block-navigation__list-item-button', {
      'is-selected': isSelected
    }),
    onClick: onClick
  }, createElement(BlockIcon, {
    icon: blockType.icon,
    showColors: true
  }), children ? children : getBlockLabel(blockType, block.attributes), isSelected && createElement(VisuallyHidden, {
    as: "span"
  }, __('(selected block)'))));
}
BlockNavigationListItem.defaultProps = {
  onClick: function onClick() {},
  wrapperComponent: function wrapperComponent(props) {
    return createElement(Button, props);
  }
};
//# sourceMappingURL=list-item.js.map