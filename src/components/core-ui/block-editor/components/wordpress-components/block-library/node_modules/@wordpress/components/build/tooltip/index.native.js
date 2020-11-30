"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
// For native mobile, just shortcircuit the Tooltip to return its child.
var Tooltip = function Tooltip(props) {
  return _element.Children.only(props.children);
};

var _default = Tooltip;
exports.default = _default;
//# sourceMappingURL=index.native.js.map