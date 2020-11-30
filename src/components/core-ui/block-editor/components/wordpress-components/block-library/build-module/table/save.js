import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { RichText, getColorClassName } from '@wordpress/block-editor';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var hasFixedLayout = attributes.hasFixedLayout,
      head = attributes.head,
      body = attributes.body,
      foot = attributes.foot,
      backgroundColor = attributes.backgroundColor,
      caption = attributes.caption;
  var isEmpty = !head.length && !body.length && !foot.length;

  if (isEmpty) {
    return null;
  }

  var backgroundClass = getColorClassName('background-color', backgroundColor);
  var classes = classnames(backgroundClass, {
    'has-fixed-layout': hasFixedLayout,
    'has-background': !!backgroundClass
  });
  var hasCaption = !RichText.isEmpty(caption);

  var Section = function Section(_ref2) {
    var type = _ref2.type,
        rows = _ref2.rows;

    if (!rows.length) {
      return null;
    }

    var Tag = "t".concat(type);
    return createElement(Tag, null, rows.map(function (_ref3, rowIndex) {
      var cells = _ref3.cells;
      return createElement("tr", {
        key: rowIndex
      }, cells.map(function (_ref4, cellIndex) {
        var content = _ref4.content,
            tag = _ref4.tag,
            scope = _ref4.scope,
            align = _ref4.align;
        var cellClasses = classnames(_defineProperty({}, "has-text-align-".concat(align), align));
        return createElement(RichText.Content, {
          className: cellClasses ? cellClasses : undefined,
          "data-align": align,
          tagName: tag,
          value: content,
          key: cellIndex,
          scope: tag === 'th' ? scope : undefined
        });
      }));
    }));
  };

  return createElement("figure", null, createElement("table", {
    className: classes === '' ? undefined : classes
  }, createElement(Section, {
    type: "head",
    rows: head
  }), createElement(Section, {
    type: "body",
    rows: body
  }), createElement(Section, {
    type: "foot",
    rows: foot
  })), hasCaption && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map