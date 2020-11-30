import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BaseControl from '../base-control';
export default function TextareaControl(_ref) {
  var label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      value = _ref.value,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$rows = _ref.rows,
      rows = _ref$rows === void 0 ? 4 : _ref$rows,
      className = _ref.className,
      props = _objectWithoutProperties(_ref, ["label", "hideLabelFromVision", "value", "help", "onChange", "rows", "className"]);

  var instanceId = useInstanceId(TextareaControl);
  var id = "inspector-textarea-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return createElement(BaseControl, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, createElement("textarea", _extends({
    className: "components-textarea-control__input",
    id: id,
    rows: rows,
    onChange: onChangeValue,
    "aria-describedby": !!help ? id + '__help' : undefined,
    value: value
  }, props)));
}
//# sourceMappingURL=index.js.map