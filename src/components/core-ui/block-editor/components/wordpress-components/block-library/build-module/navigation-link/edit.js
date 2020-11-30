import _regeneratorRuntime from "@babel/runtime/regenerator";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { escape, get, head, find } from 'lodash';
/**
 * WordPress dependencies
 */

import { compose } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch, withDispatch, withSelect } from '@wordpress/data';
import { KeyboardShortcuts, PanelBody, Popover, TextControl, TextareaControl, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { rawShortcut, displayShortcut } from '@wordpress/keycodes';
import { __, sprintf } from '@wordpress/i18n';
import { BlockControls, InnerBlocks, InspectorControls, RichText, __experimentalLinkControl as LinkControl, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { isURL, prependHTTP } from '@wordpress/url';
import { Fragment, useState, useEffect, useRef, createInterpolateElement } from '@wordpress/element';
import { placeCaretAtHorizontalEdge } from '@wordpress/dom';
import { link as linkIcon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { ToolbarSubmenuIcon, ItemSubmenuIcon } from './icons';
/**
 * A React hook to determine if it's dragging within the target element.
 *
 * @typedef {import('@wordpress/element').RefObject} RefObject
 *
 * @param {RefObject<HTMLElement>} elementRef The target elementRef object.
 *
 * @return {boolean} Is dragging within the target element.
 */

var useIsDraggingWithin = function useIsDraggingWithin(elementRef) {
  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isDraggingWithin = _useState2[0],
      setIsDraggingWithin = _useState2[1];

  useEffect(function () {
    function handleDragStart(event) {
      // Check the first time when the dragging starts.
      handleDragEnter(event);
    } // Set to false whenever the user cancel the drag event by either releasing the mouse or press Escape.


    function handleDragEnd() {
      setIsDraggingWithin(false);
    }

    function handleDragEnter(event) {
      // Check if the current target is inside the item element.
      if (elementRef.current.contains(event.target)) {
        setIsDraggingWithin(true);
      } else {
        setIsDraggingWithin(false);
      }
    } // Bind these events to the document to catch all drag events.
    // Ideally, we can also use `event.relatedTarget`, but sadly that doesn't work in Safari.


    document.addEventListener('dragstart', handleDragStart);
    document.addEventListener('dragend', handleDragEnd);
    document.addEventListener('dragenter', handleDragEnter);
    return function () {
      document.removeEventListener('dragstart', handleDragStart);
      document.removeEventListener('dragend', handleDragEnd);
      document.removeEventListener('dragenter', handleDragEnter);
    };
  }, []);
  return isDraggingWithin;
};
/**
 * Given the Link block's type attribute, return the query params to give to
 * /wp/v2/search.
 *
 * @param {string} type Link block's type attribute.
 * @return {{ type?: string, subtype?: string }} Search query params.
 */


function getSuggestionsQuery(type) {
  switch (type) {
    case 'post':
    case 'page':
      return {
        type: 'post',
        subtype: type
      };

    case 'category':
      return {
        type: 'term',
        subtype: 'category'
      };

    case 'tag':
      return {
        type: 'term',
        subtype: 'post_tag'
      };

    default:
      return {};
  }
}

function NavigationLinkEdit(_ref) {
  var _classnames;

  var attributes = _ref.attributes,
      hasDescendants = _ref.hasDescendants,
      isSelected = _ref.isSelected,
      isImmediateParentOfSelectedBlock = _ref.isImmediateParentOfSelectedBlock,
      isParentOfSelectedBlock = _ref.isParentOfSelectedBlock,
      setAttributes = _ref.setAttributes,
      showSubmenuIcon = _ref.showSubmenuIcon,
      insertLinkBlock = _ref.insertLinkBlock,
      textColor = _ref.textColor,
      backgroundColor = _ref.backgroundColor,
      rgbTextColor = _ref.rgbTextColor,
      rgbBackgroundColor = _ref.rgbBackgroundColor,
      selectedBlockHasDescendants = _ref.selectedBlockHasDescendants,
      _ref$userCanCreatePag = _ref.userCanCreatePages,
      userCanCreatePages = _ref$userCanCreatePag === void 0 ? false : _ref$userCanCreatePag,
      _ref$userCanCreatePos = _ref.userCanCreatePosts,
      userCanCreatePosts = _ref$userCanCreatePos === void 0 ? false : _ref$userCanCreatePos,
      insertBlocksAfter = _ref.insertBlocksAfter,
      mergeBlocks = _ref.mergeBlocks,
      onReplace = _ref.onReplace;
  var label = attributes.label,
      type = attributes.type,
      opensInNewTab = attributes.opensInNewTab,
      url = attributes.url,
      description = attributes.description,
      rel = attributes.rel,
      title = attributes.title;
  var link = {
    url: url,
    opensInNewTab: opensInNewTab
  };

  var _useDispatch = useDispatch('core'),
      saveEntityRecord = _useDispatch.saveEntityRecord;

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isLinkOpen = _useState4[0],
      setIsLinkOpen = _useState4[1];

  var listItemRef = useRef(null);
  var isDraggingWithin = useIsDraggingWithin(listItemRef);

  var itemLabelPlaceholder = __('Add link…');

  var ref = useRef();
  var isDraggingBlocks = useSelect(function (select) {
    return select('core/block-editor').isDraggingBlocks();
  }, []); // Show the LinkControl on mount if the URL is empty
  // ( When adding a new menu item)
  // This can't be done in the useState call because it cconflicts
  // with the autofocus behavior of the BlockListBlock component.

  useEffect(function () {
    if (!url) {
      setIsLinkOpen(true);
    }
  }, []);
  /**
   * The hook shouldn't be necessary but due to a focus loss happening
   * when selecting a suggestion in the link popover, we force close on block unselection.
   */

  useEffect(function () {
    if (!isSelected) {
      setIsLinkOpen(false);
    }
  }, [isSelected]); // If the LinkControl popover is open and the URL has changed, close the LinkControl and focus the label text.

  useEffect(function () {
    if (isLinkOpen && url) {
      // Does this look like a URL and have something TLD-ish?
      if (isURL(prependHTTP(label)) && /^.+\.[a-z]+/.test(label)) {
        // Focus and select the label text.
        selectLabelText();
      } else {
        // Focus it (but do not select).
        placeCaretAtHorizontalEdge(ref.current, true);
      }
    }
  }, [url]);
  /**
   * Focus the Link label text and select it.
   */

  function selectLabelText() {
    ref.current.focus();
    var ownerDocument = ref.current.ownerDocument;
    var defaultView = ownerDocument.defaultView;
    var selection = defaultView.getSelection();
    var range = ownerDocument.createRange(); // Get the range of the current ref contents so we can add this range to the selection.

    range.selectNodeContents(ref.current);
    selection.removeAllRanges();
    selection.addRange(range);
  }

  var userCanCreate = false;

  if (!type || type === 'page') {
    userCanCreate = userCanCreatePages;
  } else if (type === 'post') {
    userCanCreate = userCanCreatePosts;
  }

  function handleCreate(_x) {
    return _handleCreate.apply(this, arguments);
  }

  function _handleCreate() {
    _handleCreate = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(pageTitle) {
      var postType, page;
      return _regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              postType = type || 'page';
              _context.next = 3;
              return saveEntityRecord('postType', postType, {
                title: pageTitle,
                status: 'publish'
              });

            case 3:
              page = _context.sent;
              return _context.abrupt("return", {
                id: page.id,
                postType: postType,
                title: page.title.rendered,
                url: page.link
              });

            case 5:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));
    return _handleCreate.apply(this, arguments);
  }

  var blockWrapperProps = useBlockWrapperProps({
    ref: listItemRef,
    className: classnames((_classnames = {
      'is-editing': (isSelected || isParentOfSelectedBlock) && // Don't show the element as editing while dragging.
      !isDraggingBlocks,
      // Don't select the element while dragging.
      'is-selected': isSelected && !isDraggingBlocks,
      'is-dragging-within': isDraggingWithin,
      'has-link': !!url,
      'has-child': hasDescendants,
      'has-text-color': rgbTextColor
    }, _defineProperty(_classnames, "has-".concat(textColor, "-color"), !!textColor), _defineProperty(_classnames, 'has-background', rgbBackgroundColor), _defineProperty(_classnames, "has-".concat(backgroundColor, "-background-color"), !!backgroundColor), _classnames)),
    style: {
      color: rgbTextColor,
      backgroundColor: rgbBackgroundColor
    }
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(KeyboardShortcuts, {
    bindGlobal: true,
    shortcuts: _defineProperty({}, rawShortcut.primary('k'), function () {
      return setIsLinkOpen(true);
    })
  }), createElement(ToolbarButton, {
    name: "link",
    icon: linkIcon,
    title: __('Link'),
    shortcut: displayShortcut.primary('k'),
    onClick: function onClick() {
      return setIsLinkOpen(true);
    }
  }), createElement(ToolbarButton, {
    name: "submenu",
    icon: createElement(ToolbarSubmenuIcon, null),
    title: __('Add submenu'),
    onClick: insertLinkBlock
  }))), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Link settings')
  }, createElement(TextareaControl, {
    value: description || '',
    onChange: function onChange(descriptionValue) {
      setAttributes({
        description: descriptionValue
      });
    },
    label: __('Description'),
    help: __('The description will be displayed in the menu if the current theme supports it.')
  }), createElement(TextControl, {
    value: title || '',
    onChange: function onChange(titleValue) {
      setAttributes({
        title: titleValue
      });
    },
    label: __('Link title'),
    autoComplete: "off"
  }), createElement(TextControl, {
    value: rel || '',
    onChange: function onChange(relValue) {
      setAttributes({
        rel: relValue
      });
    },
    label: __('Link rel'),
    autoComplete: "off"
  }))), createElement("li", blockWrapperProps, createElement("div", {
    className: "wp-block-navigation-link__content"
  }, createElement(RichText, {
    ref: ref,
    identifier: "label",
    className: "wp-block-navigation-link__label",
    value: label,
    onChange: function onChange(labelValue) {
      return setAttributes({
        label: labelValue
      });
    },
    onMerge: mergeBlocks,
    onReplace: onReplace,
    __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
      return insertBlocksAfter(createBlock('core/navigation-link'));
    },
    placeholder: itemLabelPlaceholder,
    keepPlaceholderOnFocus: true,
    withoutInteractiveFormatting: true,
    allowedFormats: ['core/bold', 'core/italic', 'core/image', 'core/strikethrough']
  }), isLinkOpen && createElement(Popover, {
    position: "bottom center",
    onClose: function onClose() {
      return setIsLinkOpen(false);
    }
  }, createElement(LinkControl, {
    className: "wp-block-navigation-link__inline-link-input",
    value: link,
    showInitialSuggestions: true,
    withCreateSuggestion: userCanCreate,
    createSuggestion: handleCreate,
    createSuggestionButtonText: function createSuggestionButtonText(searchTerm) {
      var format;

      if (type === 'post') {
        /* translators: %s: search term. */
        format = __('Create post: <mark>%s</mark>');
      } else {
        /* translators: %s: search term. */
        format = __('Create page: <mark>%s</mark>');
      }

      return createInterpolateElement(sprintf(format, searchTerm), {
        mark: createElement("mark", null)
      });
    },
    noDirectEntry: !!type,
    noURLSuggestion: !!type,
    suggestionsQuery: getSuggestionsQuery(type),
    onChange: function onChange() {
      var _ref3 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
          _ref3$title = _ref3.title,
          newTitle = _ref3$title === void 0 ? '' : _ref3$title,
          _ref3$url = _ref3.url,
          newURL = _ref3$url === void 0 ? '' : _ref3$url,
          newOpensInNewTab = _ref3.opensInNewTab,
          id = _ref3.id;

      return setAttributes({
        url: encodeURI(newURL),
        label: function () {
          var normalizedTitle = newTitle.replace(/http(s?):\/\//gi, '');
          var normalizedURL = newURL.replace(/http(s?):\/\//gi, '');

          if (newTitle !== '' && normalizedTitle !== normalizedURL && label !== newTitle) {
            return escape(newTitle);
          } else if (label) {
            return label;
          } // If there's no label, add the URL.


          return escape(normalizedURL);
        }(),
        opensInNewTab: newOpensInNewTab,
        id: id
      });
    }
  }))), showSubmenuIcon && createElement("span", {
    className: "wp-block-navigation-link__submenu-icon"
  }, createElement(ItemSubmenuIcon, null)), createElement(InnerBlocks, {
    allowedBlocks: ['core/navigation-link'],
    renderAppender: isSelected && hasDescendants || isImmediateParentOfSelectedBlock && !selectedBlockHasDescendants || // Show the appender while dragging to allow inserting element between item and the appender.
    isDraggingBlocks && hasDescendants ? InnerBlocks.DefaultAppender : false,
    __experimentalTagName: "ul",
    __experimentalAppenderTagName: "li",
    __experimentalPassedProps: {
      className: classnames('wp-block-navigation__container', {
        'is-parent-of-selected-block': isParentOfSelectedBlock && // Don't select as parent of selected block while dragging.
        !isDraggingBlocks
      })
    }
  })));
}
/**
 * Returns the color object matching the slug, or undefined.
 *
 * @param {Array}  colors      The editor settings colors array.
 * @param {string} colorSlug   A string containing the color slug.
 * @param {string} customColor A string containing the custom color value.
 *
 * @return {Object} Color object included in the editor settings colors, or Undefined.
 */


var getColorObjectByColorSlug = function getColorObjectByColorSlug(colors, colorSlug, customColor) {
  if (customColor) {
    return customColor;
  }

  if (!colors || !colors.length) {
    return;
  }

  return get(find(colors, {
    slug: colorSlug
  }), 'color');
};

export default compose([withSelect(function (select, ownProps) {
  var _getClientIdsOfDescen;

  var _select = select('core/block-editor'),
      getBlockAttributes = _select.getBlockAttributes,
      getClientIdsOfDescendants = _select.getClientIdsOfDescendants,
      hasSelectedInnerBlock = _select.hasSelectedInnerBlock,
      getBlockParentsByBlockName = _select.getBlockParentsByBlockName,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getSettings = _select.getSettings;

  var clientId = ownProps.clientId;
  var rootBlock = head(getBlockParentsByBlockName(clientId, 'core/navigation'));
  var navigationBlockAttributes = getBlockAttributes(rootBlock);
  var colors = get(getSettings(), 'colors', []);
  var hasDescendants = !!getClientIdsOfDescendants([clientId]).length;
  var showSubmenuIcon = !!navigationBlockAttributes.showSubmenuIcon && hasDescendants;
  var isParentOfSelectedBlock = hasSelectedInnerBlock(clientId, true);
  var isImmediateParentOfSelectedBlock = hasSelectedInnerBlock(clientId, false);
  var selectedBlockId = getSelectedBlockClientId();
  var selectedBlockHasDescendants = !!((_getClientIdsOfDescen = getClientIdsOfDescendants([selectedBlockId])) === null || _getClientIdsOfDescen === void 0 ? void 0 : _getClientIdsOfDescen.length);
  return {
    isParentOfSelectedBlock: isParentOfSelectedBlock,
    isImmediateParentOfSelectedBlock: isImmediateParentOfSelectedBlock,
    hasDescendants: hasDescendants,
    selectedBlockHasDescendants: selectedBlockHasDescendants,
    showSubmenuIcon: showSubmenuIcon,
    textColor: navigationBlockAttributes.textColor,
    backgroundColor: navigationBlockAttributes.backgroundColor,
    userCanCreatePages: select('core').canUser('create', 'pages'),
    userCanCreatePosts: select('core').canUser('create', 'posts'),
    rgbTextColor: getColorObjectByColorSlug(colors, navigationBlockAttributes.textColor, navigationBlockAttributes.customTextColor),
    rgbBackgroundColor: getColorObjectByColorSlug(colors, navigationBlockAttributes.backgroundColor, navigationBlockAttributes.customBackgroundColor)
  };
}), withDispatch(function (dispatch, ownProps, registry) {
  return {
    insertLinkBlock: function insertLinkBlock() {
      var clientId = ownProps.clientId;

      var _dispatch = dispatch('core/block-editor'),
          insertBlock = _dispatch.insertBlock;

      var _registry$select = registry.select('core/block-editor'),
          getClientIdsOfDescendants = _registry$select.getClientIdsOfDescendants;

      var navItems = getClientIdsOfDescendants([clientId]);
      var insertionPoint = navItems.length ? navItems.length : 0;
      var blockToInsert = createBlock('core/navigation-link');
      insertBlock(blockToInsert, insertionPoint, clientId);
    }
  };
})])(NavigationLinkEdit);
//# sourceMappingURL=edit.js.map