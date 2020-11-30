"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
var Container = function Container(_ref) {
  var style = _ref.style,
      children = _ref.children;
  return (0, _element.createElement)(_reactNative.ScrollView, {
    alwaysBounceHorizontal: false,
    contentContainerStyle: _styles.default.content,
    horizontal: true,
    keyboardShouldPersistTaps: "always",
    showsHorizontalScrollIndicator: false,
    style: [_styles.default.container, style]
  }, children);
};

var _default = Container;
exports.default = _default;
//# sourceMappingURL=container.native.js.map