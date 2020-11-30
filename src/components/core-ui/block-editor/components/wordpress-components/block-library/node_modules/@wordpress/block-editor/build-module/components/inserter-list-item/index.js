import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { CompositeItem } from 'reakit';
/**
 * WordPress dependencies
 */

import { Button } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';

function InserterListItem(_ref) {
  var icon = _ref.icon,
      _onClick = _ref.onClick,
      isDisabled = _ref.isDisabled,
      title = _ref.title,
      className = _ref.className,
      composite = _ref.composite,
      props = _objectWithoutProperties(_ref, ["icon", "onClick", "isDisabled", "title", "className", "composite"]);

  var itemIconStyle = icon ? {
    backgroundColor: icon.background,
    color: icon.foreground
  } : {};
  return createElement("div", {
    className: "block-editor-block-types-list__list-item"
  }, createElement(CompositeItem, _extends({
    role: "option",
    as: Button
  }, composite, {
    className: classnames('block-editor-block-types-list__item', className),
    onClick: function onClick(event) {
      event.preventDefault();

      _onClick();
    },
    disabled: isDisabled
  }, props), createElement("span", {
    className: "block-editor-block-types-list__item-icon",
    style: itemIconStyle
  }, createElement(BlockIcon, {
    icon: icon,
    showColors: true
  })), createElement("span", {
    className: "block-editor-block-types-list__item-title"
  }, title)));
}

export default InserterListItem;
//# sourceMappingURL=index.js.map