import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createSlotFill } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';
var name = 'InspectorAdvancedControls';

var _createSlotFill = createSlotFill(name),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function InspectorAdvancedControls(_ref) {
  var children = _ref.children;

  var _useBlockEditContext = useBlockEditContext(),
      isSelected = _useBlockEditContext.isSelected;

  return isSelected ? createElement(Fill, null, children) : null;
}

InspectorAdvancedControls.slotName = name;
InspectorAdvancedControls.Slot = Slot;
export default InspectorAdvancedControls;
//# sourceMappingURL=index.js.map