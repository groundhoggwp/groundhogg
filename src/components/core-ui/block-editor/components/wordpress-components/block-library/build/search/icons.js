"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.toggleLabel = exports.buttonWithIcon = exports.noButton = exports.buttonInside = exports.buttonOutside = exports.buttonOnly = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var buttonOnly = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "7",
  y: "10",
  width: "10",
  height: "4",
  rx: "1",
  fill: "currentColor"
}));
exports.buttonOnly = buttonOnly;
var buttonOutside = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "4.75",
  y: "15.25",
  width: "6.5",
  height: "9.5",
  transform: "rotate(-90 4.75 15.25)",
  stroke: "currentColor",
  "stroke-width": "1.5",
  fill: "none"
}), (0, _element.createElement)(_components.Rect, {
  x: "16",
  y: "10",
  width: "4",
  height: "4",
  rx: "1",
  fill: "currentColor"
}));
exports.buttonOutside = buttonOutside;
var buttonInside = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "4.75",
  y: "15.25",
  width: "6.5",
  height: "14.5",
  transform: "rotate(-90 4.75 15.25)",
  stroke: "currentColor",
  "stroke-width": "1.5",
  fill: "none"
}), (0, _element.createElement)(_components.Rect, {
  x: "14",
  y: "10",
  width: "4",
  height: "4",
  rx: "1",
  fill: "currentColor"
}));
exports.buttonInside = buttonInside;
var noButton = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "4.75",
  y: "15.25",
  width: "6.5",
  height: "14.5",
  transform: "rotate(-90 4.75 15.25)",
  stroke: "currentColor",
  fill: "none",
  "stroke-width": "1.5"
}));
exports.noButton = noButton;
var buttonWithIcon = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "4.75",
  y: "7.75",
  width: "14.5",
  height: "8.5",
  rx: "1.25",
  stroke: "currentColor",
  fill: "none",
  "stroke-width": "1.5"
}), (0, _element.createElement)(_components.Rect, {
  x: "8",
  y: "11",
  width: "8",
  height: "2",
  fill: "currentColor"
}));
exports.buttonWithIcon = buttonWithIcon;
var toggleLabel = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "4.75",
  y: "17.25",
  width: "5.5",
  height: "14.5",
  transform: "rotate(-90 4.75 17.25)",
  stroke: "currentColor",
  fill: "none",
  "stroke-width": "1.5"
}), (0, _element.createElement)(_components.Rect, {
  x: "4",
  y: "7",
  width: "10",
  height: "2",
  fill: "currentColor"
}));
exports.toggleLabel = toggleLabel;
//# sourceMappingURL=icons.js.map