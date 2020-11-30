import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';
export default function save(_ref) {
  var attributes = _ref.attributes;
  return createElement(RawHTML, null, attributes.text);
}
//# sourceMappingURL=save.js.map