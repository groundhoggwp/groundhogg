import { createElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import BlockFormatControls from '../block-format-controls';
import FormatToolbar from './format-toolbar';

var FormatToolbarContainer = function FormatToolbarContainer() {
  // Render regular toolbar
  return createElement(BlockFormatControls, null, createElement(FormatToolbar, null));
};

export default FormatToolbarContainer;
//# sourceMappingURL=format-toolbar-container.native.js.map