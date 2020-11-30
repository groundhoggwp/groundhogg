import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { InnerBlocks } from '@wordpress/block-editor';
import { withDispatch, withSelect } from '@wordpress/data';
import { useRef, useEffect, useState } from '@wordpress/element';
import { compose, usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import styles from './editor.scss';
import variations from '../social-link/variations';
var ALLOWED_BLOCKS = variations.map(function (v) {
  return "core/social-link-".concat(v.name);
}); // Template contains the links that show when start.

var TEMPLATE = [['core/social-link-wordpress', {
  service: 'wordpress',
  url: 'https://wordpress.org'
}], ['core/social-link-facebook', {
  service: 'facebook'
}], ['core/social-link-twitter', {
  service: 'twitter'
}], ['core/social-link-instagram', {
  service: 'instagram'
}]];

function SocialLinksEdit(_ref) {
  var shouldDelete = _ref.shouldDelete,
      onDelete = _ref.onDelete,
      isSelected = _ref.isSelected,
      isInnerIconSelected = _ref.isInnerIconSelected,
      innerBlocks = _ref.innerBlocks,
      attributes = _ref.attributes,
      activeInnerBlocks = _ref.activeInnerBlocks,
      getBlock = _ref.getBlock;

  var _useState = useState(true),
      _useState2 = _slicedToArray(_useState, 2),
      initialCreation = _useState2[0],
      setInitialCreation = _useState2[1];

  var shouldRenderFooterAppender = isSelected || isInnerIconSelected;
  var align = attributes.align;
  var spacing = styles.spacing.marginLeft;
  useEffect(function () {
    if (!shouldRenderFooterAppender) {
      setInitialCreation(false);
    }
  }, [shouldRenderFooterAppender]);
  var renderFooterAppender = useRef(function () {
    return createElement(View, null, createElement(InnerBlocks.ButtonBlockAppender, {
      isFloating: true
    }));
  });
  var placeholderStyle = usePreferredColorSchemeStyle(styles.placeholder, styles.placeholderDark);

  function renderPlaceholder() {
    return _toConsumableArray(new Array(innerBlocks.length || 1)).map(function (_, index) {
      return createElement(View, {
        style: placeholderStyle,
        key: index
      });
    });
  }

  function filterInnerBlocks(blockIds) {
    return blockIds.filter(function (blockId) {
      return getBlock(blockId).attributes.url;
    });
  }

  if (!shouldRenderFooterAppender && activeInnerBlocks.length === 0) {
    return createElement(View, {
      style: styles.placeholderWrapper
    }, renderPlaceholder());
  }

  return createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    templateLock: false,
    template: initialCreation && TEMPLATE,
    renderFooterAppender: shouldRenderFooterAppender && renderFooterAppender.current,
    orientation: 'horizontal',
    onDeleteBlock: shouldDelete ? onDelete : undefined,
    marginVertical: spacing,
    marginHorizontal: spacing,
    horizontalAlignment: align,
    filterInnerBlocks: !shouldRenderFooterAppender && filterInnerBlocks
  });
}

export default compose(withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockCount = _select.getBlockCount,
      getBlockParents = _select.getBlockParents,
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlocks = _select.getBlocks,
      getBlock = _select.getBlock;

  var selectedBlockClientId = getSelectedBlockClientId();
  var selectedBlockParents = getBlockParents(selectedBlockClientId, true);
  var innerBlocks = getBlocks(clientId);
  var activeInnerBlocks = innerBlocks.filter(function (block) {
    var _block$attributes;

    return (_block$attributes = block.attributes) === null || _block$attributes === void 0 ? void 0 : _block$attributes.url;
  });
  return {
    shouldDelete: getBlockCount(clientId) === 1,
    isInnerIconSelected: selectedBlockParents[0] === clientId,
    innerBlocks: innerBlocks,
    activeInnerBlocks: activeInnerBlocks,
    getBlock: getBlock
  };
}), withDispatch(function (dispatch, _ref3) {
  var clientId = _ref3.clientId;

  var _dispatch = dispatch('core/block-editor'),
      removeBlock = _dispatch.removeBlock;

  return {
    onDelete: function onDelete() {
      removeBlock(clientId, false);
    }
  };
}))(SocialLinksEdit);
//# sourceMappingURL=edit.native.js.map