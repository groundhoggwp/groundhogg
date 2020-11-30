import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
import { every, isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { useState } from '@wordpress/element';
import { BaseControl, Button, ButtonGroup, ColorIndicator, ColorPalette, __experimentalGradientPicker as GradientPicker } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import { getColorObjectByColorValue } from '../colors';
import { __experimentalGetGradientObjectByGradientValue } from '../gradients';
import useEditorFeature from '../use-editor-feature'; // translators: first %s: the color name or value (e.g. red or #ff0000)

var colorIndicatorAriaLabel = __('(Color: %s)'); // translators: first %s: the gradient name or value (e.g. red to green or linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)


var gradientIndicatorAriaLabel = __('(Gradient: %s)');

var colorsAndGradientKeys = ['colors', 'disableCustomColors', 'gradients', 'disableCustomGradients'];

function VisualLabel(_ref) {
  var colors = _ref.colors,
      gradients = _ref.gradients,
      label = _ref.label,
      currentTab = _ref.currentTab,
      colorValue = _ref.colorValue,
      gradientValue = _ref.gradientValue;
  var value, ariaLabel;

  if (currentTab === 'color') {
    if (colorValue) {
      value = colorValue;
      var colorObject = getColorObjectByColorValue(colors, value);
      var colorName = colorObject && colorObject.name;
      ariaLabel = sprintf(colorIndicatorAriaLabel, colorName || value);
    }
  } else if (currentTab === 'gradient' && gradientValue) {
    value = gradientValue;

    var gradientObject = __experimentalGetGradientObjectByGradientValue(gradients, value);

    var gradientName = gradientObject && gradientObject.name;
    ariaLabel = sprintf(gradientIndicatorAriaLabel, gradientName || value);
  }

  return createElement(Fragment, null, label, !!value && createElement(ColorIndicator, {
    colorValue: value,
    "aria-label": ariaLabel
  }));
}

function ColorGradientControlInner(_ref2) {
  var colors = _ref2.colors,
      gradients = _ref2.gradients,
      disableCustomColors = _ref2.disableCustomColors,
      disableCustomGradients = _ref2.disableCustomGradients,
      className = _ref2.className,
      label = _ref2.label,
      onColorChange = _ref2.onColorChange,
      onGradientChange = _ref2.onGradientChange,
      colorValue = _ref2.colorValue,
      gradientValue = _ref2.gradientValue;
  var canChooseAColor = onColorChange && (!isEmpty(colors) || !disableCustomColors);
  var canChooseAGradient = onGradientChange && (!isEmpty(gradients) || !disableCustomGradients);

  var _useState = useState(gradientValue ? 'gradient' : !!canChooseAColor && 'color'),
      _useState2 = _slicedToArray(_useState, 2),
      currentTab = _useState2[0],
      setCurrentTab = _useState2[1];

  if (!canChooseAColor && !canChooseAGradient) {
    return null;
  }

  return createElement(BaseControl, {
    className: classnames('block-editor-color-gradient-control', className)
  }, createElement("fieldset", null, createElement("legend", null, createElement("div", {
    className: "block-editor-color-gradient-control__color-indicator"
  }, createElement(BaseControl.VisualLabel, null, createElement(VisualLabel, {
    currentTab: currentTab,
    label: label,
    colorValue: colorValue,
    gradientValue: gradientValue
  })))), canChooseAColor && canChooseAGradient && createElement(ButtonGroup, {
    className: "block-editor-color-gradient-control__button-tabs"
  }, createElement(Button, {
    isSmall: true,
    isPressed: currentTab === 'color',
    onClick: function onClick() {
      return setCurrentTab('color');
    }
  }, __('Solid')), createElement(Button, {
    isSmall: true,
    isPressed: currentTab === 'gradient',
    onClick: function onClick() {
      return setCurrentTab('gradient');
    }
  }, __('Gradient'))), (currentTab === 'color' || !canChooseAGradient) && createElement(ColorPalette, _extends({
    value: colorValue,
    onChange: canChooseAGradient ? function (newColor) {
      onColorChange(newColor);
      onGradientChange();
    } : onColorChange
  }, {
    colors: colors,
    disableCustomColors: disableCustomColors
  })), (currentTab === 'gradient' || !canChooseAColor) && createElement(GradientPicker, _extends({
    value: gradientValue,
    onChange: canChooseAColor ? function (newGradient) {
      onGradientChange(newGradient);
      onColorChange();
    } : onGradientChange
  }, {
    gradients: gradients,
    disableCustomGradients: disableCustomGradients
  }))));
}

function ColorGradientControlSelect(props) {
  var colorGradientSettings = {};
  colorGradientSettings.colors = useEditorFeature('color.palette');
  colorGradientSettings.gradients = useEditorFeature('color.gradients');
  colorGradientSettings.disableCustomColors = !useEditorFeature('color.custom');
  colorGradientSettings.disableCustomGradients = !useEditorFeature('color.customGradient');
  return createElement(ColorGradientControlInner, _objectSpread(_objectSpread({}, colorGradientSettings), props));
}

function ColorGradientControl(props) {
  if (every(colorsAndGradientKeys, function (key) {
    return props.hasOwnProperty(key);
  })) {
    return createElement(ColorGradientControlInner, props);
  }

  return createElement(ColorGradientControlSelect, props);
}

export default ColorGradientControl;
//# sourceMappingURL=control.js.map