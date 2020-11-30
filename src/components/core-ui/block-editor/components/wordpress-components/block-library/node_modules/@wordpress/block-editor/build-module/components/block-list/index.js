import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { AsyncModeProvider, useSelect } from '@wordpress/data';
import { useRef, forwardRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import BlockListBlock from './block';
import BlockListAppender from '../block-list-appender';
import RootContainer from './root-container';
import useBlockDropZone from '../use-block-drop-zone';
/**
 * If the block count exceeds the threshold, we disable the reordering animation
 * to avoid laginess.
 */

var BLOCK_ANIMATION_THRESHOLD = 200;

function BlockList(_ref, ref) {
  var className = _ref.className,
      rootClientId = _ref.rootClientId,
      renderAppender = _ref.renderAppender,
      _ref$__experimentalTa = _ref.__experimentalTagName,
      __experimentalTagName = _ref$__experimentalTa === void 0 ? 'div' : _ref$__experimentalTa,
      __experimentalAppenderTagName = _ref.__experimentalAppenderTagName,
      _ref$__experimentalPa = _ref.__experimentalPassedProps,
      __experimentalPassedProps = _ref$__experimentalPa === void 0 ? {} : _ref$__experimentalPa;

  function selector(select) {
    var _getBlockListSettings;

    var _select = select('core/block-editor'),
        getBlockOrder = _select.getBlockOrder,
        getBlockListSettings = _select.getBlockListSettings,
        getSelectedBlockClientId = _select.getSelectedBlockClientId,
        getMultiSelectedBlockClientIds = _select.getMultiSelectedBlockClientIds,
        hasMultiSelection = _select.hasMultiSelection,
        getGlobalBlockCount = _select.getGlobalBlockCount,
        isTyping = _select.isTyping,
        isDraggingBlocks = _select.isDraggingBlocks;

    return {
      blockClientIds: getBlockOrder(rootClientId),
      selectedBlockClientId: getSelectedBlockClientId(),
      multiSelectedBlockClientIds: getMultiSelectedBlockClientIds(),
      orientation: (_getBlockListSettings = getBlockListSettings(rootClientId)) === null || _getBlockListSettings === void 0 ? void 0 : _getBlockListSettings.orientation,
      hasMultiSelection: hasMultiSelection(),
      enableAnimation: !isTyping() && getGlobalBlockCount() <= BLOCK_ANIMATION_THRESHOLD,
      isDraggingBlocks: isDraggingBlocks()
    };
  }

  var _useSelect = useSelect(selector, [rootClientId]),
      blockClientIds = _useSelect.blockClientIds,
      selectedBlockClientId = _useSelect.selectedBlockClientId,
      multiSelectedBlockClientIds = _useSelect.multiSelectedBlockClientIds,
      orientation = _useSelect.orientation,
      hasMultiSelection = _useSelect.hasMultiSelection,
      enableAnimation = _useSelect.enableAnimation,
      isDraggingBlocks = _useSelect.isDraggingBlocks;

  var fallbackRef = useRef();
  var element = __experimentalPassedProps.ref || ref || fallbackRef;
  var Container = rootClientId ? __experimentalTagName : RootContainer;
  var dropTargetIndex = useBlockDropZone({
    element: element,
    rootClientId: rootClientId
  });
  var isAppenderDropTarget = dropTargetIndex === blockClientIds.length && isDraggingBlocks;
  return createElement(Container, _extends({}, __experimentalPassedProps, {
    ref: element,
    className: classnames('block-editor-block-list__layout', className, __experimentalPassedProps.className)
  }), blockClientIds.map(function (clientId, index) {
    var isBlockInSelection = hasMultiSelection ? multiSelectedBlockClientIds.includes(clientId) : selectedBlockClientId === clientId;
    var isDropTarget = dropTargetIndex === index && isDraggingBlocks;
    return createElement(AsyncModeProvider, {
      key: clientId,
      value: !isBlockInSelection
    }, createElement(BlockListBlock, {
      rootClientId: rootClientId,
      clientId: clientId // This prop is explicitely computed and passed down
      // to avoid being impacted by the async mode
      // otherwise there might be a small delay to trigger the animation.
      ,
      index: index,
      enableAnimation: enableAnimation,
      className: classnames({
        'is-drop-target': isDropTarget,
        'is-dropping-horizontally': isDropTarget && orientation === 'horizontal'
      })
    }));
  }), createElement(BlockListAppender, {
    tagName: __experimentalAppenderTagName,
    rootClientId: rootClientId,
    renderAppender: renderAppender,
    className: classnames({
      'is-drop-target': isAppenderDropTarget,
      'is-dropping-horizontally': isAppenderDropTarget && orientation === 'horizontal'
    })
  }));
}

var ForwardedBlockList = forwardRef(BlockList); // This component needs to always be synchronous
// as it's the one changing the async mode
// depending on the block selection.

export default forwardRef(function (props, ref) {
  return createElement(AsyncModeProvider, {
    value: false
  }, createElement(ForwardedBlockList, _extends({
    ref: ref
  }, props)));
});
//# sourceMappingURL=index.js.map