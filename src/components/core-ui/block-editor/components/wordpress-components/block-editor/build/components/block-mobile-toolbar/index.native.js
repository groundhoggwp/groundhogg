"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _reactNative = require("react-native");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _style = _interopRequireDefault(require("./style.scss"));

var _blockMover = _interopRequireDefault(require("../block-mover"));

var _blockActionsMenu = _interopRequireDefault(require("./block-actions-menu"));

var _blockSettings = require("../block-settings");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
// Defined breakpoints are used to get a point when
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

  var _useState = (0, _element.useState)(null),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      fillsLength = _useState2[0],
      setFillsLength = _useState2[1];

  var wrapBlockSettings = blockWidth < BREAKPOINTS.wrapSettings;
  var wrapBlockMover = blockWidth <= BREAKPOINTS.wrapMover;
  return (0, _element.createElement)(_reactNative.View, {
    style: [_style.default.toolbar, isFullWidth && _style.default.toolbarFullWidth]
  }, !wrapBlockMover && (0, _element.createElement)(_blockMover.default, {
    clientIds: [clientId],
    isStackedHorizontally: isStackedHorizontally
  }), (0, _element.createElement)(_reactNative.View, {
    style: _style.default.spacer
  }), (0, _element.createElement)(_blockSettings.BlockSettingsButton.Slot, null, function () {
    var fills = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [null];
    setFillsLength(fills.length);
    return wrapBlockSettings ? null : fills[0];
  }), (0, _element.createElement)(_blockActionsMenu.default, {
    clientIds: [clientId],
    wrapBlockMover: wrapBlockMover,
    wrapBlockSettings: wrapBlockSettings && fillsLength,
    isStackedHorizontally: isStackedHorizontally,
    onDelete: onDelete,
    anchorNodeRef: anchorNodeRef
  }));
};

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select, _ref2) {
  var clientId = _ref2.clientId;

  var _select = select('core/block-editor'),
      getBlockIndex = _select.getBlockIndex;

  return {
    order: getBlockIndex(clientId)
  };
}), (0, _data.withDispatch)(function (dispatch, _ref3) {
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

exports.default = _default;
//# sourceMappingURL=index.native.js.map