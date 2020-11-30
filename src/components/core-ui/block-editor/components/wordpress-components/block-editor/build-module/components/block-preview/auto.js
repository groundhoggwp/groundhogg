import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Disabled } from '@wordpress/components';
import { useResizeObserver, pure } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import BlockList from '../block-list'; // This is used to avoid rendering the block list if the sizes change.

var MemoizedBlockList;

function AutoBlockPreview(_ref) {
  var viewportWidth = _ref.viewportWidth,
      __experimentalPadding = _ref.__experimentalPadding;

  var _useResizeObserver = useResizeObserver(),
      _useResizeObserver2 = _slicedToArray(_useResizeObserver, 2),
      containerResizeListener = _useResizeObserver2[0],
      containerWidth = _useResizeObserver2[1].width;

  var _useResizeObserver3 = useResizeObserver(),
      _useResizeObserver4 = _slicedToArray(_useResizeObserver3, 2),
      containtResizeListener = _useResizeObserver4[0],
      contentHeight = _useResizeObserver4[1].height; // Initialize on render instead of module top level, to avoid circular dependency issues.


  MemoizedBlockList = MemoizedBlockList || pure(BlockList);
  var scale = (containerWidth - 2 * __experimentalPadding) / viewportWidth;
  return createElement("div", {
    className: "block-editor-block-preview__container editor-styles-wrapper",
    "aria-hidden": true,
    style: {
      height: contentHeight * scale + 2 * __experimentalPadding
    }
  }, containerResizeListener, createElement(Disabled, {
    style: {
      transform: "scale(".concat(scale, ")"),
      width: viewportWidth,
      left: __experimentalPadding,
      right: __experimentalPadding,
      top: __experimentalPadding
    },
    className: "block-editor-block-preview__content"
  }, containtResizeListener, createElement(MemoizedBlockList, null)));
}

export default AutoBlockPreview;
//# sourceMappingURL=auto.js.map