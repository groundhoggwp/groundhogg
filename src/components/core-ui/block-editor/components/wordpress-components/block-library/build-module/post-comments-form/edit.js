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
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
export default function PostCommentsFormEdit(_ref) {
  var attributes = _ref.attributes,
      context = _ref.context,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;
  var postId = context.postId,
      postType = context.postType;

  var _useEntityProp = useEntityProp('postType', postType, 'comment_status', postId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      commentStatus = _useEntityProp2[0];

  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement("div", blockWrapperProps, !commentStatus && createElement(Warning, null, __('Post Comments Form block: comments are not enabled for this post type.')), 'open' !== commentStatus && createElement(Warning, null, __('Post Comments Form block: comments to this post are not allowed.')), 'open' === commentStatus && __('Post Comments Form')));
}
//# sourceMappingURL=edit.js.map