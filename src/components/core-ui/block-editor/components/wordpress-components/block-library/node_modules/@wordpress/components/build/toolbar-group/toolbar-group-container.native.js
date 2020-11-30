"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _compose = require("@wordpress/compose");

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
var ToolbarGroupContainer = function ToolbarGroupContainer(_ref) {
  var getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      passedStyle = _ref.passedStyle,
      children = _ref.children;
  return (0, _element.createElement)(_reactNative.View, {
    style: [getStylesFromColorScheme(_style.default.container, _style.default.containerDark), passedStyle]
  }, children);
};

var _default = (0, _compose.withPreferredColorScheme)(ToolbarGroupContainer);

exports.default = _default;
//# sourceMappingURL=toolbar-group-container.native.js.map