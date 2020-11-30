import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import CyclePickerCell from '../bottom-sheet/cycle-picker-cell';

function CycleSelectControl(_ref) {
  var help = _ref.help,
      instanceId = _ref.instanceId,
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = _objectWithoutProperties(_ref, ["help", "instanceId", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);

  var id = "inspector-select-control-".concat(instanceId);
  return createElement(CyclePickerCell, _extends({
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className,
    onChangeValue: onChange,
    "aria-describedby": !!help ? "".concat(id, "__help") : undefined,
    multiple: multiple,
    options: options
  }, props));
}

export default CycleSelectControl;
//# sourceMappingURL=index.native.js.map