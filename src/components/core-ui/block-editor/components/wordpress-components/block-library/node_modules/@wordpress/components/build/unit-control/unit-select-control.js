"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = UnitSelectControl;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _unitControlStyles = require("./styles/unit-control-styles");

var _utils = require("./utils");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Renders a `select` if there are multiple units.
 * Otherwise, renders a non-selectable label.
 *
 * @param {Object}   props                     Component props.
 * @param {string}   [props.className]         Class to set on the `select` element.
 * @param {boolean}  [props.isTabbable=true]   Whether the control can be focused via keyboard navigation.
 * @param {Array}    [props.options=CSS_UNITS] Available units to select from.
 * @param {Function} [props.onChange=noop]     A callback function invoked when the value is changed.
 * @param {string}   [props.size="default"]    Size of the control option. Supports "default" and "small".
 * @param {string}   [props.value="px"]        Current unit.
 */
function UnitSelectControl(_ref) {
  var className = _ref.className,
      _ref$isTabbable = _ref.isTabbable,
      isTabbable = _ref$isTabbable === void 0 ? true : _ref$isTabbable,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? _utils.CSS_UNITS : _ref$options,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? _lodash.noop : _ref$onChange,
      _ref$size = _ref.size,
      size = _ref$size === void 0 ? 'default' : _ref$size,
      _ref$value = _ref.value,
      value = _ref$value === void 0 ? 'px' : _ref$value,
      props = (0, _objectWithoutProperties2.default)(_ref, ["className", "isTabbable", "options", "onChange", "size", "value"]);

  if (!(0, _utils.hasUnits)(options)) {
    return (0, _element.createElement)(_unitControlStyles.UnitLabel, {
      className: "components-unit-control__unit-label",
      size: size
    }, value);
  }

  var handleOnChange = function handleOnChange(event) {
    var unitValue = event.target.value;
    var data = options.find(function (option) {
      return option.value === unitValue;
    });
    onChange(unitValue, {
      event: event,
      data: data
    });
  };

  var classes = (0, _classnames.default)('components-unit-control__select', className);
  return (0, _element.createElement)(_unitControlStyles.UnitSelect, (0, _extends2.default)({
    className: classes,
    onChange: handleOnChange,
    size: size,
    tabIndex: isTabbable ? null : '-1',
    value: value
  }, props), options.map(function (option) {
    return (0, _element.createElement)("option", {
      value: option.value,
      key: option.value
    }, option.label);
  }));
}
//# sourceMappingURL=unit-select-control.js.map