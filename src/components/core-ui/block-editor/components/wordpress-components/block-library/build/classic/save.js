"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

/**
 * WordPress dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var content = attributes.content;
  return (0, _element.createElement)(_element.RawHTML, null, content);
}
//# sourceMappingURL=save.js.map