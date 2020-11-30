"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _moverDescription = require("./mover-description");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var BlockMover = function BlockMover(_ref) {
  var isFirst = _ref.isFirst,
      isLast = _ref.isLast,
      isLocked = _ref.isLocked,
      onMoveDown = _ref.onMoveDown,
      onMoveUp = _ref.onMoveUp,
      firstIndex = _ref.firstIndex,
      rootClientId = _ref.rootClientId,
      isStackedHorizontally = _ref.isStackedHorizontally;

  var _getMoversSetup = (0, _moverDescription.getMoversSetup)(isStackedHorizontally, {
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

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToolbarButton, {
    title: !isFirst ? backwardButtonTitle : firstBlockTitle,
    isDisabled: isFirst,
    onClick: onMoveUp,
    icon: backwardButtonIcon,
    extraProps: {
      hint: backwardButtonHint
    }
  }), (0, _element.createElement)(_components.ToolbarButton, {
    title: !isLast ? forwardButtonTitle : lastBlockTitle,
    isDisabled: isLast,
    onClick: onMoveDown,
    icon: forwardButtonIcon,
    extraProps: {
      hint: forwardButtonHint
    }
  }));
};

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref2) {
  var clientIds = _ref2.clientIds;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex,
      getTemplateLock = _select.getTemplateLock,
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockOrder = _select.getBlockOrder;

  var normalizedClientIds = (0, _lodash.castArray)(clientIds);
  var firstClientId = (0, _lodash.first)(normalizedClientIds);
  var rootClientId = getBlockRootClientId(firstClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex((0, _lodash.last)(normalizedClientIds), rootClientId);
  return {
    firstIndex: firstIndex,
    isFirst: firstIndex === 0,
    isLast: lastIndex === blockOrder.length - 1,
    isLocked: getTemplateLock(rootClientId) === 'all',
    rootClientId: rootClientId
  };
}), (0, _data.withDispatch)(function (dispatch, _ref3) {
  var clientIds = _ref3.clientIds,
      rootClientId = _ref3.rootClientId;

  var _dispatch = dispatch('core/block-editor'),
      moveBlocksDown = _dispatch.moveBlocksDown,
      moveBlocksUp = _dispatch.moveBlocksUp;

  return {
    onMoveDown: (0, _lodash.partial)(moveBlocksDown, clientIds, rootClientId),
    onMoveUp: (0, _lodash.partial)(moveBlocksUp, clientIds, rootClientId)
  };
}), _compose.withInstanceId)(BlockMover);

exports.default = _default;
//# sourceMappingURL=index.native.js.map