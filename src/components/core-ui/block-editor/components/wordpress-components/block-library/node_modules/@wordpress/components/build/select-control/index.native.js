"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _pickerCell = _interopRequireDefault(require("../mobile/bottom-sheet/picker-cell"));

/**
 * Internal dependencies
 */
function SelectControl(_ref) {
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
      props = (0, _objectWithoutProperties2.default)(_ref, ["help", "instanceId", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);
  var id = "inspector-select-control-".concat(instanceId);
  return (0, _element.createElement)(_pickerCell.default, (0, _extends2.default)({
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

var _default = SelectControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map