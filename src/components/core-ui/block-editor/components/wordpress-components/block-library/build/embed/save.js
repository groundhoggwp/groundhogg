"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _dedupe = _interopRequireDefault(require("classnames/dedupe"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function save(_ref) {
  var _classnames;

  var attributes = _ref.attributes;
  var url = attributes.url,
      caption = attributes.caption,
      type = attributes.type,
      providerNameSlug = attributes.providerNameSlug;

  if (!url) {
    return null;
  }

  var embedClassName = (0, _dedupe.default)('wp-block-embed', (_classnames = {}, (0, _defineProperty2.default)(_classnames, "is-type-".concat(type), type), (0, _defineProperty2.default)(_classnames, "is-provider-".concat(providerNameSlug), providerNameSlug), (0, _defineProperty2.default)(_classnames, "wp-block-embed-".concat(providerNameSlug), providerNameSlug), _classnames));
  return (0, _element.createElement)("figure", {
    className: embedClassName
  }, (0, _element.createElement)("div", {
    className: "wp-block-embed__wrapper"
  }, "\n".concat(url, "\n")
  /* URL needs to be on its own line. */
  ), !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map