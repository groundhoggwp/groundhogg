"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _label = _interopRequireDefault(require("./label"));

var _utils = require("./utils");

var _resizeTooltip = require("./styles/resize-tooltip.styles");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ResizeTooltip(_ref, ref) {
  var axis = _ref.axis,
      className = _ref.className,
      _ref$fadeTimeout = _ref.fadeTimeout,
      fadeTimeout = _ref$fadeTimeout === void 0 ? 180 : _ref$fadeTimeout,
      _ref$isVisible = _ref.isVisible,
      isVisible = _ref$isVisible === void 0 ? true : _ref$isVisible,
      labelRef = _ref.labelRef,
      _ref$onResize = _ref.onResize,
      onResize = _ref$onResize === void 0 ? _lodash.noop : _ref$onResize,
      _ref$position = _ref.position,
      position = _ref$position === void 0 ? _utils.POSITIONS.bottom : _ref$position,
      _ref$showPx = _ref.showPx,
      showPx = _ref$showPx === void 0 ? true : _ref$showPx,
      _ref$zIndex = _ref.zIndex,
      zIndex = _ref$zIndex === void 0 ? 1000 : _ref$zIndex,
      props = (0, _objectWithoutProperties2.default)(_ref, ["axis", "className", "fadeTimeout", "isVisible", "labelRef", "onResize", "position", "showPx", "zIndex"]);

  var _useResizeLabel = (0, _utils.useResizeLabel)({
    axis: axis,
    fadeTimeout: fadeTimeout,
    onResize: onResize,
    showPx: showPx,
    position: position
  }),
      label = _useResizeLabel.label,
      resizeListener = _useResizeLabel.resizeListener;

  if (!isVisible) return null;
  var classes = (0, _classnames.default)('components-resize-tooltip', className);
  return (0, _element.createElement)(_resizeTooltip.Root, (0, _extends2.default)({
    "aria-hidden": "true",
    className: classes,
    ref: ref
  }, props), resizeListener, (0, _element.createElement)(_label.default, {
    "aria-hidden": props['aria-hidden'],
    fadeTimeout: fadeTimeout,
    isVisible: isVisible,
    label: label,
    position: position,
    ref: labelRef,
    zIndex: zIndex
  }));
}

var ForwardedComponent = (0, _element.forwardRef)(ResizeTooltip);
var _default = ForwardedComponent;
exports.default = _default;
//# sourceMappingURL=index.js.map