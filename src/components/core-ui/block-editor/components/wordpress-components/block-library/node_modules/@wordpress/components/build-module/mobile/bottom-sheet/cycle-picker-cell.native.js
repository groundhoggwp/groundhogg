import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { findIndex } from 'lodash';
/**
 * Internal dependencies
 */

import Cell from './cell';
export default function BottomSheetCyclePickerCell(props) {
  var value = props.value,
      options = props.options,
      onChangeValue = props.onChangeValue,
      cellProps = _objectWithoutProperties(props, ["value", "options", "onChangeValue"]);

  var nextOptionValue = function nextOptionValue() {
    return options[(selectedOptionIndex + 1) % options.length].value;
  };

  var selectedOptionIndex = findIndex(options, ['value', value]);
  var optionsContainsValue = options.length > 0 && selectedOptionIndex !== -1;
  return createElement(Cell, _extends({
    onPress: function onPress() {
      return optionsContainsValue && onChangeValue(nextOptionValue());
    },
    editable: false,
    value: optionsContainsValue && options[selectedOptionIndex].name
  }, cellProps));
}
//# sourceMappingURL=cycle-picker-cell.native.js.map