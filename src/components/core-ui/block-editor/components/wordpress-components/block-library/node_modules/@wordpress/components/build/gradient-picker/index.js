"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = GradientPicker;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _circularOptionPicker = _interopRequireDefault(require("../circular-option-picker"));

var _customGradientPicker = _interopRequireDefault(require("../custom-gradient-picker"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function GradientPicker(_ref) {
  var className = _ref.className,
      gradients = _ref.gradients,
      onChange = _ref.onChange,
      value = _ref.value,
      _ref$clearable = _ref.clearable,
      clearable = _ref$clearable === void 0 ? true : _ref$clearable,
      _ref$disableCustomGra = _ref.disableCustomGradients,
      disableCustomGradients = _ref$disableCustomGra === void 0 ? false : _ref$disableCustomGra;
  var clearGradient = (0, _element.useCallback)(function () {
    return onChange(undefined);
  }, [onChange]);
  var gradientOptions = (0, _element.useMemo)(function () {
    return (0, _lodash.map)(gradients, function (_ref2) {
      var gradient = _ref2.gradient,
          name = _ref2.name;
      return (0, _element.createElement)(_circularOptionPicker.default.Option, {
        key: gradient,
        value: gradient,
        isSelected: value === gradient,
        tooltipText: name || // translators: %s: gradient code e.g: "linear-gradient(90deg, rgba(98,16,153,1) 0%, rgba(172,110,22,1) 100%);".
        (0, _i18n.sprintf)((0, _i18n.__)('Gradient code: %s'), gradient),
        style: {
          color: 'rgba( 0,0,0,0 )',
          background: gradient
        },
        onClick: value === gradient ? clearGradient : function () {
          return onChange(gradient);
        },
        "aria-label": name ? // translators: %s: The name of the gradient e.g: "Angular red to blue".
        (0, _i18n.sprintf)((0, _i18n.__)('Gradient: %s'), name) : // translators: %s: gradient code e.g: "linear-gradient(90deg, rgba(98,16,153,1) 0%, rgba(172,110,22,1) 100%);".
        (0, _i18n.sprintf)((0, _i18n.__)('Gradient code: %s'), gradient)
      });
    });
  }, [gradients, value, onChange, clearGradient]);
  return (0, _element.createElement)(_circularOptionPicker.default, {
    className: className,
    options: gradientOptions,
    actions: clearable && (0, _element.createElement)(_circularOptionPicker.default.ButtonAction, {
      onClick: clearGradient
    }, (0, _i18n.__)('Clear'))
  }, !disableCustomGradients && (0, _element.createElement)(_customGradientPicker.default, {
    value: value,
    onChange: onChange
  }));
}
//# sourceMappingURL=index.js.map