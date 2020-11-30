"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.PanelBody = PanelBody;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _body = _interopRequireDefault(require("./body.scss"));

var _bottomSeparatorCover = _interopRequireDefault(require("./bottom-separator-cover"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function PanelBody(_ref) {
  var children = _ref.children,
      title = _ref.title,
      _ref$style = _ref.style,
      style = _ref$style === void 0 ? {} : _ref$style;
  return (0, _element.createElement)(_reactNative.View, {
    style: [_body.default.panelContainer, style]
  }, title && (0, _element.createElement)(_reactNative.Text, {
    style: _body.default.sectionHeaderText
  }, title), children, (0, _element.createElement)(_bottomSeparatorCover.default, null));
}

var _default = PanelBody;
exports.default = _default;
//# sourceMappingURL=body.native.js.map