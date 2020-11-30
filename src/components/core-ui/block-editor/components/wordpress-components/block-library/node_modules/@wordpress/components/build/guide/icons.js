"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.PageControlIcon = void 0;

var _element = require("@wordpress/element");

var _primitives = require("@wordpress/primitives");

/**
 * WordPress dependencies
 */
var PageControlIcon = function PageControlIcon(_ref) {
  var isSelected = _ref.isSelected;
  return (0, _element.createElement)(_primitives.SVG, {
    width: "8",
    height: "8",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, (0, _element.createElement)(_primitives.Circle, {
    cx: "4",
    cy: "4",
    r: "4",
    fill: isSelected ? '#419ECD' : '#E1E3E6'
  }));
};

exports.PageControlIcon = PageControlIcon;
//# sourceMappingURL=icons.js.map