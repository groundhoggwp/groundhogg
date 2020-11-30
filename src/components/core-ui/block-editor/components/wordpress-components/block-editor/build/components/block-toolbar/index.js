"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockToolbar;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _blockMover = _interopRequireDefault(require("../block-mover"));

var _blockParentSelector = _interopRequireDefault(require("../block-parent-selector"));

var _blockSwitcher = _interopRequireDefault(require("../block-switcher"));

var _blockControls = _interopRequireDefault(require("../block-controls"));

var _blockFormatControls = _interopRequireDefault(require("../block-format-controls"));

var _blockSettingsMenu = _interopRequireDefault(require("../block-settings-menu"));

var _utils = require("./utils");

var _expandedBlockControlsContainer = _interopRequireDefault(require("./expanded-block-controls-container"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockToolbar(_ref) {
  var hideDragHandle = _ref.hideDragHandle,
      _ref$__experimentalEx = _ref.__experimentalExpandedControl,
      __experimentalExpandedControl = _ref$__experimentalEx === void 0 ? false : _ref$__experimentalEx;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getBlockMode = _select.getBlockMode,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
        isBlockValid = _select.isBlockValid,
        getBlockRootClientId = _select.getBlockRootClientId,
        getSettings = _select.getSettings;

    var selectedBlockClientIds = getSelectedBlockClientIds();
    var selectedBlockClientId = selectedBlockClientIds[0];
    var blockRootClientId = getBlockRootClientId(selectedBlockClientId);
    return {
      blockClientIds: selectedBlockClientIds,
      blockClientId: selectedBlockClientId,
      blockType: selectedBlockClientId && (0, _blocks.getBlockType)(getBlockName(selectedBlockClientId)),
      hasFixedToolbar: getSettings().hasFixedToolbar,
      rootClientId: blockRootClientId,
      isValid: selectedBlockClientIds.every(function (id) {
        return isBlockValid(id);
      }),
      isVisual: selectedBlockClientIds.every(function (id) {
        return getBlockMode(id) === 'visual';
      })
    };
  }, []),
      blockClientIds = _useSelect.blockClientIds,
      blockClientId = _useSelect.blockClientId,
      blockType = _useSelect.blockType,
      hasFixedToolbar = _useSelect.hasFixedToolbar,
      isValid = _useSelect.isValid,
      isVisual = _useSelect.isVisual;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      toggleBlockHighlight = _useDispatch.toggleBlockHighlight;

  var nodeRef = (0, _element.useRef)();

  var _useShowMoversGesture = (0, _utils.useShowMoversGestures)({
    ref: nodeRef,
    onChange: function onChange(isFocused) {
      toggleBlockHighlight(blockClientId, isFocused);
    }
  }),
      showMovers = _useShowMoversGesture.showMovers,
      showMoversGestures = _useShowMoversGesture.gestures;

  var displayHeaderToolbar = (0, _compose.useViewportMatch)('medium', '<') || hasFixedToolbar;

  if (blockType) {
    if (!(0, _blocks.hasBlockSupport)(blockType, '__experimentalToolbar', true)) {
      return null;
    }
  }

  var shouldShowMovers = displayHeaderToolbar || showMovers;

  if (blockClientIds.length === 0) {
    return null;
  }

  var shouldShowVisualToolbar = isValid && isVisual;
  var isMultiToolbar = blockClientIds.length > 1;
  var classes = (0, _classnames.default)('block-editor-block-toolbar', shouldShowMovers && 'is-showing-movers');
  var Wrapper = __experimentalExpandedControl ? _expandedBlockControlsContainer.default : 'div';
  return (0, _element.createElement)(Wrapper, {
    className: classes
  }, (0, _element.createElement)("div", (0, _extends2.default)({
    ref: nodeRef
  }, showMoversGestures), !isMultiToolbar && (0, _element.createElement)("div", {
    className: "block-editor-block-toolbar__block-parent-selector-wrapper"
  }, (0, _element.createElement)(_blockParentSelector.default, {
    clientIds: blockClientIds
  })), (shouldShowVisualToolbar || isMultiToolbar) && (0, _element.createElement)(_components.ToolbarGroup, {
    className: "block-editor-block-toolbar__block-controls"
  }, (0, _element.createElement)(_blockSwitcher.default, {
    clientIds: blockClientIds
  }), (0, _element.createElement)(_blockMover.default, {
    clientIds: blockClientIds,
    hideDragHandle: hideDragHandle
  }))), shouldShowVisualToolbar && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockControls.default.Slot, {
    bubblesVirtually: true,
    className: "block-editor-block-toolbar__slot"
  }), (0, _element.createElement)(_blockFormatControls.default.Slot, {
    bubblesVirtually: true,
    className: "block-editor-block-toolbar__slot"
  })), (0, _element.createElement)(_blockSettingsMenu.default, {
    clientIds: blockClientIds
  }));
}
//# sourceMappingURL=index.js.map