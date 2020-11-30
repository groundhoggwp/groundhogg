import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withSelect } from '@wordpress/data';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { InnerBlocks, withColors } from '@wordpress/block-editor';
import { useCallback } from '@wordpress/element';
import { WIDE_ALIGNMENTS } from '@wordpress/components';
/**
 * Internal dependencies
 */

import styles from './editor.scss';

function GroupEdit(_ref) {
  var attributes = _ref.attributes,
      hasInnerBlocks = _ref.hasInnerBlocks,
      isSelected = _ref.isSelected,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme;
  var align = attributes.align;
  var isFullWidth = align === WIDE_ALIGNMENTS.alignments.full;
  var renderAppender = useCallback(function () {
    return createElement(View, {
      style: [isFullWidth && hasInnerBlocks && styles.fullWidthAppender]
    }, createElement(InnerBlocks.ButtonBlockAppender, null));
  }, [align, hasInnerBlocks]);

  if (!isSelected && !hasInnerBlocks) {
    return createElement(View, {
      style: [getStylesFromColorScheme(styles.groupPlaceholder, styles.groupPlaceholderDark), !hasInnerBlocks && _objectSpread(_objectSpread({}, styles.marginVerticalDense), styles.marginHorizontalNone)]
    });
  }

  return createElement(View, {
    style: [isSelected && hasInnerBlocks && styles.innerBlocks, isSelected && !hasInnerBlocks && isFullWidth && styles.fullWidth]
  }, createElement(InnerBlocks, {
    renderAppender: isSelected && renderAppender
  }));
}

export default compose([withColors('backgroundColor'), withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock;

  var block = getBlock(clientId);
  return {
    hasInnerBlocks: !!(block && block.innerBlocks.length)
  };
}), withPreferredColorScheme])(GroupEdit);
//# sourceMappingURL=edit.native.js.map