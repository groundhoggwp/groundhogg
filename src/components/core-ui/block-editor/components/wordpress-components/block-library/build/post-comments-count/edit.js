"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostCommentsCountEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _apiFetch = _interopRequireDefault(require("@wordpress/api-fetch"));

var _url = require("@wordpress/url");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PostCommentsCountEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;
  var postId = context.postId;

  var _useState = (0, _element.useState)(),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      commentsCount = _useState2[0],
      setCommentsCount = _useState2[1];

  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  (0, _element.useEffect)(function () {
    if (!postId) {
      return;
    }

    var currentPostId = postId;
    (0, _apiFetch.default)({
      path: (0, _url.addQueryArgs)('/wp/v2/comments', {
        post: postId
      }),
      parse: false
    }).then(function (res) {
      // Stale requests will have the `currentPostId` of an older closure.
      if (currentPostId === postId) {
        setCommentsCount(res.headers.get('X-WP-Total'));
      }
    });
  }, [postId]);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)("div", blockWrapperProps, postId && commentsCount !== undefined ? commentsCount : (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post Comments Count block: post not found.'))));
}
//# sourceMappingURL=edit.js.map