"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _searchItems = require("../components/inserter/search-items");

var _useBlockTypesState3 = _interopRequireDefault(require("../components/inserter/hooks/use-block-types-state"));

var _blockIcon = _interopRequireDefault(require("../components/block-icon"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var SHOWN_BLOCK_TYPES = 9;

var createBlocksFromInnerBlocksTemplate = function createBlocksFromInnerBlocksTemplate(innerBlocksTemplate) {
  return (0, _lodash.map)(innerBlocksTemplate, function (_ref) {
    var _ref2 = (0, _slicedToArray2.default)(_ref, 3),
        name = _ref2[0],
        attributes = _ref2[1],
        _ref2$ = _ref2[2],
        innerBlocks = _ref2$ === void 0 ? [] : _ref2$;

    return (0, _blocks.createBlock)(name, attributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
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
      var _useSelect = (0, _data.useSelect)(function (select) {
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

      var _useBlockTypesState = (0, _useBlockTypesState3.default)(rootClientId, _lodash.noop),
          _useBlockTypesState2 = (0, _slicedToArray2.default)(_useBlockTypesState, 3),
          items = _useBlockTypesState2[0],
          categories = _useBlockTypesState2[1],
          collections = _useBlockTypesState2[2];

      var filteredItems = (0, _element.useMemo)(function () {
        var initialFilteredItems = !!filterValue.trim() ? (0, _searchItems.searchBlockItems)(items, categories, collections, filterValue) : (0, _lodash.orderBy)(items, ['frecency'], ['desc']);
        return initialFilteredItems.filter(function (item) {
          return item.name !== selectedBlockName;
        }).slice(0, SHOWN_BLOCK_TYPES);
      }, [filterValue, selectedBlockName, items, categories, collections]);
      var options = (0, _element.useMemo)(function () {
        return filteredItems.map(function (blockItem) {
          var title = blockItem.title,
              icon = blockItem.icon,
              isDisabled = blockItem.isDisabled;
          return {
            key: "block-".concat(blockItem.id),
            value: blockItem,
            label: (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockIcon.default, {
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
        value: (0, _blocks.createBlock)(name, initialAttributes, createBlocksFromInnerBlocksTemplate(innerBlocks))
      };
    }
  };
}
/**
 * Creates a blocks repeater for replacing the current block with a selected block type.
 *
 * @return {WPCompleter} A blocks completer.
 */


var _default = createBlockCompleter();

exports.default = _default;
//# sourceMappingURL=block.js.map