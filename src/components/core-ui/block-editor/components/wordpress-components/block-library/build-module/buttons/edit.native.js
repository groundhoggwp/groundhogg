import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { InnerBlocks, __experimentalAlignmentHookSettingsProvider as AlignmentHookSettingsProvider } from '@wordpress/block-editor';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose, useResizeObserver } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { useState, useEffect, useRef } from '@wordpress/element';
import { debounce } from 'lodash';
/**
 * Internal dependencies
 */

import { name as buttonBlockName } from '../button/';
import styles from './editor.scss';
var ALLOWED_BLOCKS = [buttonBlockName];
var BUTTONS_TEMPLATE = [['core/button']];

function ButtonsEdit(_ref) {
  var isSelected = _ref.isSelected,
      attributes = _ref.attributes,
      onDelete = _ref.onDelete,
      onAddNextButton = _ref.onAddNextButton,
      shouldDelete = _ref.shouldDelete,
      isInnerButtonSelected = _ref.isInnerButtonSelected;
  var align = attributes.align;

  var _useResizeObserver = useResizeObserver(),
      _useResizeObserver2 = _slicedToArray(_useResizeObserver, 2),
      resizeObserver = _useResizeObserver2[0],
      sizes = _useResizeObserver2[1];

  var _useState = useState(0),
      _useState2 = _slicedToArray(_useState, 2),
      maxWidth = _useState2[0],
      setMaxWidth = _useState2[1];

  var shouldRenderFooterAppender = isSelected || isInnerButtonSelected;
  var spacing = styles.spacing.marginLeft;
  useEffect(function () {
    var margins = 2 * styles.parent.marginRight;

    var _ref2 = sizes || {},
        width = _ref2.width;

    if (width) {
      setMaxWidth(width - margins);
    }
  }, [sizes]);
  var debounceAddNextButton = debounce(onAddNextButton, 200);
  var renderFooterAppender = useRef(function () {
    return createElement(View, {
      style: styles.appenderContainer
    }, createElement(InnerBlocks.ButtonBlockAppender, {
      isFloating: true,
      onAddBlock: debounceAddNextButton
    }));
  }); // Inside buttons block alignment options are not supported.

  var alignmentHooksSetting = {
    isEmbedButton: true
  };
  return createElement(AlignmentHookSettingsProvider, {
    value: alignmentHooksSetting
  }, resizeObserver, createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    template: BUTTONS_TEMPLATE,
    renderFooterAppender: shouldRenderFooterAppender && renderFooterAppender.current,
    orientation: "horizontal",
    horizontalAlignment: align,
    onDeleteBlock: shouldDelete ? onDelete : undefined,
    onAddBlock: debounceAddNextButton,
    parentWidth: maxWidth,
    marginHorizontal: spacing,
    marginVertical: spacing
  }));
}

export default compose(withSelect(function (select, _ref3) {
  var clientId = _ref3.clientId;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockParents = _select.getBlockParents,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockParents = getBlockParents(selectedBlockClientId, true);
  return {
    // The purpose of `shouldDelete` check is giving the ability to pass to
    // mobile toolbar function called `onDelete` which removes the whole
    // `Buttons` container along with the last inner button when
    // there is exactly one button.
    shouldDelete: getBlockCount(clientId) === 1,
    isInnerButtonSelected: selectedBlockParents[0] === clientId
  };
}), withDispatch(function (dispatch, _ref4, registry) {
  var clientId = _ref4.clientId;

  var _dispatch = dispatch('core/block-editor'),
      selectBlock = _dispatch.selectBlock,
      removeBlock = _dispatch.removeBlock,
      insertBlock = _dispatch.insertBlock;

  var _registry$select = registry.select('core/block-editor'),
      getBlockOrder = _registry$select.getBlockOrder;

  return {
    // The purpose of `onAddNextButton` is giving the ability to automatically
    // adding `Button` inside `Buttons` block on the appender press event.
    onAddNextButton: function onAddNextButton(selectedId) {
      var order = getBlockOrder(clientId);
      var selectedButtonIndex = order.findIndex(function (i) {
        return i === selectedId;
      });
      var index = selectedButtonIndex === -1 ? order.length + 1 : selectedButtonIndex;
      var insertedBlock = createBlock('core/button');
      insertBlock(insertedBlock, index, clientId);
      selectBlock(insertedBlock.clientId);
    },
    onDelete: function onDelete() {
      removeBlock(clientId);
    }
  };
}))(ButtonsEdit);
//# sourceMappingURL=edit.native.js.map