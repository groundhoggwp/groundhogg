import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { check } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import MenuItem from '../menu-item';
export default function MenuItemsChoice(_ref) {
  var _ref$choices = _ref.choices,
      choices = _ref$choices === void 0 ? [] : _ref$choices,
      _ref$onHover = _ref.onHover,
      onHover = _ref$onHover === void 0 ? noop : _ref$onHover,
      onSelect = _ref.onSelect,
      value = _ref.value;
  return choices.map(function (item) {
    var isSelected = value === item.value;
    return createElement(MenuItem, {
      key: item.value,
      role: "menuitemradio",
      icon: isSelected && check,
      isSelected: isSelected,
      shortcut: item.shortcut,
      className: "components-menu-items-choice",
      onClick: function onClick() {
        if (!isSelected) {
          onSelect(item.value);
        }
      },
      onMouseEnter: function onMouseEnter() {
        return onHover(item.value);
      },
      onMouseLeave: function onMouseLeave() {
        return onHover(null);
      }
    }, item.label);
  });
}
//# sourceMappingURL=index.js.map