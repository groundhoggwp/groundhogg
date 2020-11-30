import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { RichText, BlockControls, RichTextShortcut, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { ToolbarGroup } from '@wordpress/components';
import { __unstableCanIndentListItems as canIndentListItems, __unstableCanOutdentListItems as canOutdentListItems, __unstableIndentListItems as indentListItems, __unstableOutdentListItems as outdentListItems, __unstableChangeListType as changeListType, __unstableIsListRootSelected as isListRootSelected, __unstableIsActiveListType as isActiveListType } from '@wordpress/rich-text';
import { formatListBullets, formatListBulletsRTL, formatListNumbered, formatListNumberedRTL, formatIndent, formatIndentRTL, formatOutdent, formatOutdentRTL } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { name } from './';
import OrderedListSettings from './ordered-list-settings';
export default function ListEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      mergeBlocks = _ref.mergeBlocks,
      onReplace = _ref.onReplace,
      isSelected = _ref.isSelected;
  var ordered = attributes.ordered,
      values = attributes.values,
      type = attributes.type,
      reversed = attributes.reversed,
      start = attributes.start;
  var tagName = ordered ? 'ol' : 'ul';
  var isRTL = useSelect(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);

  var controls = function controls(_ref2) {
    var value = _ref2.value,
        onChange = _ref2.onChange,
        onFocus = _ref2.onFocus;
    return createElement(Fragment, null, isSelected && createElement(Fragment, null, createElement(RichTextShortcut, {
      type: "primary",
      character: "[",
      onUse: function onUse() {
        onChange(outdentListItems(value));
      }
    }), createElement(RichTextShortcut, {
      type: "primary",
      character: "]",
      onUse: function onUse() {
        onChange(indentListItems(value, {
          type: tagName
        }));
      }
    }), createElement(RichTextShortcut, {
      type: "primary",
      character: "m",
      onUse: function onUse() {
        onChange(indentListItems(value, {
          type: tagName
        }));
      }
    }), createElement(RichTextShortcut, {
      type: "primaryShift",
      character: "m",
      onUse: function onUse() {
        onChange(outdentListItems(value));
      }
    })), createElement(BlockControls, null, createElement(ToolbarGroup, {
      controls: [{
        icon: isRTL ? formatListBulletsRTL : formatListBullets,
        title: __('Convert to unordered list'),
        isActive: isActiveListType(value, 'ul', tagName),
        onClick: function onClick() {
          onChange(changeListType(value, {
            type: 'ul'
          }));
          onFocus();

          if (isListRootSelected(value)) {
            setAttributes({
              ordered: false
            });
          }
        }
      }, {
        icon: isRTL ? formatListNumberedRTL : formatListNumbered,
        title: __('Convert to ordered list'),
        isActive: isActiveListType(value, 'ol', tagName),
        onClick: function onClick() {
          onChange(changeListType(value, {
            type: 'ol'
          }));
          onFocus();

          if (isListRootSelected(value)) {
            setAttributes({
              ordered: true
            });
          }
        }
      }, {
        icon: isRTL ? formatOutdentRTL : formatOutdent,
        title: __('Outdent list item'),
        shortcut: _x('Backspace', 'keyboard key'),
        isDisabled: !canOutdentListItems(value),
        onClick: function onClick() {
          onChange(outdentListItems(value));
          onFocus();
        }
      }, {
        icon: isRTL ? formatIndentRTL : formatIndent,
        title: __('Indent list item'),
        shortcut: _x('Space', 'keyboard key'),
        isDisabled: !canIndentListItems(value),
        onClick: function onClick() {
          onChange(indentListItems(value, {
            type: tagName
          }));
          onFocus();
        }
      }]
    })));
  };

  var blockWrapperProps = useBlockWrapperProps();
  return createElement(Fragment, null, createElement(RichText, _extends({
    identifier: "values",
    multiline: "li",
    __unstableMultilineRootTag: tagName,
    tagName: tagName,
    onChange: function onChange(nextValues) {
      return setAttributes({
        values: nextValues
      });
    },
    value: values,
    placeholder: __('Write list…'),
    onMerge: mergeBlocks,
    onSplit: function onSplit(value) {
      return createBlock(name, _objectSpread(_objectSpread({}, attributes), {}, {
        values: value
      }));
    },
    __unstableOnSplitMiddle: function __unstableOnSplitMiddle() {
      return createBlock('core/paragraph');
    },
    onReplace: onReplace,
    onRemove: function onRemove() {
      return onReplace([]);
    },
    start: start,
    reversed: reversed,
    type: type
  }, blockWrapperProps), controls), ordered && createElement(OrderedListSettings, {
    setAttributes: setAttributes,
    ordered: ordered,
    reversed: reversed,
    start: start
  }));
}
//# sourceMappingURL=edit.js.map