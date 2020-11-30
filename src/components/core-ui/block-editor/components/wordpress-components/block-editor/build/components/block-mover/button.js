"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockMoverDownButton = exports.BlockMoverUpButton = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

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
var getArrowIcon = function getArrowIcon(direction, orientation, isRTL) {
  if (direction === 'up') {
    if (orientation === 'horizontal') {
      return isRTL ? _icons.chevronRight : _icons.chevronLeft;
    }

    return _icons.chevronUp;
  } else if (direction === 'down') {
    if (orientation === 'horizontal') {
      return isRTL ? _icons.chevronLeft : _icons.chevronRight;
    }

    return _icons.chevronDown;
  }

  return null;
};

var getMovementDirectionLabel = function getMovementDirectionLabel(moveDirection, orientation, isRTL) {
  if (moveDirection === 'up') {
    if (orientation === 'horizontal') {
      return isRTL ? (0, _i18n.__)('Move right') : (0, _i18n.__)('Move left');
    }

    return (0, _i18n.__)('Move up');
  } else if (moveDirection === 'down') {
    if (orientation === 'horizontal') {
      return isRTL ? (0, _i18n.__)('Move left') : (0, _i18n.__)('Move right');
    }

    return (0, _i18n.__)('Move down');
  }

  return null;
};

var BlockMoverButton = (0, _element.forwardRef)(function (_ref, ref) {
  var clientIds = _ref.clientIds,
      direction = _ref.direction,
      moverOrientation = _ref.orientation,
      props = (0, _objectWithoutProperties2.default)(_ref, ["clientIds", "direction", "orientation"]);
  var instanceId = (0, _compose.useInstanceId)(BlockMoverButton);
  var blocksCount = (0, _lodash.castArray)(clientIds).length;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockIndex = _select.getBlockIndex,
        getBlockRootClientId = _select.getBlockRootClientId,
        getBlockOrder = _select.getBlockOrder,
        getBlock = _select.getBlock,
        getSettings = _select.getSettings,
        getBlockListSettings = _select.getBlockListSettings;

    var normalizedClientIds = (0, _lodash.castArray)(clientIds);
    var firstClientId = (0, _lodash.first)(normalizedClientIds);
    var blockRootClientId = getBlockRootClientId(firstClientId);
    var firstBlockIndex = getBlockIndex(firstClientId, blockRootClientId);
    var lastBlockIndex = getBlockIndex((0, _lodash.last)(normalizedClientIds), blockRootClientId);
    var blockOrder = getBlockOrder(blockRootClientId);
    var block = getBlock(firstClientId);
    var isFirstBlock = firstBlockIndex === 0;
    var isLastBlock = lastBlockIndex === blockOrder.length - 1;

    var _ref2 = getBlockListSettings(blockRootClientId) || {},
        blockListOrientation = _ref2.orientation;

    return {
      blockType: block ? (0, _blocks.getBlockType)(block.name) : null,
      isDisabled: direction === 'up' ? isFirstBlock : isLastBlock,
      rootClientId: blockRootClientId,
      firstIndex: firstBlockIndex,
      isFirst: isFirstBlock,
      isLast: isLastBlock,
      isRTL: getSettings().isRTL,
      orientation: moverOrientation || blockListOrientation
    };
  }, [clientIds, direction]),
      blockType = _useSelect.blockType,
      isDisabled = _useSelect.isDisabled,
      rootClientId = _useSelect.rootClientId,
      isFirst = _useSelect.isFirst,
      isLast = _useSelect.isLast,
      firstIndex = _useSelect.firstIndex,
      isRTL = _useSelect.isRTL,
      _useSelect$orientatio = _useSelect.orientation,
      orientation = _useSelect$orientatio === void 0 ? 'vertical' : _useSelect$orientatio;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      moveBlocksDown = _useDispatch.moveBlocksDown,
      moveBlocksUp = _useDispatch.moveBlocksUp;

  var moverFunction = direction === 'up' ? moveBlocksUp : moveBlocksDown;

  var onClick = function onClick(event) {
    moverFunction(clientIds, rootClientId);

    if (props.onClick) {
      props.onClick(event);
    }
  };

  var descriptionId = "block-editor-block-mover-button__description-".concat(instanceId);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.Button, (0, _extends2.default)({
    ref: ref,
    className: (0, _classnames.default)('block-editor-block-mover-button', "is-".concat(direction, "-button")),
    icon: getArrowIcon(direction, orientation, isRTL),
    label: getMovementDirectionLabel(direction, orientation, isRTL),
    "aria-describedby": descriptionId
  }, props, {
    onClick: isDisabled ? null : onClick,
    "aria-disabled": isDisabled
  })), (0, _element.createElement)("span", {
    id: descriptionId,
    className: "block-editor-block-mover-button__description"
  }, (0, _moverDescription.getBlockMoverDescription)(blocksCount, blockType && blockType.title, firstIndex, isFirst, isLast, direction === 'up' ? -1 : 1, orientation, isRTL)));
});
var BlockMoverUpButton = (0, _element.forwardRef)(function (props, ref) {
  return (0, _element.createElement)(BlockMoverButton, (0, _extends2.default)({
    direction: "up",
    ref: ref
  }, props));
});
exports.BlockMoverUpButton = BlockMoverUpButton;
var BlockMoverDownButton = (0, _element.forwardRef)(function (props, ref) {
  return (0, _element.createElement)(BlockMoverButton, (0, _extends2.default)({
    direction: "down",
    ref: ref
  }, props));
});
exports.BlockMoverDownButton = BlockMoverDownButton;
//# sourceMappingURL=button.js.map