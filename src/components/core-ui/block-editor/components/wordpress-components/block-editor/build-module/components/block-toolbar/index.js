import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { useViewportMatch } from '@wordpress/compose';
import { getBlockType, hasBlockSupport } from '@wordpress/blocks';
import { ToolbarGroup } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockMover from '../block-mover';
import BlockParentSelector from '../block-parent-selector';
import BlockSwitcher from '../block-switcher';
import BlockControls from '../block-controls';
import BlockFormatControls from '../block-format-controls';
import BlockSettingsMenu from '../block-settings-menu';
import { useShowMoversGestures } from './utils';
import ExpandedBlockControlsContainer from './expanded-block-controls-container';
export default function BlockToolbar(_ref) {
  var hideDragHandle = _ref.hideDragHandle,
      _ref$__experimentalEx = _ref.__experimentalExpandedControl,
      __experimentalExpandedControl = _ref$__experimentalEx === void 0 ? false : _ref$__experimentalEx;

  var _useSelect = useSelect(function (select) {
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
      blockType: selectedBlockClientId && getBlockType(getBlockName(selectedBlockClientId)),
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

  var _useDispatch = useDispatch('core/block-editor'),
      toggleBlockHighlight = _useDispatch.toggleBlockHighlight;

  var nodeRef = useRef();

  var _useShowMoversGesture = useShowMoversGestures({
    ref: nodeRef,
    onChange: function onChange(isFocused) {
      toggleBlockHighlight(blockClientId, isFocused);
    }
  }),
      showMovers = _useShowMoversGesture.showMovers,
      showMoversGestures = _useShowMoversGesture.gestures;

  var displayHeaderToolbar = useViewportMatch('medium', '<') || hasFixedToolbar;

  if (blockType) {
    if (!hasBlockSupport(blockType, '__experimentalToolbar', true)) {
      return null;
    }
  }

  var shouldShowMovers = displayHeaderToolbar || showMovers;

  if (blockClientIds.length === 0) {
    return null;
  }

  var shouldShowVisualToolbar = isValid && isVisual;
  var isMultiToolbar = blockClientIds.length > 1;
  var classes = classnames('block-editor-block-toolbar', shouldShowMovers && 'is-showing-movers');
  var Wrapper = __experimentalExpandedControl ? ExpandedBlockControlsContainer : 'div';
  return createElement(Wrapper, {
    className: classes
  }, createElement("div", _extends({
    ref: nodeRef
  }, showMoversGestures), !isMultiToolbar && createElement("div", {
    className: "block-editor-block-toolbar__block-parent-selector-wrapper"
  }, createElement(BlockParentSelector, {
    clientIds: blockClientIds
  })), (shouldShowVisualToolbar || isMultiToolbar) && createElement(ToolbarGroup, {
    className: "block-editor-block-toolbar__block-controls"
  }, createElement(BlockSwitcher, {
    clientIds: blockClientIds
  }), createElement(BlockMover, {
    clientIds: blockClientIds,
    hideDragHandle: hideDragHandle
  }))), shouldShowVisualToolbar && createElement(Fragment, null, createElement(BlockControls.Slot, {
    bubblesVirtually: true,
    className: "block-editor-block-toolbar__slot"
  }), createElement(BlockFormatControls.Slot, {
    bubblesVirtually: true,
    className: "block-editor-block-toolbar__slot"
  })), createElement(BlockSettingsMenu, {
    clientIds: blockClientIds
  }));
}
//# sourceMappingURL=index.js.map