"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockNavigationList;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

/**
 * WordPress dependencies
 */
function BlockNavigationList(_ref) {
  var clientId = _ref.clientId,
      __experimentalFeatures = _ref.__experimentalFeatures;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSelectedBlockClientId = _select.getSelectedBlockClientId,
        getBlock = _select.getBlock;

    return {
      block: getBlock(clientId),
      selectedBlockClientId: getSelectedBlockClientId()
    };
  }, [clientId]),
      block = _useSelect.block,
      selectedBlockClientId = _useSelect.selectedBlockClientId;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  return (0, _element.createElement)(_blockEditor.__experimentalBlockNavigationTree, {
    blocks: block.innerBlocks,
    selectedBlockClientId: selectedBlockClientId,
    selectBlock: selectBlock,
    __experimentalFeatures: __experimentalFeatures,
    showNestedBlocks: true,
    showAppender: true,
    showBlockMovers: true
  });
}
//# sourceMappingURL=block-navigation-list.js.map