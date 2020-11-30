"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
var SeparatorSettings = function SeparatorSettings(_ref) {
  var color = _ref.color,
      setColor = _ref.setColor;
  return (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_blockEditor.PanelColorSettings, {
    title: (0, _i18n.__)('Color settings'),
    colorSettings: [{
      value: color.color,
      onChange: setColor,
      label: (0, _i18n.__)('Color')
    }]
  }));
};

var _default = SeparatorSettings;
exports.default = _default;
//# sourceMappingURL=separator-settings.js.map