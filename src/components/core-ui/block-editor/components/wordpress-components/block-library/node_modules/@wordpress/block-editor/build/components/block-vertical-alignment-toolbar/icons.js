"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.alignTop = exports.alignCenter = exports.alignBottom = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var alignBottom = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M15 4H9v11h6V4zM4 18.5V20h16v-1.5H4z"
}));
exports.alignBottom = alignBottom;
var alignCenter = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M20 11h-5V4H9v7H4v1.5h5V20h6v-7.5h5z"
}));
exports.alignCenter = alignCenter;
var alignTop = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M9 20h6V9H9v11zM4 4v1.5h16V4H4z"
}));
exports.alignTop = alignTop;
//# sourceMappingURL=icons.js.map