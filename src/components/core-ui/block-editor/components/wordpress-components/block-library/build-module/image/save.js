import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { isEmpty } from 'lodash';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
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
  var newRel = isEmpty(rel) ? undefined : rel;
  var classes = classnames((_classnames = {}, _defineProperty(_classnames, "align".concat(align), align), _defineProperty(_classnames, "size-".concat(sizeSlug), sizeSlug), _defineProperty(_classnames, 'is-resized', width || height), _classnames));
  var image = createElement("img", {
    src: url,
    alt: alt,
    className: id ? "wp-image-".concat(id) : null,
    width: width,
    height: height,
    title: title
  });
  var figure = createElement(Fragment, null, href ? createElement("a", {
    className: linkClass,
    href: href,
    target: linkTarget,
    rel: newRel
  }, image) : image, !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));

  if ('left' === align || 'right' === align || 'center' === align) {
    return createElement("div", null, createElement("figure", {
      className: classes
    }, figure));
  }

  return createElement("figure", {
    className: classes
  }, figure);
}
//# sourceMappingURL=save.js.map