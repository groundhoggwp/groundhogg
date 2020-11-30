import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { createSlotFill } from '@wordpress/components';
/**
 * Internal dependencies
 */

import useDisplayBlockControls from '../use-display-block-controls';

var _createSlotFill = createSlotFill('InspectorControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function InspectorControls(_ref) {
  var children = _ref.children;
  return useDisplayBlockControls() ? createElement(Fill, null, children) : null;
}

InspectorControls.Slot = Slot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inspector-controls/README.md
 */

export default InspectorControls;
//# sourceMappingURL=index.js.map