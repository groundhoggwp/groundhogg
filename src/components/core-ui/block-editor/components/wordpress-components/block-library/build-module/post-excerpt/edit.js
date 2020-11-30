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
import { useMemo } from '@wordpress/element';
import { AlignmentToolbar, BlockControls, InspectorControls, RichText, Warning, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function usePostContentExcerpt(wordCount, postId, postType) {
  // Don't destrcuture items from content here, it can be undefined.
  var _useEntityProp = useEntityProp('postType', postType, 'content', postId),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 3),
      content = _useEntityProp2[2];

  var rawPostContent = content === null || content === void 0 ? void 0 : content.raw;
  return useMemo(function () {
    if (!rawPostContent) {
      return '';
    }

    var excerptElement = document.createElement('div');
    excerptElement.innerHTML = rawPostContent;
    var excerpt = excerptElement.textContent || excerptElement.innerText || '';
    return excerpt.trim().split(' ', wordCount).join(' ');
  }, [rawPostContent, wordCount]);
}

function PostExcerptEditor(_ref) {
  var _ref$attributes = _ref.attributes,
      textAlign = _ref$attributes.textAlign,
      wordCount = _ref$attributes.wordCount,
      moreText = _ref$attributes.moreText,
      showMoreOnNewLine = _ref$attributes.showMoreOnNewLine,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      _ref$context = _ref.context,
      postId = _ref$context.postId,
      postType = _ref$context.postType;

  var _useEntityProp3 = useEntityProp('postType', postType, 'excerpt', postId),
      _useEntityProp4 = _slicedToArray(_useEntityProp3, 2),
      excerpt = _useEntityProp4[0],
      setExcerpt = _useEntityProp4[1];

  var postContentExcerpt = usePostContentExcerpt(wordCount, postId, postType);
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(newAlign) {
      return setAttributes({
        textAlign: newAlign
      });
    }
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Post Excerpt Settings')
  }, !excerpt && createElement(RangeControl, {
    label: __('Max words'),
    value: wordCount,
    onChange: function onChange(newExcerptLength) {
      return setAttributes({
        wordCount: newExcerptLength
      });
    },
    min: 10,
    max: 100
  }), createElement(ToggleControl, {
    label: __('Show link on new line'),
    checked: showMoreOnNewLine,
    onChange: function onChange(newShowMoreOnNewLine) {
      return setAttributes({
        showMoreOnNewLine: newShowMoreOnNewLine
      });
    }
  }))), createElement("div", blockWrapperProps, createElement(RichText, {
    className: !showMoreOnNewLine && 'wp-block-post-excerpt__excerpt is-inline',
    placeholder: postContentExcerpt,
    value: excerpt || (isSelected ? '' : postContentExcerpt || __('No post excerpt found')),
    onChange: setExcerpt,
    keepPlaceholderOnFocus: true
  }), !showMoreOnNewLine && ' ', showMoreOnNewLine ? createElement("p", {
    className: "wp-block-post-excerpt__more-text"
  }, createElement(RichText, {
    tagName: "a",
    placeholder: __('Read more…'),
    value: moreText,
    onChange: function onChange(newMoreText) {
      return setAttributes({
        moreText: newMoreText
      });
    }
  })) : createElement(RichText, {
    tagName: "a",
    placeholder: __('Read more…'),
    value: moreText,
    onChange: function onChange(newMoreText) {
      return setAttributes({
        moreText: newMoreText
      });
    }
  })));
}

export default function PostExcerptEdit(_ref2) {
  var attributes = _ref2.attributes,
      setAttributes = _ref2.setAttributes,
      isSelected = _ref2.isSelected,
      context = _ref2.context;

  if (!context.postType || !context.postId) {
    return createElement(Warning, null, __('Post excerpt block: no post found.'));
  }

  return createElement(PostExcerptEditor, {
    attributes: attributes,
    setAttributes: setAttributes,
    isSelected: isSelected,
    context: context
  });
}
//# sourceMappingURL=edit.js.map