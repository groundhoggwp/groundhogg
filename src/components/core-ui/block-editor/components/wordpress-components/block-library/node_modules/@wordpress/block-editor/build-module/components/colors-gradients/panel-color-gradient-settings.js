import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

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

import { PanelBody, ColorIndicator } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import ColorGradientControl from './control';
import { getColorObjectByColorValue } from '../colors';
import { __experimentalGetGradientObjectByGradientValue } from '../gradients';
import useEditorFeature from '../use-editor-feature'; // translators: first %s: The type of color or gradient (e.g. background, overlay...), second %s: the color name or value (e.g. red or #ff0000)

var colorIndicatorAriaLabel = __('(%s: color %s)'); // translators: first %s: The type of color or gradient (e.g. background, overlay...), second %s: the color name or value (e.g. red or #ff0000)


var gradientIndicatorAriaLabel = __('(%s: gradient %s)');

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
      var colorObject = getColorObjectByColorValue(availableColors || colors, colorValue);
      ariaLabel = sprintf(colorIndicatorAriaLabel, label.toLowerCase(), colorObject && colorObject.name || colorValue);
    } else {
      var gradientObject = __experimentalGetGradientObjectByGradientValue(availableGradients || gradients, colorValue);

      ariaLabel = sprintf(gradientIndicatorAriaLabel, label.toLowerCase(), gradientObject && gradientObject.name || gradientValue);
    }

    return createElement(ColorIndicator, {
      key: index,
      colorValue: colorValue || gradientValue,
      "aria-label": ariaLabel
    });
  });
};

export var PanelColorGradientSettingsInner = function PanelColorGradientSettingsInner(_ref3) {
  var className = _ref3.className,
      colors = _ref3.colors,
      gradients = _ref3.gradients,
      disableCustomColors = _ref3.disableCustomColors,
      disableCustomGradients = _ref3.disableCustomGradients,
      children = _ref3.children,
      settings = _ref3.settings,
      title = _ref3.title,
      props = _objectWithoutProperties(_ref3, ["className", "colors", "gradients", "disableCustomColors", "disableCustomGradients", "children", "settings", "title"]);

  if (isEmpty(colors) && isEmpty(gradients) && disableCustomColors && disableCustomGradients && every(settings, function (setting) {
    return isEmpty(setting.colors) && isEmpty(setting.gradients) && (setting.disableCustomColors === undefined || setting.disableCustomColors) && (setting.disableCustomGradients === undefined || setting.disableCustomGradients);
  })) {
    return null;
  }

  var titleElement = createElement("span", {
    className: "block-editor-panel-color-gradient-settings__panel-title"
  }, title, createElement(Indicators, {
    colors: colors,
    gradients: gradients,
    settings: settings
  }));
  return createElement(PanelBody, _extends({
    className: classnames('block-editor-panel-color-gradient-settings', className),
    title: titleElement
  }, props), settings.map(function (setting, index) {
    return createElement(ColorGradientControl, _extends({
      key: index
    }, _objectSpread({
      colors: colors,
      gradients: gradients,
      disableCustomColors: disableCustomColors,
      disableCustomGradients: disableCustomGradients
    }, setting)));
  }), children);
};

var PanelColorGradientSettingsSelect = function PanelColorGradientSettingsSelect(props) {
  var colorGradientSettings = {};
  colorGradientSettings.colors = useEditorFeature('color.palette');
  colorGradientSettings.gradients = useEditorFeature('color.gradients');
  colorGradientSettings.disableCustomColors = !useEditorFeature('color.custom');
  colorGradientSettings.disableCustomGradients = !useEditorFeature('color.customGradient');
  return createElement(PanelColorGradientSettingsInner, _objectSpread(_objectSpread({}, colorGradientSettings), props));
};

var PanelColorGradientSettings = function PanelColorGradientSettings(props) {
  if (every(colorsAndGradientKeys, function (key) {
    return props.hasOwnProperty(key);
  })) {
    return createElement(PanelColorGradientSettingsInner, props);
  }

  return createElement(PanelColorGradientSettingsSelect, props);
};

export default PanelColorGradientSettings;
//# sourceMappingURL=panel-color-gradient-settings.js.map