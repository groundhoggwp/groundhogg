import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import BlockMover from '../block-mover';
import BlockActionsMenu from './block-actions-menu';
import { BlockSettingsButton } from '../block-settings'; // Defined breakpoints are used to get a point when
// `settings` and `mover` controls should be wrapped into `BlockActionsMenu`
// and accessed through `BottomSheet`(Android)/`ActionSheet`(iOS).

var BREAKPOINTS = {
  wrapSettings: 65,
  wrapMover: 150
};

var BlockMobileToolbar = function BlockMobileToolbar(_ref) {
  var clientId = _ref.clientId,
      onDelete = _ref.onDelete,
      isStackedHorizontally = _ref.isStackedHorizontally,
      blockWidth = _ref.blockWidth,
      anchorNodeRef = _ref.anchorNodeRef,
      isFullWidth = _ref.isFullWidth;

  var _useState = useState(null),
      _useState2 = _slicedToArray(_useState, 2),
      fillsLength = _useState2[0],
      setFillsLength = _useState2[1];

  var wrapBlockSettings = blockWidth < BREAKPOINTS.wrapSettings;
  var wrapBlockMover = blockWidth <= BREAKPOINTS.wrapMover;
  return createElement(View, {
    style: [styles.toolbar, isFullWidth && styles.toolbarFullWidth]
  }, !wrapBlockMover && createElement(BlockMover, {
    clientIds: [clientId],
    isStackedHorizontally: isStackedHorizontally
  }), createElement(View, {
    style: styles.spacer
  }), createElement(BlockSettingsButton.Slot, null, function () {
    var fills = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [null];
    setFillsLength(fills.length);
    return wrapBlockSettings ? null : fills[0];
  }), createElement(BlockActionsMenu, {
    clientIds: [clientId],
    wrapBlockMover: wrapBlockMover,
    wrapBlockSettings: wrapBlockSettings && fillsLength,
    isStackedHorizontally: isStackedHorizontally,
    onDelete: onDelete,
    anchorNodeRef: anchorNodeRef
  }));
};

export default compose(withSelect(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex;

  return {
    order: getBlockIndex(clientId)
  };
}), withDispatch(function (dispatch, _ref3) {
  var clientId = _ref3.clientId,
      rootClientId = _ref3.rootClientId,
      onDelete = _ref3.onDelete;

  var _dispatch = dispatch('core/block-editor'),
      removeBlock = _dispatch.removeBlock;

  return {
    onDelete: onDelete || function () {
      return removeBlock(clientId, rootClientId);
    }
  };
}))(BlockMobileToolbar);
//# sourceMappingURL=index.native.js.map