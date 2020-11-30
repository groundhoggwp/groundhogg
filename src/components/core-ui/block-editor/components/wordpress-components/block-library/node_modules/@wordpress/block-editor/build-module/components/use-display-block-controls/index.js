/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';
export default function useDisplayBlockControls() {
  var _useBlockEditContext = useBlockEditContext(),
      isSelected = _useBlockEditContext.isSelected,
      clientId = _useBlockEditContext.clientId,
      name = _useBlockEditContext.name;

  var isFirstAndSameTypeMultiSelected = useSelect(function (select) {
    // Don't bother checking, see OR statement below.
    if (isSelected) {
      return;
    }

    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        isFirstMultiSelectedBlock = _select.isFirstMultiSelectedBlock,
        getMultiSelectedBlockClientIds = _select.getMultiSelectedBlockClientIds;

    if (!isFirstMultiSelectedBlock(clientId)) {
      return false;
    }

    return getMultiSelectedBlockClientIds().every(function (id) {
      return getBlockName(id) === name;
    });
  }, [clientId, isSelected, name]);
  return isSelected || isFirstAndSameTypeMultiSelected;
}
//# sourceMappingURL=index.js.map