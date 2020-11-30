"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = QueryToolbar;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _utils = require("../utils");

var _constants = require("../constants");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function QueryToolbar(_ref) {
  var query = _ref.query,
      setQuery = _ref.setQuery;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core'),
        getEntityRecords = _select.getEntityRecords;

    var termsQuery = {
      per_page: _constants.MAX_FETCHED_TERMS
    };

    var _categories = getEntityRecords('taxonomy', 'category', termsQuery);

    var _tags = getEntityRecords('taxonomy', 'post_tag', termsQuery);

    return {
      categories: (0, _utils.getTermsInfo)(_categories),
      tags: (0, _utils.getTermsInfo)(_tags)
    };
  }, []),
      categories = _useSelect.categories,
      tags = _useSelect.tags;

  var _useState = (0, _element.useState)(query.search),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      querySearch = _useState2[0],
      setQuerySearch = _useState2[1];

  var onChangeDebounced = (0, _element.useCallback)((0, _lodash.debounce)(function () {
    return setQuery({
      search: querySearch
    });
  }, 250), [querySearch]);
  (0, _element.useEffect)(function () {
    onChangeDebounced();
    return onChangeDebounced.cancel;
  }, [querySearch, onChangeDebounced]); // Handles categories and tags changes.

  var onTermsChange = function onTermsChange(terms, queryProperty) {
    return function (newTermValues) {
      var termIds = newTermValues.reduce(function (accumulator, termValue) {
        var _terms$mapByName$term;

        var termId = (termValue === null || termValue === void 0 ? void 0 : termValue.id) || ((_terms$mapByName$term = terms.mapByName[termValue]) === null || _terms$mapByName$term === void 0 ? void 0 : _terms$mapByName$term.id);
        if (termId) accumulator.push(termId);
        return accumulator;
      }, []);
      setQuery((0, _defineProperty2.default)({}, queryProperty, termIds));
    };
  };

  var onCategoriesChange = onTermsChange(categories, 'categoryIds');
  var onTagsChange = onTermsChange(tags, 'tagIds');
  return (0, _element.createElement)(_components.Toolbar, null, (0, _element.createElement)(_components.Dropdown, {
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle;
      return (0, _element.createElement)(_components.ToolbarButton, {
        icon: _icons.postList,
        label: (0, _i18n.__)('Query'),
        onClick: onToggle
      });
    },
    renderContent: function renderContent() {
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Posts per Page'),
        min: 1,
        allowReset: true,
        value: query.perPage,
        onChange: function onChange(value) {
          return setQuery({
            perPage: value !== null && value !== void 0 ? value : -1
          });
        }
      }), (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Number of Pages'),
        min: 1,
        allowReset: true,
        value: query.pages,
        onChange: function onChange(value) {
          return setQuery({
            pages: value !== null && value !== void 0 ? value : -1
          });
        }
      }), (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Offset'),
        min: 0,
        allowReset: true,
        value: query.offset,
        onChange: function onChange(value) {
          return setQuery({
            offset: value !== null && value !== void 0 ? value : 0
          });
        }
      }), (categories === null || categories === void 0 ? void 0 : categories.terms) && (0, _element.createElement)(_components.FormTokenField, {
        label: (0, _i18n.__)('Categories'),
        value: query.categoryIds.map(function (categoryId) {
          return {
            id: categoryId,
            value: categories.mapById[categoryId].name
          };
        }),
        suggestions: categories.names,
        onChange: onCategoriesChange
      }), (tags === null || tags === void 0 ? void 0 : tags.terms) && (0, _element.createElement)(_components.FormTokenField, {
        label: (0, _i18n.__)('Tags'),
        value: (query.tagIds || []).map(function (tagId) {
          return {
            id: tagId,
            value: tags.mapById[tagId].name
          };
        }),
        suggestions: tags.names,
        onChange: onTagsChange
      }), (0, _element.createElement)(_components.TextControl, {
        label: (0, _i18n.__)('Search'),
        value: querySearch,
        onChange: function onChange(value) {
          return setQuerySearch(value);
        }
      }));
    }
  }));
}
//# sourceMappingURL=query-toolbar.js.map