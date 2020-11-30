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

var _flexStyles = require("./styles/flex-styles");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * WordPress dependencies
 */
function FlexBlock(_ref, ref) {
  var className = _ref.className,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className"]);
  var classes = (0, _classnames.default)('components-flex__block', className);
  return (0, _element.createElement)(_flexStyles.Block, (0, _extends2.default)({}, props, {
    className: classes,
    ref: ref
  }));
}

var _default = (0, _element.forwardRef)(FlexBlock);

exports.default = _default;
//# sourceMappingURL=block.js.map