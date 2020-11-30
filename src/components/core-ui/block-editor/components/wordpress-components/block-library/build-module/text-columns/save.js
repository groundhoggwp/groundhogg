import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { get, times } from 'lodash';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var width = attributes.width,
      content = attributes.content,
      columns = attributes.columns;
  return createElement("div", {
    className: "align".concat(width, " columns-").concat(columns)
  }, times(columns, function (index) {
    return createElement("div", {
      className: "wp-block-column",
      key: "column-".concat(index)
    }, createElement(RichText.Content, {
      tagName: "p",
      value: get(content, [index, 'children'])
    }));
  }));
}
//# sourceMappingURL=save.js.map