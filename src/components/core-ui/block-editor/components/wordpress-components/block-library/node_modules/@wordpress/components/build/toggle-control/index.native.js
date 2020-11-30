"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _switchCell = _interopRequireDefault(require("../mobile/bottom-sheet/switch-cell"));

/**
 * Internal dependencies
 */
function ToggleControl(_ref) {
  var label = _ref.label,
      checked = _ref.checked,
      help = _ref.help,
      instanceId = _ref.instanceId,
      className = _ref.className,
      onChange = _ref.onChange,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "checked", "help", "instanceId", "className", "onChange"]);
  var id = "inspector-toggle-control-".concat(instanceId);
  return (0, _element.createElement)(_switchCell.default, (0, _extends2.default)({
    label: label,
    id: id,
    help: help,
    className: className,
    value: checked,
    onValueChange: onChange,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props));
}

var _default = ToggleControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map