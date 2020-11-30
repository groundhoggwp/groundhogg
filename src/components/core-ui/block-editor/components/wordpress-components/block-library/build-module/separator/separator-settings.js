import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, PanelColorSettings } from '@wordpress/block-editor';

var SeparatorSettings = function SeparatorSettings(_ref) {
  var color = _ref.color,
      setColor = _ref.setColor;
  return createElement(InspectorControls, null, createElement(PanelColorSettings, {
    title: __('Color settings'),
    colorSettings: [{
      value: color.color,
      onChange: setColor,
      label: __('Color')
    }]
  }));
};

export default SeparatorSettings;
//# sourceMappingURL=separator-settings.js.map