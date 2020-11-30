"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var EmbedLoading = function EmbedLoading() {
  return (0, _element.createElement)("div", {
    className: "wp-block-embed is-loading"
  }, (0, _element.createElement)(_components.Spinner, null), (0, _element.createElement)("p", null, (0, _i18n.__)('Embedding…')));
};

var _default = EmbedLoading;
exports.default = _default;
//# sourceMappingURL=embed-loading.js.map