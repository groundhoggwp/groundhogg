"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BottomSheetSwitchCell;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _cell = _interopRequireDefault(require("./cell"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BottomSheetSwitchCell(props) {
  var value = props.value,
      onValueChange = props.onValueChange,
      cellProps = (0, _objectWithoutProperties2.default)(props, ["value", "onValueChange"]);

  var onPress = function onPress() {
    onValueChange(!value);
  };

  var accessibilityLabel = value ? (0, _i18n.sprintf)(
  /* translators: accessibility text. Switch setting ON state. %s: Switch title. */
  (0, _i18n._x)('%s. On', 'switch control'), cellProps.label) : (0, _i18n.sprintf)(
  /* translators: accessibility text. Switch setting OFF state. %s: Switch title. */
  (0, _i18n._x)('%s. Off', 'switch control'), cellProps.label);
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({}, cellProps, {
    accessibilityLabel: accessibilityLabel,
    accessibilityRole: 'none',
    accessibilityHint:
    /* translators: accessibility text (hint for switches) */
    (0, _i18n.__)('Double tap to toggle setting'),
    onPress: onPress,
    editable: false,
    value: ''
  }), (0, _element.createElement)(_reactNative.Switch, {
    value: value,
    onValueChange: onValueChange
  }));
}
//# sourceMappingURL=switch-cell.native.js.map