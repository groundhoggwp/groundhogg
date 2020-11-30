import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { fromPairs } from 'lodash';
/**
 * WordPress dependencies
 */

import { useMemo, useCallback, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { useAsyncList } from '@wordpress/compose';
/**
 * Internal dependencies
 */

import InserterPanel from './panel';
import PatternInserterPanel from './pattern-panel';
import { searchItems } from './search-items';
import InserterNoResults from './no-results';
import usePatternsState from './hooks/use-patterns-state';
import BlockPatternList from '../block-patterns-list';

function BlockPatternsSearchResults(_ref) {
  var filterValue = _ref.filterValue,
      onInsert = _ref.onInsert;

  var _usePatternsState = usePatternsState(onInsert),
      _usePatternsState2 = _slicedToArray(_usePatternsState, 3),
      allPatterns = _usePatternsState2[0],
      onClick = _usePatternsState2[2];

  var filteredPatterns = useMemo(function () {
    return searchItems(allPatterns, filterValue);
  }, [filterValue, allPatterns]);
  var currentShownPatterns = useAsyncList(filteredPatterns);

  if (filterValue) {
    return !!filteredPatterns.length ? createElement(InserterPanel, {
      title: __('Search Results')
    }, createElement(BlockPatternList, {
      shownPatterns: currentShownPatterns,
      blockPatterns: filteredPatterns,
      onClickPattern: onClick
    })) : createElement(InserterNoResults, null);
  }
}

function BlockPatternsCategory(_ref2) {
  var onInsert = _ref2.onInsert,
      selectedCategory = _ref2.selectedCategory,
      onClickCategory = _ref2.onClickCategory;

  var _usePatternsState3 = usePatternsState(onInsert),
      _usePatternsState4 = _slicedToArray(_usePatternsState3, 3),
      allPatterns = _usePatternsState4[0],
      allCategories = _usePatternsState4[1],
      onClick = _usePatternsState4[2]; // Remove any empty categories


  var populatedCategories = useMemo(function () {
    return allCategories.filter(function (category) {
      return allPatterns.some(function (pattern) {
        return pattern.categories.includes(category.name);
      });
    });
  }, [allPatterns, allCategories]);
  var patternCategory = selectedCategory ? selectedCategory : populatedCategories[0];
  useEffect(function () {
    if (allPatterns.some(function (pattern) {
      return getPatternIndex(pattern) === Infinity;
    }) && !populatedCategories.find(function (category) {
      return category.name === 'uncategorized';
    })) {
      populatedCategories.push({
        name: 'uncategorized',
        label: _x('Uncategorized')
      });
    }
  }, [populatedCategories, allPatterns]);
  var getPatternIndex = useCallback(function (pattern) {
    if (!pattern.categories || !pattern.categories.length) {
      return Infinity;
    }

    var indexedCategories = fromPairs(populatedCategories.map(function (_ref3, index) {
      var name = _ref3.name;
      return [name, index];
    }));
    return Math.min.apply(Math, _toConsumableArray(pattern.categories.map(function (cat) {
      return indexedCategories[cat] !== undefined ? indexedCategories[cat] : Infinity;
    })));
  }, [populatedCategories]);
  var currentCategoryPatterns = useMemo(function () {
    return allPatterns.filter(function (pattern) {
      return patternCategory.name === 'uncategorized' ? getPatternIndex(pattern) === Infinity : pattern.categories && pattern.categories.includes(patternCategory.name);
    });
  }, [allPatterns, patternCategory]); // Ordering the patterns is important for the async rendering.

  var orderedPatterns = useMemo(function () {
    return currentCategoryPatterns.sort(function (a, b) {
      return getPatternIndex(a) - getPatternIndex(b);
    });
  }, [currentCategoryPatterns, getPatternIndex]);
  var currentShownPatterns = useAsyncList(orderedPatterns);
  return createElement(Fragment, null, !!currentCategoryPatterns.length && createElement(PatternInserterPanel, {
    key: patternCategory.name,
    title: patternCategory.title,
    selectedCategory: patternCategory,
    patternCategories: populatedCategories,
    onClickCategory: onClickCategory
  }, createElement(BlockPatternList, {
    shownPatterns: currentShownPatterns,
    blockPatterns: currentCategoryPatterns,
    onClickPattern: onClick
  })));
}

function BlockPatternsTabs(_ref4) {
  var onInsert = _ref4.onInsert,
      onClickCategory = _ref4.onClickCategory,
      filterValue = _ref4.filterValue,
      selectedCategory = _ref4.selectedCategory;
  return filterValue ? createElement(BlockPatternsSearchResults, {
    onInsert: onInsert,
    filterValue: filterValue
  }) : createElement(BlockPatternsCategory, {
    selectedCategory: selectedCategory,
    onInsert: onInsert,
    onClickCategory: onClickCategory
  });
}

export default BlockPatternsTabs;
//# sourceMappingURL=block-patterns-tab.js.map