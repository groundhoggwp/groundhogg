"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkedButton;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _button = _interopRequireDefault(require("../button"));

var _tooltip = _interopRequireDefault(require("../tooltip"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function LinkedButton(_ref) {
  var isLinked = _ref.isLinked,
      props = (0, _objectWithoutProperties2.default)(_ref, ["isLinked"]);
  var linkedTooltipText = isLinked ? (0, _i18n.__)('Unlink Sides') : (0, _i18n.__)('Link Sides');
  return (0, _element.createElement)(_tooltip.default, {
    text: linkedTooltipText
  }, (0, _element.createElement)("span", null, (0, _element.createElement)(_button.default, (0, _extends2.default)({}, props, {
    className: "component-box-control__linked-button",
    isPrimary: isLinked,
    isSecondary: !isLinked,
    isSmall: true,
    icon: isLinked ? _icons.link : _icons.linkOff,
    iconSize: 16
  }))));
}
//# sourceMappingURL=linked-button.js.map