"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

/**
 * External dependencies
 */
var ColorIndicator = function ColorIndicator(_ref) {
  var className = _ref.className,
      colorValue = _ref.colorValue,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "colorValue"]);
  return (0, _element.createElement)("span", (0, _extends2.default)({
    className: (0, _classnames.default)('component-color-indicator', className),
    style: {
      background: colorValue
    }
  }, props));
};

var _default = ColorIndicator;
exports.default = _default;
//# sourceMappingURL=index.js.map