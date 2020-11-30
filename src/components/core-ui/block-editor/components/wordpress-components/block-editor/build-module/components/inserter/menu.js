import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { LEFT, RIGHT, UP, DOWN, BACKSPACE, ENTER } from '@wordpress/keycodes';
import { VisuallyHidden } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import Tips from './tips';
import InserterSearchForm from './search-form';
import InserterPreviewPanel from './preview-panel';
import BlockTypesTab from './block-types-tab';
import BlockPatternsTabs from './block-patterns-tab';
import ReusableBlocksTab from './reusable-blocks-tab';
import useInsertionPoint from './hooks/use-insertion-point';
import InserterTabs from './tabs';

var stopKeyPropagation = function stopKeyPropagation(event) {
  return event.stopPropagation();
};

function InserterMenu(_ref) {
  var rootClientId = _ref.rootClientId,
      clientId = _ref.clientId,
      isAppender = _ref.isAppender,
      __experimentalSelectBlockOnInsert = _ref.__experimentalSelectBlockOnInsert,
      onSelect = _ref.onSelect,
      showInserterHelpPanel = _ref.showInserterHelpPanel,
      showMostUsedBlocks = _ref.showMostUsedBlocks;

  var _useState = useState('blocks'),
      _useState2 = _slicedToArray(_useState, 2),
      activeTab = _useState2[0],
      setActiveTab = _useState2[1];

  var _useState3 = useState(''),
      _useState4 = _slicedToArray(_useState3, 2),
      filterValue = _useState4[0],
      setFilterValue = _useState4[1];

  var _useState5 = useState(null),
      _useState6 = _slicedToArray(_useState5, 2),
      hoveredItem = _useState6[0],
      setHoveredItem = _useState6[1];

  var _useState7 = useState(null),
      _useState8 = _slicedToArray(_useState7, 2),
      selectedPatternCategory = _useState8[0],
      setSelectedPatternCategory = _useState8[1];

  var _useInsertionPoint = useInsertionPoint({
    rootClientId: rootClientId,
    clientId: clientId,
    isAppender: isAppender,
    selectBlockOnInsert: __experimentalSelectBlockOnInsert
  }),
      _useInsertionPoint2 = _slicedToArray(_useInsertionPoint, 3),
      destinationRootClientId = _useInsertionPoint2[0],
      onInsertBlocks = _useInsertionPoint2[1],
      onToggleInsertionPoint = _useInsertionPoint2[2];

  var _useSelect = useSelect(function (select) {
    var _select$getSettings = select('core/block-editor').getSettings(),
        __experimentalBlockPatterns = _select$getSettings.__experimentalBlockPatterns,
        __experimentalReusableBlocks = _select$getSettings.__experimentalReusableBlocks;

    return {
      hasPatterns: !!(__experimentalBlockPatterns === null || __experimentalBlockPatterns === void 0 ? void 0 : __experimentalBlockPatterns.length),
      hasReusableBlocks: !!(__experimentalReusableBlocks === null || __experimentalReusableBlocks === void 0 ? void 0 : __experimentalReusableBlocks.length)
    };
  }, []),
      hasPatterns = _useSelect.hasPatterns,
      hasReusableBlocks = _useSelect.hasReusableBlocks;

  var showPatterns = !destinationRootClientId && hasPatterns;

  var onKeyDown = function onKeyDown(event) {
    if ([LEFT, DOWN, RIGHT, UP, BACKSPACE, ENTER].includes(event.keyCode)) {
      // Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
      event.stopPropagation();
    }
  };

  var onInsert = function onInsert(blocks) {
    onInsertBlocks(blocks);
    onSelect();
  };

  var onInsertPattern = function onInsertPattern(blocks, patternName) {
    onInsertBlocks(blocks, {
      patternName: patternName
    });
    onSelect();
  };

  var onHover = function onHover(item) {
    onToggleInsertionPoint(!!item);
    setHoveredItem(item);
  };

  var onClickPatternCategory = function onClickPatternCategory(patternCategory) {
    setSelectedPatternCategory(patternCategory);
  };

  var blocksTab = createElement(Fragment, null, createElement("div", {
    className: "block-editor-inserter__block-list"
  }, createElement(BlockTypesTab, {
    rootClientId: destinationRootClientId,
    onInsert: onInsert,
    onHover: onHover,
    filterValue: filterValue,
    showMostUsedBlocks: showMostUsedBlocks
  })), showInserterHelpPanel && createElement("div", {
    className: "block-editor-inserter__tips"
  }, createElement(VisuallyHidden, {
    as: "h2"
  }, __('A tip for using the block editor')), createElement(Tips, null)));
  var patternsTab = createElement(BlockPatternsTabs, {
    onInsert: onInsertPattern,
    filterValue: filterValue,
    onClickCategory: onClickPatternCategory,
    selectedCategory: selectedPatternCategory
  });
  var reusableBlocksTab = createElement(ReusableBlocksTab, {
    rootClientId: destinationRootClientId,
    onInsert: onInsert,
    onHover: onHover,
    filterValue: filterValue
  });

  var searchFormPlaceholder = function searchFormPlaceholder() {
    if (activeTab === 'reusable') {
      return __('Search for a reusable block');
    }

    if (activeTab === 'patterns') {
      return __('Search for a pattern');
    }

    return __('Search for a block');
  }; // Disable reason (no-autofocus): The inserter menu is a modal display, not one which
  // is always visible, and one which already incurs this behavior of autoFocus via
  // Popover's focusOnMount.
  // Disable reason (no-static-element-interactions): Navigational key-presses within
  // the menu are prevented from triggering WritingFlow and ObserveTyping interactions.

  /* eslint-disable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */


  return createElement("div", {
    className: "block-editor-inserter__menu",
    onKeyPress: stopKeyPropagation,
    onKeyDown: onKeyDown
  }, createElement("div", {
    className: "block-editor-inserter__main-area"
  }, createElement("div", {
    className: "block-editor-inserter__content"
  }, createElement(InserterSearchForm, {
    onChange: function onChange(value) {
      if (hoveredItem) setHoveredItem(null);
      setFilterValue(value);
    },
    value: filterValue,
    placeholder: searchFormPlaceholder()
  }), (showPatterns || hasReusableBlocks) && createElement(InserterTabs, {
    showPatterns: showPatterns,
    showReusableBlocks: hasReusableBlocks,
    onSelect: setActiveTab
  }, function (tab) {
    if (tab.name === 'blocks') {
      return blocksTab;
    } else if (tab.name === 'patterns') {
      return patternsTab;
    }

    return reusableBlocksTab;
  }), !showPatterns && !hasReusableBlocks && blocksTab)), showInserterHelpPanel && hoveredItem && createElement(InserterPreviewPanel, {
    item: hoveredItem
  }));
  /* eslint-enable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */
}

export default InserterMenu;
//# sourceMappingURL=menu.js.map