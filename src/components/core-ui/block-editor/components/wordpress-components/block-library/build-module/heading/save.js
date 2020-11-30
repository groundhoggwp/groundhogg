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
      content = attributes.content,
      level = attributes.level;
  var tagName = 'h' + level;
  var className = classnames(_defineProperty({}, "has-text-align-".concat(align), align));
  return createElement(RichText.Content, {
    className: className ? className : undefined,
    tagName: tagName,
    value: content
  });
}
//# sourceMappingURL=save.js.map