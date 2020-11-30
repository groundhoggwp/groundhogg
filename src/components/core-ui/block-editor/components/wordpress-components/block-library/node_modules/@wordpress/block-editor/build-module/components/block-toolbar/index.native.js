import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import BlockControls from '../block-controls';
import BlockFormatControls from '../block-format-controls';
import UngroupButton from '../ungroup-button';
export default function BlockToolbar() {
  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlockMode = _select.getBlockMode,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
        isBlockValid = _select.isBlockValid;

    var selectedBlockClientIds = getSelectedBlockClientIds();
    return {
      blockClientIds: selectedBlockClientIds,
      isValid: selectedBlockClientIds.length === 1 ? isBlockValid(selectedBlockClientIds[0]) : null,
      mode: selectedBlockClientIds.length === 1 ? getBlockMode(selectedBlockClientIds[0]) : null
    };
  }, []),
      blockClientIds = _useSelect.blockClientIds,
      isValid = _useSelect.isValid,
      mode = _useSelect.mode;

  if (blockClientIds.length === 0) {
    return null;
  }

  return createElement(Fragment, null, mode === 'visual' && isValid && createElement(Fragment, null, createElement(UngroupButton, null), createElement(BlockControls.Slot, null), createElement(BlockFormatControls.Slot, null)));
}
//# sourceMappingURL=index.native.js.map