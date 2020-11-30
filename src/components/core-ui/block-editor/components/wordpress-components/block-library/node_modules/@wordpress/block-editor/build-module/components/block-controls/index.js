import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { useContext } from '@wordpress/element';
import { __experimentalToolbarContext as ToolbarContext, createSlotFill, ToolbarGroup } from '@wordpress/components';
/**
 * Internal dependencies
 */

import useDisplayBlockControls from '../use-display-block-controls';

var _createSlotFill = createSlotFill('BlockControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

function BlockControlsSlot(_ref) {
  var _ref$__experimentalIs = _ref.__experimentalIsExpanded,
      __experimentalIsExpanded = _ref$__experimentalIs === void 0 ? false : _ref$__experimentalIs,
      props = _objectWithoutProperties(_ref, ["__experimentalIsExpanded"]);

  var accessibleToolbarState = useContext(ToolbarContext);
  return createElement(Slot, _extends({
    name: buildSlotName(__experimentalIsExpanded)
  }, props, {
    fillProps: accessibleToolbarState
  }));
}

function BlockControlsFill(_ref2) {
  var controls = _ref2.controls,
      __experimentalIsExpanded = _ref2.__experimentalIsExpanded,
      children = _ref2.children;

  if (!useDisplayBlockControls()) {
    return null;
  }

  return createElement(Fill, {
    name: buildSlotName(__experimentalIsExpanded)
  }, function (fillProps) {
    // Children passed to BlockControlsFill will not have access to any
    // React Context whose Provider is part of the BlockControlsSlot tree.
    // So we re-create the Provider in this subtree.
    var value = !isEmpty(fillProps) ? fillProps : null;
    return createElement(ToolbarContext.Provider, {
      value: value
    }, createElement(ToolbarGroup, {
      controls: controls
    }), children);
  });
}

var buildSlotName = function buildSlotName(isExpanded) {
  return "BlockControls".concat(isExpanded ? '-expanded' : '');
};

var BlockControls = BlockControlsFill;
BlockControls.Slot = BlockControlsSlot;
export default BlockControls;
//# sourceMappingURL=index.js.map