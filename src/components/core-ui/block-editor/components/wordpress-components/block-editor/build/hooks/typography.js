"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.TypographyPanel = TypographyPanel;
exports.TYPOGRAPHY_SUPPORT_KEYS = void 0;

var _element = require("@wordpress/element");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _inspectorControls = _interopRequireDefault(require("../components/inspector-controls"));

var _lineHeight = require("./line-height");

var _fontSize = require("./font-size");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var TYPOGRAPHY_SUPPORT_KEYS = [_lineHeight.LINE_HEIGHT_SUPPORT_KEY, _fontSize.FONT_SIZE_SUPPORT_KEY];
exports.TYPOGRAPHY_SUPPORT_KEYS = TYPOGRAPHY_SUPPORT_KEYS;

function TypographyPanel(props) {
  var isDisabled = useIsTypographyDisabled(props);
  var isSupported = hasTypographySupport(props.name);
  if (isDisabled || !isSupported) return null;
  return (0, _element.createElement)(_inspectorControls.default, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Typography')
  }, (0, _element.createElement)(_fontSize.FontSizeEdit, props), (0, _element.createElement)(_lineHeight.LineHeightEdit, props)));
}

var hasTypographySupport = function hasTypographySupport(blockName) {
  return _element.Platform.OS === 'web' && TYPOGRAPHY_SUPPORT_KEYS.some(function (key) {
    return (0, _blocks.hasBlockSupport)(blockName, key);
  });
};

function useIsTypographyDisabled() {
  var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var configs = [(0, _fontSize.useIsFontSizeDisabled)(props), (0, _lineHeight.useIsLineHeightDisabled)(props)];
  return configs.filter(Boolean).length === configs.length;
}
//# sourceMappingURL=typography.js.map