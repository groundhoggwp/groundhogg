"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = RangeMark;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _rangeControlStyles = require("./styles/range-control-styles");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function RangeMark(_ref) {
  var className = _ref.className,
      _ref$isFilled = _ref.isFilled,
      isFilled = _ref$isFilled === void 0 ? false : _ref$isFilled,
      label = _ref.label,
      _ref$style = _ref.style,
      style = _ref$style === void 0 ? {} : _ref$style,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "isFilled", "label", "style"]);
  var classes = (0, _classnames.default)('components-range-control__mark', isFilled && 'is-filled', className);
  var labelClasses = (0, _classnames.default)('components-range-control__mark-label', isFilled && 'is-filled');
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_rangeControlStyles.Mark, (0, _extends2.default)({}, props, {
    "aria-hidden": "true",
    className: classes,
    isFilled: isFilled,
    style: style
  })), label && (0, _element.createElement)(_rangeControlStyles.MarkLabel, {
    "aria-hidden": "true",
    className: labelClasses,
    isFilled: isFilled,
    style: style
  }, label));
}
//# sourceMappingURL=mark.js.map