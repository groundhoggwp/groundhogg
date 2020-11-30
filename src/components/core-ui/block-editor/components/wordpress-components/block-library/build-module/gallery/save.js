import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import { defaultColumnsNumber } from './shared';
import { LINK_DESTINATION_ATTACHMENT, LINK_DESTINATION_MEDIA } from './constants';
export default function save(_ref) {
  var attributes = _ref.attributes;
  var images = attributes.images,
      _attributes$columns = attributes.columns,
      columns = _attributes$columns === void 0 ? defaultColumnsNumber(attributes) : _attributes$columns,
      imageCrop = attributes.imageCrop,
      caption = attributes.caption,
      linkTo = attributes.linkTo;
  return createElement("figure", {
    className: "columns-".concat(columns, " ").concat(imageCrop ? 'is-cropped' : '')
  }, createElement("ul", {
    className: "blocks-gallery-grid"
  }, images.map(function (image) {
    var href;

    switch (linkTo) {
      case LINK_DESTINATION_MEDIA:
        href = image.fullUrl || image.url;
        break;

      case LINK_DESTINATION_ATTACHMENT:
        href = image.link;
        break;
    }

    var img = createElement("img", {
      src: image.url,
      alt: image.alt,
      "data-id": image.id,
      "data-full-url": image.fullUrl,
      "data-link": image.link,
      className: image.id ? "wp-image-".concat(image.id) : null
    });
    return createElement("li", {
      key: image.id || image.url,
      className: "blocks-gallery-item"
    }, createElement("figure", null, href ? createElement("a", {
      href: href
    }, img) : img, !RichText.isEmpty(image.caption) && createElement(RichText.Content, {
      tagName: "figcaption",
      className: "blocks-gallery-item__caption",
      value: image.caption
    })));
  })), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    className: "blocks-gallery-caption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map