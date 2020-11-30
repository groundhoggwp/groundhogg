import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var className = attributes.className;
  return createElement("div", {
    className: className
  }, createElement(InnerBlocks.Content, null));
}
//# sourceMappingURL=save.js.map