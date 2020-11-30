"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.DefaultBlockAppender = DefaultBlockAppender;
exports.default = void 0;

var _element = require("@wordpress/element");

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _compose = require("@wordpress/compose");

var _htmlEntities = require("@wordpress/html-entities");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _insertionPoint = _interopRequireDefault(require("../block-list/insertion-point"));

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
function DefaultBlockAppender(_ref) {
  var isLocked = _ref.isLocked,
      isVisible = _ref.isVisible,
      onAppend = _ref.onAppend,
      placeholder = _ref.placeholder,
      containerStyle = _ref.containerStyle,
      showSeparator = _ref.showSeparator;

  if (isLocked || !isVisible) {
    return null;
  }

  var value = typeof placeholder === 'string' ? (0, _htmlEntities.decodeEntities)(placeholder) : (0, _i18n.__)('Start writing…');
  return (0, _element.createElement)(_reactNative.TouchableWithoutFeedback, {
    onPress: onAppend
  }, (0, _element.createElement)(_reactNative.View, {
    style: [_style.default.blockHolder, showSeparator && containerStyle],
    pointerEvents: "box-only"
  }, showSeparator ? (0, _element.createElement)(_insertionPoint.default, null) : (0, _element.createElement)(_blockEditor.RichText, {
    placeholder: value,
    onChange: function onChange() {}
  })));
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, ownProps) {
  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockName = _select.getBlockName,
      isBlockValid = _select.isBlockValid,
      getTemplateLock = _select.getTemplateLock;

  var isEmpty = !getBlockCount(ownProps.rootClientId);
  var isLastBlockDefault = getBlockName(ownProps.lastBlockClientId) === (0, _blocks.getDefaultBlockName)();
  var isLastBlockValid = isBlockValid(ownProps.lastBlockClientId);
  return {
    isVisible: isEmpty || !isLastBlockDefault || !isLastBlockValid,
    isLocked: !!getTemplateLock(ownProps.rootClientId)
  };
}), (0, _data.withDispatch)(function (dispatch, ownProps) {
  var _dispatch = dispatch('core/block-editor'),
      insertDefaultBlock = _dispatch.insertDefaultBlock,
      startTyping = _dispatch.startTyping;

  return {
    onAppend: function onAppend() {
      var rootClientId = ownProps.rootClientId;
      insertDefaultBlock(undefined, rootClientId);
      startTyping();
    }
  };
}))(DefaultBlockAppender);

exports.default = _default;
//# sourceMappingURL=index.native.js.map