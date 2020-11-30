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
import { AlignmentToolbar, BlockControls, InspectorControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { ToolbarGroup, ToggleControl, TextControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import HeadingLevelDropdown from '../heading/heading-level-dropdown';
export default function PostTitleEdit(_ref) {
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
  var post = useSelect(function (select) {
    return select('core').getEditedEntityRecord('postType', postType, postId);
  }, [postType, postId]);
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });

  if (!post) {
    return null;
  }

  var title = post.title || __('Post Title');

  if (isLink) {
    title = createElement("a", {
      href: post.link,
      target: linkTarget,
      rel: rel
    }, title);
  }

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(HeadingLevelDropdown, {
    selectedLevel: level,
    onChange: function onChange(newLevel) {
      return setAttributes({
        level: newLevel
      });
    }
  })), createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Link settings')
  }, createElement(ToggleControl, {
    label: __('Make title a link'),
    onChange: function onChange() {
      return setAttributes({
        isLink: !isLink
      });
    },
    checked: isLink
  }), isLink && createElement(Fragment, null, createElement(ToggleControl, {
    label: __('Open in new tab'),
    onChange: function onChange(value) {
      return setAttributes({
        linkTarget: value ? '_blank' : '_self'
      });
    },
    checked: linkTarget === '_blank'
  }), createElement(TextControl, {
    label: __('Link rel'),
    value: rel,
    onChange: function onChange(newRel) {
      return setAttributes({
        rel: newRel
      });
    }
  })))), createElement(TagName, blockWrapperProps, title));
}
//# sourceMappingURL=edit.js.map