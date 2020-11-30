import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { first, last, partial, castArray } from 'lodash';
/**
 * WordPress dependencies
 */

import { ToolbarButton } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { withInstanceId, compose } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import { getMoversSetup } from './mover-description';

var BlockMover = function BlockMover(_ref) {
  var isFirst = _ref.isFirst,
      isLast = _ref.isLast,
      isLocked = _ref.isLocked,
      onMoveDown = _ref.onMoveDown,
      onMoveUp = _ref.onMoveUp,
      firstIndex = _ref.firstIndex,
      rootClientId = _ref.rootClientId,
      isStackedHorizontally = _ref.isStackedHorizontally;

  var _getMoversSetup = getMoversSetup(isStackedHorizontally, {
    firstIndex: firstIndex
  }),
      _getMoversSetup$descr = _getMoversSetup.description,
      backwardButtonHint = _getMoversSetup$descr.backwardButtonHint,
      forwardButtonHint = _getMoversSetup$descr.forwardButtonHint,
      firstBlockTitle = _getMoversSetup$descr.firstBlockTitle,
      lastBlockTitle = _getMoversSetup$descr.lastBlockTitle,
      _getMoversSetup$icon = _getMoversSetup.icon,
      backwardButtonIcon = _getMoversSetup$icon.backward,
      forwardButtonIcon = _getMoversSetup$icon.forward,
      _getMoversSetup$title = _getMoversSetup.title,
      backwardButtonTitle = _getMoversSetup$title.backward,
      forwardButtonTitle = _getMoversSetup$title.forward;

  if (isLocked || isFirst && isLast && !rootClientId) {
    return null;
  }

  return createElement(Fragment, null, createElement(ToolbarButton, {
    title: !isFirst ? backwardButtonTitle : firstBlockTitle,
    isDisabled: isFirst,
    onClick: onMoveUp,
    icon: backwardButtonIcon,
    extraProps: {
      hint: backwardButtonHint
    }
  }), createElement(ToolbarButton, {
    title: !isLast ? forwardButtonTitle : lastBlockTitle,
    isDisabled: isLast,
    onClick: onMoveDown,
    icon: forwardButtonIcon,
    extraProps: {
      hint: forwardButtonHint
    }
  }));
};

export default compose(withSelect(function (select, _ref2) {
  var clientIds = _ref2.clientIds;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex,
      getTemplateLock = _select.getTemplateLock,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockOrder = _select.getBlockOrder;

  var normalizedClientIds = castArray(clientIds);
  var firstClientId = first(normalizedClientIds);
  var rootClientId = getBlockRootClientId(firstClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex(last(normalizedClientIds), rootClientId);
  return {
    firstIndex: firstIndex,
    isFirst: firstIndex === 0,
    isLast: lastIndex === blockOrder.length - 1,
    isLocked: getTemplateLock(rootClientId) === 'all',
    rootClientId: rootClientId
  };
}), withDispatch(function (dispatch, _ref3) {
  var clientIds = _ref3.clientIds,
      rootClientId = _ref3.rootClientId;

  var _dispatch = dispatch('core/block-editor'),
      moveBlocksDown = _dispatch.moveBlocksDown,
      moveBlocksUp = _dispatch.moveBlocksUp;

  return {
    onMoveDown: partial(moveBlocksDown, clientIds, rootClientId),
    onMoveUp: partial(moveBlocksUp, clientIds, rootClientId)
  };
}), withInstanceId)(BlockMover);
//# sourceMappingURL=index.native.js.map