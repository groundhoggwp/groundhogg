import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { getBlockType } from '@wordpress/blocks';
import { ToolbarButton } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockIcon from '../block-icon';
/**
 * Block parent selector component, displaying the hierarchy of the
 * current block selection as a single icon to "go up" a level.
 *
 * @return {WPComponent} Parent block selector.
 */

export default function BlockParentSelector() {
  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getBlockParents = _select.getBlockParents,
        getSelectedBlockClientId = _select.getSelectedBlockClientId;

    var selectedBlockClientId = getSelectedBlockClientId();
    var parents = getBlockParents(selectedBlockClientId);
    var _firstParentClientId = parents[parents.length - 1];
    var parentBlockName = getBlockName(_firstParentClientId);
    return {
      parentBlockType: getBlockType(parentBlockName),
      firstParentClientId: _firstParentClientId
    };
  }, []),
      parentBlockType = _useSelect.parentBlockType,
      firstParentClientId = _useSelect.firstParentClientId;

  if (firstParentClientId !== undefined) {
    return createElement("div", {
      className: "block-editor-block-parent-selector",
      key: firstParentClientId
    }, createElement(ToolbarButton, {
      className: "block-editor-block-parent-selector__button",
      onClick: function onClick() {
        return selectBlock(firstParentClientId);
      },
      label: sprintf(
      /* translators: %s: Name of the block's parent. */
      __('Select parent (%s)'), parentBlockType.title),
      showTooltip: true,
      icon: createElement(BlockIcon, {
        icon: parentBlockType.icon
      })
    }));
  }

  return null;
}
//# sourceMappingURL=index.js.map