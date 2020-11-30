"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _rovingTabIndexItem = _interopRequireDefault(require("./roving-tab-index-item"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _default = (0, _element.forwardRef)(function TreeGridItem(_ref, ref) {
  var children = _ref.children,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children"]);
  return (0, _element.createElement)(_rovingTabIndexItem.default, (0, _extends2.default)({
    ref: ref
  }, props), children);
});

exports.default = _default;
//# sourceMappingURL=item.js.map