import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { withSpokenMessages } from '@wordpress/components';
import { useMemo, useEffect } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
/**
 * Internal dependencies
 */

import BlockTypesList from '../block-types-list';
import { searchBlockItems } from './search-items';
import InserterPanel from './panel';
import InserterNoResults from './no-results';
import useBlockTypesState from './hooks/use-block-types-state';

function ReusableBlocksList(_ref) {
  var debouncedSpeak = _ref.debouncedSpeak,
      filterValue = _ref.filterValue,
      onHover = _ref.onHover,
      onInsert = _ref.onInsert,
      rootClientId = _ref.rootClientId;

  var _useBlockTypesState = useBlockTypesState(rootClientId, onInsert),
      _useBlockTypesState2 = _slicedToArray(_useBlockTypesState, 4),
      items = _useBlockTypesState2[0],
      categories = _useBlockTypesState2[1],
      collections = _useBlockTypesState2[2],
      onSelectItem = _useBlockTypesState2[3];

  var filteredItems = useMemo(function () {
    var reusableItems = items.filter(function (_ref2) {
      var category = _ref2.category;
      return category === 'reusable';
    });

    if (!filterValue) {
      return reusableItems;
    }

    return searchBlockItems(reusableItems, categories, collections, filterValue);
  }, [filterValue, items, categories, collections]); // Announce search results on change.

  useEffect(function () {
    var resultsFoundMessage = sprintf(
    /* translators: %d: number of results. */
    _n('%d result found.', '%d results found.', filteredItems.length), filteredItems.length);
    debouncedSpeak(resultsFoundMessage);
  }, [filterValue, debouncedSpeak]);

  if (filteredItems.length === 0) {
    return createElement(InserterNoResults, null);
  }

  return createElement(InserterPanel, {
    title: filterValue ? __('Search Results') : __('Reusable blocks')
  }, createElement(BlockTypesList, {
    items: filteredItems,
    onSelect: onSelectItem,
    onHover: onHover,
    label: filterValue ? __('Search Results') : __('Reusable blocks')
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


export function ReusableBlocksTab(_ref3) {
  var rootClientId = _ref3.rootClientId,
      onInsert = _ref3.onInsert,
      onHover = _ref3.onHover,
      filterValue = _ref3.filterValue,
      debouncedSpeak = _ref3.debouncedSpeak;
  return createElement(Fragment, null, createElement(ReusableBlocksList, {
    debouncedSpeak: debouncedSpeak,
    filterValue: filterValue,
    onHover: onHover,
    onInsert: onInsert,
    rootClientId: rootClientId
  }), createElement("div", {
    className: "block-editor-inserter__manage-reusable-blocks-container"
  }, createElement("a", {
    className: "block-editor-inserter__manage-reusable-blocks",
    href: addQueryArgs('edit.php', {
      post_type: 'wp_block'
    })
  }, __('Manage all reusable blocks'))));
}
export default withSpokenMessages(ReusableBlocksTab);
//# sourceMappingURL=reusable-blocks-tab.js.map