"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = VerseEdit;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function VerseEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      mergeBlocks = _ref.mergeBlocks;
  var textAlign = attributes.textAlign,
      content = attributes.content;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)(_blockEditor.RichText, (0, _extends2.default)({
    tagName: "pre",
    identifier: "content",
    preserveWhiteSpace: true,
    value: content,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    placeholder: (0, _i18n.__)('Write…'),
    onMerge: mergeBlocks,
    textAlign: textAlign
  }, blockWrapperProps)));
}
//# sourceMappingURL=edit.js.map