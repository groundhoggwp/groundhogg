import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var ordered = attributes.ordered,
      values = attributes.values,
      type = attributes.type,
      reversed = attributes.reversed,
      start = attributes.start;
  var tagName = ordered ? 'ol' : 'ul';
  return createElement(RichText.Content, {
    tagName: tagName,
    value: values,
    type: type,
    reversed: reversed,
    start: start,
    multiline: "li"
  });
}
//# sourceMappingURL=save.js.map