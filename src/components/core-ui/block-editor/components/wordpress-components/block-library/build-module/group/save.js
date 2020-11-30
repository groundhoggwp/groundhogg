import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var Tag = attributes.tagName;
  return createElement(Tag, null, createElement("div", {
    className: "wp-block-group__inner-container"
  }, createElement(InnerBlocks.Content, null)));
}
//# sourceMappingURL=save.js.map