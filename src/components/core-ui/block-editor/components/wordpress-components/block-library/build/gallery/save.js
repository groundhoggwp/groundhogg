"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _blockEditor = require("@wordpress/block-editor");

var _shared = require("./shared");

var _constants = require("./constants");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function save(_ref) {
  var attributes = _ref.attributes;
  var images = attributes.images,
      _attributes$columns = attributes.columns,
      columns = _attributes$columns === void 0 ? (0, _shared.defaultColumnsNumber)(attributes) : _attributes$columns,
      imageCrop = attributes.imageCrop,
      caption = attributes.caption,
      linkTo = attributes.linkTo;
  return (0, _element.createElement)("figure", {
    className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
  }, (0, _element.createElement)("ul", {
    className: "blocks-gallery-grid"
  }, images.map(function (image) {
    var href;

    switch (linkTo) {
      case _constants.LINK_DESTINATION_MEDIA:
        href = image.fullUrl || image.url;
        break;

      case _constants.LINK_DESTINATION_ATTACHMENT:
        href = image.link;
        break;
    }

    var img = (0, _element.createElement)("img", {
      src: image.url,
      alt: image.alt,
      "data-id": image.id,
      "data-full-url": image.fullUrl,
      "data-link": image.link,
      className: image.id ? "wp-image-".concat(image.id) : null
    });
    return (0, _element.createElement)("li", {
      key: image.id || image.url,
      className: "blocks-gallery-item"
    }, (0, _element.createElement)("figure", null, href ? (0, _element.createElement)("a", {
      href: href
    }, img) : img, !_blockEditor.RichText.isEmpty(image.caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "figcaption",
      className: "blocks-gallery-item__caption",
      value: image.caption
    })));
  })), !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "figcaption",
    className: "blocks-gallery-caption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map