"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _item = _interopRequireDefault(require("./item"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _default = (0, _element.forwardRef)(function TreeGridCell(_ref, ref) {
  var children = _ref.children,
      _ref$withoutGridItem = _ref.withoutGridItem,
      withoutGridItem = _ref$withoutGridItem === void 0 ? false : _ref$withoutGridItem,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "withoutGridItem"]);
  return (0, _element.createElement)("td", (0, _extends2.default)({}, props, {
    role: "gridcell"
  }), withoutGridItem ? children : (0, _element.createElement)(_item.default, {
    ref: ref
  }, children));
});

exports.default = _default;
//# sourceMappingURL=cell.js.map