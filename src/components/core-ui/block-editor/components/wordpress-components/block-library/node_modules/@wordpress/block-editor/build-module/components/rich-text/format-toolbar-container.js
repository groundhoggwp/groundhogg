import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Popover } from '@wordpress/components';
/**
 * Internal dependencies
 */

import BlockFormatControls from '../block-format-controls';
import FormatToolbar from './format-toolbar';

var FormatToolbarContainer = function FormatToolbarContainer(_ref) {
  var inline = _ref.inline,
      anchorRef = _ref.anchorRef;

  if (inline) {
    // Render in popover
    return createElement(Popover, {
      noArrow: true,
      position: "top center",
      focusOnMount: false,
      anchorRef: anchorRef,
      className: "block-editor-rich-text__inline-format-toolbar"
    }, createElement(FormatToolbar, null));
  } // Render regular toolbar


  return createElement(BlockFormatControls, null, createElement(FormatToolbar, null));
};

export default FormatToolbarContainer;
//# sourceMappingURL=format-toolbar-container.js.map