"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

var _dropdownMenu = _interopRequireDefault(require("../dropdown-menu"));

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ToolbarGroupCollapsed(_ref) {
  var _ref$controls = _ref.controls,
      controls = _ref$controls === void 0 ? [] : _ref$controls,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      passedStyle = _ref.passedStyle,
      props = (0, _objectWithoutProperties2.default)(_ref, ["controls", "getStylesFromColorScheme", "passedStyle"]);
  return (0, _element.createElement)(_reactNative.View, {
    style: [getStylesFromColorScheme(_style.default.container, _style.default.containerDark), passedStyle]
  }, (0, _element.createElement)(_dropdownMenu.default, (0, _extends2.default)({
    controls: controls
  }, props)));
}

var _default = (0, _compose.withPreferredColorScheme)(ToolbarGroupCollapsed);

exports.default = _default;
//# sourceMappingURL=toolbar-group-collapsed.native.js.map