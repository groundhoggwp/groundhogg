"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.SetBlockNodes = exports.BlockNodes = exports.Context = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _data = require("@wordpress/data");

var _useMultiSelection = _interopRequireDefault(require("./use-multi-selection"));

var _dom = require("../../utils/dom");

var _insertionPoint = _interopRequireDefault(require("./insertion-point"));

var _blockPopover = _interopRequireDefault(require("./block-popover"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/** @typedef {import('@wordpress/element').WPSyntheticEvent} WPSyntheticEvent */
var Context = (0, _element.createContext)();
exports.Context = Context;
var BlockNodes = (0, _element.createContext)();
exports.BlockNodes = BlockNodes;
var SetBlockNodes = (0, _element.createContext)();
exports.SetBlockNodes = SetBlockNodes;

function selector(select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      hasMultiSelection = _select.hasMultiSelection;

  return {
    selectedBlockClientId: getSelectedBlockClientId(),
    hasMultiSelection: hasMultiSelection()
  };
}
/**
 * Prevents default dragging behavior within a block.
 * To do: we must handle this in the future and clean up the drag target.
 * Previously dragging was prevented for multi-selected, but this is no longer
 * needed.
 *
 * @param {WPSyntheticEvent} event Synthetic drag event.
 */


function onDragStart(event) {
  // Ensure we target block content, not block controls.
  if ((0, _dom.getBlockClientId)(event.target)) {
    event.preventDefault();
  }
}

function RootContainer(_ref, ref) {
  var children = _ref.children,
      className = _ref.className;

  var _useSelect = (0, _data.useSelect)(selector, []),
      selectedBlockClientId = _useSelect.selectedBlockClientId,
      hasMultiSelection = _useSelect.hasMultiSelection;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var onSelectionStart = (0, _useMultiSelection.default)(ref);
  /**
   * Marks the block as selected when focused and not already selected. This
   * specifically handles the case where block does not set focus on its own
   * (via `setFocus`), typically if there is no focusable input in the block.
   *
   * @param {WPSyntheticEvent} event
   */

  function onFocus(event) {
    if (hasMultiSelection) {
      return;
    }

    var clientId = (0, _dom.getBlockClientId)(event.target);

    if (clientId && clientId !== selectedBlockClientId) {
      selectBlock(clientId);
    }
  }

  var _useState = (0, _element.useState)({}),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      blockNodes = _useState2[0],
      setBlockNodes = _useState2[1];

  return (0, _element.createElement)(_insertionPoint.default, {
    containerRef: ref
  }, (0, _element.createElement)(BlockNodes.Provider, {
    value: blockNodes
  }, (0, _element.createElement)(_blockPopover.default, null), (0, _element.createElement)("div", {
    ref: ref,
    className: (0, _classnames.default)(className, 'is-root-container'),
    onFocus: onFocus,
    onDragStart: onDragStart
  }, (0, _element.createElement)(SetBlockNodes.Provider, {
    value: setBlockNodes
  }, (0, _element.createElement)(Context.Provider, {
    value: onSelectionStart
  }, children)))));
}

var _default = (0, _element.forwardRef)(RootContainer);

exports.default = _default;
//# sourceMappingURL=root-container.js.map