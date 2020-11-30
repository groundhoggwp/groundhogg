import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { AlignmentToolbar, BlockControls, Warning, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';

function PostCommentsDisplay(_ref) {
  var postId = _ref.postId;
  return useSelect(function (select) {
    var comments = select('core').getEntityRecords('root', 'comment', {
      post: postId
    }); // TODO: "No Comments" placeholder should be editable.

    return comments && comments.length ? comments.map(function (comment) {
      return createElement(RawHTML, {
        className: "wp-block-post-comments__comment",
        key: comment.id
      }, comment.content.rendered);
    }) : __('No comments.');
  }, [postId]);
}

export default function PostCommentsEdit(_ref2) {
  var attributes = _ref2.attributes,
      setAttributes = _ref2.setAttributes,
      context = _ref2.context;
  var postType = context.postType,
      postId = context.postId;
  var textAlign = attributes.textAlign;
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!postType || !postId) {
    return createElement(Warning, null, __('Post comments block: no post found.'));
  }

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement("div", blockWrapperProps, createElement(PostCommentsDisplay, {
    postId: postId
  })));
}
//# sourceMappingURL=edit.js.map