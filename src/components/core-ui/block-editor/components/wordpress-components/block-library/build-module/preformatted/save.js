import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var content = attributes.content;
  return createElement(RichText.Content, {
    tagName: "pre",
    value: content
  });
}
//# sourceMappingURL=save.js.map