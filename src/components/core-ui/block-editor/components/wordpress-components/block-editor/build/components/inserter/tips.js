"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var globalTips = [(0, _element.createInterpolateElement)((0, _i18n.__)('While writing, you can press <kbd>/</kbd> to quickly insert new blocks.'), {
  kbd: (0, _element.createElement)("kbd", null)
}), (0, _element.createInterpolateElement)((0, _i18n.__)('Indent a list by pressing <kbd>space</kbd> at the beginning of a line.'), {
  kbd: (0, _element.createElement)("kbd", null)
}), (0, _element.createInterpolateElement)((0, _i18n.__)('Outdent a list by pressing <kbd>backspace</kbd> at the beginning of a line.'), {
  kbd: (0, _element.createElement)("kbd", null)
}), (0, _i18n.__)('Drag files into the editor to automatically insert media blocks.'), (0, _i18n.__)("Change a block's type by pressing the block icon on the toolbar.")];

function Tips() {
  var _useState = (0, _element.useState)( // Disable Reason: I'm not generating an HTML id.
  // eslint-disable-next-line no-restricted-syntax
  Math.floor(Math.random() * globalTips.length)),
      _useState2 = (0, _slicedToArray2.default)(_useState, 1),
      randomIndex = _useState2[0];

  return (0, _element.createElement)(_components.Tip, null, globalTips[randomIndex]);
}

var _default = Tips;
exports.default = _default;
//# sourceMappingURL=tips.js.map