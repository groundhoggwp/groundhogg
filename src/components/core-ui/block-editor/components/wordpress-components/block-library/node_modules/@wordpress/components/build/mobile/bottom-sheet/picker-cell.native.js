"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BottomSheetPickerCell;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _cell = _interopRequireDefault(require("./cell"));

var _picker = _interopRequireDefault(require("../picker"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function BottomSheetPickerCell(props) {
  var options = props.options,
      hideCancelButton = props.hideCancelButton,
      onChangeValue = props.onChangeValue,
      value = props.value,
      cellProps = (0, _objectWithoutProperties2.default)(props, ["options", "hideCancelButton", "onChangeValue", "value"]);
  var picker;

  var onCellPress = function onCellPress() {
    picker.presentPicker();
  };

  var onChange = function onChange(newValue) {
    onChangeValue(newValue);
  };

  var option = (0, _lodash.find)(options, {
    value: value
  });
  var label = option ? option.label : value;
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({
    onPress: onCellPress,
    editable: false,
    value: label
  }, cellProps), (0, _element.createElement)(_picker.default, {
    leftAlign: true,
    hideCancelButton: hideCancelButton,
    ref: function ref(instance) {
      return picker = instance;
    },
    options: options,
    onChange: onChange
  }));
}
//# sourceMappingURL=picker-cell.native.js.map