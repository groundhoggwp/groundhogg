"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _tree = _interopRequireDefault(require("./tree"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
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
  return (0, _element.createElement)("div", {
    className: "block-editor-block-navigation__container"
  }, (0, _element.createElement)("p", {
    className: "block-editor-block-navigation__label"
  }, (0, _i18n.__)('List view')), hasHierarchy && (0, _element.createElement)(_tree.default, {
    blocks: [rootBlock],
    selectedBlockClientId: selectedBlockClientId,
    selectBlock: selectBlock,
    __experimentalFeatures: __experimentalFeatures,
    showNestedBlocks: true
  }), !hasHierarchy && (0, _element.createElement)(_tree.default, {
    blocks: rootBlocks,
    selectedBlockClientId: selectedBlockClientId,
    selectBlock: selectBlock,
    __experimentalFeatures: __experimentalFeatures
  }));
}

var _default = (0, _compose.compose)((0, _data.withSelect)(function (select) {
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
}), (0, _data.withDispatch)(function (dispatch, _ref2) {
  var _ref2$onSelect = _ref2.onSelect,
      onSelect = _ref2$onSelect === void 0 ? _lodash.noop : _ref2$onSelect;
  return {
    selectBlock: function selectBlock(clientId) {
      dispatch('core/block-editor').selectBlock(clientId);
      onSelect(clientId);
    }
  };
}))(BlockNavigation);

exports.default = _default;
//# sourceMappingURL=index.js.map