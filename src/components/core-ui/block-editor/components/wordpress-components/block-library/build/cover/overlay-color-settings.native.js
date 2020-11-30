"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function OverlayColorSettings(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var EMPTY_ARRAY = [];
  var colors = (0, _blockEditor.__experimentalUseEditorFeature)('color.palette') || EMPTY_ARRAY;
  var gradients = (0, _blockEditor.__experimentalUseEditorFeature)('color.gradients') || EMPTY_ARRAY;
  var overlayColor = attributes.overlayColor,
      customOverlayColor = attributes.customOverlayColor,
      gradient = attributes.gradient,
      customGradient = attributes.customGradient;
  var gradientValue = customGradient || (0, _blockEditor.getGradientValueBySlug)(gradients, gradient);
  var colorValue = (0, _blockEditor.getColorObjectByAttributeValues)(colors, overlayColor, customOverlayColor).color;

  var setOverlayAttribute = function setOverlayAttribute(attributeName, value) {
    setAttributes((0, _defineProperty2.default)({
      // clear all related attributes (only one should be set)
      overlayColor: undefined,
      customOverlayColor: undefined,
      gradient: undefined,
      customGradient: undefined
    }, attributeName, value));
  };

  var onColorChange = function onColorChange(value) {
    // do nothing for falsy values
    if (!value) {
      return;
    }

    var colorObject = (0, _blockEditor.getColorObjectByColorValue)(colors, value);

    if (colorObject === null || colorObject === void 0 ? void 0 : colorObject.slug) {
      setOverlayAttribute('overlayColor', colorObject.slug);
    } else {
      setOverlayAttribute('customOverlayColor', value);
    }
  };

  var onGradientChange = function onGradientChange(value) {
    // do nothing for falsy values
    if (!value) {
      return;
    }

    var slug = (0, _blockEditor.getGradientSlugByValue)(gradients, value);

    if (slug) {
      setOverlayAttribute('gradient', slug);
    } else {
      setOverlayAttribute('customGradient', value);
    }
  };

  return (0, _element.createElement)(_blockEditor.__experimentalPanelColorGradientSettings, {
    title: (0, _i18n.__)('Overlay'),
    initialOpen: false,
    settings: [{
      label: (0, _i18n.__)('Color'),
      onColorChange: onColorChange,
      colorValue: colorValue,
      gradientValue: gradientValue,
      onGradientChange: onGradientChange
    }]
  });
}

var _default = OverlayColorSettings;
exports.default = _default;
//# sourceMappingURL=overlay-color-settings.native.js.map