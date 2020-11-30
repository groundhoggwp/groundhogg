"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _colors = require("../colors");

var _gradients = require("../gradients");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

// translators: first %s: the color name or value (e.g. red or #ff0000)
var colorIndicatorAriaLabel = (0, _i18n.__)('(Color: %s)'); // translators: first %s: the gradient name or value (e.g. red to green or linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)

var gradientIndicatorAriaLabel = (0, _i18n.__)('(Gradient: %s)');
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
      var colorObject = (0, _colors.getColorObjectByColorValue)(colors, value);
      var colorName = colorObject && colorObject.name;
      ariaLabel = (0, _i18n.sprintf)(colorIndicatorAriaLabel, colorName || value);
    }
  } else if (currentTab === 'gradient' && gradientValue) {
    value = gradientValue;
    var gradientObject = (0, _gradients.__experimentalGetGradientObjectByGradientValue)(gradients, value);
    var gradientName = gradientObject && gradientObject.name;
    ariaLabel = (0, _i18n.sprintf)(gradientIndicatorAriaLabel, gradientName || value);
  }

  return (0, _element.createElement)(_element.Fragment, null, label, !!value && (0, _element.createElement)(_components.ColorIndicator, {
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
  var canChooseAColor = onColorChange && (!(0, _lodash.isEmpty)(colors) || !disableCustomColors);
  var canChooseAGradient = onGradientChange && (!(0, _lodash.isEmpty)(gradients) || !disableCustomGradients);

  var _useState = (0, _element.useState)(gradientValue ? 'gradient' : !!canChooseAColor && 'color'),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      currentTab = _useState2[0],
      setCurrentTab = _useState2[1];

  if (!canChooseAColor && !canChooseAGradient) {
    return null;
  }

  return (0, _element.createElement)(_components.BaseControl, {
    className: (0, _classnames.default)('block-editor-color-gradient-control', className)
  }, (0, _element.createElement)("fieldset", null, (0, _element.createElement)("legend", null, (0, _element.createElement)("div", {
    className: "block-editor-color-gradient-control__color-indicator"
  }, (0, _element.createElement)(_components.BaseControl.VisualLabel, null, (0, _element.createElement)(VisualLabel, {
    currentTab: currentTab,
    label: label,
    colorValue: colorValue,
    gradientValue: gradientValue
  })))), canChooseAColor && canChooseAGradient && (0, _element.createElement)(_components.ButtonGroup, {
    className: "block-editor-color-gradient-control__button-tabs"
  }, (0, _element.createElement)(_components.Button, {
    isSmall: true,
    isPressed: currentTab === 'color',
    onClick: function onClick() {
      return setCurrentTab('color');
    }
  }, (0, _i18n.__)('Solid')), (0, _element.createElement)(_components.Button, {
    isSmall: true,
    isPressed: currentTab === 'gradient',
    onClick: function onClick() {
      return setCurrentTab('gradient');
    }
  }, (0, _i18n.__)('Gradient'))), (currentTab === 'color' || !canChooseAGradient) && (0, _element.createElement)(_components.ColorPalette, (0, _extends2.default)({
    value: colorValue,
    onChange: canChooseAGradient ? function (newColor) {
      onColorChange(newColor);
      onGradientChange();
    } : onColorChange
  }, {
    colors: colors,
    disableCustomColors: disableCustomColors
  })), (currentTab === 'gradient' || !canChooseAColor) && (0, _element.createElement)(_components.__experimentalGradientPicker, (0, _extends2.default)({
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
  colorGradientSettings.colors = (0, _useEditorFeature.default)('color.palette');
  colorGradientSettings.gradients = (0, _useEditorFeature.default)('color.gradients');
  colorGradientSettings.disableCustomColors = !(0, _useEditorFeature.default)('color.custom');
  colorGradientSettings.disableCustomGradients = !(0, _useEditorFeature.default)('color.customGradient');
  return (0, _element.createElement)(ColorGradientControlInner, _objectSpread(_objectSpread({}, colorGradientSettings), props));
}

function ColorGradientControl(props) {
  if ((0, _lodash.every)(colorsAndGradientKeys, function (key) {
    return props.hasOwnProperty(key);
  })) {
    return (0, _element.createElement)(ColorGradientControlInner, props);
  }

  return (0, _element.createElement)(ColorGradientControlSelect, props);
}

var _default = ColorGradientControl;
exports.default = _default;
//# sourceMappingURL=control.js.map