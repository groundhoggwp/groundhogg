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

/**
 * WordPress dependencies
 */
function ButtonGroup(_ref, ref) {
  var className = _ref.className,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className"]);
  var classes = (0, _classnames.default)('components-button-group', className);
  return (0, _element.createElement)("div", (0, _extends2.default)({
    ref: ref,
    role: "group",
    className: classes
  }, props));
}

var _default = (0, _element.forwardRef)(ButtonGroup);

exports.default = _default;
//# sourceMappingURL=index.js.map