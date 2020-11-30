"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "RichTextShortcut", {
  enumerable: true,
  get: function get() {
    return _shortcut.RichTextShortcut;
  }
});
Object.defineProperty(exports, "RichTextToolbarButton", {
  enumerable: true,
  get: function get() {
    return _toolbarButton.RichTextToolbarButton;
  }
});
Object.defineProperty(exports, "__unstableRichTextInputEvent", {
  enumerable: true,
  get: function get() {
    return _inputEvent.__unstableRichTextInputEvent;
  }
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _compose = require("@wordpress/compose");

var _richText = require("@wordpress/rich-text");

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

var _url = require("@wordpress/url");

var _shortcode = require("@wordpress/shortcode");

var _autocomplete = _interopRequireDefault(require("../autocomplete"));

var _blockEdit = require("../block-edit");

var _removeBrowserShortcuts = require("./remove-browser-shortcuts");

var _filePasteHandler = require("./file-paste-handler");

var _formatToolbarContainer = _interopRequireDefault(require("./format-toolbar-container"));

var _shortcut = require("./shortcut");

var _toolbarButton = require("./toolbar-button");

var _inputEvent = require("./input-event");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var wrapperClasses = 'block-editor-rich-text';
var classes = 'block-editor-rich-text__editable';
/**
 * Get the multiline tag based on the multiline prop.
 *
 * @param {?(string|boolean)} multiline The multiline prop.
 *
 * @return {?string} The multiline tag.
 */

function getMultilineTag(multiline) {
  if (multiline !== true && multiline !== 'p' && multiline !== 'li') {
    return;
  }

  return multiline === true ? 'p' : multiline;
}

function getAllowedFormats(_ref) {
  var allowedFormats = _ref.allowedFormats,
      formattingControls = _ref.formattingControls,
      disableFormats = _ref.disableFormats;

  if (disableFormats) {
    return getAllowedFormats.EMPTY_ARRAY;
  }

  if (!allowedFormats && !formattingControls) {
    return;
  }

  if (allowedFormats) {
    return allowedFormats;
  }

  (0, _deprecated.default)('wp.blockEditor.RichText formattingControls prop', {
    alternative: 'allowedFormats'
  });
  return formattingControls.map(function (name) {
    return "core/".concat(name);
  });
}

getAllowedFormats.EMPTY_ARRAY = [];

var isShortcode = function isShortcode(text) {
  return (0, _shortcode.regexp)('.*').test(text);
};

function RichTextWrapper(_ref2, forwardedRef) {
  var children = _ref2.children,
      tagName = _ref2.tagName,
      originalValue = _ref2.value,
      originalOnChange = _ref2.onChange,
      originalIsSelected = _ref2.isSelected,
      multiline = _ref2.multiline,
      inlineToolbar = _ref2.inlineToolbar,
      wrapperClassName = _ref2.wrapperClassName,
      className = _ref2.className,
      autocompleters = _ref2.autocompleters,
      onReplace = _ref2.onReplace,
      placeholder = _ref2.placeholder,
      keepPlaceholderOnFocus = _ref2.keepPlaceholderOnFocus,
      allowedFormats = _ref2.allowedFormats,
      formattingControls = _ref2.formattingControls,
      withoutInteractiveFormatting = _ref2.withoutInteractiveFormatting,
      onRemove = _ref2.onRemove,
      onMerge = _ref2.onMerge,
      onSplit = _ref2.onSplit,
      onSplitAtEnd = _ref2.__unstableOnSplitAtEnd,
      onSplitMiddle = _ref2.__unstableOnSplitMiddle,
      identifier = _ref2.identifier,
      startAttr = _ref2.start,
      reversed = _ref2.reversed,
      style = _ref2.style,
      preserveWhiteSpace = _ref2.preserveWhiteSpace,
      __unstableEmbedURLOnPaste = _ref2.__unstableEmbedURLOnPaste,
      disableFormats = _ref2.__unstableDisableFormats,
      disableLineBreaks = _ref2.disableLineBreaks,
      unstableOnFocus = _ref2.unstableOnFocus,
      __unstableAllowPrefixTransformations = _ref2.__unstableAllowPrefixTransformations,
      __unstableMultilineRootTag = _ref2.__unstableMultilineRootTag,
      __unstableMobileNoFocusOnMount = _ref2.__unstableMobileNoFocusOnMount,
      deleteEnter = _ref2.deleteEnter,
      placeholderTextColor = _ref2.placeholderTextColor,
      textAlign = _ref2.textAlign,
      selectionColor = _ref2.selectionColor,
      tagsToEliminate = _ref2.tagsToEliminate,
      rootTagsToEliminate = _ref2.rootTagsToEliminate,
      disableEditingMenu = _ref2.disableEditingMenu,
      fontSize = _ref2.fontSize,
      fontFamily = _ref2.fontFamily,
      fontWeight = _ref2.fontWeight,
      fontStyle = _ref2.fontStyle,
      minWidth = _ref2.minWidth,
      maxWidth = _ref2.maxWidth,
      onBlur = _ref2.onBlur,
      setRef = _ref2.setRef,
      props = (0, _objectWithoutProperties2.default)(_ref2, ["children", "tagName", "value", "onChange", "isSelected", "multiline", "inlineToolbar", "wrapperClassName", "className", "autocompleters", "onReplace", "placeholder", "keepPlaceholderOnFocus", "allowedFormats", "formattingControls", "withoutInteractiveFormatting", "onRemove", "onMerge", "onSplit", "__unstableOnSplitAtEnd", "__unstableOnSplitMiddle", "identifier", "start", "reversed", "style", "preserveWhiteSpace", "__unstableEmbedURLOnPaste", "__unstableDisableFormats", "disableLineBreaks", "unstableOnFocus", "__unstableAllowPrefixTransformations", "__unstableMultilineRootTag", "__unstableMobileNoFocusOnMount", "deleteEnter", "placeholderTextColor", "textAlign", "selectionColor", "tagsToEliminate", "rootTagsToEliminate", "disableEditingMenu", "fontSize", "fontFamily", "fontWeight", "fontStyle", "minWidth", "maxWidth", "onBlur", "setRef"]);
  var instanceId = (0, _compose.useInstanceId)(RichTextWrapper);
  identifier = identifier || instanceId;
  var fallbackRef = (0, _element.useRef)();
  var ref = forwardedRef || fallbackRef;

  var _useBlockEditContext = (0, _blockEdit.useBlockEditContext)(),
      clientId = _useBlockEditContext.clientId,
      onCaretVerticalPositionChange = _useBlockEditContext.onCaretVerticalPositionChange,
      blockIsSelected = _useBlockEditContext.isSelected;

  var selector = function selector(select) {
    var _select = select('core/block-editor'),
        isCaretWithinFormattedText = _select.isCaretWithinFormattedText,
        getSelectionStart = _select.getSelectionStart,
        getSelectionEnd = _select.getSelectionEnd,
        getSettings = _select.getSettings,
        didAutomaticChange = _select.didAutomaticChange,
        __unstableGetBlockWithoutInnerBlocks = _select.__unstableGetBlockWithoutInnerBlocks,
        isMultiSelecting = _select.isMultiSelecting,
        hasMultiSelection = _select.hasMultiSelection;

    var selectionStart = getSelectionStart();
    var selectionEnd = getSelectionEnd();

    var _getSettings = getSettings(),
        undo = _getSettings.__experimentalUndo;

    var isSelected;

    if (originalIsSelected === undefined) {
      isSelected = selectionStart.clientId === clientId && selectionStart.attributeKey === identifier;
    } else if (originalIsSelected) {
      isSelected = selectionStart.clientId === clientId;
    }

    var extraProps = {};

    if (_element.Platform.OS === 'native') {
      // If the block of this RichText is unmodified then it's a candidate for replacing when adding a new block.
      // In order to fix https://github.com/wordpress-mobile/gutenberg-mobile/issues/1126, let's blur on unmount in that case.
      // This apparently assumes functionality the BlockHlder actually
      var block = clientId && __unstableGetBlockWithoutInnerBlocks(clientId);

      var _shouldBlurOnUnmount = block && isSelected && (0, _blocks.isUnmodifiedDefaultBlock)(block);

      extraProps = {
        shouldBlurOnUnmount: _shouldBlurOnUnmount
      };
    }

    return _objectSpread({
      isCaretWithinFormattedText: isCaretWithinFormattedText(),
      selectionStart: isSelected ? selectionStart.offset : undefined,
      selectionEnd: isSelected ? selectionEnd.offset : undefined,
      isSelected: isSelected,
      didAutomaticChange: didAutomaticChange(),
      disabled: isMultiSelecting() || hasMultiSelection(),
      undo: undo
    }, extraProps);
  }; // This selector must run on every render so the right selection state is
  // retreived from the store on merge.
  // To do: fix this somehow.


  var _useSelect = (0, _data.useSelect)(selector),
      isCaretWithinFormattedText = _useSelect.isCaretWithinFormattedText,
      selectionStart = _useSelect.selectionStart,
      selectionEnd = _useSelect.selectionEnd,
      isSelected = _useSelect.isSelected,
      didAutomaticChange = _useSelect.didAutomaticChange,
      disabled = _useSelect.disabled,
      undo = _useSelect.undo,
      shouldBlurOnUnmount = _useSelect.shouldBlurOnUnmount;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      __unstableMarkLastChangeAsPersistent = _useDispatch.__unstableMarkLastChangeAsPersistent,
      enterFormattedText = _useDispatch.enterFormattedText,
      exitFormattedText = _useDispatch.exitFormattedText,
      selectionChange = _useDispatch.selectionChange,
      __unstableMarkAutomaticChange = _useDispatch.__unstableMarkAutomaticChange;

  var multilineTag = getMultilineTag(multiline);
  var adjustedAllowedFormats = getAllowedFormats({
    allowedFormats: allowedFormats,
    formattingControls: formattingControls,
    disableFormats: disableFormats
  });
  var hasFormats = !adjustedAllowedFormats || adjustedAllowedFormats.length > 0;
  var adjustedValue = originalValue;
  var adjustedOnChange = originalOnChange; // Handle deprecated format.

  if (Array.isArray(originalValue)) {
    adjustedValue = _blocks.children.toHTML(originalValue);

    adjustedOnChange = function adjustedOnChange(newValue) {
      return originalOnChange(_blocks.children.fromDOM((0, _richText.__unstableCreateElement)(document, newValue).childNodes));
    };
  }

  var onSelectionChange = (0, _element.useCallback)(function (start, end) {
    selectionChange(clientId, identifier, start, end);
  }, [clientId, identifier]);
  var onDelete = (0, _element.useCallback)(function (_ref3) {
    var value = _ref3.value,
        isReverse = _ref3.isReverse;

    if (onMerge) {
      onMerge(!isReverse);
    } // Only handle remove on Backspace. This serves dual-purpose of being
    // an intentional user interaction distinguishing between Backspace and
    // Delete to remove the empty field, but also to avoid merge & remove
    // causing destruction of two fields (merge, then removed merged).


    if (onRemove && (0, _richText.isEmpty)(value) && isReverse) {
      onRemove(!isReverse);
    }
  }, [onMerge, onRemove]);
  /**
   * Signals to the RichText owner that the block can be replaced with two
   * blocks as a result of splitting the block by pressing enter, or with
   * blocks as a result of splitting the block by pasting block content in the
   * instance.
   *
   * @param  {Object} record       The rich text value to split.
   * @param  {Array}  pastedBlocks The pasted blocks to insert, if any.
   */

  var splitValue = (0, _element.useCallback)(function (record) {
    var pastedBlocks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];

    if (!onReplace || !onSplit) {
      return;
    }

    var blocks = [];

    var _split = (0, _richText.split)(record),
        _split2 = (0, _slicedToArray2.default)(_split, 2),
        before = _split2[0],
        after = _split2[1];

    var hasPastedBlocks = pastedBlocks.length > 0;
    var lastPastedBlockIndex = -1; // Create a block with the content before the caret if there's no pasted
    // blocks, or if there are pasted blocks and the value is not empty.
    // We do not want a leading empty block on paste, but we do if split
    // with e.g. the enter key.

    if (!hasPastedBlocks || !(0, _richText.isEmpty)(before)) {
      blocks.push(onSplit((0, _richText.toHTMLString)({
        value: before,
        multilineTag: multilineTag
      })));
      lastPastedBlockIndex += 1;
    }

    if (hasPastedBlocks) {
      blocks.push.apply(blocks, (0, _toConsumableArray2.default)(pastedBlocks));
      lastPastedBlockIndex += pastedBlocks.length;
    } else if (onSplitMiddle) {
      blocks.push(onSplitMiddle());
    } // If there's pasted blocks, append a block with non empty content
    /// after the caret. Otherwise, do append an empty block if there
    // is no `onSplitMiddle` prop, but if there is and the content is
    // empty, the middle block is enough to set focus in.


    if (hasPastedBlocks ? !(0, _richText.isEmpty)(after) : !onSplitMiddle || !(0, _richText.isEmpty)(after)) {
      blocks.push(onSplit((0, _richText.toHTMLString)({
        value: after,
        multilineTag: multilineTag
      })));
    } // If there are pasted blocks, set the selection to the last one.
    // Otherwise, set the selection to the second block.


    var indexToSelect = hasPastedBlocks ? lastPastedBlockIndex : 1; // If there are pasted blocks, move the caret to the end of the selected block
    // Otherwise, retain the default value.

    var initialPosition = hasPastedBlocks ? -1 : null;
    onReplace(blocks, indexToSelect, initialPosition);
  }, [onReplace, onSplit, multilineTag, onSplitMiddle]);
  var onEnter = (0, _element.useCallback)(function (_ref4) {
    var value = _ref4.value,
        onChange = _ref4.onChange,
        shiftKey = _ref4.shiftKey;
    var canSplit = onReplace && onSplit;

    if (onReplace) {
      var transforms = (0, _blocks.getBlockTransforms)('from').filter(function (_ref5) {
        var type = _ref5.type;
        return type === 'enter';
      });
      var transformation = (0, _blocks.findTransform)(transforms, function (item) {
        return item.regExp.test(value.text);
      });

      if (transformation) {
        onReplace([transformation.transform({
          content: value.text
        })]);

        __unstableMarkAutomaticChange();
      }
    }

    if (multiline) {
      if (shiftKey) {
        if (!disableLineBreaks) {
          onChange((0, _richText.insert)(value, '\n'));
        }
      } else if (canSplit && (0, _richText.__unstableIsEmptyLine)(value)) {
        splitValue(value);
      } else {
        onChange((0, _richText.__unstableInsertLineSeparator)(value));
      }
    } else {
      var text = value.text,
          start = value.start,
          end = value.end;
      var canSplitAtEnd = onSplitAtEnd && start === end && end === text.length;

      if (shiftKey || !canSplit && !canSplitAtEnd) {
        if (!disableLineBreaks) {
          onChange((0, _richText.insert)(value, '\n'));
        }
      } else if (!canSplit && canSplitAtEnd) {
        onSplitAtEnd();
      } else if (canSplit) {
        splitValue(value);
      }
    }
  }, [onReplace, onSplit, __unstableMarkAutomaticChange, multiline, splitValue, onSplitAtEnd]);
  var onPaste = (0, _element.useCallback)(function (_ref6) {
    var value = _ref6.value,
        onChange = _ref6.onChange,
        html = _ref6.html,
        plainText = _ref6.plainText,
        files = _ref6.files,
        activeFormats = _ref6.activeFormats;

    // Only process file if no HTML is present.
    // Note: a pasted file may have the URL as plain text.
    if (files && files.length && !html) {
      var _content = (0, _blocks.pasteHandler)({
        HTML: (0, _filePasteHandler.filePasteHandler)(files),
        mode: 'BLOCKS',
        tagName: tagName
      }); // Allows us to ask for this information when we get a report.
      // eslint-disable-next-line no-console


      window.console.log('Received items:\n\n', files);

      if (onReplace && (0, _richText.isEmpty)(value)) {
        onReplace(_content);
      } else {
        splitValue(value, _content);
      }

      return;
    }

    var mode = onReplace && onSplit ? 'AUTO' : 'INLINE'; // Force the blocks mode when the user is pasting
    // on a new line & the content resembles a shortcode.
    // Otherwise it's going to be detected as inline
    // and the shortcode won't be replaced.

    if (mode === 'AUTO' && (0, _richText.isEmpty)(value) && isShortcode(plainText)) {
      mode = 'BLOCKS';
    }

    if (__unstableEmbedURLOnPaste && (0, _richText.isEmpty)(value) && (0, _url.isURL)(plainText.trim())) {
      mode = 'BLOCKS';
    }

    var content = (0, _blocks.pasteHandler)({
      HTML: html,
      plainText: plainText,
      mode: mode,
      tagName: tagName
    });

    if (typeof content === 'string') {
      var valueToInsert = (0, _richText.create)({
        html: content
      }); // If there are active formats, merge them with the pasted formats.

      if (activeFormats.length) {
        var index = valueToInsert.formats.length;

        while (index--) {
          valueToInsert.formats[index] = [].concat((0, _toConsumableArray2.default)(activeFormats), (0, _toConsumableArray2.default)(valueToInsert.formats[index] || []));
        }
      } // If the content should be multiline, we should process text
      // separated by a line break as separate lines.


      if (multiline) {
        valueToInsert = (0, _richText.replace)(valueToInsert, /\n+/g, _richText.__UNSTABLE_LINE_SEPARATOR);
      }

      onChange((0, _richText.insert)(value, valueToInsert));
    } else if (content.length > 0) {
      if (onReplace && (0, _richText.isEmpty)(value)) {
        onReplace(content, content.length - 1, -1);
      } else {
        splitValue(value, content);
      }
    }
  }, [tagName, onReplace, onSplit, splitValue, __unstableEmbedURLOnPaste, multiline]);
  var inputRule = (0, _element.useCallback)(function (value, valueToFormat) {
    if (!onReplace) {
      return;
    }

    var start = value.start,
        text = value.text;
    var characterBefore = text.slice(start - 1, start); // The character right before the caret must be a plain space.

    if (characterBefore !== ' ') {
      return;
    }

    var trimmedTextBefore = text.slice(0, start).trim();
    var prefixTransforms = (0, _blocks.getBlockTransforms)('from').filter(function (_ref7) {
      var type = _ref7.type;
      return type === 'prefix';
    });
    var transformation = (0, _blocks.findTransform)(prefixTransforms, function (_ref8) {
      var prefix = _ref8.prefix;
      return trimmedTextBefore === prefix;
    });

    if (!transformation) {
      return;
    }

    var content = valueToFormat((0, _richText.slice)(value, start, text.length));
    var block = transformation.transform(content);
    onReplace([block]);

    __unstableMarkAutomaticChange();
  }, [onReplace, __unstableMarkAutomaticChange]);
  var content = (0, _element.createElement)(_richText.__experimentalRichText, {
    clientId: clientId,
    identifier: identifier,
    ref: ref,
    value: adjustedValue,
    onChange: adjustedOnChange,
    selectionStart: selectionStart,
    selectionEnd: selectionEnd,
    onSelectionChange: onSelectionChange,
    tagName: tagName,
    className: (0, _classnames.default)(classes, className, {
      'keep-placeholder-on-focus': keepPlaceholderOnFocus
    }),
    placeholder: placeholder,
    allowedFormats: adjustedAllowedFormats,
    withoutInteractiveFormatting: withoutInteractiveFormatting,
    onEnter: onEnter,
    onDelete: onDelete,
    onPaste: onPaste,
    __unstableIsSelected: isSelected,
    __unstableInputRule: inputRule,
    __unstableMultilineTag: multilineTag,
    __unstableIsCaretWithinFormattedText: isCaretWithinFormattedText,
    __unstableOnEnterFormattedText: enterFormattedText,
    __unstableOnExitFormattedText: exitFormattedText,
    __unstableOnCreateUndoLevel: __unstableMarkLastChangeAsPersistent,
    __unstableMarkAutomaticChange: __unstableMarkAutomaticChange,
    __unstableDidAutomaticChange: didAutomaticChange,
    __unstableUndo: undo,
    __unstableDisableFormats: disableFormats,
    style: style,
    preserveWhiteSpace: preserveWhiteSpace,
    disabled: disabled,
    start: startAttr,
    reversed: reversed,
    unstableOnFocus: unstableOnFocus,
    __unstableAllowPrefixTransformations: __unstableAllowPrefixTransformations,
    __unstableMultilineRootTag: __unstableMultilineRootTag // Native props.
    ,
    onCaretVerticalPositionChange: onCaretVerticalPositionChange,
    blockIsSelected: originalIsSelected !== undefined ? originalIsSelected : blockIsSelected,
    shouldBlurOnUnmount: shouldBlurOnUnmount,
    __unstableMobileNoFocusOnMount: __unstableMobileNoFocusOnMount,
    deleteEnter: deleteEnter,
    placeholderTextColor: placeholderTextColor,
    textAlign: textAlign,
    selectionColor: selectionColor,
    tagsToEliminate: tagsToEliminate,
    rootTagsToEliminate: rootTagsToEliminate,
    disableEditingMenu: disableEditingMenu,
    fontSize: fontSize,
    fontFamily: fontFamily,
    fontWeight: fontWeight,
    fontStyle: fontStyle,
    minWidth: minWidth,
    maxWidth: maxWidth,
    onBlur: onBlur,
    setRef: setRef // Destructuring the id prop before { ...props } doesn't work
    // correctly on web https://github.com/WordPress/gutenberg/pull/25624
    ,
    id: props.id
  }, function (_ref9) {
    var nestedIsSelected = _ref9.isSelected,
        value = _ref9.value,
        onChange = _ref9.onChange,
        onFocus = _ref9.onFocus,
        editableProps = _ref9.editableProps,
        TagName = _ref9.editableTagName;
    return (0, _element.createElement)(_element.Fragment, null, children && children({
      value: value,
      onChange: onChange,
      onFocus: onFocus
    }), nestedIsSelected && hasFormats && (0, _element.createElement)(_formatToolbarContainer.default, {
      inline: inlineToolbar,
      anchorRef: ref.current
    }), nestedIsSelected && (0, _element.createElement)(_removeBrowserShortcuts.RemoveBrowserShortcuts, null), (0, _element.createElement)(_autocomplete.default, {
      onReplace: onReplace,
      completers: autocompleters,
      record: value,
      onChange: onChange,
      isSelected: nestedIsSelected
    }, function (_ref10) {
      var listBoxId = _ref10.listBoxId,
          activeId = _ref10.activeId,
          _onKeyDown = _ref10.onKeyDown;
      return (0, _element.createElement)(TagName, (0, _extends2.default)({}, editableProps, props, {
        "aria-autocomplete": listBoxId ? 'list' : undefined,
        "aria-owns": listBoxId,
        "aria-activedescendant": activeId,
        start: startAttr,
        reversed: reversed,
        onKeyDown: function onKeyDown(event) {
          _onKeyDown(event);

          editableProps.onKeyDown(event);
        }
      }));
    }));
  });

  if (!wrapperClassName) {
    return content;
  }

  (0, _deprecated.default)('wp.blockEditor.RichText wrapperClassName prop', {
    alternative: 'className prop or create your own wrapper div'
  });
  return (0, _element.createElement)("div", {
    className: (0, _classnames.default)(wrapperClasses, wrapperClassName)
  }, content);
}

var ForwardedRichTextContainer = (0, _element.forwardRef)(RichTextWrapper);

ForwardedRichTextContainer.Content = function (_ref11) {
  var value = _ref11.value,
      Tag = _ref11.tagName,
      multiline = _ref11.multiline,
      props = (0, _objectWithoutProperties2.default)(_ref11, ["value", "tagName", "multiline"]);

  // Handle deprecated `children` and `node` sources.
  if (Array.isArray(value)) {
    value = _blocks.children.toHTML(value);
  }

  var MultilineTag = getMultilineTag(multiline);

  if (!value && MultilineTag) {
    value = "<".concat(MultilineTag, "></").concat(MultilineTag, ">");
  }

  var content = (0, _element.createElement)(_element.RawHTML, null, value);

  if (Tag) {
    return (0, _element.createElement)(Tag, (0, _lodash.omit)(props, ['format']), content);
  }

  return content;
};

ForwardedRichTextContainer.isEmpty = function (value) {
  return !value || value.length === 0;
};

ForwardedRichTextContainer.Content.defaultProps = {
  format: 'string',
  value: ''
};
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/rich-text/README.md
 */

var _default = ForwardedRichTextContainer;
exports.default = _default;
//# sourceMappingURL=index.js.map