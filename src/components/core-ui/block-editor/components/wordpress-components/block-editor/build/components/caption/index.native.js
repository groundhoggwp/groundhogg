"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _blocks = require("@wordpress/blocks");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var Caption = function Caption(_ref) {
  var accessibilityLabelCreator = _ref.accessibilityLabelCreator,
      accessible = _ref.accessible,
      inlineToolbar = _ref.inlineToolbar,
      isSelected = _ref.isSelected,
      onBlur = _ref.onBlur,
      onChange = _ref.onChange,
      onFocus = _ref.onFocus,
      _ref$placeholder = _ref.placeholder,
      placeholder = _ref$placeholder === void 0 ? (0, _i18n.__)('Write caption…') : _ref$placeholder,
      placeholderTextColor = _ref.placeholderTextColor,
      _ref$shouldDisplay = _ref.shouldDisplay,
      shouldDisplay = _ref$shouldDisplay === void 0 ? true : _ref$shouldDisplay,
      style = _ref.style,
      value = _ref.value,
      _ref$insertBlocksAfte = _ref.insertBlocksAfter,
      insertBlocksAfter = _ref$insertBlocksAfte === void 0 ? function () {} : _ref$insertBlocksAfte;
  return (0, _element.createElement)(_reactNative.View, {
    accessibilityLabel: accessibilityLabelCreator ? accessibilityLabelCreator(value) : undefined,
    accessibilityRole: "button",
    accessible: accessible,
    style: {
      flex: 1,
      display: shouldDisplay ? 'flex' : 'none'
    }
  }, (0, _element.createElement)(_blockEditor.RichText, {
    __unstableMobileNoFocusOnMount: true,
    fontSize: style && style.fontSize ? style.fontSize : 14,
    inlineToolbar: inlineToolbar,
    isSelected: isSelected,
    onBlur: onBlur,
    onChange: onChange,
    placeholder: placeholder,
    placeholderTextColor: placeholderTextColor,
    rootTagsToEliminate: ['p'],
    style: style,
    tagName: "p",
    textAlign: "center",
    underlineColorAndroid: "transparent",
    unstableOnFocus: onFocus,
    value: value,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
    },
    deleteEnter: true
  }));
};

var _default = Caption;
exports.default = _default;
//# sourceMappingURL=index.native.js.map