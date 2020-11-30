import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import React from 'react';
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { createSlotFill, BottomSheetConsumer } from '@wordpress/components';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit/context';
import { BlockSettingsButton } from '../block-settings';

var _createSlotFill = createSlotFill('InspectorControls'),
    Fill = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var FillWithSettingsButton = function FillWithSettingsButton(_ref) {
  var children = _ref.children,
      props = _objectWithoutProperties(_ref, ["children"]);

  var _useBlockEditContext = useBlockEditContext(),
      isSelected = _useBlockEditContext.isSelected;

  if (!isSelected) {
    return null;
  }

  return createElement(Fragment, null, createElement(Fill, props, createElement(BottomSheetConsumer, null, function () {
    return createElement(View, null, children);
  })), React.Children.count(children) > 0 && createElement(BlockSettingsButton, null));
};

var InspectorControls = FillWithSettingsButton;
InspectorControls.Slot = Slot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/inspector-controls/README.md
 */

export default InspectorControls;
//# sourceMappingURL=index.native.js.map