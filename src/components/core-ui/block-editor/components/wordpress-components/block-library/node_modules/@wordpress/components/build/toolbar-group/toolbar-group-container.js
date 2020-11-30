"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var ToolbarGroupContainer = function ToolbarGroupContainer(_ref) {
  var className = _ref.className,
      children = _ref.children,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "children"]);
  return (0, _element.createElement)("div", (0, _extends2.default)({
    className: className
  }, props), children);
};

var _default = ToolbarGroupContainer;
exports.default = _default;
//# sourceMappingURL=toolbar-group-container.js.map