"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _icons = require("@wordpress/icons");

var _components = require("@wordpress/components");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _blockDraggable = _interopRequireDefault(require("../block-draggable"));

var _button = require("./button");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockMover(_ref) {
  var isFirst = _ref.isFirst,
      isLast = _ref.isLast,
      clientIds = _ref.clientIds,
      isLocked = _ref.isLocked,
      isHidden = _ref.isHidden,
      rootClientId = _ref.rootClientId,
      orientation = _ref.orientation,
      hideDragHandle = _ref.hideDragHandle;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isFocused = _useState2[0],
      setIsFocused = _useState2[1];

  var onFocus = function onFocus() {
    return setIsFocused(true);
  };

  var onBlur = function onBlur() {
    return setIsFocused(false);
  };

  if (isLocked || isFirst && isLast && !rootClientId) {
    return null;
  } // We emulate a disabled state because forcefully applying the `disabled`
  // attribute on the buttons while it has focus causes the screen to change
  // to an unfocused state (body as active element) without firing blur on,
  // the rendering parent, leaving it unable to react to focus out.


  return (0, _element.createElement)("div", {
    className: (0, _classnames.default)('block-editor-block-mover', {
      'is-visible': isFocused || !isHidden,
      'is-horizontal': orientation === 'horizontal'
    })
  }, !hideDragHandle && (0, _element.createElement)(_blockDraggable.default, {
    clientIds: clientIds,
    cloneClassname: "block-editor-block-mover__drag-clone"
  }, function (_ref2) {
    var isDraggable = _ref2.isDraggable,
        onDraggableStart = _ref2.onDraggableStart,
        onDraggableEnd = _ref2.onDraggableEnd;
    return (0, _element.createElement)(_components.Button, {
      icon: _icons.dragHandle,
      className: "block-editor-block-mover__drag-handle",
      "aria-hidden": "true",
      label: (0, _i18n._n)('Drag block', 'Drag blocks', clientIds.length) // Should not be able to tab to drag handle as this
      // button can only be used with a pointer device.
      ,
      tabIndex: "-1",
      onDragStart: onDraggableStart,
      onDragEnd: onDraggableEnd,
      draggable: isDraggable
    });
  }), (0, _element.createElement)(_components.ToolbarGroup, {
    className: "block-editor-block-mover__move-button-container"
  }, (0, _element.createElement)(_components.ToolbarItem, {
    onFocus: onFocus,
    onBlur: onBlur
  }, function (itemProps) {
    return (0, _element.createElement)(_button.BlockMoverUpButton, (0, _extends2.default)({
      clientIds: clientIds
    }, itemProps));
  }), (0, _element.createElement)(_components.ToolbarItem, {
    onFocus: onFocus,
    onBlur: onBlur
  }, function (itemProps) {
    return (0, _element.createElement)(_button.BlockMoverDownButton, (0, _extends2.default)({
      clientIds: clientIds
    }, itemProps));
  })));
}

var _default = (0, _data.withSelect)(function (select, _ref3) {
  var _getBlockListSettings;

  var clientIds = _ref3.clientIds;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock,
      getBlockIndex = _select.getBlockIndex,
      getBlockListSettings = _select.getBlockListSettings,
      getTemplateLock = _select.getTemplateLock,
      getBlockOrder = _select.getBlockOrder,
      getBlockRootClientId = _select.getBlockRootClientId;

  var normalizedClientIds = (0, _lodash.castArray)(clientIds);
  var firstClientId = (0, _lodash.first)(normalizedClientIds);
  var block = getBlock(firstClientId);
  var rootClientId = getBlockRootClientId((0, _lodash.first)(normalizedClientIds));
  var firstIndex = getBlockIndex(firstClientId, rootClientId);
  var lastIndex = getBlockIndex((0, _lodash.last)(normalizedClientIds), rootClientId);
  var blockOrder = getBlockOrder(rootClientId);
  var isFirst = firstIndex === 0;
  var isLast = lastIndex === blockOrder.length - 1;
  return {
    blockType: block ? (0, _blocks.getBlockType)(block.name) : null,
    isLocked: getTemplateLock(rootClientId) === 'all',
    rootClientId: rootClientId,
    firstIndex: firstIndex,
    isFirst: isFirst,
    isLast: isLast,
    orientation: (_getBlockListSettings = getBlockListSettings(rootClientId)) === null || _getBlockListSettings === void 0 ? void 0 : _getBlockListSettings.orientation
  };
})(BlockMover);

exports.default = _default;
//# sourceMappingURL=index.js.map