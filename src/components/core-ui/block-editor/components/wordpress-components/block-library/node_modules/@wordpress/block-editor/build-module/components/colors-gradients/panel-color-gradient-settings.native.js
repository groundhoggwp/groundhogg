import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { useNavigation } from '@react-navigation/native';
/**
 * WordPress dependencies
 */

import { ColorControl, PanelBody } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { blockSettingsScreens } from '../block-settings';
export default function PanelColorGradientSettings(_ref) {
  var settings = _ref.settings,
      title = _ref.title;
  var navigation = useNavigation();
  return createElement(PanelBody, {
    title: title
  }, settings.map(function (_ref2) {
    var onColorChange = _ref2.onColorChange,
        colorValue = _ref2.colorValue,
        onGradientChange = _ref2.onGradientChange,
        gradientValue = _ref2.gradientValue,
        label = _ref2.label;
    return createElement(ColorControl, {
      onPress: function onPress() {
        navigation.navigate(blockSettingsScreens.color, {
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