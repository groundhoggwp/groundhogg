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

import { useEntityProp } from '@wordpress/core-data';
import { BlockControls, Warning, __experimentalUseBlockWrapperProps as useBlockWrapperProps, AlignmentToolbar } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
export default function PostTagsEdit(_ref) {
  var context = _ref.context,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;

  var _useEntityProp = useEntityProp('postType', context.postType, 'tags', context.postId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 1),
      tags = _useEntityProp2[0];

  var tagLinks = useSelect(function (select) {
    var _select = select('core'),
        getEntityRecord = _select.getEntityRecord;

    var loaded = true;
    var links = tags === null || tags === void 0 ? void 0 : tags.map(function (tagId) {
      var tag = getEntityRecord('taxonomy', 'post_tag', tagId);

      if (!tag) {
        return loaded = false;
      }

      return createElement("a", {
        key: tagId,
        href: tag.link
      }, tag.name);
    });
    return loaded && links;
  }, [tags]);
  var display = tagLinks && (tagLinks.length === 0 ? __('No tags.') : tagLinks.reduce(function (prev, curr) {
    return [prev, ' | ', curr];
  }));

  if (!context.postType || !context.postId) {
    display = createElement(Warning, null, __('Post tags block: No post found for this block.'));
  } else if (context.postType !== 'post') {
    /**
     * Do not render the block when viewing a page (as opposed to a post)
     *
     * @todo By default, only posts can be grouped by tags. Therefore, without any configuration,
     * the post tags block will have no tags for pages. Plugins, however, can modify this behavior.
     * In the future, instead of only evaluating posts, we should check whether the
     * post_tag taxonomy is registered for the current post type.
     */
    display = createElement(Warning, null, __('Post tags block: Tags are not available for this post type.'));
  }

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
  })), createElement("div", blockWrapperProps, display));
}
//# sourceMappingURL=edit.js.map