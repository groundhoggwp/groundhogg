import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { find } from 'lodash';
/**
 * Internal dependencies
 */

import Cell from './cell';
import Picker from '../picker';
export default function BottomSheetPickerCell(props) {
  var options = props.options,
      hideCancelButton = props.hideCancelButton,
      onChangeValue = props.onChangeValue,
      value = props.value,
      cellProps = _objectWithoutProperties(props, ["options", "hideCancelButton", "onChangeValue", "value"]);

  var picker;

  var onCellPress = function onCellPress() {
    picker.presentPicker();
  };

  var onChange = function onChange(newValue) {
    onChangeValue(newValue);
  };

  var option = find(options, {
    value: value
  });
  var label = option ? option.label : value;
  return createElement(Cell, _extends({
    onPress: onCellPress,
    editable: false,
    value: label
  }, cellProps), createElement(Picker, {
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