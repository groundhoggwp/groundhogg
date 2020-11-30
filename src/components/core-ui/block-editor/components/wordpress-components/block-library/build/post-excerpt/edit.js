"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostExcerptEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _coreData = require("@wordpress/core-data");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function usePostContentExcerpt(wordCount, postId, postType) {
  // Don't destrcuture items from content here, it can be undefined.
  var _useEntityProp = (0, _coreData.useEntityProp)('postType', postType, 'content', postId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 3),
      content = _useEntityProp2[2];

  var rawPostContent = content === null || content === void 0 ? void 0 : content.raw;
  return (0, _element.useMemo)(function () {
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

  var _useEntityProp3 = (0, _coreData.useEntityProp)('postType', postType, 'excerpt', postId),
      _useEntityProp4 = (0, _slicedToArray2.default)(_useEntityProp3, 2),
      excerpt = _useEntityProp4[0],
      setExcerpt = _useEntityProp4[1];

  var postContentExcerpt = usePostContentExcerpt(wordCount, postId, postType);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(newAlign) {
      return setAttributes({
        textAlign: newAlign
      });
    }
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Post Excerpt Settings')
  }, !excerpt && (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Max words'),
    value: wordCount,
    onChange: function onChange(newExcerptLength) {
      return setAttributes({
        wordCount: newExcerptLength
      });
    },
    min: 10,
    max: 100
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Show link on new line'),
    checked: showMoreOnNewLine,
    onChange: function onChange(newShowMoreOnNewLine) {
      return setAttributes({
        showMoreOnNewLine: newShowMoreOnNewLine
      });
    }
  }))), (0, _element.createElement)("div", blockWrapperProps, (0, _element.createElement)(_blockEditor.RichText, {
    className: !showMoreOnNewLine && 'wp-block-post-excerpt__excerpt is-inline',
    placeholder: postContentExcerpt,
    value: excerpt || (isSelected ? '' : postContentExcerpt || (0, _i18n.__)('No post excerpt found')),
    onChange: setExcerpt,
    keepPlaceholderOnFocus: true
  }), !showMoreOnNewLine && ' ', showMoreOnNewLine ? (0, _element.createElement)("p", {
    className: "wp-block-post-excerpt__more-text"
  }, (0, _element.createElement)(_blockEditor.RichText, {
    tagName: "a",
    placeholder: (0, _i18n.__)('Read more…'),
    value: moreText,
    onChange: function onChange(newMoreText) {
      return setAttributes({
        moreText: newMoreText
      });
    }
  })) : (0, _element.createElement)(_blockEditor.RichText, {
    tagName: "a",
    placeholder: (0, _i18n.__)('Read more…'),
    value: moreText,
    onChange: function onChange(newMoreText) {
      return setAttributes({
        moreText: newMoreText
      });
    }
  })));
}

function PostExcerptEdit(_ref2) {
  var attributes = _ref2.attributes,
      setAttributes = _ref2.setAttributes,
      isSelected = _ref2.isSelected,
      context = _ref2.context;

  if (!context.postType || !context.postId) {
    return (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post excerpt block: no post found.'));
  }

  return (0, _element.createElement)(PostExcerptEditor, {
    attributes: attributes,
    setAttributes: setAttributes,
    isSelected: isSelected,
    context: context
  });
}
//# sourceMappingURL=edit.js.map