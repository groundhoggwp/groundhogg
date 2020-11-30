import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var content = attributes.content;
  return createElement(RawHTML, null, content);
}
//# sourceMappingURL=save.js.map