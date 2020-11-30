import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder, TextControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { blockDefault } from '@wordpress/icons';
import { InnerBlocks } from '@wordpress/block-editor';
var ALLOWED_BLOCKS = ['core/post-comment-content', 'core/post-comment-author']; // TODO: JSDOC types

export default function Edit(_ref) {
  var className = _ref.className,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var commentId = attributes.commentId;

  var _useState = useState(commentId),
      _useState2 = _slicedToArray(_useState, 2),
      commentIdInput = _useState2[0],
      setCommentIdInput = _useState2[1];

  if (!commentId) {
    return createElement(Placeholder, {
      icon: blockDefault,
      label: __('Post Comment'),
      instructions: __('Input post comment ID')
    }, createElement(TextControl, {
      value: commentId,
      onChange: function onChange(val) {
        return setCommentIdInput(parseInt(val));
      }
    }), createElement(Button, {
      isPrimary: true,
      onClick: function onClick() {
        setAttributes({
          commentId: commentIdInput
        });
      }
    }, __('Save')));
  }

  return createElement("div", {
    className: className
  }, createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS
  }));
}
//# sourceMappingURL=edit.js.map