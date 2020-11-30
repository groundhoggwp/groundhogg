import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { escape } from './utils';
export default function save(_ref) {
  var attributes = _ref.attributes;
  return createElement("pre", null, createElement(RichText.Content, {
    tagName: "code",
    value: escape(attributes.content)
  }));
}
//# sourceMappingURL=save.js.map