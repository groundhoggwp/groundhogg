import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalBlockNavigationTree } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
export default function BlockNavigationList(_ref) {
  var clientId = _ref.clientId,
      __experimentalFeatures = _ref.__experimentalFeatures;

  var _useSelect = useSelect(function (select) {
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

  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  return createElement(__experimentalBlockNavigationTree, {
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