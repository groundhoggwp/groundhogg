import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { InnerBlocks } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var verticalAlignment = attributes.verticalAlignment;
  var className = classnames(_defineProperty({}, "are-vertically-aligned-".concat(verticalAlignment), verticalAlignment));
  return createElement("div", {
    className: className ? className : undefined
  }, createElement(InnerBlocks.Content, null));
}
//# sourceMappingURL=save.js.map