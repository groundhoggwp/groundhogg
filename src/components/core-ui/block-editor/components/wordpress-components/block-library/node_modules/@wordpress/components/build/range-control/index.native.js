"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _rangeCell = _interopRequireDefault(require("../mobile/bottom-sheet/range-cell"));

var _stepperCell = _interopRequireDefault(require("../mobile/bottom-sheet/stepper-cell"));

/**
 * Internal dependencies
 */
function RangeControl(_ref) {
  var className = _ref.className,
      currentInput = _ref.currentInput,
      label = _ref.label,
      value = _ref.value,
      instanceId = _ref.instanceId,
      onChange = _ref.onChange,
      beforeIcon = _ref.beforeIcon,
      afterIcon = _ref.afterIcon,
      help = _ref.help,
      allowReset = _ref.allowReset,
      initialPosition = _ref.initialPosition,
      min = _ref.min,
      max = _ref.max,
      type = _ref.type,
      separatorType = _ref.separatorType,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "currentInput", "label", "value", "instanceId", "onChange", "beforeIcon", "afterIcon", "help", "allowReset", "initialPosition", "min", "max", "type", "separatorType"]);

  if (type === 'stepper') {
    return (0, _element.createElement)(_stepperCell.default, {
      label: label,
      max: max,
      min: min,
      onChange: onChange,
      separatorType: separatorType,
      value: value
    });
  }

  var id = "inspector-range-control-".concat(instanceId);
  var currentInputValue = currentInput === null ? value : currentInput;
  var initialSliderValue = isFinite(currentInputValue) ? currentInputValue : initialPosition;
  return (0, _element.createElement)(_rangeCell.default, (0, _extends2.default)({
    label: label,
    id: id,
    help: help,
    className: className,
    onChange: onChange,
    "aria-describedby": !!help ? "".concat(id, "__help") : undefined,
    minimumValue: min,
    maximumValue: max,
    value: value,
    beforeIcon: beforeIcon,
    afterIcon: afterIcon,
    allowReset: allowReset,
    defaultValue: initialSliderValue,
    separatorType: separatorType
  }, props));
}

var _default = RangeControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map