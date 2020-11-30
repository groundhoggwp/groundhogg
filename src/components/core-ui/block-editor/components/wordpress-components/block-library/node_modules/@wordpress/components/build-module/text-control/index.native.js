import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import Cell from '../mobile/bottom-sheet/cell';

function TextControl(_ref) {
  var label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      value = _ref.value,
      help = _ref.help,
      className = _ref.className,
      instanceId = _ref.instanceId,
      onChange = _ref.onChange,
      _ref$type = _ref.type,
      type = _ref$type === void 0 ? 'text' : _ref$type,
      props = _objectWithoutProperties(_ref, ["label", "hideLabelFromVision", "value", "help", "className", "instanceId", "onChange", "type"]);

  var id = "inspector-text-control-".concat(instanceId);
  return createElement(Cell, _extends({
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className,
    type: type,
    value: value,
    onChangeValue: onChange,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props));
}

export default TextControl;
//# sourceMappingURL=index.native.js.map