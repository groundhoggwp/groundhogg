"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "FlexBlock", {
  enumerable: true,
  get: function get() {
    return _block.default;
  }
});
Object.defineProperty(exports, "FlexItem", {
  enumerable: true,
  get: function get() {
    return _item.default;
  }
});
exports.default = exports.Flex = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _flexStyles = require("./styles/flex-styles");

var _block = _interopRequireDefault(require("./block"));

var _item = _interopRequireDefault(require("./item"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function FlexComponent(_ref, ref) {
  var _ref$align = _ref.align,
      align = _ref$align === void 0 ? 'center' : _ref$align,
      className = _ref.className,
      _ref$gap = _ref.gap,
      gap = _ref$gap === void 0 ? 2 : _ref$gap,
      _ref$justify = _ref.justify,
      justify = _ref$justify === void 0 ? 'space-between' : _ref$justify,
      _ref$isReversed = _ref.isReversed,
      isReversed = _ref$isReversed === void 0 ? false : _ref$isReversed,
      props = (0, _objectWithoutProperties2.default)(_ref, ["align", "className", "gap", "justify", "isReversed"]);
  var classes = (0, _classnames.default)('components-flex', className);
  return (0, _element.createElement)(_flexStyles.Flex, (0, _extends2.default)({}, props, {
    align: align,
    className: classes,
    ref: ref,
    gap: gap,
    justify: justify,
    isReversed: isReversed
  }));
}

var Flex = (0, _element.forwardRef)(FlexComponent);
exports.Flex = Flex;
var _default = Flex;
exports.default = _default;
//# sourceMappingURL=index.js.map