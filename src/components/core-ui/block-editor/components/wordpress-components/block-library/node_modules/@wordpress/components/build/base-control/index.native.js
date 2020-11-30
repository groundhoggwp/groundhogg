"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BaseControl;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

/**
 * External dependencies
 */
function BaseControl(_ref) {
  var label = _ref.label,
      help = _ref.help,
      children = _ref.children;
  return (0, _element.createElement)(_reactNative.View, {
    accessible: true,
    accessibilityLabel: label
  }, label && (0, _element.createElement)(_reactNative.Text, null, label), children, help && (0, _element.createElement)(_reactNative.Text, null, help));
}
//# sourceMappingURL=index.native.js.map