"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ItemSubmenuIcon = exports.ToolbarSubmenuIcon = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var ToolbarSubmenuIcon = function ToolbarSubmenuIcon() {
  return (0, _element.createElement)(_components.SVG, {
    xmlns: "http://www.w3.org/2000/svg",
    width: "24",
    height: "24"
  }, (0, _element.createElement)(_components.Path, {
    d: "M2 12c0 3.6 2.4 5.5 6 5.5h.5V19l3-2.5-3-2.5v2H8c-2.5 0-4.5-1.5-4.5-4s2-4.5 4.5-4.5h3.5V6H8c-3.6 0-6 2.4-6 6zm19.5-1h-8v1.5h8V11zm0 5h-8v1.5h8V16zm0-10h-8v1.5h8V6z"
  }));
};

exports.ToolbarSubmenuIcon = ToolbarSubmenuIcon;

var ItemSubmenuIcon = function ItemSubmenuIcon() {
  return (0, _element.createElement)(_components.SVG, {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 24 24",
    transform: "rotate(90)"
  }, (0, _element.createElement)(_components.Path, {
    d: "M8 5v14l11-7z"
  }), (0, _element.createElement)(_components.Path, {
    d: "M0 0h24v24H0z",
    fill: "none"
  }));
};

exports.ItemSubmenuIcon = ItemSubmenuIcon;
//# sourceMappingURL=icons.js.map