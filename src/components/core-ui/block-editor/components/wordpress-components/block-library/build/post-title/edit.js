"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = PostTitleEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _headingLevelDropdown = _interopRequireDefault(require("../heading/heading-level-dropdown"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function PostTitleEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      level = _ref$attributes.level,
      textAlign = _ref$attributes.textAlign,
      isLink = _ref$attributes.isLink,
      rel = _ref$attributes.rel,
      linkTarget = _ref$attributes.linkTarget,
      setAttributes = _ref.setAttributes,
      _ref$context = _ref.context,
      postType = _ref$context.postType,
      postId = _ref$context.postId;
  var TagName = 0 === level ? 'p' : 'h' + level;
  var post = (0, _data.useSelect)(function (select) {
    return select('core').getEditedEntityRecord('postType', postType, postId);
  }, [postType, postId]);
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)({
    className: (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!post) {
    return null;
  }

  var title = post.title || (0, _i18n.__)('Post Title');

  if (isLink) {
    title = (0, _element.createElement)("a", {
      href: post.link,
      target: linkTarget,
      rel: rel
    }, title);
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_headingLevelDropdown.default, {
    selectedLevel: level,
    onChange: function onChange(newLevel) {
      return setAttributes({
        level: newLevel
      });
    }
  })), (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Link settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Make title a link'),
    onChange: function onChange() {
      return setAttributes({
        isLink: !isLink
      });
    },
    checked: isLink
  }), isLink && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Open in new tab'),
    onChange: function onChange(value) {
      return setAttributes({
        linkTarget: value ? '_blank' : '_self'
      });
    },
    checked: linkTarget === '_blank'
  }), (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Link rel'),
    value: rel,
    onChange: function onChange(newRel) {
      return setAttributes({
        rel: newRel
      });
    }
  })))), (0, _element.createElement)(TagName, blockWrapperProps, title));
}
//# sourceMappingURL=edit.js.map