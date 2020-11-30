"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.CodeEdit = CodeEdit;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _theme = _interopRequireDefault(require("./theme.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Block code style
 */
// Note: styling is applied directly to the (nested) PlainText component. Web-side components
// apply it to the container 'div' but we don't have a proper proposal for cascading styling yet.
function CodeEdit(props) {
  var attributes = props.attributes,
      setAttributes = props.setAttributes,
      onFocus = props.onFocus,
      onBlur = props.onBlur,
      getStylesFromColorScheme = props.getStylesFromColorScheme;
  var codeStyle = getStylesFromColorScheme(_theme.default.blockCode, _theme.default.blockCodeDark);
  var placeholderStyle = getStylesFromColorScheme(_theme.default.placeholder, _theme.default.placeholderDark);
  return (0, _element.createElement)(_reactNative.View, null, (0, _element.createElement)(_blockEditor.PlainText, {
    value: attributes.content,
    style: codeStyle,
    multiline: true,
    underlineColorAndroid: "transparent",
    onChange: function onChange(content) {
      return setAttributes({
        content: content
      });
    },
    placeholder: (0, _i18n.__)('Write code…'),
    "aria-label": (0, _i18n.__)('Code'),
    isSelected: props.isSelected,
    onFocus: onFocus,
    onBlur: onBlur,
    placeholderTextColor: placeholderStyle.color
  }));
}

var _default = (0, _compose.withPreferredColorScheme)(CodeEdit);

exports.default = _default;
//# sourceMappingURL=edit.native.js.map