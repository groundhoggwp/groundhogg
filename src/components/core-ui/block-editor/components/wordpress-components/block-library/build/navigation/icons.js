"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.justifyRightIcon = exports.justifyCenterIcon = exports.justifyLeftIcon = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var justifyLeftIcon = (0, _element.createElement)(_components.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M11 16v-3h10v-2H11V8l-4 4 4 4zM5 4H3v16h2V4z"
}));
exports.justifyLeftIcon = justifyLeftIcon;
var justifyCenterIcon = (0, _element.createElement)(_components.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M5 8v3H1v2h4v3l4-4-4-4zm14 8v-3h4v-2h-4V8l-4 4 4 4zM13 4h-2v16h2V4z"
}));
exports.justifyCenterIcon = justifyCenterIcon;
var justifyRightIcon = (0, _element.createElement)(_components.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M13 8v3H3v2h10v3l4-4-4-4zm8-4h-2v16h2V4z"
}));
exports.justifyRightIcon = justifyRightIcon;
//# sourceMappingURL=icons.js.map