"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _blocks = require("@wordpress/blocks");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
// Copied over from the Columns block. It seems like it should become part of public API.
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
/**
 * Retrieves the block types inserter state.
 *
 * @param {string=}  rootClientId        Insertion's root client ID.
 * @param {Function} onInsert            function called when inserter a list of blocks.
 * @return {Array} Returns the block types state. (block types, categories, collections, onSelect handler)
 */


var useBlockTypesState = function useBlockTypesState(rootClientId, onInsert) {
  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getInserterItems = _select.getInserterItems,
        getSettings = _select.getSettings;

    var _select2 = select('core/blocks'),
        getCategories = _select2.getCategories,
        getCollections = _select2.getCollections;

    var _getSettings = getSettings(),
        __experimentalFetchReusableBlocks = _getSettings.__experimentalFetchReusableBlocks;

    return {
      categories: getCategories(),
      collections: getCollections(),
      items: getInserterItems(rootClientId),
      fetchReusableBlocks: __experimentalFetchReusableBlocks
    };
  }, [rootClientId]),
      categories = _useSelect.categories,
      collections = _useSelect.collections,
      items = _useSelect.items,
      fetchReusableBlocks = _useSelect.fetchReusableBlocks; // Fetch resuable blocks on mount


  (0, _element.useEffect)(function () {
    if (fetchReusableBlocks) {
      fetchReusableBlocks();
    }
  }, []);

  var onSelectItem = function onSelectItem(_ref3) {
    var name = _ref3.name,
        initialAttributes = _ref3.initialAttributes,
        innerBlocks = _ref3.innerBlocks;
    var insertedBlock = (0, _blocks.createBlock)(name, initialAttributes, createBlocksFromInnerBlocksTemplate(innerBlocks));
    onInsert(insertedBlock);
  };

  return [items, categories, collections, onSelectItem];
};

var _default = useBlockTypesState;
exports.default = _default;
//# sourceMappingURL=use-block-types-state.js.map