"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _compose = require("@wordpress/compose");

var _panel = _interopRequireDefault(require("./panel"));

var _patternPanel = _interopRequireDefault(require("./pattern-panel"));

var _searchItems = require("./search-items");

var _noResults = _interopRequireDefault(require("./no-results"));

var _usePatternsState5 = _interopRequireDefault(require("./hooks/use-patterns-state"));

var _blockPatternsList = _interopRequireDefault(require("../block-patterns-list"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockPatternsSearchResults(_ref) {
  var filterValue = _ref.filterValue,
      onInsert = _ref.onInsert;

  var _usePatternsState = (0, _usePatternsState5.default)(onInsert),
      _usePatternsState2 = (0, _slicedToArray2.default)(_usePatternsState, 3),
      allPatterns = _usePatternsState2[0],
      onClick = _usePatternsState2[2];

  var filteredPatterns = (0, _element.useMemo)(function () {
    return (0, _searchItems.searchItems)(allPatterns, filterValue);
  }, [filterValue, allPatterns]);
  var currentShownPatterns = (0, _compose.useAsyncList)(filteredPatterns);

  if (filterValue) {
    return !!filteredPatterns.length ? (0, _element.createElement)(_panel.default, {
      title: (0, _i18n.__)('Search Results')
    }, (0, _element.createElement)(_blockPatternsList.default, {
      shownPatterns: currentShownPatterns,
      blockPatterns: filteredPatterns,
      onClickPattern: onClick
    })) : (0, _element.createElement)(_noResults.default, null);
  }
}

function BlockPatternsCategory(_ref2) {
  var onInsert = _ref2.onInsert,
      selectedCategory = _ref2.selectedCategory,
      onClickCategory = _ref2.onClickCategory;

  var _usePatternsState3 = (0, _usePatternsState5.default)(onInsert),
      _usePatternsState4 = (0, _slicedToArray2.default)(_usePatternsState3, 3),
      allPatterns = _usePatternsState4[0],
      allCategories = _usePatternsState4[1],
      onClick = _usePatternsState4[2]; // Remove any empty categories


  var populatedCategories = (0, _element.useMemo)(function () {
    return allCategories.filter(function (category) {
      return allPatterns.some(function (pattern) {
        return pattern.categories.includes(category.name);
      });
    });
  }, [allPatterns, allCategories]);
  var patternCategory = selectedCategory ? selectedCategory : populatedCategories[0];
  (0, _element.useEffect)(function () {
    if (allPatterns.some(function (pattern) {
      return getPatternIndex(pattern) === Infinity;
    }) && !populatedCategories.find(function (category) {
      return category.name === 'uncategorized';
    })) {
      populatedCategories.push({
        name: 'uncategorized',
        label: (0, _i18n._x)('Uncategorized')
      });
    }
  }, [populatedCategories, allPatterns]);
  var getPatternIndex = (0, _element.useCallback)(function (pattern) {
    if (!pattern.categories || !pattern.categories.length) {
      return Infinity;
    }

    var indexedCategories = (0, _lodash.fromPairs)(populatedCategories.map(function (_ref3, index) {
      var name = _ref3.name;
      return [name, index];
    }));
    return Math.min.apply(Math, (0, _toConsumableArray2.default)(pattern.categories.map(function (cat) {
      return indexedCategories[cat] !== undefined ? indexedCategories[cat] : Infinity;
    })));
  }, [populatedCategories]);
  var currentCategoryPatterns = (0, _element.useMemo)(function () {
    return allPatterns.filter(function (pattern) {
      return patternCategory.name === 'uncategorized' ? getPatternIndex(pattern) === Infinity : pattern.categories && pattern.categories.includes(patternCategory.name);
    });
  }, [allPatterns, patternCategory]); // Ordering the patterns is important for the async rendering.

  var orderedPatterns = (0, _element.useMemo)(function () {
    return currentCategoryPatterns.sort(function (a, b) {
      return getPatternIndex(a) - getPatternIndex(b);
    });
  }, [currentCategoryPatterns, getPatternIndex]);
  var currentShownPatterns = (0, _compose.useAsyncList)(orderedPatterns);
  return (0, _element.createElement)(_element.Fragment, null, !!currentCategoryPatterns.length && (0, _element.createElement)(_patternPanel.default, {
    key: patternCategory.name,
    title: patternCategory.title,
    selectedCategory: patternCategory,
    patternCategories: populatedCategories,
    onClickCategory: onClickCategory
  }, (0, _element.createElement)(_blockPatternsList.default, {
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
  return filterValue ? (0, _element.createElement)(BlockPatternsSearchResults, {
    onInsert: onInsert,
    filterValue: filterValue
  }) : (0, _element.createElement)(BlockPatternsCategory, {
    selectedCategory: selectedCategory,
    onInsert: onInsert,
    onClickCategory: onClickCategory
  });
}

var _default = BlockPatternsTabs;
exports.default = _default;
//# sourceMappingURL=block-patterns-tab.js.map