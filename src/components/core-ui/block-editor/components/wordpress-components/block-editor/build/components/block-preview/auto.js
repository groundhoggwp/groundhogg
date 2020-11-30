"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _blockList = _interopRequireDefault(require("../block-list"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
// This is used to avoid rendering the block list if the sizes change.
var MemoizedBlockList;

function AutoBlockPreview(_ref) {
  var viewportWidth = _ref.viewportWidth,
      __experimentalPadding = _ref.__experimentalPadding;

  var _useResizeObserver = (0, _compose.useResizeObserver)(),
      _useResizeObserver2 = (0, _slicedToArray2.default)(_useResizeObserver, 2),
      containerResizeListener = _useResizeObserver2[0],
      containerWidth = _useResizeObserver2[1].width;

  var _useResizeObserver3 = (0, _compose.useResizeObserver)(),
      _useResizeObserver4 = (0, _slicedToArray2.default)(_useResizeObserver3, 2),
      containtResizeListener = _useResizeObserver4[0],
      contentHeight = _useResizeObserver4[1].height; // Initialize on render instead of module top level, to avoid circular dependency issues.


  MemoizedBlockList = MemoizedBlockList || (0, _compose.pure)(_blockList.default);
  var scale = (containerWidth - 2 * __experimentalPadding) / viewportWidth;
  return (0, _element.createElement)("div", {
    className: "block-editor-block-preview__container editor-styles-wrapper",
    "aria-hidden": true,
    style: {
      height: contentHeight * scale + 2 * __experimentalPadding
    }
  }, containerResizeListener, (0, _element.createElement)(_components.Disabled, {
    style: {
      transform: "scale(".concat(scale, ")"),
      width: viewportWidth,
      left: __experimentalPadding,
      right: __experimentalPadding,
      top: __experimentalPadding
    },
    className: "block-editor-block-preview__content"
  }, containtResizeListener, (0, _element.createElement)(MemoizedBlockList, null)));
}

var _default = AutoBlockPreview;
exports.default = _default;
//# sourceMappingURL=auto.js.map