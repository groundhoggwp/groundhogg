import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __, _x } from '@wordpress/i18n';
import { PanelBody, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { AlignmentToolbar, BlockControls, InspectorControls, RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps, getFontSize, __experimentalUseEditorFeature as useEditorFeature } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { formatLtr } from '@wordpress/icons';

function getComputedStyle(node, pseudo) {
  return node.ownerDocument.defaultView.getComputedStyle(node, pseudo);
}

var querySelector = window.document.querySelector.bind(document);
var name = 'core/paragraph';
var PARAGRAPH_DROP_CAP_SELECTOR = 'p.has-drop-cap';

function ParagraphRTLToolbar(_ref) {
  var direction = _ref.direction,
      setDirection = _ref.setDirection;
  var isRTL = useSelect(function (select) {
    return !!select('core/block-editor').getSettings().isRTL;
  }, []);
  return isRTL && createElement(ToolbarGroup, {
    controls: [{
      icon: formatLtr,
      title: _x('Left to right', 'editor button'),
      isActive: direction === 'ltr',
      onClick: function onClick() {
        setDirection(direction === 'ltr' ? undefined : 'ltr');
      }
    }]
  });
}

function useDropCap(isDropCap, fontSize, styleFontSize) {
  var isDisabled = !useEditorFeature('typography.dropCap');

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      minimumHeight = _useState2[0],
      setMinimumHeight = _useState2[1];

  var _useSelect = useSelect(function (select) {
    return select('core/block-editor').getSettings();
  }),
      fontSizes = _useSelect.fontSizes;

  var fontSizeObject = getFontSize(fontSizes, fontSize, styleFontSize);
  useEffect(function () {
    if (isDisabled) {
      return;
    }

    var element = querySelector(PARAGRAPH_DROP_CAP_SELECTOR);

    if (isDropCap && element) {
      setMinimumHeight(getComputedStyle(element, 'first-letter').lineHeight);
    } else if (minimumHeight) {
      setMinimumHeight(undefined);
    }
  }, [isDisabled, isDropCap, minimumHeight, setMinimumHeight, fontSizeObject.size]);
  return [!isDisabled, minimumHeight];
}

function ParagraphBlock(_ref2) {
  var attributes = _ref2.attributes,
      mergeBlocks = _ref2.mergeBlocks,
      onReplace = _ref2.onReplace,
      onRemove = _ref2.onRemove,
      setAttributes = _ref2.setAttributes;
  var align = attributes.align,
      content = attributes.content,
      direction = attributes.direction,
      dropCap = attributes.dropCap,
      placeholder = attributes.placeholder,
      fontSize = attributes.fontSize,
      style = attributes.style;

  var _useDropCap = useDropCap(dropCap, fontSize, style === null || style === void 0 ? void 0 : style.fontSize),
      _useDropCap2 = _slicedToArray(_useDropCap, 2),
      isDropCapEnabled = _useDropCap2[0],
      dropCapMinimumHeight = _useDropCap2[1];

  var styles = {
    direction: direction,
    minHeight: dropCapMinimumHeight
  };
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({
      'has-drop-cap': dropCap
    }, "has-text-align-".concat(align), align)),
    style: styles
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: align,
    onChange: function onChange(newAlign) {
      return setAttributes({
        align: newAlign
      });
    }
  }), createElement(ParagraphRTLToolbar, {
    direction: direction,
    setDirection: function setDirection(newDirection) {
      return setAttributes({
        direction: newDirection
      });
    }
  })), createElement(InspectorControls, null, isDropCapEnabled && createElement(PanelBody, {
    title: __('Text settings')
  }, createElement(ToggleControl, {
    label: __('Drop cap'),
    checked: !!dropCap,
    onChange: function onChange() {
      return setAttributes({
        dropCap: !dropCap
      });
    },
    help: dropCap ? __('Showing large initial letter.') : __('Toggle to show a large initial letter.')
  }))), createElement(RichText, _extends({
    identifier: "content",
    tagName: "p"
  }, blockWrapperProps, {
    value: content,
    onChange: function onChange(newContent) {
      return setAttributes({
        content: newContent
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
    onRemove: onRemove,
    "aria-label": content ? __('Paragraph block') : __('Empty block; start writing or type forward slash to choose a block'),
    placeholder: placeholder || __('Start writing or type / to choose a block'),
    __unstableEmbedURLOnPaste: true,
    __unstableAllowPrefixTransformations: true
  })));
}

export default ParagraphBlock;
//# sourceMappingURL=edit.js.map