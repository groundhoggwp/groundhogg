"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = LinkControlSearchResults;

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _classnames = _interopRequireDefault(require("classnames"));

var _element = require("@wordpress/element");

var _searchCreateButton = _interopRequireDefault(require("./search-create-button"));

var _searchItem = _interopRequireDefault(require("./search-item"));

var _constants = require("./constants");

/**
 * WordPress dependencies
 */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function LinkControlSearchResults(_ref) {
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
  var resultsListClasses = (0, _classnames.default)('block-editor-link-control__search-results', {
    'is-loading': isLoading
  });
  var directLinkEntryTypes = ['url', 'mailto', 'tel', 'internal'];
  var isSingleDirectEntryResult = suggestions.length === 1 && directLinkEntryTypes.includes(suggestions[0].type.toLowerCase());
  var shouldShowCreateSuggestion = withCreateSuggestion && !isSingleDirectEntryResult && !isInitialSuggestions; // If the query has a specified type, then we can skip showing them in the result. See #24839.

  var shouldShowSuggestionsTypes = !(suggestionsQuery === null || suggestionsQuery === void 0 ? void 0 : suggestionsQuery.type); // According to guidelines aria-label should be added if the label
  // itself is not visible.
  // See: https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/listbox_role

  var searchResultsLabelId = "block-editor-link-control-search-results-label-".concat(instanceId);
  var labelText = isInitialSuggestions ? (0, _i18n.__)('Recently updated') : (0, _i18n.sprintf)(
  /* translators: %s: search term. */
  (0, _i18n.__)('Search results for "%s"'), currentInputValue); // VisuallyHidden rightly doesn't accept custom classNames
  // so we conditionally render it as a wrapper to visually hide the label
  // when that is required.

  var searchResultsLabel = (0, _element.createElement)(isInitialSuggestions ? _element.Fragment : _components.VisuallyHidden, {}, // empty props
  (0, _element.createElement)("span", {
    className: "block-editor-link-control__search-results-label",
    id: searchResultsLabelId
  }, labelText));
  return (0, _element.createElement)("div", {
    className: "block-editor-link-control__search-results-wrapper"
  }, searchResultsLabel, (0, _element.createElement)("div", (0, _extends2.default)({}, suggestionsListProps, {
    className: resultsListClasses,
    "aria-labelledby": searchResultsLabelId
  }), suggestions.map(function (suggestion, index) {
    if (shouldShowCreateSuggestion && _constants.CREATE_TYPE === suggestion.type) {
      return (0, _element.createElement)(_searchCreateButton.default, {
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


    if (_constants.CREATE_TYPE === suggestion.type) {
      return null;
    }

    return (0, _element.createElement)(_searchItem.default, {
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