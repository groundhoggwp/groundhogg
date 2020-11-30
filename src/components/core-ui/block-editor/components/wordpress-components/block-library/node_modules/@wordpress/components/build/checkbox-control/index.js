"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = CheckboxControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _compose = require("@wordpress/compose");

var _icons = require("@wordpress/icons");

var _baseControl = _interopRequireDefault(require("../base-control"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function CheckboxControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      heading = _ref.heading,
      checked = _ref.checked,
      help = _ref.help,
      onChange = _ref.onChange,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "className", "heading", "checked", "help", "onChange"]);
  var instanceId = (0, _compose.useInstanceId)(CheckboxControl);
  var id = "inspector-checkbox-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.checked);
  };

  return (0, _element.createElement)(_baseControl.default, {
    label: heading,
    id: id,
    help: help,
    className: className
  }, (0, _element.createElement)("span", {
    className: "components-checkbox-control__input-container"
  }, (0, _element.createElement)("input", (0, _extends2.default)({
    id: id,
    className: "components-checkbox-control__input",
    type: "checkbox",
    value: "1",
    onChange: onChangeValue,
    checked: checked,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)), checked ? (0, _element.createElement)(_icons.Icon, {
    icon: _icons.check,
    className: "components-checkbox-control__checked",
    role: "presentation"
  }) : null), (0, _element.createElement)("label", {
    className: "components-checkbox-control__label",
    htmlFor: id
  }, label));
}
//# sourceMappingURL=index.js.map