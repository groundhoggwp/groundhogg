"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ShortcodeEdit = ShortcodeEdit;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _compose = require("@wordpress/compose");

var _style = _interopRequireDefault(require("./style.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ShortcodeEdit(props) {
  var attributes = props.attributes,
      setAttributes = props.setAttributes,
      onFocus = props.onFocus,
      onBlur = props.onBlur,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var titleStyle = getStylesFromColorScheme(_style.default.blockTitle, _style.default.blockTitleDark);
  var shortcodeStyle = getStylesFromColorScheme(_style.default.blockShortcode, _style.default.blockShortcodeDark);
  var placeholderStyle = getStylesFromColorScheme(_style.default.placeholder, _style.default.placeholderDark);
  return (0, _element.createElement)(_reactNative.View, null, (0, _element.createElement)(_reactNative.Text, {
    style: titleStyle
  }, (0, _i18n.__)('Shortcode')), (0, _element.createElement)(_blockEditor.PlainText, {
    value: attributes.text,
    style: shortcodeStyle,
    multiline: true,
    underlineColorAndroid: "transparent",
    onChange: function onChange(text) {
      return setAttributes({
        text: text
      });
    },
    placeholder: (0, _i18n.__)('Add a shortcode…'),
    "aria-label": (0, _i18n.__)('Shortcode'),
    isSelected: props.isSelected,
    onFocus: onFocus,
    onBlur: onBlur,
    autoCorrect: false,
    autoComplete: "off",
    placeholderTextColor: placeholderStyle.color
  }));
}

var _default = (0, _compose.withPreferredColorScheme)(ShortcodeEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map