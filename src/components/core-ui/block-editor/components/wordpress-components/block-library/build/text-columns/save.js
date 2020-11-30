"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var width = attributes.width,
      content = attributes.content,
      columns = attributes.columns;
  return (0, _element.createElement)("div", {
    className: "align".concat(width, " columns-").concat(columns)
  }, (0, _lodash.times)(columns, function (index) {
    return (0, _element.createElement)("div", {
      className: "wp-block-column",
      key: "column-".concat(index)
    }, (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "p",
      value: (0, _lodash.get)(content, [index, 'children'])
    }));
  }));
}
//# sourceMappingURL=save.js.map