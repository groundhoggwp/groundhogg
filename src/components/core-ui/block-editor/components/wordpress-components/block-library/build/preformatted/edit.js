"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PreformattedEdit;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function PreformattedEdit(_ref) {
  var attributes = _ref.attributes,
      mergeBlocks = _ref.mergeBlocks,
      setAttributes = _ref.setAttributes;
  var content = attributes.content;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();
  return (0, _element.createElement)(_blockEditor.RichText, (0, _extends2.default)({
    tagName: "pre",
    identifier: "content",
    preserveWhiteSpace: true,
    value: content,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    placeholder: (0, _i18n.__)('Write preformatted text…'),
    onMerge: mergeBlocks
  }, blockWrapperProps));
}
//# sourceMappingURL=edit.js.map