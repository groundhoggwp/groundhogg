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
  var textAlign = attributes.textAlign,
      content = attributes.content;
  var className = classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign));
  return createElement(RichText.Content, {
    tagName: "pre",
    className: className,
    value: content
  });
}
//# sourceMappingURL=save.js.map