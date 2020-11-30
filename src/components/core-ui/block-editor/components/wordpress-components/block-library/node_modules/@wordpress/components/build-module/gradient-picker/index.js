import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import CircularOptionPicker from '../circular-option-picker';
import CustomGradientPicker from '../custom-gradient-picker';
export default function GradientPicker(_ref) {
  var className = _ref.className,
      gradients = _ref.gradients,
      onChange = _ref.onChange,
      value = _ref.value,
      _ref$clearable = _ref.clearable,
      clearable = _ref$clearable === void 0 ? true : _ref$clearable,
      _ref$disableCustomGra = _ref.disableCustomGradients,
      disableCustomGradients = _ref$disableCustomGra === void 0 ? false : _ref$disableCustomGra;
  var clearGradient = useCallback(function () {
    return onChange(undefined);
  }, [onChange]);
  var gradientOptions = useMemo(function () {
    return map(gradients, function (_ref2) {
      var gradient = _ref2.gradient,
          name = _ref2.name;
      return createElement(CircularOptionPicker.Option, {
        key: gradient,
        value: gradient,
        isSelected: value === gradient,
        tooltipText: name || // translators: %s: gradient code e.g: "linear-gradient(90deg, rgba(98,16,153,1) 0%, rgba(172,110,22,1) 100%);".
        sprintf(__('Gradient code: %s'), gradient),
        style: {
          color: 'rgba( 0,0,0,0 )',
          background: gradient
        },
        onClick: value === gradient ? clearGradient : function () {
          return onChange(gradient);
        },
        "aria-label": name ? // translators: %s: The name of the gradient e.g: "Angular red to blue".
        sprintf(__('Gradient: %s'), name) : // translators: %s: gradient code e.g: "linear-gradient(90deg, rgba(98,16,153,1) 0%, rgba(172,110,22,1) 100%);".
        sprintf(__('Gradient code: %s'), gradient)
      });
    });
  }, [gradients, value, onChange, clearGradient]);
  return createElement(CircularOptionPicker, {
    className: className,
    options: gradientOptions,
    actions: clearable && createElement(CircularOptionPicker.ButtonAction, {
      onClick: clearGradient
    }, __('Clear'))
  }, !disableCustomGradients && createElement(CustomGradientPicker, {
    value: value,
    onChange: onChange
  }));
}
//# sourceMappingURL=index.js.map