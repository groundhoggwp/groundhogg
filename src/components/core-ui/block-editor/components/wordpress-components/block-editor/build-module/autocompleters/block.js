import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop, map, orderBy } from 'lodash';
/**
 * WordPress dependencies
 */

import { useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { searchBlockItems } from '../components/inserter/search-items';
import useBlockTypesState from '../components/inserter/hooks/use-block-types-state';
import BlockIcon from '../components/block-icon';
var SHOWN_BLOCK_TYPES = 9;

var createBlocksFromInnerBlocksTemplate = function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return map(innerBlocksTemplate, function (_ref) {
    var _ref2 = _slicedToArray(_ref, 3),
        name = _ref2[0],
        attributes = _ref2[1],
        _ref2$ = _ref2[2],
        innerBlocks = _ref2$ === void 0 ? [] : _ref2$;

    return createBlock(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
  });
};
/** @typedef {import('@wordpress/block-editor').WPEditorInserterItem} WPEditorInserterItem */

/** @typedef {import('@wordpress/components').WPCompleter} WPCompleter */

/**
 * Creates a blocks repeater for replacing the current block with a selected block type.
 *
 * @param {Object} props                                   Component props.
 * @param {string} [props.getBlockInsertionParentClientId] Client ID of the parent.
 * @param {string} [props.getInserterItems]                Inserter items for parent.
 * @param {string} [props.getSelectedBlockName]            Name of selected block or null.
 *
 * @return {WPCompleter} A blocks completer.
 */


function createBlockCompleter() {
  return {
    name: 'blocks',
    className: 'block-editor-autocompleters__block',
    triggerPrefix: '/',
    useItems: function useItems(filterValue) {
      var _useSelect = useSelect(function (select) {
        var _select = select('core/block-editor'),
            getSelectedBlockClientId = _select.getSelectedBlockClientId,
            getBlockName = _select.getBlockName,
            getBlockInsertionPoint = _select.getBlockInsertionPoint;

        var selectedBlockClientId = getSelectedBlockClientId();
        return {
          selectedBlockName: selectedBlockClientId ? getBlockName(selectedBlockClientId) : null,
          rootClientId: getBlockInsertionPoint().rootClientId
        };
      }, []),
          rootClientId = _useSelect.rootClientId,
          selectedBlockName = _useSelect.selectedBlockName;

      var _useBlockTypesState = useBlockTypesState(rootClientId, noop),
          _useBlockTypesState2 = _slicedToArray(_useBlockTypesState, 3),
          items = _useBlockTypesState2[0],
          categories = _useBlockTypesState2[1],
          collections = _useBlockTypesState2[2];

      var filteredItems = useMemo(function () {
        var initialFilteredItems = !!filterValue.trim() ? searchBlockItems(items, categories, collections, filterValue) : orderBy(items, ['frecency'], ['desc']);
        return initialFilteredItems.filter(function (item) {
          return item.name !== selectedBlockName;
        }).slice(0, SHOWN_BLOCK_TYPES);
      }, [filterValue, selectedBlockName, items, categories, collections]);
      var options = useMemo(function () {
        return filteredItems.map(function (blockItem) {
          var title = blockItem.title,
              icon = blockItem.icon,
              isDisabled = blockItem.isDisabled;
          return {
            key: "block-".concat(blockItem.id),
            value: blockItem,
            label: createElement(Fragment, null, createElement(BlockIcon, {
              key: "icon",
              icon: icon,
              showColors: true
            }), title),
            isDisabled: isDisabled
          };
        });
      }, [filteredItems]);
      return [options];
    },
    allowContext: function allowContext(before, after) {
      return !(/\S/.test(before) || /\S/.test(after));
    },
    getOptionCompletion: function getOptionCompletion(inserterItem) {
      var name = inserterItem.name,
          initialAttributes = inserterItem.initialAttributes,
          innerBlocks = inserterItem.innerBlocks;
      return {
        action: 'replace',
        value: createBlock(name, initialAttributes, createBlocksFromInnerBlocksTemplate(innerBlocks))
      };
    }
  };
}
/**
 * Creates a blocks repeater for replacing the current block with a selected block type.
 *
 * @return {WPCompleter} A blocks completer.
 */


export default createBlockCompleter();
//# sourceMappingURL=block.js.map