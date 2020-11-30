"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockTypesTab = BlockTypesTab;
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _blockTypesList = _interopRequireDefault(require("../block-types-list"));

var _childBlocks = _interopRequireDefault(require("./child-blocks"));

var _inserterMenuExtension = _interopRequireDefault(require("../inserter-menu-extension"));

var _searchItems = require("./search-items");

var _panel = _interopRequireDefault(require("./panel"));

var _noResults = _interopRequireDefault(require("./no-results"));

var _useBlockTypesState3 = _interopRequireDefault(require("./hooks/use-block-types-state"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var getBlockNamespace = function getBlockNamespace(item) {
  return item.name.split('/')[0];
};

var MAX_SUGGESTED_ITEMS = 6;

function BlockTypesTab(_ref) {
  var rootClientId = _ref.rootClientId,
      onInsert = _ref.onInsert,
      onHover = _ref.onHover,
      filterValue = _ref.filterValue,
      debouncedSpeak = _ref.debouncedSpeak,
      showMostUsedBlocks = _ref.showMostUsedBlocks;

  var _useBlockTypesState = (0, _useBlockTypesState3.default)(rootClientId, onInsert),
      _useBlockTypesState2 = (0, _slicedToArray2.default)(_useBlockTypesState, 4),
      items = _useBlockTypesState2[0],
      categories = _useBlockTypesState2[1],
      collections = _useBlockTypesState2[2],
      onSelectItem = _useBlockTypesState2[3];

  var hasChildItems = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName;

    var _select2 = select('core/blocks'),
        getChildBlockNames = _select2.getChildBlockNames;

    var rootBlockName = getBlockName(rootClientId);
    return !!getChildBlockNames(rootBlockName).length;
  }, [rootClientId]);
  var filteredItems = (0, _element.useMemo)(function () {
    return (0, _searchItems.searchBlockItems)(items, categories, collections, filterValue);
  }, [filterValue, items, categories, collections]);
  var suggestedItems = (0, _element.useMemo)(function () {
    return (0, _lodash.orderBy)(items, ['frecency'], ['desc']).slice(0, MAX_SUGGESTED_ITEMS);
  }, [items]);
  var uncategorizedItems = (0, _element.useMemo)(function () {
    return filteredItems.filter(function (item) {
      return !item.category;
    });
  }, [filteredItems]);
  var itemsPerCategory = (0, _element.useMemo)(function () {
    var getCategoryIndex = function getCategoryIndex(item) {
      return (0, _lodash.findIndex)(categories, function (category) {
        return category.slug === item.category;
      });
    };

    return (0, _lodash.flow)(function (itemList) {
      return itemList.filter(function (item) {
        return item.category && item.category !== 'reusable';
      });
    }, function (itemList) {
      return (0, _lodash.sortBy)(itemList, getCategoryIndex);
    }, function (itemList) {
      return (0, _lodash.groupBy)(itemList, 'category');
    })(filteredItems);
  }, [filteredItems, categories]);
  var itemsPerCollection = (0, _element.useMemo)(function () {
    // Create a new Object to avoid mutating collection.
    var result = _objectSpread({}, collections);

    Object.keys(collections).forEach(function (namespace) {
      result[namespace] = filteredItems.filter(function (item) {
        return getBlockNamespace(item) === namespace;
      });

      if (result[namespace].length === 0) {
        delete result[namespace];
      }
    });
    return result;
  }, [filteredItems, collections]); // Hide block preview on unmount.

  (0, _element.useEffect)(function () {
    return function () {
      return onHover(null);
    };
  }, []); // Announce search results on change.

  (0, _element.useEffect)(function () {
    var resultsFoundMessage = (0, _i18n.sprintf)(
    /* translators: %d: number of results. */
    (0, _i18n._n)('%d result found.', '%d results found.', filteredItems.length), filteredItems.length);
    debouncedSpeak(resultsFoundMessage);
  }, [filterValue, debouncedSpeak]);
  var hasItems = !(0, _lodash.isEmpty)(filteredItems);
  return (0, _element.createElement)("div", null, hasChildItems && (0, _element.createElement)(_childBlocks.default, {
    rootClientId: rootClientId
  }, (0, _element.createElement)(_blockTypesList.default // Pass along every block, as useBlockTypesState() and
  // getInserterItems() will have already filtered out
  // non-child blocks.
  , {
    items: filteredItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: (0, _i18n.__)('Child Blocks')
  })), showMostUsedBlocks && !hasChildItems && !!suggestedItems.length && !filterValue && (0, _element.createElement)(_panel.default, {
    title: (0, _i18n._x)('Most used', 'blocks')
  }, (0, _element.createElement)(_blockTypesList.default, {
    items: suggestedItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: (0, _i18n._x)('Most used', 'blocks')
  })), !hasChildItems && (0, _lodash.map)(categories, function (category) {
    var categoryItems = itemsPerCategory[category.slug];

    if (!categoryItems || !categoryItems.length) {
      return null;
    }

    return (0, _element.createElement)(_panel.default, {
      key: category.slug,
      title: category.title,
      icon: category.icon
    }, (0, _element.createElement)(_blockTypesList.default, {
      items: categoryItems,
      onSelect: onSelectItem,
      onHover: onHover,
      label: category.title
    }));
  }), !hasChildItems && !!uncategorizedItems.length && (0, _element.createElement)(_panel.default, {
    className: "block-editor-inserter__uncategorized-blocks-panel",
    title: (0, _i18n.__)('Uncategorized')
  }, (0, _element.createElement)(_blockTypesList.default, {
    items: uncategorizedItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: (0, _i18n.__)('Uncategorized')
  })), !hasChildItems && (0, _lodash.map)(collections, function (collection, namespace) {
    var collectionItems = itemsPerCollection[namespace];

    if (!collectionItems || !collectionItems.length) {
      return null;
    }

    return (0, _element.createElement)(_panel.default, {
      key: namespace,
      title: collection.title,
      icon: collection.icon
    }, (0, _element.createElement)(_blockTypesList.default, {
      items: collectionItems,
      onSelect: onSelectItem,
      onHover: onHover,
      label: collection.title
    }));
  }), (0, _element.createElement)(_inserterMenuExtension.default.Slot, {
    fillProps: {
      onSelect: onSelectItem,
      onHover: onHover,
      filterValue: filterValue,
      hasItems: hasItems
    }
  }, function (fills) {
    if (fills.length) {
      return fills;
    }

    if (!hasItems) {
      return (0, _element.createElement)(_noResults.default, null);
    }

    return null;
  }));
}

var _default = (0, _components.withSpokenMessages)(BlockTypesTab);

exports.default = _default;
//# sourceMappingURL=block-types-tab.js.map