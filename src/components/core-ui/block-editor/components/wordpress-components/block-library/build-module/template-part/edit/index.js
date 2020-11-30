import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { BlockControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { Dropdown, ToolbarGroup, ToolbarButton, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronUp, chevronDown } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import useTemplatePartPost from './use-template-part-post';
import TemplatePartNamePanel from './name-panel';
import TemplatePartInnerBlocks from './inner-blocks';
import TemplatePartPlaceholder from './placeholder';
import TemplatePartSelection from './selection';
export default function TemplatePartEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      _postId = _ref$attributes.postId,
      slug = _ref$attributes.slug,
      theme = _ref$attributes.theme,
      _ref$attributes$tagNa = _ref$attributes.tagName,
      TagName = _ref$attributes$tagNa === void 0 ? 'div' : _ref$attributes$tagNa,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId;
  var initialPostId = useRef(_postId);
  var initialSlug = useRef(slug);
  var initialTheme = useRef(theme); // Resolve the post ID if not set, and load its post.

  var postId = useTemplatePartPost(_postId, slug, theme); // Set the post ID, once found, so that edits persist,
  // but wait until the third inner blocks change,
  // because the first 2 are just the template part
  // content loading.

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlocks = _select.getBlocks;

    return {
      innerBlocks: getBlocks(clientId)
    };
  }, [clientId]),
      innerBlocks = _useSelect.innerBlocks;

  var _useDispatch = useDispatch('core'),
      editEntityRecord = _useDispatch.editEntityRecord;

  var blockChanges = useRef(0);
  useEffect(function () {
    if (blockChanges.current < 4) blockChanges.current++;

    if (blockChanges.current === 3 && (initialPostId.current === undefined || initialPostId.current === null) && postId !== undefined && postId !== null) {
      setAttributes({
        postId: postId
      });
      editEntityRecord('postType', 'wp_template_part', postId, {
        status: 'publish'
      });
    }
  }, [innerBlocks]);
  var blockWrapperProps = useBlockWrapperProps(); // Part of a template file, post ID already resolved.

  var isTemplateFile = !!postId; // Fresh new block.

  var isPlaceholder = !postId && !initialSlug.current && !initialTheme.current; // Part of a template file, post ID not resolved yet.

  var isUnresolvedTemplateFile = !isPlaceholder && !postId;
  return createElement(TagName, blockWrapperProps, isPlaceholder && createElement(TemplatePartPlaceholder, {
    setAttributes: setAttributes
  }), isTemplateFile && createElement(BlockControls, null, createElement(ToolbarGroup, {
    className: "wp-block-template-part__block-control-group"
  }, createElement(TemplatePartNamePanel, {
    postId: postId,
    setAttributes: setAttributes
  }), createElement(Dropdown, {
    className: "wp-block-template-part__preview-dropdown-button",
    contentClassName: "wp-block-template-part__preview-dropdown-content",
    position: "bottom right left",
    renderToggle: function renderToggle(_ref2) {
      var isOpen = _ref2.isOpen,
          onToggle = _ref2.onToggle;
      return createElement(ToolbarButton, {
        "aria-expanded": isOpen,
        icon: isOpen ? chevronUp : chevronDown,
        label: __('Choose another'),
        onClick: onToggle // Disable when open to prevent odd FireFox bug causing reopening.
        // As noted in https://github.com/WordPress/gutenberg/pull/24990#issuecomment-689094119 .
        ,
        disabled: isOpen
      });
    },
    renderContent: function renderContent(_ref3) {
      var onClose = _ref3.onClose;
      return createElement(TemplatePartSelection, {
        setAttributes: setAttributes,
        onClose: onClose
      });
    }
  }))), isTemplateFile && createElement(TemplatePartInnerBlocks, {
    postId: postId,
    hasInnerBlocks: innerBlocks.length > 0
  }), isUnresolvedTemplateFile && createElement(Spinner, null));
}
//# sourceMappingURL=index.js.map