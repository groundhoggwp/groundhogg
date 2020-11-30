import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var align = attributes.align,
      value = attributes.value,
      citation = attributes.citation;
  var className = classnames(_defineProperty({}, "has-text-align-".concat(align), align));
  return createElement("blockquote", {
    className: className
  }, createElement(RichText.Content, {
    multiline: true,
    value: value
  }), !RichText.isEmpty(citation) && createElement(RichText.Content, {
    tagName: "cite",
    value: citation
  }));
}
//# sourceMappingURL=save.js.map