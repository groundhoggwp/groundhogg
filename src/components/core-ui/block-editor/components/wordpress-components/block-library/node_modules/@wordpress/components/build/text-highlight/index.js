"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var TextHighlight = function TextHighlight(_ref) {
  var _ref$text = _ref.text,
      text = _ref$text === void 0 ? '' : _ref$text,
      _ref$highlight = _ref.highlight,
      highlight = _ref$highlight === void 0 ? '' : _ref$highlight;
  var trimmedHighlightText = highlight.trim();

  if (!trimmedHighlightText) {
    return text;
  }

  var regex = new RegExp("(".concat((0, _lodash.escapeRegExp)(trimmedHighlightText), ")"), 'gi');
  return (0, _element.createInterpolateElement)(text.replace(regex, '<mark>$&</mark>'), {
    mark: (0, _element.createElement)("mark", null)
  });
};

var _default = TextHighlight;
exports.default = _default;
//# sourceMappingURL=index.js.map