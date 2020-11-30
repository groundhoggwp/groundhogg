import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { AlignmentToolbar, BlockControls, RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
var name = 'core/paragraph';

function ParagraphBlock(_ref) {
  var attributes = _ref.attributes,
      mergeBlocks = _ref.mergeBlocks,
      onReplace = _ref.onReplace,
      setAttributes = _ref.setAttributes,
      mergedStyle = _ref.mergedStyle,
      style = _ref.style;
  var isRTL = useSelect(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);
  var align = attributes.align,
      content = attributes.content,
      placeholder = attributes.placeholder;

  var styles = _objectSpread(_objectSpread({}, mergedStyle), style);

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: align,
    isRTL: isRTL,
    onChange: function onChange(nextAlign) {
      setAttributes({
        align: nextAlign
      });
    }
  })), createElement(RichText, {
    identifier: "content",
    tagName: "p",
    value: content,
    deleteEnter: true,
    style: styles,
    onChange: function onChange(nextContent) {
      setAttributes({
        content: nextContent
      });
    },
    onSplit: function onSplit(value) {
      if (!value) {
        return createBlock(name);
      }

      return createBlock(name, _objectSpread(_objectSpread({}, attributes), {}, {
        content: value
      }));
    },
    onMerge: mergeBlocks,
    onReplace: onReplace,
    onRemove: onReplace ? function () {
      return onReplace([]);
    } : undefined,
    placeholder: placeholder || __('Start writing…'),
    textAlign: align
  }));
}

export default ParagraphBlock;
//# sourceMappingURL=edit.native.js.map