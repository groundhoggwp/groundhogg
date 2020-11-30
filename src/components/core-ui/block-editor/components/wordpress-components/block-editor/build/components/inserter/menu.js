"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _keycodes = require("@wordpress/keycodes");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _tips = _interopRequireDefault(require("./tips"));

var _searchForm = _interopRequireDefault(require("./search-form"));

var _previewPanel = _interopRequireDefault(require("./preview-panel"));

var _blockTypesTab = _interopRequireDefault(require("./block-types-tab"));

var _blockPatternsTab = _interopRequireDefault(require("./block-patterns-tab"));

var _reusableBlocksTab = _interopRequireDefault(require("./reusable-blocks-tab"));

var _useInsertionPoint3 = _interopRequireDefault(require("./hooks/use-insertion-point"));

var _tabs = _interopRequireDefault(require("./tabs"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
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

  var _useState = (0, _element.useState)('blocks'),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      activeTab = _useState2[0],
      setActiveTab = _useState2[1];

  var _useState3 = (0, _element.useState)(''),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      filterValue = _useState4[0],
      setFilterValue = _useState4[1];

  var _useState5 = (0, _element.useState)(null),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      hoveredItem = _useState6[0],
      setHoveredItem = _useState6[1];

  var _useState7 = (0, _element.useState)(null),
      _useState8 = (0, _slicedToArray2.default)(_useState7, 2),
      selectedPatternCategory = _useState8[0],
      setSelectedPatternCategory = _useState8[1];

  var _useInsertionPoint = (0, _useInsertionPoint3.default)({
    rootClientId: rootClientId,
    clientId: clientId,
    isAppender: isAppender,
    selectBlockOnInsert: __experimentalSelectBlockOnInsert
  }),
      _useInsertionPoint2 = (0, _slicedToArray2.default)(_useInsertionPoint, 3),
      destinationRootClientId = _useInsertionPoint2[0],
      onInsertBlocks = _useInsertionPoint2[1],
      onToggleInsertionPoint = _useInsertionPoint2[2];

  var _useSelect = (0, _data.useSelect)(function (select) {
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
    if ([_keycodes.LEFT, _keycodes.DOWN, _keycodes.RIGHT, _keycodes.UP, _keycodes.BACKSPACE, _keycodes.ENTER].includes(event.keyCode)) {
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

  var blocksTab = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: "block-editor-inserter__block-list"
  }, (0, _element.createElement)(_blockTypesTab.default, {
    rootClientId: destinationRootClientId,
    onInsert: onInsert,
    onHover: onHover,
    filterValue: filterValue,
    showMostUsedBlocks: showMostUsedBlocks
  })), showInserterHelpPanel && (0, _element.createElement)("div", {
    className: "block-editor-inserter__tips"
  }, (0, _element.createElement)(_components.VisuallyHidden, {
    as: "h2"
  }, (0, _i18n.__)('A tip for using the block editor')), (0, _element.createElement)(_tips.default, null)));
  var patternsTab = (0, _element.createElement)(_blockPatternsTab.default, {
    onInsert: onInsertPattern,
    filterValue: filterValue,
    onClickCategory: onClickPatternCategory,
    selectedCategory: selectedPatternCategory
  });
  var reusableBlocksTab = (0, _element.createElement)(_reusableBlocksTab.default, {
    rootClientId: destinationRootClientId,
    onInsert: onInsert,
    onHover: onHover,
    filterValue: filterValue
  });

  var searchFormPlaceholder = function searchFormPlaceholder() {
    if (activeTab === 'reusable') {
      return (0, _i18n.__)('Search for a reusable block');
    }

    if (activeTab === 'patterns') {
      return (0, _i18n.__)('Search for a pattern');
    }

    return (0, _i18n.__)('Search for a block');
  }; // Disable reason (no-autofocus): The inserter menu is a modal display, not one which
  // is always visible, and one which already incurs this behavior of autoFocus via
  // Popover's focusOnMount.
  // Disable reason (no-static-element-interactions): Navigational key-presses within
  // the menu are prevented from triggering WritingFlow and ObserveTyping interactions.

  /* eslint-disable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */


  return (0, _element.createElement)("div", {
    className: "block-editor-inserter__menu",
    onKeyPress: stopKeyPropagation,
    onKeyDown: onKeyDown
  }, (0, _element.createElement)("div", {
    className: "block-editor-inserter__main-area"
  }, (0, _element.createElement)("div", {
    className: "block-editor-inserter__content"
  }, (0, _element.createElement)(_searchForm.default, {
    onChange: function onChange(value) {
      if (hoveredItem) setHoveredItem(null);
      setFilterValue(value);
    },
    value: filterValue,
    placeholder: searchFormPlaceholder()
  }), (showPatterns || hasReusableBlocks) && (0, _element.createElement)(_tabs.default, {
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
  }), !showPatterns && !hasReusableBlocks && blocksTab)), showInserterHelpPanel && hoveredItem && (0, _element.createElement)(_previewPanel.default, {
    item: hoveredItem
  }));
  /* eslint-enable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */
}

var _default = InserterMenu;
exports.default = _default;
//# sourceMappingURL=menu.js.map