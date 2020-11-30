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

var _components = require("@wordpress/components");

var _cell = _interopRequireDefault(require("./cell"));

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BottomSheetColorCell(props) {
  var color = props.color,
      _props$withColorIndic = props.withColorIndicator,
      withColorIndicator = _props$withColorIndic === void 0 ? true : _props$withColorIndic,
      cellProps = (0, _objectWithoutProperties2.default)(props, ["color", "withColorIndicator"]);
  return (0, _element.createElement)(_cell.default, (0, _extends2.default)({}, cellProps, {
    accessibilityRole: 'button',
    accessibilityHint:
    /* translators: accessibility text (hint for moving to color settings) */
    (0, _i18n.__)('Double tap to go to color settings'),
    editable: false,
    value: withColorIndicator && !color && (0, _i18n.__)('Default')
  }), withColorIndicator && color && (0, _element.createElement)(_components.ColorIndicator, {
    color: color,
    style: _styles.default.colorCircle
  }), (0, _element.createElement)(_icons.Icon, {
    icon: _icons.chevronRight
  }));
}
//# sourceMappingURL=color-cell.native.js.map