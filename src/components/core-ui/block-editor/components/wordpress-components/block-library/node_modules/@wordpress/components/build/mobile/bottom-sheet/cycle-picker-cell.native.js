"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BottomSheetCyclePickerCell;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _cell = _interopRequireDefault(require("./cell"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function BottomSheetCyclePickerCell(props) {
  var value = props.value,
      options = props.options,
      onChangeValue = props.onChangeValue,
      cellProps = (0, _objectWithoutProperties2.default)(props, ["value", "options", "onChangeValue"]);

  var nextOptionValue = function nextOptionValue() {
    return options[(selectedOptionIndex + 1) % options.length].value;
  };

  var selectedOptionIndex = (0, _lodash.findIndex)(options, ['value', value]);
  var optionsContainsValue = options.length > 0 && selectedOptionIndex !== -1;
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({
    onPress: function onPress() {
      return optionsContainsValue && onChangeValue(nextOptionValue());
    },
    editable: false,
    value: optionsContainsValue && options[selectedOptionIndex].name
  }, cellProps));
}
//# sourceMappingURL=cycle-picker-cell.native.js.map