import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { Icon, check } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import BaseControl from '../base-control';
export default function CheckboxControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      heading = _ref.heading,
      checked = _ref.checked,
      help = _ref.help,
      onChange = _ref.onChange,
      props = _objectWithoutProperties(_ref, ["label", "className", "heading", "checked", "help", "onChange"]);

  var instanceId = useInstanceId(CheckboxControl);
  var id = "inspector-checkbox-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.checked);
  };

  return createElement(BaseControl, {
    label: heading,
    id: id,
    help: help,
    className: className
  }, createElement("span", {
    className: "components-checkbox-control__input-container"
  }, createElement("input", _extends({
    id: id,
    className: "components-checkbox-control__input",
    type: "checkbox",
    value: "1",
    onChange: onChangeValue,
    checked: checked,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)), checked ? createElement(Icon, {
    icon: check,
    className: "components-checkbox-control__checked",
    role: "presentation"
  }) : null), createElement("label", {
    className: "components-checkbox-control__label",
    htmlFor: id
  }, label));
}
//# sourceMappingURL=index.js.map