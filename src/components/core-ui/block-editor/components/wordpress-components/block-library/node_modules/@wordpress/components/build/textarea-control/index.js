"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TextareaControl;

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
function TextareaControl(_ref) {
  var label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      value = _ref.value,
      help = _ref.help,
      onChange = _ref.onChange,
      _ref$rows = _ref.rows,
      rows = _ref$rows === void 0 ? 4 : _ref$rows,
      className = _ref.className,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "hideLabelFromVision", "value", "help", "onChange", "rows", "className"]);
  var instanceId = (0, _compose.useInstanceId)(TextareaControl);
  var id = "inspector-textarea-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return (0, _element.createElement)(_baseControl.default, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, (0, _element.createElement)("textarea", (0, _extends2.default)({
    className: "components-textarea-control__input",
    id: id,
    rows: rows,
    onChange: onChangeValue,
    "aria-describedby": !!help ? id + '__help' : undefined,
    value: value
  }, props)));
}
//# sourceMappingURL=index.js.map