import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
/**
 * WordPress dependencies
 */

import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockNavigationTree from './tree';

function BlockNavigation(_ref) {
  var rootBlock = _ref.rootBlock,
      rootBlocks = _ref.rootBlocks,
      selectedBlockClientId = _ref.selectedBlockClientId,
      selectBlock = _ref.selectBlock,
      __experimentalFeatures = _ref.__experimentalFeatures;

  if (!rootBlocks || rootBlocks.length === 0) {
    return null;
  }

  var hasHierarchy = rootBlock && (rootBlock.clientId !== selectedBlockClientId || rootBlock.innerBlocks && rootBlock.innerBlocks.length !== 0);
  return createElement("div", {
    className: "block-editor-block-navigation__container"
  }, createElement("p", {
    className: "block-editor-block-navigation__label"
  }, __('List view')), hasHierarchy && createElement(BlockNavigationTree, {
    blocks: [rootBlock],
    selectedBlockClientId: selectedBlockClientId,
    selectBlock: selectBlock,
    __experimentalFeatures: __experimentalFeatures,
    showNestedBlocks: true
  }), !hasHierarchy && createElement(BlockNavigationTree, {
    blocks: rootBlocks,
    selectedBlockClientId: selectedBlockClientId,
    selectBlock: selectBlock,
    __experimentalFeatures: __experimentalFeatures
  }));
}

export default compose(withSelect(function (select) {
  var _select = select('core/block-editor'),
      getSelectedBlockClientId = _select.getSelectedBlockClientId,
      getBlockHierarchyRootClientId = _select.getBlockHierarchyRootClientId,
      __unstableGetBlockWithBlockTree = _select.__unstableGetBlockWithBlockTree,
      __unstableGetBlockTree = _select.__unstableGetBlockTree;

  var selectedBlockClientId = getSelectedBlockClientId();
  return {
    rootBlocks: __unstableGetBlockTree(),
    rootBlock: selectedBlockClientId ? __unstableGetBlockWithBlockTree(getBlockHierarchyRootClientId(selectedBlockClientId)) : null,
    selectedBlockClientId: selectedBlockClientId
  };
}), withDispatch(function (dispatch, _ref2) {
  var _ref2$onSelect = _ref2.onSelect,
      onSelect = _ref2$onSelect === void 0 ? noop : _ref2$onSelect;
  return {
    selectBlock: function selectBlock(clientId) {
      dispatch('core/block-editor').selectBlock(clientId);
      onSelect(clientId);
    }
  };
}))(BlockNavigation);
//# sourceMappingURL=index.js.map