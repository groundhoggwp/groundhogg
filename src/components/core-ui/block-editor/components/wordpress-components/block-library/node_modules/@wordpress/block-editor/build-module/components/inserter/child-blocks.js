import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
export default function ChildBlocks(_ref) {
  var rootClientId = _ref.rootClientId,
      children = _ref.children;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/blocks'),
        getBlockType = _select.getBlockType;

    var _select2 = select('core/block-editor'),
        getBlockName = _select2.getBlockName;

    var rootBlockName = getBlockName(rootClientId);
    var rootBlockType = getBlockType(rootBlockName);
    return {
      rootBlockTitle: rootBlockType && rootBlockType.title,
      rootBlockIcon: rootBlockType && rootBlockType.icon
    };
  }),
      rootBlockTitle = _useSelect.rootBlockTitle,
      rootBlockIcon = _useSelect.rootBlockIcon;

  return createElement("div", {
    className: "block-editor-inserter__child-blocks"
  }, (rootBlockIcon || rootBlockTitle) && createElement("div", {
    className: "block-editor-inserter__parent-block-header"
  }, createElement(BlockIcon, {
    icon: rootBlockIcon,
    showColors: true
  }), rootBlockTitle && createElement("h2", null, rootBlockTitle)), children);
}
//# sourceMappingURL=child-blocks.js.map