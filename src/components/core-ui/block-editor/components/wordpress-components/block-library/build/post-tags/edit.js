"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostTagsEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _coreData = require("@wordpress/core-data");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function PostTagsEdit(_ref) {
  var context = _ref.context,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var textAlign = attributes.textAlign;

  var _useEntityProp = (0, _coreData.useEntityProp)('postType', context.postType, 'tags', context.postId),
      _useEntityProp2 = (0, _slicedToArray2.default)(_useEntityProp, 1),
      tags = _useEntityProp2[0];

  var tagLinks = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getEntityRecord = _select.getEntityRecord;

    var loaded = true;
    var links = tags === null || tags === void 0 ? void 0 : tags.map(function (tagId) {
      var tag = getEntityRecord('taxonomy', 'post_tag', tagId);

      if (!tag) {
        return loaded = false;
      }

      return (0, _element.createElement)("a", {
        key: tagId,
        href: tag.link
      }, tag.name);
    });
    return loaded && links;
  }, [tags]);
  var display = tagLinks && (tagLinks.length === 0 ? (0, _i18n.__)('No tags.') : tagLinks.reduce(function (prev, curr) {
    return [prev, ' | ', curr];
  }));

  if (!context.postType || !context.postId) {
    display = (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post tags block: No post found for this block.'));
  } else if (context.postType !== 'post') {
    /**
     * Do not render the block when viewing a page (as opposed to a post)
     *
     * @todo By default, only posts can be grouped by tags. Therefore, without any configuration,
     * the post tags block will have no tags for pages. Plugins, however, can modify this behavior.
     * In the future, instead of only evaluating posts, we should check whether the
     * post_tag taxonomy is registered for the current post type.
     */
    display = (0, _element.createElement)(_blockEditor.Warning, null, (0, _i18n.__)('Post tags block: Tags are not available for this post type.'));
  }

  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)("div", blockWrapperProps, display));
}
//# sourceMappingURL=edit.js.map