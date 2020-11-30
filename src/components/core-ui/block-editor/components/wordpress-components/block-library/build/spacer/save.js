"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

function save(_ref) {
  var attributes = _ref.attributes;
  return (0, _element.createElement)("div", {
    style: {
      height: attributes.height
    },
    "aria-hidden": true
  });
}
//# sourceMappingURL=save.js.map