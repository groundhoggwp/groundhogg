"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _radioCell = _interopRequireDefault(require("../mobile/bottom-sheet/radio-cell"));

/**
 * Internal dependencies
 */
function RadioControl(_ref) {
  var onChange = _ref.onChange,
      selected = _ref.selected,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      props = (0, _objectWithoutProperties2.default)(_ref, ["onChange", "selected", "options"]);
  return (0, _element.createElement)(_element.Fragment, null, options.map(function (option, index) {
    return (0, _element.createElement)(_radioCell.default, (0, _extends2.default)({
      label: option.label,
      onPress: function onPress() {
        return onChange(option.value);
      },
      selected: option.value === selected,
      key: "".concat(option.value, "-").concat(index)
    }, props));
  }));
}

var _default = RadioControl;
exports.default = _default;
//# sourceMappingURL=index.native.js.map