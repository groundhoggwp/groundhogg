"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function save(_ref) {
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

  var backgroundClass = (0, _blockEditor.getColorClassName)('background-color', backgroundColor);
  var classes = (0, _classnames2.default)(backgroundClass, {
    'has-fixed-layout': hasFixedLayout,
    'has-background': !!backgroundClass
  });
  var hasCaption = !_blockEditor.RichText.isEmpty(caption);

  var Section = function Section(_ref2) {
    var type = _ref2.type,
        rows = _ref2.rows;

    if (!rows.length) {
      return null;
    }

    var Tag = "t".concat(type);
    return (0, _element.createElement)(Tag, null, rows.map(function (_ref3, rowIndex) {
      var cells = _ref3.cells;
      return (0, _element.createElement)("tr", {
        key: rowIndex
      }, cells.map(function (_ref4, cellIndex) {
        var content = _ref4.content,
            tag = _ref4.tag,
            scope = _ref4.scope,
            align = _ref4.align;
        var cellClasses = (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(align), align));
        return (0, _element.createElement)(_blockEditor.RichText.Content, {
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

  return (0, _element.createElement)("figure", null, (0, _element.createElement)("table", {
    className: classes === '' ? undefined : classes
  }, (0, _element.createElement)(Section, {
    type: "head",
    rows: head
  }), (0, _element.createElement)(Section, {
    type: "body",
    rows: body
  }), (0, _element.createElement)(Section, {
    type: "foot",
    rows: foot
  })), hasCaption && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map