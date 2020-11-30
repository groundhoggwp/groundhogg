"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _cell = _interopRequireDefault(require("../mobile/bottom-sheet/cell"));

/**
 * Internal dependencies
 */
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
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "hideLabelFromVision", "value", "help", "className", "instanceId", "onChange", "type"]);
  var id = "inspector-text-control-".concat(instanceId);
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({
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

var _default = TextControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map