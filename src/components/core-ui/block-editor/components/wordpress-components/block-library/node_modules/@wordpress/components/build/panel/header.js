"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

function PanelHeader(_ref) {
  var label = _ref.label,
      children = _ref.children;
  return (0, _element.createElement)("div", {
    className: "components-panel__header"
  }, label && (0, _element.createElement)("h2", null, label), children);
}

var _default = PanelHeader;
exports.default = _default;
//# sourceMappingURL=header.js.map