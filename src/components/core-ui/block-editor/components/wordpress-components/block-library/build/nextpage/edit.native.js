"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.NextPageEdit = NextPageEdit;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _reactNativeHr = _interopRequireDefault(require("react-native-hr"));

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _editor = _interopRequireDefault(require("./editor.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function NextPageEdit(_ref) {
  var attributes = _ref.attributes,
      isSelected = _ref.isSelected,
      onFocus = _ref.onFocus,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var _attributes$customTex = attributes.customText,
      customText = _attributes$customTex === void 0 ? (0, _i18n.__)('Page break') : _attributes$customTex;
  var accessibilityTitle = attributes.customText || '';
  var accessibilityState = isSelected ? ['selected'] : [];
  var textStyle = getStylesFromColorScheme(_editor.default.nextpageText, _editor.default.nextpageTextDark);
  var lineStyle = getStylesFromColorScheme(_editor.default.nextpageLine, _editor.default.nextpageLineDark);
  return (0, _element.createElement)(_reactNative.View, {
    accessible: true,
    accessibilityLabel: (0, _i18n.sprintf)(
    /* translators: accessibility text. %s: Page break text. */
    (0, _i18n.__)('Page break block. %s'), accessibilityTitle),
    accessibilityStates: accessibilityState,
    onAccessibilityTap: onFocus
  }, (0, _element.createElement)(_reactNativeHr.default, {
    text: customText,
    marginLeft: 0,
    marginRight: 0,
    textStyle: textStyle,
    lineStyle: lineStyle
  }));
}

var _default = (0, _compose.withPreferredColorScheme)(NextPageEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map