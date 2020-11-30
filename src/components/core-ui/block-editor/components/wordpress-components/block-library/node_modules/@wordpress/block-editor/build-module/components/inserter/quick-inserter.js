import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { orderBy } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useState, useMemo, useEffect } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { VisuallyHidden, Button, withSpokenMessages } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { LEFT, RIGHT, UP, DOWN, BACKSPACE, ENTER } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */

import BlockTypesList from '../block-types-list';
import BlockPatternsList from '../block-patterns-list';
import InserterSearchForm from './search-form';
import InserterPanel from './panel';
import InserterNoResults from './no-results';
import useInsertionPoint from './hooks/use-insertion-point';
import usePatternsState from './hooks/use-patterns-state';
import useBlockTypesState from './hooks/use-block-types-state';
import { searchBlockItems, searchItems } from './search-items';
var SEARCH_THRESHOLD = 6;
var SHOWN_BLOCK_TYPES = 6;
var SHOWN_BLOCK_PATTERNS = 2;

var preventArrowKeysPropagation = function preventArrowKeysPropagation(event) {
  if ([LEFT, DOWN, RIGHT, UP, BACKSPACE, ENTER].includes(event.keyCode)) {
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
  var shownBlockTypes = useMemo(function () {
    return orderBy(blockTypes, ['frecency'], ['desc']).slice(0, SHOWN_BLOCK_TYPES);
  }, [blockTypes]);
  var shownBlockPatterns = useMemo(function () {
    return blockPatterns.slice(0, SHOWN_BLOCK_PATTERNS);
  }, [blockTypes]);
  return createElement("div", {
    className: "block-editor-inserter__quick-inserter-results"
  }, !shownBlockTypes.length && !shownBlockPatterns.length && createElement(InserterNoResults, null), !!shownBlockTypes.length && createElement(InserterPanel, {
    title: createElement(VisuallyHidden, null, __('Blocks'))
  }, createElement(BlockTypesList, {
    items: shownBlockTypes,
    onSelect: onSelectBlockType,
    onHover: onHover,
    label: __('Blocks')
  })), !!shownBlockTypes.length && !!shownBlockPatterns.length && createElement("div", {
    className: "block-editor-inserter__quick-inserter-separator"
  }), !!shownBlockPatterns.length && createElement(InserterPanel, {
    title: createElement(VisuallyHidden, null, __('Blocks'))
  }, createElement("div", {
    className: "block-editor-inserter__quick-inserter-patterns"
  }, createElement(BlockPatternsList, {
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

  var _useState = useState(''),
      _useState2 = _slicedToArray(_useState, 2),
      filterValue = _useState2[0],
      setFilterValue = _useState2[1];

  var _useInsertionPoint = useInsertionPoint({
    onSelect: onSelect,
    rootClientId: rootClientId,
    clientId: clientId,
    isAppender: isAppender,
    selectBlockOnInsert: selectBlockOnInsert
  }),
      _useInsertionPoint2 = _slicedToArray(_useInsertionPoint, 3),
      destinationRootClientId = _useInsertionPoint2[0],
      onInsertBlocks = _useInsertionPoint2[1],
      onToggleInsertionPoint = _useInsertionPoint2[2];

  var _useBlockTypesState = useBlockTypesState(destinationRootClientId, onInsertBlocks),
      _useBlockTypesState2 = _slicedToArray(_useBlockTypesState, 4),
      blockTypes = _useBlockTypesState2[0],
      blockTypeCategories = _useBlockTypesState2[1],
      blockTypeCollections = _useBlockTypesState2[2],
      onSelectBlockType = _useBlockTypesState2[3];

  var _usePatternsState = usePatternsState(onInsertBlocks),
      _usePatternsState2 = _slicedToArray(_usePatternsState, 3),
      patterns = _usePatternsState2[0],
      onSelectBlockPattern = _usePatternsState2[2];

  var showPatterns = !destinationRootClientId && patterns.length && !!filterValue;
  var showSearch = showPatterns && patterns.length > SEARCH_THRESHOLD || blockTypes.length > SEARCH_THRESHOLD;
  var filteredBlockTypes = useMemo(function () {
    return searchBlockItems(blockTypes, blockTypeCategories, blockTypeCollections, filterValue);
  }, [filterValue, blockTypes, blockTypeCategories, blockTypeCollections]);
  var filteredBlockPatterns = useMemo(function () {
    return searchItems(patterns, filterValue);
  }, [filterValue, patterns]);
  var setInserterIsOpened = useSelect(function (select) {
    return select('core/block-editor').getSettings().__experimentalSetIsInserterOpened;
  }, []);
  var previousBlockClientId = useSelect(function (select) {
    return select('core/block-editor').getPreviousBlockClientId(clientId);
  }, [clientId]);
  useEffect(function () {
    if (setInserterIsOpened) {
      setInserterIsOpened(false);
    }
  }, [setInserterIsOpened]);

  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock; // Announce search results on change


  useEffect(function () {
    if (!filterValue) {
      return;
    }

    var count = filteredBlockTypes.length + filteredBlockPatterns.length;
    var resultsFoundMessage = sprintf(
    /* translators: %d: number of results. */
    _n('%d result found.', '%d results found.', count), count);
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


  return createElement("div", {
    className: classnames('block-editor-inserter__quick-inserter', {
      'has-search': showSearch,
      'has-expand': setInserterIsOpened
    }),
    onKeyPress: stopKeyPropagation,
    onKeyDown: preventArrowKeysPropagation
  }, showSearch && createElement(InserterSearchForm, {
    value: filterValue,
    onChange: function onChange(value) {
      setFilterValue(value);
    },
    placeholder: __('Search for a block')
  }), createElement(QuickInserterList, {
    blockTypes: filteredBlockTypes,
    blockPatterns: showPatterns ? filteredBlockPatterns : [],
    onSelectBlockPattern: onSelectBlockPattern,
    onSelectBlockType: onSelectBlockType,
    onHover: onToggleInsertionPoint
  }), setInserterIsOpened && createElement(Button, {
    className: "block-editor-inserter__quick-inserter-expand",
    onClick: onBrowseAll,
    "aria-label": __('Browse all. This will open the main inserter panel in the editor toolbar.')
  }, __('Browse all')));
  /* eslint-enable jsx-a11y/no-autofocus, jsx-a11y/no-static-element-interactions */
}

export default withSpokenMessages(QuickInserter);
//# sourceMappingURL=quick-inserter.js.map