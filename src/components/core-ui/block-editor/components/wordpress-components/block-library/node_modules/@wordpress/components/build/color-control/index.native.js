"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _colorCell = _interopRequireDefault(require("../mobile/bottom-sheet/color-cell"));

/**
 * Internal dependencies
 */
function ColorControl(_ref) {
  var label = _ref.label,
      onPress = _ref.onPress,
      color = _ref.color,
      withColorIndicator = _ref.withColorIndicator,
      props = (0, _objectWithoutProperties2.default)(_ref, ["label", "onPress", "color", "withColorIndicator"]);
  return (0, _element.createElement)(_colorCell.default, (0, _extends2.default)({
    label: label,
    onPress: onPress,
    color: color,
    withColorIndicator: withColorIndicator
  }, props));
}

var _default = ColorControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map