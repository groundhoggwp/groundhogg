import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { Switch } from 'react-native';
/**
 * WordPress dependencies
 */

import { __, _x, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import Cell from './cell';
export default function BottomSheetSwitchCell(props) {
  var value = props.value,
      onValueChange = props.onValueChange,
      cellProps = _objectWithoutProperties(props, ["value", "onValueChange"]);

  var onPress = function onPress() {
    onValueChange(!value);
  };

  var accessibilityLabel = value ? sprintf(
  /* translators: accessibility text. Switch setting ON state. %s: Switch title. */
  _x('%s. On', 'switch control'), cellProps.label) : sprintf(
  /* translators: accessibility text. Switch setting OFF state. %s: Switch title. */
  _x('%s. Off', 'switch control'), cellProps.label);
  return createElement(Cell, _extends({}, cellProps, {
    accessibilityLabel: accessibilityLabel,
    accessibilityRole: 'none',
    accessibilityHint:
    /* translators: accessibility text (hint for switches) */
    __('Double tap to toggle setting'),
    onPress: onPress,
    editable: false,
    value: ''
  }), createElement(Switch, {
    value: value,
    onValueChange: onValueChange
  }));
}
//# sourceMappingURL=switch-cell.native.js.map