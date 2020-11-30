"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostCommentsEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PostCommentsDisplay(_ref) {
  var postId = _ref.postId;
  return (0, _data.useSelect)(function (select) {
    var comments = select('core').getEntityRecords('root', 'comment', {
      post: postId
    }); // TODO: "No Comments" placeholder should be editable.

    return comments && comments.length ? comments.map(function (comment) {
      return (0, _element.createElement)(_element.RawHTML, {
        className: "wp-block-post-comments__comment",
        key: comment.id
      }, comment.content.rendered);
    }) : (0, _i18n.__)('No comments.');
  }, [postId]);
}

function PostCommentsEdit(_ref2) {
  var attributes = _ref2.attributes,
      setAttributes = _ref2.setAttributes,
      context = _ref2.context;
  var postType = context.postType,
      postId = context.postId;
  var textAlign = attributes.textAlign;
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!postType || !postId) {
    return (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post comments block: no post found.'));
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(PostCommentsDisplay, {
    postId: postId
  })));
}
//# sourceMappingURL=edit.js.map