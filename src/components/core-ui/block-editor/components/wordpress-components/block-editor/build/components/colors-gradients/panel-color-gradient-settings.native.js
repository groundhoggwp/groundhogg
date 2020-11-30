"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PanelColorGradientSettings;

var _element = require("@wordpress/element");

var _native = require("@react-navigation/native");

var _components = require("@wordpress/components");

var _blockSettings = require("../block-settings");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PanelColorGradientSettings(_ref) {
  var settings = _ref.settings,
      title = _ref.title;
  var navigation = (0, _native.useNavigation)();
  return (0, _element.createElement)(_components.PanelBody, {
    title: title
  }, settings.map(function (_ref2) {
    var onColorChange = _ref2.onColorChange,
        colorValue = _ref2.colorValue,
        onGradientChange = _ref2.onGradientChange,
        gradientValue = _ref2.gradientValue,
        label = _ref2.label;
    return (0, _element.createElement)(_components.ColorControl, {
      onPress: function onPress() {
        navigation.navigate(_blockSettings.blockSettingsScreens.color, {
          onColorChange: onColorChange,
          colorValue: gradientValue || colorValue,
          gradientValue: gradientValue,
          onGradientChange: onGradientChange,
          label: label
        });
      },
      key: "color-setting-".concat(label),
      label: label,
      color: gradientValue || colorValue
    });
  }));
}
//# sourceMappingURL=panel-color-gradient-settings.native.js.map