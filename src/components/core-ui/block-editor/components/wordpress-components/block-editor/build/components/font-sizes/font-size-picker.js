"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _components = require("@wordpress/components");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function FontSizePicker(props) {
  var fontSizes = (0, _useEditorFeature.default)('typography.fontSizes');
  var disableCustomFontSizes = !(0, _useEditorFeature.default)('typography.customFontSize');
  return (0, _element.createElement)(_components.FontSizePicker, (0, _extends2.default)({}, props, {
    fontSizes: fontSizes,
    disableCustomFontSizes: disableCustomFontSizes
  }));
}

var _default = FontSizePicker;
exports.default = _default;
//# sourceMappingURL=font-size-picker.js.map