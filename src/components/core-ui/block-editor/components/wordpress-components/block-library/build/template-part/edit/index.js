"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TemplatePartEdit;

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _useTemplatePartPost = _interopRequireDefault(require("./use-template-part-post"));

var _namePanel = _interopRequireDefault(require("./name-panel"));

var _innerBlocks = _interopRequireDefault(require("./inner-blocks"));

var _placeholder = _interopRequireDefault(require("./placeholder"));

var _selection = _interopRequireDefault(require("./selection"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function TemplatePartEdit(_ref) {
  var _ref$attributes = _ref.attributes,
      _postId = _ref$attributes.postId,
      slug = _ref$attributes.slug,
      theme = _ref$attributes.theme,
      _ref$attributes$tagNa = _ref$attributes.tagName,
      TagName = _ref$attributes$tagNa === void 0 ? 'div' : _ref$attributes$tagNa,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId;
  var initialPostId = (0, _element.useRef)(_postId);
  var initialSlug = (0, _element.useRef)(slug);
  var initialTheme = (0, _element.useRef)(theme); // Resolve the post ID if not set, and load its post.

  var postId = (0, _useTemplatePartPost.default)(_postId, slug, theme); // Set the post ID, once found, so that edits persist,
  // but wait until the third inner blocks change,
  // because the first 2 are just the template part
  // content loading.

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlocks = _select.getBlocks;

    return {
      innerBlocks: getBlocks(clientId)
    };
  }, [clientId]),
      innerBlocks = _useSelect.innerBlocks;

  var _useDispatch = (0, _data.useDispatch)('core'),
      editEntityRecord = _useDispatch.editEntityRecord;

  var blockChanges = (0, _element.useRef)(0);
  (0, _element.useEffect)(function () {
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
  var blockWrapperProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)(); // Part of a template file, post ID already resolved.

  var isTemplateFile = !!postId; // Fresh new block.

  var isPlaceholder = !postId && !initialSlug.current && !initialTheme.current; // Part of a template file, post ID not resolved yet.

  var isUnresolvedTemplateFile = !isPlaceholder && !postId;
  return (0, _element.createElement)(TagName, blockWrapperProps, isPlaceholder && (0, _element.createElement)(_placeholder.default, {
    setAttributes: setAttributes
  }), isTemplateFile && (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, {
    className: "wp-block-template-part__block-control-group"
  }, (0, _element.createElement)(_namePanel.default, {
    postId: postId,
    setAttributes: setAttributes
  }), (0, _element.createElement)(_components.Dropdown, {
    className: "wp-block-template-part__preview-dropdown-button",
    contentClassName: "wp-block-template-part__preview-dropdown-content",
    position: "bottom right left",
    renderToggle: function renderToggle(_ref2) {
      var isOpen = _ref2.isOpen,
          onToggle = _ref2.onToggle;
      return (0, _element.createElement)(_components.ToolbarButton, {
        "aria-expanded": isOpen,
        icon: isOpen ? _icons.chevronUp : _icons.chevronDown,
        label: (0, _i18n.__)('Choose another'),
        onClick: onToggle // Disable when open to prevent odd FireFox bug causing reopening.
        // As noted in https://github.com/WordPress/gutenberg/pull/24990#issuecomment-689094119 .
        ,
        disabled: isOpen
      });
    },
    renderContent: function renderContent(_ref3) {
      var onClose = _ref3.onClose;
      return (0, _element.createElement)(_selection.default, {
        setAttributes: setAttributes,
        onClose: onClose
      });
    }
  }))), isTemplateFile && (0, _element.createElement)(_innerBlocks.default, {
    postId: postId,
    hasInnerBlocks: innerBlocks.length > 0
  }), isUnresolvedTemplateFile && (0, _element.createElement)(_components.Spinner, null));
}
//# sourceMappingURL=index.js.map