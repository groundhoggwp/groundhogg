import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __experimentalAlignmentHookSettingsProvider as AlignmentHookSettingsProvider, InnerBlocks, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { name as buttonBlockName } from '../button/';
var ALLOWED_BLOCKS = [buttonBlockName];
var BUTTONS_TEMPLATE = [['core/button']]; // Inside buttons block alignment options are not supported.

var alignmentHooksSetting = {
  isEmbedButton: true
};

function ButtonsEdit() {
  var blockWrapperProps = useBlockWrapperProps();
  return createElement(AlignmentHookSettingsProvider, {
    value: alignmentHooksSetting
  }, createElement(InnerBlocks, {
    allowedBlocks: ALLOWED_BLOCKS,
    __experimentalPassedProps: blockWrapperProps,
    __experimentalTagName: "div",
    template: BUTTONS_TEMPLATE,
    orientation: "horizontal"
  }));
}

export default ButtonsEdit;
//# sourceMappingURL=edit.js.map