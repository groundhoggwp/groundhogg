import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import SwitchCell from '../mobile/bottom-sheet/switch-cell';

function ToggleControl(_ref) {
  var label = _ref.label,
      checked = _ref.checked,
      help = _ref.help,
      instanceId = _ref.instanceId,
      className = _ref.className,
      onChange = _ref.onChange,
      props = _objectWithoutProperties(_ref, ["label", "checked", "help", "instanceId", "className", "onChange"]);

  var id = "inspector-toggle-control-".concat(instanceId);
  return createElement(SwitchCell, _extends({
    label: label,
    id: id,
    help: help,
    className: className,
    value: checked,
    onValueChange: onChange,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props));
}

export default ToggleControl;
//# sourceMappingURL=index.native.js.map