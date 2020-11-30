import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import RangeCell from '../mobile/bottom-sheet/range-cell';
import StepperCell from '../mobile/bottom-sheet/stepper-cell';

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
      props = _objectWithoutProperties(_ref, ["className", "currentInput", "label", "value", "instanceId", "onChange", "beforeIcon", "afterIcon", "help", "allowReset", "initialPosition", "min", "max", "type", "separatorType"]);

  if (type === 'stepper') {
    return createElement(StepperCell, {
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
  return createElement(RangeCell, _extends({
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

export default RangeControl;
//# sourceMappingURL=index.native.js.map