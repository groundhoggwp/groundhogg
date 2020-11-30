import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Toolbar, Slot } from '@wordpress/components';

var FormatToolbar = function FormatToolbar() {
  return createElement(Toolbar, null, ['bold', 'italic', 'link'].map(function (format) {
    return createElement(Slot, {
      name: "RichText.ToolbarControls.".concat(format),
      key: format
    });
  }), createElement(Slot, {
    name: "RichText.ToolbarControls"
  }));
};

export default FormatToolbar;
//# sourceMappingURL=index.native.js.map