"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostContentInnerBlocks;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _coreData = require("@wordpress/core-data");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function PostContentInnerBlocks(_ref) {
  var postType = _ref.postType,
      postId = _ref.postId;

  var _useEntityBlockEditor = (0, _coreData.useEntityBlockEditor)('postType', postType, {
    id: postId
  }),
      _useEntityBlockEditor2 = (0, _slicedToArray2.default)(_useEntityBlockEditor, 3),
      blocks = _useEntityBlockEditor2[0],
      onInput = _useEntityBlockEditor2[1],
      onChange = _useEntityBlockEditor2[2];

  return (0, _element.createElement)(_blockEditor.InnerBlocks, {
    value: blocks,
    onInput: onInput,
    onChange: onChange
  });
}
//# sourceMappingURL=inner-blocks.js.map