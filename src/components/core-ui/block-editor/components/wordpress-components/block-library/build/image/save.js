"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

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
      alt = attributes.alt,
      caption = attributes.caption,
      align = attributes.align,
      href = attributes.href,
      rel = attributes.rel,
      linkClass = attributes.linkClass,
      width = attributes.width,
      height = attributes.height,
      id = attributes.id,
      linkTarget = attributes.linkTarget,
      sizeSlug = attributes.sizeSlug,
      title = attributes.title;
  var newRel = (0, _lodash.isEmpty)(rel) ? undefined : rel;
  var classes = (0, _classnames2.default)((_classnames = {}, (0, _defineProperty2.default)(_classnames, "align".concat(align), align), (0, _defineProperty2.default)(_classnames, "size-".concat(sizeSlug), sizeSlug), (0, _defineProperty2.default)(_classnames, 'is-resized', width || height), _classnames));
  var image = (0, _element.createElement)("img", {
    src: url,
    alt: alt,
    className: id ? "wp-image-".concat(id) : null,
    width: width,
    height: height,
    title: title
  });
  var figure = (0, _element.createElement)(_element.Fragment, null, href ? (0, _element.createElement)("a", {
    className: linkClass,
    href: href,
    target: linkTarget,
    rel: newRel
  }, image) : image, !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));

  if ('left' === align || 'right' === align || 'center' === align) {
    return (0, _element.createElement)("div", null, (0, _element.createElement)("figure", {
      className: classes
    }, figure));
  }

  return (0, _element.createElement)("figure", {
    className: classes
  }, figure);
}
//# sourceMappingURL=save.js.map