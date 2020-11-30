import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { debounce } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { Toolbar, Dropdown, ToolbarButton, RangeControl, TextControl, FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { postList } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { getTermsInfo } from '../utils';
import { MAX_FETCHED_TERMS } from '../constants';
export default function QueryToolbar(_ref) {
  var query = _ref.query,
      setQuery = _ref.setQuery;

  var _useSelect = useSelect(function (select) {
    var _select = select('core'),
        getEntityRecords = _select.getEntityRecords;

    var termsQuery = {
      per_page: MAX_FETCHED_TERMS
    };

    var _categories = getEntityRecords('taxonomy', 'category', termsQuery);

    var _tags = getEntityRecords('taxonomy', 'post_tag', termsQuery);

    return {
      categories: getTermsInfo(_categories),
      tags: getTermsInfo(_tags)
    };
  }, []),
      categories = _useSelect.categories,
      tags = _useSelect.tags;

  var _useState = useState(query.search),
      _useState2 = _slicedToArray(_useState, 2),
      querySearch = _useState2[0],
      setQuerySearch = _useState2[1];

  var onChangeDebounced = useCallback(debounce(function () {
    return setQuery({
      search: querySearch
    });
  }, 250), [querySearch]);
  useEffect(function () {
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
      setQuery(_defineProperty({}, queryProperty, termIds));
    };
  };

  var onCategoriesChange = onTermsChange(categories, 'categoryIds');
  var onTagsChange = onTermsChange(tags, 'tagIds');
  return createElement(Toolbar, null, createElement(Dropdown, {
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle;
      return createElement(ToolbarButton, {
        icon: postList,
        label: __('Query'),
        onClick: onToggle
      });
    },
    renderContent: function renderContent() {
      return createElement(Fragment, null, createElement(RangeControl, {
        label: __('Posts per Page'),
        min: 1,
        allowReset: true,
        value: query.perPage,
        onChange: function onChange(value) {
          return setQuery({
            perPage: value !== null && value !== void 0 ? value : -1
          });
        }
      }), createElement(RangeControl, {
        label: __('Number of Pages'),
        min: 1,
        allowReset: true,
        value: query.pages,
        onChange: function onChange(value) {
          return setQuery({
            pages: value !== null && value !== void 0 ? value : -1
          });
        }
      }), createElement(RangeControl, {
        label: __('Offset'),
        min: 0,
        allowReset: true,
        value: query.offset,
        onChange: function onChange(value) {
          return setQuery({
            offset: value !== null && value !== void 0 ? value : 0
          });
        }
      }), (categories === null || categories === void 0 ? void 0 : categories.terms) && createElement(FormTokenField, {
        label: __('Categories'),
        value: query.categoryIds.map(function (categoryId) {
          return {
            id: categoryId,
            value: categories.mapById[categoryId].name
          };
        }),
        suggestions: categories.names,
        onChange: onCategoriesChange
      }), (tags === null || tags === void 0 ? void 0 : tags.terms) && createElement(FormTokenField, {
        label: __('Tags'),
        value: (query.tagIds || []).map(function (tagId) {
          return {
            id: tagId,
            value: tags.mapById[tagId].name
          };
        }),
        suggestions: tags.names,
        onChange: onTagsChange
      }), createElement(TextControl, {
        label: __('Search'),
        value: querySearch,
        onChange: function onChange(value) {
          return setQuerySearch(value);
        }
      }));
    }
  }));
}
//# sourceMappingURL=query-toolbar.js.map