"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getItemSearchRank = getItemSearchRank;
exports.searchItems = exports.searchBlockItems = exports.getNormalizedSearchTerms = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

/**
 * External dependencies
 */
// Default search helpers
var defaultGetName = function defaultGetName(item) {
  return item.name || '';
};

var defaultGetTitle = function defaultGetTitle(item) {
  return item.title;
};

var defaultGetKeywords = function defaultGetKeywords(item) {
  return item.keywords || [];
};

var defaultGetCategory = function defaultGetCategory(item) {
  return item.category;
};

var defaultGetCollection = function defaultGetCollection() {
  return null;
};
/**
 * Sanitizes the search input string.
 *
 * @param {string} input The search input to normalize.
 *
 * @return {string} The normalized search input.
 */


function normalizeSearchInput() {
  var input = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  // Disregard diacritics.
  //  Input: "média"
  input = (0, _lodash.deburr)(input); // Accommodate leading slash, matching autocomplete expectations.
  //  Input: "/media"

  input = input.replace(/^\//, ''); // Lowercase.
  //  Input: "MEDIA"

  input = input.toLowerCase();
  return input;
}
/**
 * Converts the search term into a list of normalized terms.
 *
 * @param {string} input The search term to normalize.
 *
 * @return {string[]} The normalized list of search terms.
 */


var getNormalizedSearchTerms = function getNormalizedSearchTerms() {
  var input = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  // Extract words.
  return (0, _lodash.words)(normalizeSearchInput(input));
};

exports.getNormalizedSearchTerms = getNormalizedSearchTerms;

var removeMatchingTerms = function removeMatchingTerms(unmatchedTerms, unprocessedTerms) {
  return (0, _lodash.differenceWith)(unmatchedTerms, getNormalizedSearchTerms(unprocessedTerms), function (unmatchedTerm, unprocessedTerm) {
    return unprocessedTerm.includes(unmatchedTerm);
  });
};

var searchBlockItems = function searchBlockItems(items, categories, collections, searchInput) {
  var normalizedSearchTerms = getNormalizedSearchTerms(searchInput);

  if (normalizedSearchTerms.length === 0) {
    return items;
  }

  var config = {
    getCategory: function getCategory(item) {
      var _find;

      return (_find = (0, _lodash.find)(categories, {
        slug: item.category
      })) === null || _find === void 0 ? void 0 : _find.title;
    },
    getCollection: function getCollection(item) {
      var _collections$item$nam;

      return (_collections$item$nam = collections[item.name.split('/')[0]]) === null || _collections$item$nam === void 0 ? void 0 : _collections$item$nam.title;
    },
    getVariations: function getVariations(_ref) {
      var _ref$variations = _ref.variations,
          variations = _ref$variations === void 0 ? [] : _ref$variations;
      return Array.from(variations.reduce(function (accumulator, _ref2) {
        var title = _ref2.title,
            _ref2$keywords = _ref2.keywords,
            keywords = _ref2$keywords === void 0 ? [] : _ref2$keywords;
        accumulator.add(title);
        keywords.forEach(function (keyword) {
          return accumulator.add(keyword);
        });
        return accumulator;
      }, new Set()));
    }
  };
  return searchItems(items, searchInput, config);
};
/**
 * Filters an item list given a search term.
 *
 * @param {Array}  items       Item list
 * @param {string} searchInput Search input.
 * @param {Object} config      Search Config.
 * @return {Array}             Filtered item list.
 */


exports.searchBlockItems = searchBlockItems;

var searchItems = function searchItems() {
  var items = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var searchInput = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var config = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var normalizedSearchTerms = getNormalizedSearchTerms(searchInput);

  if (normalizedSearchTerms.length === 0) {
    return items;
  }

  var rankedItems = items.map(function (item) {
    return [item, getItemSearchRank(item, searchInput, config)];
  }).filter(function (_ref3) {
    var _ref4 = (0, _slicedToArray2.default)(_ref3, 2),
        rank = _ref4[1];

    return rank > 0;
  });
  rankedItems.sort(function (_ref5, _ref6) {
    var _ref7 = (0, _slicedToArray2.default)(_ref5, 2),
        rank1 = _ref7[1];

    var _ref8 = (0, _slicedToArray2.default)(_ref6, 2),
        rank2 = _ref8[1];

    return rank2 - rank1;
  });
  return rankedItems.map(function (_ref9) {
    var _ref10 = (0, _slicedToArray2.default)(_ref9, 1),
        item = _ref10[0];

    return item;
  });
};
/**
 * Get the search rank for a given item and a specific search term.
 * The better the match, the higher the rank.
 * If the rank equals 0, it should be excluded from the results.
 *
 * @param {Object} item       Item to filter.
 * @param {string} searchTerm Search term.
 * @param {Object} config     Search Config.
 * @return {number}           Search Rank.
 */


exports.searchItems = searchItems;

function getItemSearchRank(item, searchTerm) {
  var config = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var _config$getName = config.getName,
      getName = _config$getName === void 0 ? defaultGetName : _config$getName,
      _config$getTitle = config.getTitle,
      getTitle = _config$getTitle === void 0 ? defaultGetTitle : _config$getTitle,
      _config$getKeywords = config.getKeywords,
      getKeywords = _config$getKeywords === void 0 ? defaultGetKeywords : _config$getKeywords,
      _config$getCategory = config.getCategory,
      getCategory = _config$getCategory === void 0 ? defaultGetCategory : _config$getCategory,
      _config$getCollection = config.getCollection,
      getCollection = _config$getCollection === void 0 ? defaultGetCollection : _config$getCollection;
  var name = getName(item);
  var title = getTitle(item);
  var keywords = getKeywords(item);
  var category = getCategory(item);
  var collection = getCollection(item);
  var normalizedSearchInput = normalizeSearchInput(searchTerm);
  var normalizedTitle = normalizeSearchInput(title);
  var rank = 0; // Prefers exact matches
  // Then prefers if the beginning of the title matches the search term
  // name, keywords, categories, collection, variations match come later.

  if (normalizedSearchInput === normalizedTitle) {
    rank += 30;
  } else if (normalizedTitle.startsWith(normalizedSearchInput)) {
    rank += 20;
  } else {
    var terms = [name, title].concat((0, _toConsumableArray2.default)(keywords), [category, collection]).join(' ');
    var normalizedSearchTerms = (0, _lodash.words)(normalizedSearchInput);
    var unmatchedTerms = removeMatchingTerms(normalizedSearchTerms, terms);

    if (unmatchedTerms.length === 0) {
      rank += 10;
    }
  } // Give a better rank to "core" namespaced items.


  if (rank !== 0 && name.startsWith('core/')) {
    rank++;
  }

  return rank;
}
//# sourceMappingURL=search-items.js.map