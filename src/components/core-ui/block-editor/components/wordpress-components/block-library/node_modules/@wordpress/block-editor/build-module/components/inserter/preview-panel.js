import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { isReusableBlock, createBlock, getBlockFromExample, getBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockCard from '../block-card';
import BlockPreview from '../block-preview';

function InserterPreviewPanel(_ref) {
  var item = _ref.item;
  var hoveredItemBlockType = getBlockType(item.name);
  return createElement("div", {
    className: "block-editor-inserter__preview-container"
  }, createElement("div", {
    className: "block-editor-inserter__preview"
  }, isReusableBlock(item) || hoveredItemBlockType.example ? createElement("div", {
    className: "block-editor-inserter__preview-content"
  }, createElement(BlockPreview, {
    __experimentalPadding: 16,
    viewportWidth: 500,
    blocks: hoveredItemBlockType.example ? getBlockFromExample(item.name, {
      attributes: _objectSpread(_objectSpread({}, hoveredItemBlockType.example.attributes), item.initialAttributes),
      innerBlocks: hoveredItemBlockType.example.innerBlocks
    }) : createBlock(item.name, item.initialAttributes)
  })) : createElement("div", {
    className: "block-editor-inserter__preview-content-missing"
  }, __('No Preview Available.'))), !isReusableBlock(item) && createElement(BlockCard, {
    blockType: item
  }));
}

export default InserterPreviewPanel;
//# sourceMappingURL=preview-panel.js.map