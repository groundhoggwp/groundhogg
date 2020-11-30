"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

var _button = _interopRequireDefault(require("../button"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function IconButton(_ref, ref) {
  var labelPosition = _ref.labelPosition,
      size = _ref.size,
      tooltip = _ref.tooltip,
      label = _ref.label,
      props = (0, _objectWithoutProperties2.default)(_ref, ["labelPosition", "size", "tooltip", "label"]);
  (0, _deprecated.default)('wp.components.IconButton', {
    alternative: 'wp.components.Button'
  });
  return (0, _element.createElement)(_button.default, (0, _extends2.default)({}, props, {
    ref: ref,
    tooltipPosition: labelPosition,
    iconSize: size,
    showTooltip: tooltip !== undefined ? !!tooltip : undefined,
    label: tooltip || label
  }));
}

var _default = (0, _element.forwardRef)(IconButton);

exports.default = _default;
//# sourceMappingURL=deprecated.js.map