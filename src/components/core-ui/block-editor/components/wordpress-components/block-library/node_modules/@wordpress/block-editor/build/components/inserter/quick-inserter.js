"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _keycodes = require("@wordpress/keycodes");

var _blockTypesList = _interopRequireDefault(require("../block-types-list"));

var _blockPatternsList = _interopRequireDefault(require("../block-patterns-list"));

var _searchForm = _interopRequireDefault(require("./search-form"));

var _panel = _interopRequireDefault(require("./panel"));

var _noResults = _interopRequireDefault(require("./no-results"));

var _useInsertionPoint3 = _interopRequireDefault(require("./hooks/use-insertion-point"));

var _usePatternsState3 = _interopRequireDefault(require("./hooks/use-patterns-state"));

var _useBlockTypesState3 = _interopRequireDefault(require("./hooks/use-block-types-state"));

var _searchItems = require("./search-items");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var SEARCH_THRESHOLD = 6;
var SHOWN_BLOCK_TYPES = 6;
var SHOWN_BLOCK_PATTERNS = 2;

var preventArrowKeysPropagation = function preventArrowKeysPropagation(event) {
  if ([_keycodes.LEFT, _keycodes.DOWN, _keycodes.RIGHT, _keycodes.UP, _keycodes.BACKSPACE, _keycodes.ENTER].includes(event.keyCode)) {
    // Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
    event.stopPropagation();
  }
};

var stopKeyPropagation = function stopKeyPropagation(event) {
  return event.stopPropagation();
};

function QuickInserterList(_ref) {
  var blockTypes = _ref.blockTypes,
      blockPatterns = _ref.blockPatterns,
      onSelectBlockType = _ref.onSelectBlockType,
      onSelectBlockPattern = _ref.onSelectBlockPattern,
      onHover = _ref.onHover;
  var shownBlockTypes = (0, _element.useMemo)(function () {
    return (0, _lodash.orderBy)(blockTypes, ['frecency'], ['desc']).slice(0, SHOWN_BLOCK_TYPES);
  }, [blockTypes]);
  var shownBlockPatterns = (0, _element.useMemo)(function () {
    return blockPatterns.slice(0, SHOWN_BLOCK_PATTERNS);
  }, [blockTypes]);
  return (0, _element.createElement)("div", {
    className: "block-editor-inserter__quick-inserter-results"
  }, !shownBlockTypes.length && !shownBlockPatterns.length && (0, _element.createElement)(_noResults.default, null), !!shownBlockTypes.length && (0, _element.createElement)(_panel.default, {
    title: (0, _element.createElement)(_components.VisuallyHidden, null, (0, _i18n.__)('Blocks'))
  }, (0, _element.createElement)(_blockTypesList.default, {
    items: shownBlockTypes,
    onSelect: onSelectBlockType,
    onHover: onHover,
    label: (0, _i18n.__)('Blocks')
  })), !!shownBlockTypes.length && !!shownBlockPatterns.length && (0, _element.createElement)("div", {
    className: "block-editor-inserter__quick-inserter-separator"
  }), !!shownBlockPatterns.length && (0, _element.createElement)(_panel.default, {
    title: (0, _element.createElement)(_components.VisuallyHidden, null, (0, _i18n.__)('Blocks'))
  }, (0, _element.createElement)("div", {
    className: "block-editor-inserter__quick-inserter-patterns"
  }, (0, _element.createElement)(_blockPatternsList.default, {
    shownPatterns: shownBlockPatterns,
    blockPatterns: shownBlockPatterns,
    onClickPattern: onSelectBlockPattern
  }))));
}

function QuickInserter(_ref2) {
  var onSelect = _ref2.onSelect,
      rootClientId = _ref2.rootClientId,
      clientId = _ref2.clientId,
      isAppender = _ref2.isAppender,
      selectBlockOnInsert = _ref2.selectBlockOnInsert,
      debouncedSpeak = _ref2.debouncedSpeak;

  var _useState = (0, _element.useState)(''),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      filterValue = _useState2[0],
      setFilterValue = _useState2[1];

  var _useInsertionPoint = (0, _useInsertionPoint3.default)({
    onSelect: onSelect,
    rootClientId: rootClientId,
    clientId: clientId,
    isAppender: isAppender,
    selectBlockOnInsert: selectBlockOnInsert
  }),
      _useInsertionPoint2 = (0, _slicedToArray2.default)(_useInsertionPoint, 3),
      destinationRootClientId = _useInsertionPoint2[0],
      onInsertBlocks = _useInsertionPoint2[1],
      onToggleInsertionPoint = _useInsertionPoint2[2];

  var _useBlockTypesState = (0, _useBlockTypesState3.default)(destinationRootClientId, onInsertBlocks),
      _useBlockTypesState2 = (0, _slicedToArray2.default)(_useBlockTypesState, 4),
      blockTypes = _useBlockTypesState2[0],
      blockTypeCategories = _useBlockTypesState2[1],
      blockTypeCollections = _useBlockTypesState2[2],
      onSelectBlockType = _useBlockTypesState2[3];

  var _usePatternsState = (0, _usePatternsState3.default)(onInsertBlocks),
      _usePatternsState2 = (0, _slicedToArray2.default)(_usePatternsState, 3),
      patterns = _usePatternsState2[0],
      onSelectBlockPattern = _usePatternsState2[2];

  var showPatterns = !destinationRootClientId && patterns.length && !!filterValue;
  var showSearch = showPatterns && patterns.length > SEARCH_THRESHOLD || blockTypes.length > SEARCH_THRESHOLD;
  var filteredBlockTypes = (0, _element.useMemo)(function () {
    return (0, _searchItems.searchBlockItems)(blockTypes, blockTypeCategories, blockTypeCollections, filterValue);
  }, [filterValue, blockTypes, blockTypeCategories, blockTypeCollections]);
  var filteredBlockPatterns = (0, _element.useMemo)(function () {
    return (0, _searchItems.searchItems)(patterns, filterValue);
  }, [filterValue, patterns]);
  var setInserterIsOpened = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings().__experimentalSetIsInserterOpened;
  }, []);
  var previousBlockClientId = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getPreviousBlockClientId(clientId);
  }, [clientId]);
  (0, _element.useEffect)(function () {
    if (setInserterIsOpened) {
      setInserterIsOpened(false);
    }
  }, [setInserterIsOpened]);

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock; // Announce search results on change


  (0, _element.useEffect)(function () {
    if (!filterValue) {
      return;
    }

    var count = filteredBlockTypes.length + filteredBlockPatterns.length;
    var resultsFoundMessage = (0, _i18n.sprintf)(
    /* translators: %d: number of results. */
    (0, _i18n._n)('%d result found.', '%d results found.', count), count);
    debouncedSpeak(resultsFoundMessage);
  }, [filterValue, debouncedSpeak]); // When clicking Browse All select the appropriate block so as
  // the insertion point can work as expected

  var onBrowseAll = function onBrowseAll() {
    // We have to select the previous block because the menu inserter
    // inserts the new block after the selected one.
    // Ideally, this selection shouldn't focus the block to avoid the setTimeout.
    selectBlock(previousBlockClientId); // eslint-disable-next-line @wordpress/react-no-unsafe-timeout

    setTimeout(function () {
      setInserterIsOpened(true);
    });
  }; // Disable reason (no-autofocus): The inserter menu is a modal display, not one which
  // is always visible, and one which already incurs this behavior of autoFocus via
  // Popover's focusOnMount.
  // Disable reason (no-static-element-interactions): Navigational key-presses within
  // the menu are prevented from triggering WritingFlow and ObserveTyping interactions.

  /* eslint-disable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */


  return (0, _element.createElement)("div", {
    className: (0, _classnames.default)('block-editor-inserter__quick-inserter', {
      'has-search': showSearch,
      'has-expand': setInserterIsOpened
    }),
    onKeyPress: stopKeyPropagation,
    onKeyDown: preventArrowKeysPropagation
  }, showSearch && (0, _element.createElement)(_searchForm.default, {
    value: filterValue,
    onChange: function onChange(value) {
      setFilterValue(value);
    },
    placeholder: (0, _i18n.__)('Search for a block')
  }), (0, _element.createElement)(QuickInserterList, {
    blockTypes: filteredBlockTypes,
    blockPatterns: showPatterns ? filteredBlockPatterns : [],
    onSelectBlockPattern: onSelectBlockPattern,
    onSelectBlockType: onSelectBlockType,
    onHover: onToggleInsertionPoint
  }), setInserterIsOpened && (0, _element.createElement)(_components.Button, {
    className: "block-editor-inserter__quick-inserter-expand",
    onClick: onBrowseAll,
    "aria-label": (0, _i18n.__)('Browse all. This will open the main inserter panel in the editor toolbar.')
  }, (0, _i18n.__)('Browse all')));
  /* eslint-enable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */
}

var _default = (0, _components.withSpokenMessages)(QuickInserter);

exports.default = _default;
//# sourceMappingURL=quick-inserter.js.map