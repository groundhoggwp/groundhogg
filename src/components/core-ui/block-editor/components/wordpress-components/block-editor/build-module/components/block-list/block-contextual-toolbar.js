import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { hasBlockSupport } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import NavigableToolbar from '../navigable-toolbar';
import { BlockToolbar } from '../';

function BlockContextualToolbar(_ref) {
  var focusOnMount = _ref.focusOnMount,
      props = _objectWithoutProperties(_ref, ["focusOnMount"]);

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds;

    var _select2 = select('core/blocks'),
        getBlockType = _select2.getBlockType;

    var selectedBlockClientIds = getSelectedBlockClientIds();
    var selectedBlockClientId = selectedBlockClientIds[0];
    return {
      blockType: selectedBlockClientId && getBlockType(getBlockName(selectedBlockClientId))
    };
  }, []),
      blockType = _useSelect.blockType;

  if (blockType) {
    if (!hasBlockSupport(blockType, '__experimentalToolbar', true)) {
      return null;
    }
  }

  return createElement("div", {
    className: "block-editor-block-contextual-toolbar-wrapper"
  }, createElement(NavigableToolbar, _extends({
    focusOnMount: focusOnMount,
    className: "block-editor-block-contextual-toolbar"
    /* translators: accessibility text for the block toolbar */
    ,
    "aria-label": __('Block tools')
  }, props), createElement(BlockToolbar, null)));
}

export default BlockContextualToolbar;
//# sourceMappingURL=block-contextual-toolbar.js.map