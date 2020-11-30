"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _blockEditor = require("@wordpress/block-editor");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _styles = _interopRequireDefault(require("./styles.scss"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockCaption = function BlockCaption(_ref) {
  var accessible = _ref.accessible,
      accessibilityLabelCreator = _ref.accessibilityLabelCreator,
      onBlur = _ref.onBlur,
      onChange = _ref.onChange,
      onFocus = _ref.onFocus,
      isSelected = _ref.isSelected,
      shouldDisplay = _ref.shouldDisplay,
      text = _ref.text,
      insertBlocksAfter = _ref.insertBlocksAfter;
  return (0, _element.createElement)(_reactNative.View, {
    style: [_styles.default.container, shouldDisplay && _styles.default.padding]
  }, (0, _element.createElement)(_blockEditor.Caption, {
    accessibilityLabelCreator: accessibilityLabelCreator,
    accessible: accessible,
    isSelected: isSelected,
    onBlur: onBlur,
    onChange: onChange,
    onFocus: onFocus,
    shouldDisplay: shouldDisplay,
    value: text,
    insertBlocksAfter: insertBlocksAfter
  }));
};

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockAttributes = _select.getBlockAttributes,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var _ref3 = getBlockAttributes(clientId) || {},
      caption = _ref3.caption;

  var isBlockSelected = getSelectedBlockClientId() === clientId; // We'll render the caption so that the soft keyboard is not forced to close on Android
  // but still hide it by setting its display style to none. See wordpress-mobile/gutenberg-mobile#1221

  var shouldDisplay = !_blockEditor.RichText.isEmpty(caption) > 0 || isBlockSelected;
  return {
    shouldDisplay: shouldDisplay,
    text: caption
  };
}), (0, _data.withDispatch)(function (dispatch, _ref4) {
  var clientId = _ref4.clientId;

  var _dispatch = dispatch('core/block-editor'),
      updateBlockAttributes = _dispatch.updateBlockAttributes;

  return {
    onChange: function onChange(caption) {
      updateBlockAttributes(clientId, {
        caption: caption
      });
    }
  };
})])(BlockCaption);

exports.default = _default;
//# sourceMappingURL=index.native.js.map