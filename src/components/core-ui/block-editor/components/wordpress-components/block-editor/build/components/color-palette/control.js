"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ColorPaletteControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _control = _interopRequireDefault(require("../colors-gradients/control"));

/**
 * Internal dependencies
 */
function ColorPaletteControl(_ref) {
  var onChange = _ref.onChange,
      value = _ref.value,
      otherProps = (0, _objectWithoutProperties2.default)(_ref, ["onChange", "value"]);
  return (0, _element.createElement)(_control.default, (0, _extends2.default)({}, otherProps, {
    onColorChange: onChange,
    colorValue: value,
    gradients: [],
    disableCustomGradients: true
  }));
}
//# sourceMappingURL=control.js.map