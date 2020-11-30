import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var align = attributes.align,
      content = attributes.content;
  return createElement(RichText.Content, {
    tagName: "p",
    style: {
      textAlign: align
    },
    value: content
  });
}
//# sourceMappingURL=save.js.map