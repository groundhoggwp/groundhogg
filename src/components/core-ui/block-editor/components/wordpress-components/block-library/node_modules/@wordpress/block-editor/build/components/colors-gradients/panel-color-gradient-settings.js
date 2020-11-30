"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.PanelColorGradientSettingsInner = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _control = _interopRequireDefault(require("./control"));

var _colors = require("../colors");

var _gradients = require("../gradients");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

// translators: first %s: The type of color or gradient (e.g. background, overlay...), second %s: the color name or value (e.g. red or #ff0000)
var colorIndicatorAriaLabel = (0, _i18n.__)('(%s: color %s)'); // translators: first %s: The type of color or gradient (e.g. background, overlay...), second %s: the color name or value (e.g. red or #ff0000)

var gradientIndicatorAriaLabel = (0, _i18n.__)('(%s: gradient %s)');
var colorsAndGradientKeys = ['colors', 'disableCustomColors', 'gradients', 'disableCustomGradients'];

var Indicators = function Indicators(_ref) {
  var colors = _ref.colors,
      gradients = _ref.gradients,
      settings = _ref.settings;
  return settings.map(function (_ref2, index) {
    var colorValue = _ref2.colorValue,
        gradientValue = _ref2.gradientValue,
        label = _ref2.label,
        availableColors = _ref2.colors,
        availableGradients = _ref2.gradients;

    if (!colorValue && !gradientValue) {
      return null;
    }

    var ariaLabel;

    if (colorValue) {
      var colorObject = (0, _colors.getColorObjectByColorValue)(availableColors || colors, colorValue);
      ariaLabel = (0, _i18n.sprintf)(colorIndicatorAriaLabel, label.toLowerCase(), colorObject && colorObject.name || colorValue);
    } else {
      var gradientObject = (0, _gradients.__experimentalGetGradientObjectByGradientValue)(availableGradients || gradients, colorValue);
      ariaLabel = (0, _i18n.sprintf)(gradientIndicatorAriaLabel, label.toLowerCase(), gradientObject && gradientObject.name || gradientValue);
    }

    return (0, _element.createElement)(_components.ColorIndicator, {
      key: index,
      colorValue: colorValue || gradientValue,
      "aria-label": ariaLabel
    });
  });
};

var PanelColorGradientSettingsInner = function PanelColorGradientSettingsInner(_ref3) {
  var className = _ref3.className,
      colors = _ref3.colors,
      gradients = _ref3.gradients,
      disableCustomColors = _ref3.disableCustomColors,
      disableCustomGradients = _ref3.disableCustomGradients,
      children = _ref3.children,
      settings = _ref3.settings,
      title = _ref3.title,
      props = (0, _objectWithoutProperties2.default)(_ref3, ["className", "colors", "gradients", "disableCustomColors", "disableCustomGradients", "children", "settings", "title"]);

  if ((0, _lodash.isEmpty)(colors) && (0, _lodash.isEmpty)(gradients) && disableCustomColors && disableCustomGradients && (0, _lodash.every)(settings, function (setting) {
    return (0, _lodash.isEmpty)(setting.colors) && (0, _lodash.isEmpty)(setting.gradients) && (setting.disableCustomColors === undefined || setting.disableCustomColors) && (setting.disableCustomGradients === undefined || setting.disableCustomGradients);
  })) {
    return null;
  }

  var titleElement = (0, _element.createElement)("span", {
    className: "block-editor-panel-color-gradient-settings__panel-title"
  }, title, (0, _element.createElement)(Indicators, {
    colors: colors,
    gradients: gradients,
    settings: settings
  }));
  return (0, _element.createElement)(_components.PanelBody, (0, _extends2.default)({
    className: (0, _classnames.default)('block-editor-panel-color-gradient-settings', className),
    title: titleElement
  }, props), settings.map(function (setting, index) {
    return (0, _element.createElement)(_control.default, (0, _extends2.default)({
      key: index
    }, _objectSpread({
      colors: colors,
      gradients: gradients,
      disableCustomColors: disableCustomColors,
      disableCustomGradients: disableCustomGradients
    }, setting)));
  }), children);
};

exports.PanelColorGradientSettingsInner = PanelColorGradientSettingsInner;

var PanelColorGradientSettingsSelect = function PanelColorGradientSettingsSelect(props) {
  var colorGradientSettings = {};
  colorGradientSettings.colors = (0, _useEditorFeature.default)('color.palette');
  colorGradientSettings.gradients = (0, _useEditorFeature.default)('color.gradients');
  colorGradientSettings.disableCustomColors = !(0, _useEditorFeature.default)('color.custom');
  colorGradientSettings.disableCustomGradients = !(0, _useEditorFeature.default)('color.customGradient');
  return (0, _element.createElement)(PanelColorGradientSettingsInner, _objectSpread(_objectSpread({}, colorGradientSettings), props));
};

var PanelColorGradientSettings = function PanelColorGradientSettings(props) {
  if ((0, _lodash.every)(colorsAndGradientKeys, function (key) {
    return props.hasOwnProperty(key);
  })) {
    return (0, _element.createElement)(PanelColorGradientSettingsInner, props);
  }

  return (0, _element.createElement)(PanelColorGradientSettingsSelect, props);
};

var _default = PanelColorGradientSettings;
exports.default = _default;
//# sourceMappingURL=panel-color-gradient-settings.js.map