import _extends from "@babel/runtime/helpers/esm/extends";

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { VisuallyHidden } from '@wordpress/components';
/**
 * External dependencies
 */

import classnames from 'classnames';
import { createElement, Fragment } from '@wordpress/element';
/**
 * Internal dependencies
 */

import LinkControlSearchCreate from './search-create-button';
import LinkControlSearchItem from './search-item';
import { CREATE_TYPE } from './constants';
export default function LinkControlSearchResults(_ref) {
  var instanceId = _ref.instanceId,
      withCreateSuggestion = _ref.withCreateSuggestion,
      currentInputValue = _ref.currentInputValue,
      handleSuggestionClick = _ref.handleSuggestionClick,
      suggestionsListProps = _ref.suggestionsListProps,
      buildSuggestionItemProps = _ref.buildSuggestionItemProps,
      suggestions = _ref.suggestions,
      selectedSuggestion = _ref.selectedSuggestion,
      isLoading = _ref.isLoading,
      isInitialSuggestions = _ref.isInitialSuggestions,
      createSuggestionButtonText = _ref.createSuggestionButtonText,
      suggestionsQuery = _ref.suggestionsQuery;
  var resultsListClasses = classnames('block-editor-link-control__search-results', {
    'is-loading': isLoading
  });
  var directLinkEntryTypes = ['url', 'mailto', 'tel', 'internal'];
  var isSingleDirectEntryResult = suggestions.length === 1 && directLinkEntryTypes.includes(suggestions[0].type.toLowerCase());
  var shouldShowCreateSuggestion = withCreateSuggestion && !isSingleDirectEntryResult && !isInitialSuggestions; // If the query has a specified type, then we can skip showing them in the result. See #24839.

  var shouldShowSuggestionsTypes = !(suggestionsQuery === null || suggestionsQuery === void 0 ? void 0 : suggestionsQuery.type); // According to guidelines aria-label should be added if the label
  // itself is not visible.
  // See: https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/listbox_role

  var searchResultsLabelId = "block-editor-link-control-search-results-label-".concat(instanceId);
  var labelText = isInitialSuggestions ? __('Recently updated') : sprintf(
  /* translators: %s: search term. */
  __('Search results for "%s"'), currentInputValue); // VisuallyHidden rightly doesn't accept custom classNames
  // so we conditionally render it as a wrapper to visually hide the label
  // when that is required.

  var searchResultsLabel = createElement(isInitialSuggestions ? Fragment : VisuallyHidden, {}, // empty props
  createElement("span", {
    className: "block-editor-link-control__search-results-label",
    id: searchResultsLabelId
  }, labelText));
  return createElement("div", {
    className: "block-editor-link-control__search-results-wrapper"
  }, searchResultsLabel, createElement("div", _extends({}, suggestionsListProps, {
    className: resultsListClasses,
    "aria-labelledby": searchResultsLabelId
  }), suggestions.map(function (suggestion, index) {
    if (shouldShowCreateSuggestion && CREATE_TYPE === suggestion.type) {
      return createElement(LinkControlSearchCreate, {
        searchTerm: currentInputValue,
        buttonText: createSuggestionButtonText,
        onClick: function onClick() {
          return handleSuggestionClick(suggestion);
        } // Intentionally only using `type` here as
        // the constant is enough to uniquely
        // identify the single "CREATE" suggestion.
        ,
        key: suggestion.type,
        itemProps: buildSuggestionItemProps(suggestion, index),
        isSelected: index === selectedSuggestion
      });
    } // If we're not handling "Create" suggestions above then
    // we don't want them in the main results so exit early


    if (CREATE_TYPE === suggestion.type) {
      return null;
    }

    return createElement(LinkControlSearchItem, {
      key: "".concat(suggestion.id, "-").concat(suggestion.type),
      itemProps: buildSuggestionItemProps(suggestion, index),
      suggestion: suggestion,
      index: index,
      onClick: function onClick() {
        handleSuggestionClick(suggestion);
      },
      isSelected: index === selectedSuggestion,
      isURL: directLinkEntryTypes.includes(suggestion.type.toLowerCase()),
      searchTerm: currentInputValue,
      shouldShowType: shouldShowSuggestionsTypes
    });
  })));
}
//# sourceMappingURL=search-results.js.map