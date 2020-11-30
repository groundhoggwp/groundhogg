"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
var blockAttributes = {
  content: {
    type: 'string',
    source: 'html',
    selector: 'pre',
    default: ''
  },
  textAlign: {
    type: 'string'
  }
};
var deprecated = [{
  attributes: blockAttributes,
  save: function save(_ref) {
    var attributes = _ref.attributes;
    var textAlign = attributes.textAlign,
        content = attributes.content;
    return (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "pre",
      style: {
        textAlign: textAlign
      },
      value: content
    });
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map