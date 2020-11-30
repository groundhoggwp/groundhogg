import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
export default function save(_ref) {
  var className = _ref.className;
  return createElement("ul", {
    className: className
  }, createElement(InnerBlocks.Content, null));
}
//# sourceMappingURL=save.js.map