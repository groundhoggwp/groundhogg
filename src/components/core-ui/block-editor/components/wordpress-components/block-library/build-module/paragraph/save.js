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
      dropCap = attributes.dropCap,
      direction = attributes.direction;
  var className = classnames(_defineProperty({
    'has-drop-cap': dropCap
  }, "has-text-align-".concat(align), align));
  return createElement(RichText.Content, {
    tagName: "p",
    className: className ? className : undefined,
    value: content,
    dir: direction
  });
}
//# sourceMappingURL=save.js.map