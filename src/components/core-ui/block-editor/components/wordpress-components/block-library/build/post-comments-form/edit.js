"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostCommentsFormEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _coreData = require("@wordpress/core-data");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PostCommentsFormEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;
  var postId = context.postId,
      postType = context.postType;

  var _useEntityProp = (0, _coreData.useEntityProp)('postType', postType, 'comment_status', postId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      commentStatus = _useEntityProp2[0];

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
  })), (0, _element.createElement)("div", blockWrapperProps, !commentStatus && (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post Comments Form block: comments are not enabled for this post type.')), 'open' !== commentStatus && (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post Comments Form block: comments to this post are not allowed.')), 'open' === commentStatus && (0, _i18n.__)('Post Comments Form')));
}
//# sourceMappingURL=edit.js.map