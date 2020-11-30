"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BottomSheetColorCell;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _compose = require("@wordpress/compose");

var _cell = _interopRequireDefault(require("./cell"));

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BottomSheetColorCell(props) {
  var selected = props.selected,
      cellProps = (0, _objectWithoutProperties2.default)(props, ["selected"]);
  var selectedIconStyle = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.selectedIcon, _styles.default.selectedIconDark);
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({}, cellProps, {
    accessibilityRole: 'radio',
    accessibilityState: {
      selected: selected
    },
    accessibilityHint:
    /* translators: accessibility text (hint for selecting option) */
    (0, _i18n.__)('Double tap to select the option'),
    editable: false,
    value: ''
  }), selected && (0, _element.createElement)(_icons.Icon, {
    icon: _icons.check,
    style: selectedIconStyle
  }));
}
//# sourceMappingURL=radio-cell.native.js.map