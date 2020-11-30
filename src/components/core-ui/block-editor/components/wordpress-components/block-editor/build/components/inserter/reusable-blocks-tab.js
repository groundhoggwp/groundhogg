"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ReusableBlocksTab = ReusableBlocksTab;
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _url = require("@wordpress/url");

var _blockTypesList = _interopRequireDefault(require("../block-types-list"));

var _searchItems = require("./search-items");

var _panel = _interopRequireDefault(require("./panel"));

var _noResults = _interopRequireDefault(require("./no-results"));

var _useBlockTypesState3 = _interopRequireDefault(require("./hooks/use-block-types-state"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ReusableBlocksList(_ref) {
  var debouncedSpeak = _ref.debouncedSpeak,
      filterValue = _ref.filterValue,
      onHover = _ref.onHover,
      onInsert = _ref.onInsert,
      rootClientId = _ref.rootClientId;

  var _useBlockTypesState = (0, _useBlockTypesState3.default)(rootClientId, onInsert),
      _useBlockTypesState2 = (0, _slicedToArray2.default)(_useBlockTypesState, 4),
      items = _useBlockTypesState2[0],
      categories = _useBlockTypesState2[1],
      collections = _useBlockTypesState2[2],
      onSelectItem = _useBlockTypesState2[3];

  var filteredItems = (0, _element.useMemo)(function () {
    var reusableItems = items.filter(function (_ref2) {
      var category = _ref2.category;
      return category === 'reusable';
    });

    if (!filterValue) {
      return reusableItems;
    }

    return (0, _searchItems.searchBlockItems)(reusableItems, categories, collections, filterValue);
  }, [filterValue, items, categories, collections]); // Announce search results on change.

  (0, _element.useEffect)(function () {
    var resultsFoundMessage = (0, _i18n.sprintf)(
    /* translators: %d: number of results. */
    (0, _i18n._n)('%d result found.', '%d results found.', filteredItems.length), filteredItems.length);
    debouncedSpeak(resultsFoundMessage);
  }, [filterValue, debouncedSpeak]);

  if (filteredItems.length === 0) {
    return (0, _element.createElement)(_noResults.default, null);
  }

  return (0, _element.createElement)(_panel.default, {
    title: filterValue ? (0, _i18n.__)('Search Results') : (0, _i18n.__)('Reusable blocks')
  }, (0, _element.createElement)(_blockTypesList.default, {
    items: filteredItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: filterValue ? (0, _i18n.__)('Search Results') : (0, _i18n.__)('Reusable blocks')
  }));
} // The unwrapped component is only exported for use by unit tests.

/**
 * List of reusable blocks shown in the "Reusable" tab of the inserter.
 *
 * @param {Object}   props                Component props.
 * @param {?string}  props.rootClientId   Client id of block to insert into.
 * @param {Function} props.onInsert       Callback to run when item is inserted.
 * @param {Function} props.onHover        Callback to run when item is hovered.
 * @param {?string}  props.filterValue    Search term.
 * @param {Function} props.debouncedSpeak Debounced speak function.
 *
 * @return {WPComponent} The component.
 */


function ReusableBlocksTab(_ref3) {
  var rootClientId = _ref3.rootClientId,
      onInsert = _ref3.onInsert,
      onHover = _ref3.onHover,
      filterValue = _ref3.filterValue,
      debouncedSpeak = _ref3.debouncedSpeak;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(ReusableBlocksList, {
    debouncedSpeak: debouncedSpeak,
    filterValue: filterValue,
    onHover: onHover,
    onInsert: onInsert,
    rootClientId: rootClientId
  }), (0, _element.createElement)("div", {
    className: "block-editor-inserter__manage-reusable-blocks-container"
  }, (0, _element.createElement)("a", {
    className: "block-editor-inserter__manage-reusable-blocks",
    href: (0, _url.addQueryArgs)('edit.php', {
      post_type: 'wp_block'
    })
  }, (0, _i18n.__)('Manage all reusable blocks'))));
}

var _default = (0, _components.withSpokenMessages)(ReusableBlocksTab);

exports.default = _default;
//# sourceMappingURL=reusable-blocks-tab.js.map