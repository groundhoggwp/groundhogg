import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';
export default function save(_ref) {
  var attributes = _ref.attributes;
  // Preserve the missing block's content.
  return createElement(RawHTML, null, attributes.originalContent);
}
//# sourceMappingURL=save.js.map