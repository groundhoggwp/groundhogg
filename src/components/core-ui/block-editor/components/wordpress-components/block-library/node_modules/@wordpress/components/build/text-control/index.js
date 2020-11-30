"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TextControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _compose = require("@wordpress/compose");

var _baseControl = _interopRequireDefault(require("../base-control"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function TextControl(_ref) {
  var label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      value = _ref.value,
      help = _ref.help,
      className = _ref.className,
      onChange = _ref.onChange,
      _ref$type = _ref.type,
      type = _ref$type === void 0 ? 'text' : _ref$type,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "hideLabelFromVision", "value", "help", "className", "onChange", "type"]);
  var instanceId = (0, _compose.useInstanceId)(TextControl);
  var id = "inspector-text-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return (0, _element.createElement)(_baseControl.default, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, (0, _element.createElement)("input", (0, _extends2.default)({
    className: "components-text-control__input",
    type: type,
    id: id,
    value: value,
    onChange: onChangeValue,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)));
}
//# sourceMappingURL=index.js.map