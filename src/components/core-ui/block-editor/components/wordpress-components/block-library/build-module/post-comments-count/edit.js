import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { AlignmentToolbar, BlockControls, Warning, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
export default function PostCommentsCountEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;
  var postId = context.postId;

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      commentsCount = _useState2[0],
      setCommentsCount = _useState2[1];

  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  useEffect(function () {
    if (!postId) {
      return;
    }

    var currentPostId = postId;
    apiFetch({
      path: addQueryArgs('/wp/v2/comments', {
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
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement("div", blockWrapperProps, postId && commentsCount !== undefined ? commentsCount : createElement(Warning, null, __('Post Comments Count block: post not found.'))));
}
//# sourceMappingURL=edit.js.map