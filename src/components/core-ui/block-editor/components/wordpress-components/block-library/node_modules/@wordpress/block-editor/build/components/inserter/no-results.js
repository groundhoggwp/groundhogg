"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

/**
 * WordPress dependencies
 */
function InserterNoResults() {
  return (0, _element.createElement)("div", {
    className: "block-editor-inserter__no-results"
  }, (0, _element.createElement)(_icons.Icon, {
    className: "block-editor-inserter__no-results-icon",
    icon: _icons.blockDefault
  }), (0, _element.createElement)("p", null, (0, _i18n.__)('No results found.')));
}

var _default = InserterNoResults;
exports.default = _default;
//# sourceMappingURL=no-results.js.map