import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import PostContentInnerBlocks from './inner-blocks';
export default function PostContentEdit(_ref) {
  var _ref$context = _ref.context,
      contextPostId = _ref$context.postId,
      contextPostType = _ref$context.postType;

  var _useSelect = useSelect(function (select) {
    var _select$getCurrentPos;

    return (_select$getCurrentPos = select('core/editor').getCurrentPost()) !== null && _select$getCurrentPos !== void 0 ? _select$getCurrentPos : {};
  }),
      currentPostId = _useSelect.id,
      currentPostType = _useSelect.type; // Only render InnerBlocks if the context is different from the active post
  // to avoid infinite recursion of post content.


  if (contextPostId && contextPostType && contextPostId !== currentPostId && contextPostType !== currentPostType) {
    return createElement(PostContentInnerBlocks, {
      postType: contextPostType,
      postId: contextPostId
    });
  }

  return createElement("div", {
    className: "wp-block-post-content__placeholder"
  }, createElement("span", null, __('This is a placeholder for post content.')));
}
//# sourceMappingURL=edit.js.map