import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { useContext } from '@wordpress/element';
import { __experimentalToolbarContext as ToolbarContext, createSlotFill } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';

var _createSlotFill = createSlotFill('BlockFormatControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function BlockFormatControlsSlot(props) {
  var accessibleToolbarState = useContext(ToolbarContext);
  return createElement(Slot, _extends({}, props, {
    fillProps: accessibleToolbarState
  }));
}

function BlockFormatControlsFill(props) {
  var _useBlockEditContext = useBlockEditContext(),
      isSelected = _useBlockEditContext.isSelected;

  if (!isSelected) {
    return null;
  }

  return createElement(Fill, null, function (fillProps) {
    var value = !isEmpty(fillProps) ? fillProps : null;
    return createElement(ToolbarContext.Provider, {
      value: value
    }, props.children);
  });
}

var BlockFormatControls = BlockFormatControlsFill;
BlockFormatControls.Slot = BlockFormatControlsSlot;
export default BlockFormatControls;
//# sourceMappingURL=index.js.map