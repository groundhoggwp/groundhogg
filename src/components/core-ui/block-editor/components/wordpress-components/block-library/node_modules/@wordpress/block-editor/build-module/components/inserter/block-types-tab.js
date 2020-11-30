import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { map, findIndex, flow, sortBy, groupBy, isEmpty, orderBy } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, _x, _n, sprintf } from '@wordpress/i18n';
import { withSpokenMessages } from '@wordpress/components';
import { useMemo, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockTypesList from '../block-types-list';
import ChildBlocks from './child-blocks';
import __experimentalInserterMenuExtension from '../inserter-menu-extension';
import { searchBlockItems } from './search-items';
import InserterPanel from './panel';
import InserterNoResults from './no-results';
import useBlockTypesState from './hooks/use-block-types-state';

var getBlockNamespace = function getBlockNamespace(item) {
  return item.name.split('/')[0];
};

var MAX_SUGGESTED_ITEMS = 6;
export function BlockTypesTab(_ref) {
  var rootClientId = _ref.rootClientId,
      onInsert = _ref.onInsert,
      onHover = _ref.onHover,
      filterValue = _ref.filterValue,
      debouncedSpeak = _ref.debouncedSpeak,
      showMostUsedBlocks = _ref.showMostUsedBlocks;

  var _useBlockTypesState = useBlockTypesState(rootClientId, onInsert),
      _useBlockTypesState2 = _slicedToArray(_useBlockTypesState, 4),
      items = _useBlockTypesState2[0],
      categories = _useBlockTypesState2[1],
      collections = _useBlockTypesState2[2],
      onSelectItem = _useBlockTypesState2[3];

  var hasChildItems = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName;

    var _select2 = select('core/blocks'),
        getChildBlockNames = _select2.getChildBlockNames;

    var rootBlockName = getBlockName(rootClientId);
    return !!getChildBlockNames(rootBlockName).length;
  }, [rootClientId]);
  var filteredItems = useMemo(function () {
    return searchBlockItems(items, categories, collections, filterValue);
  }, [filterValue, items, categories, collections]);
  var suggestedItems = useMemo(function () {
    return orderBy(items, ['frecency'], ['desc']).slice(0, MAX_SUGGESTED_ITEMS);
  }, [items]);
  var uncategorizedItems = useMemo(function () {
    return filteredItems.filter(function (item) {
      return !item.category;
    });
  }, [filteredItems]);
  var itemsPerCategory = useMemo(function () {
    var getCategoryIndex = function getCategoryIndex(item) {
      return findIndex(categories, function (category) {
        return category.slug === item.category;
      });
    };

    return flow(function (itemList) {
      return itemList.filter(function (item) {
        return item.category && item.category !== 'reusable';
      });
    }, function (itemList) {
      return sortBy(itemList, getCategoryIndex);
    }, function (itemList) {
      return groupBy(itemList, 'category');
    })(filteredItems);
  }, [filteredItems, categories]);
  var itemsPerCollection = useMemo(function () {
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

  useEffect(function () {
    return function () {
      return onHover(null);
    };
  }, []); // Announce search results on change.

  useEffect(function () {
    var resultsFoundMessage = sprintf(
    /* translators: %d: number of results. */
    _n('%d result found.', '%d results found.', filteredItems.length), filteredItems.length);
    debouncedSpeak(resultsFoundMessage);
  }, [filterValue, debouncedSpeak]);
  var hasItems = !isEmpty(filteredItems);
  return createElement("div", null, hasChildItems && createElement(ChildBlocks, {
    rootClientId: rootClientId
  }, createElement(BlockTypesList // Pass along every block, as useBlockTypesState() and
  // getInserterItems() will have already filtered out
  // non-child blocks.
  , {
    items: filteredItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: __('Child Blocks')
  })), showMostUsedBlocks && !hasChildItems && !!suggestedItems.length && !filterValue && createElement(InserterPanel, {
    title: _x('Most used', 'blocks')
  }, createElement(BlockTypesList, {
    items: suggestedItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: _x('Most used', 'blocks')
  })), !hasChildItems && map(categories, function (category) {
    var categoryItems = itemsPerCategory[category.slug];

    if (!categoryItems || !categoryItems.length) {
      return null;
    }

    return createElement(InserterPanel, {
      key: category.slug,
      title: category.title,
      icon: category.icon
    }, createElement(BlockTypesList, {
      items: categoryItems,
      onSelect: onSelectItem,
      onHover: onHover,
      label: category.title
    }));
  }), !hasChildItems && !!uncategorizedItems.length && createElement(InserterPanel, {
    className: "block-editor-inserter__uncategorized-blocks-panel",
    title: __('Uncategorized')
  }, createElement(BlockTypesList, {
    items: uncategorizedItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: __('Uncategorized')
  })), !hasChildItems && map(collections, function (collection, namespace) {
    var collectionItems = itemsPerCollection[namespace];

    if (!collectionItems || !collectionItems.length) {
      return null;
    }

    return createElement(InserterPanel, {
      key: namespace,
      title: collection.title,
      icon: collection.icon
    }, createElement(BlockTypesList, {
      items: collectionItems,
      onSelect: onSelectItem,
      onHover: onHover,
      label: collection.title
    }));
  }), createElement(__experimentalInserterMenuExtension.Slot, {
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
      return createElement(InserterNoResults, null);
    }

    return null;
  }));
}
export default withSpokenMessages(BlockTypesTab);
//# sourceMappingURL=block-types-tab.js.map