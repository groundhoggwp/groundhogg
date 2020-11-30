"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = MenuItemsChoice;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _icons = require("@wordpress/icons");

var _menuItem = _interopRequireDefault(require("../menu-item"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function MenuItemsChoice(_ref) {
  var _ref$choices = _ref.choices,
      choices = _ref$choices === void 0 ? [] : _ref$choices,
      _ref$onHover = _ref.onHover,
      onHover = _ref$onHover === void 0 ? _lodash.noop : _ref$onHover,
      onSelect = _ref.onSelect,
      value = _ref.value;
  return choices.map(function (item) {
    var isSelected = value === item.value;
    return (0, _element.createElement)(_menuItem.default, {
      key: item.value,
      role: "menuitemradio",
      icon: isSelected && _icons.check,
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