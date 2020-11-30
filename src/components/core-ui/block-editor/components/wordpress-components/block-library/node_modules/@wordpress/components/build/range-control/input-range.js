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

var _rangeControlStyles = require("./styles/range-control-styles");

var _utils = require("./utils");

var _hooks = require("../utils/hooks");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function InputRange(_ref, ref) {
  var describedBy = _ref.describedBy,
      _ref$isShiftStepEnabl = _ref.isShiftStepEnabled,
      isShiftStepEnabled = _ref$isShiftStepEnabl === void 0 ? true : _ref$isShiftStepEnabl,
      label = _ref.label,
      _ref$onHideTooltip = _ref.onHideTooltip,
      onHideTooltip = _ref$onHideTooltip === void 0 ? _lodash.noop : _ref$onHideTooltip,
      _ref$onMouseLeave = _ref.onMouseLeave,
      onMouseLeave = _ref$onMouseLeave === void 0 ? _lodash.noop : _ref$onMouseLeave,
      _ref$step = _ref.step,
      step = _ref$step === void 0 ? 1 : _ref$step,
      _ref$onBlur = _ref.onBlur,
      onBlur = _ref$onBlur === void 0 ? _lodash.noop : _ref$onBlur,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$onFocus = _ref.onFocus,
      onFocus = _ref$onFocus === void 0 ? _lodash.noop : _ref$onFocus,
      _ref$onMouseMove = _ref.onMouseMove,
      onMouseMove = _ref$onMouseMove === void 0 ? _lodash.noop : _ref$onMouseMove,
      _ref$onShowTooltip = _ref.onShowTooltip,
      onShowTooltip = _ref$onShowTooltip === void 0 ? _lodash.noop : _ref$onShowTooltip,
      _ref$shiftStep = _ref.shiftStep,
      shiftStep = _ref$shiftStep === void 0 ? 10 : _ref$shiftStep,
      value = _ref.value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["describedBy", "isShiftStepEnabled", "label", "onHideTooltip", "onMouseLeave", "step", "onBlur", "onChange", "onFocus", "onMouseMove", "onShowTooltip", "shiftStep", "value"]);
  var jumpStep = (0, _hooks.useJumpStep)({
    step: step,
    shiftStep: shiftStep,
    isShiftStepEnabled: isShiftStepEnabled
  });
  var hoverInteractions = (0, _utils.useDebouncedHoverInteraction)({
    onHide: onHideTooltip,
    onMouseLeave: onMouseLeave,
    onMouseMove: onMouseMove,
    onShow: onShowTooltip
  });
  return (0, _element.createElement)(_rangeControlStyles.InputRange, (0, _extends2.default)({}, props, hoverInteractions, {
    "aria-describedby": describedBy,
    "aria-label": label,
    "aria-hidden": false,
    onBlur: onBlur,
    onChange: onChange,
    onFocus: onFocus,
    ref: ref,
    step: jumpStep,
    tabIndex: 0,
    type: "range",
    value: value
  }));
}

var ForwardedComponent = (0, _element.forwardRef)(InputRange);
var _default = ForwardedComponent;
exports.default = _default;
//# sourceMappingURL=input-range.js.map