import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames/dedupe';
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
export default function save(_ref) {
  var _classnames;

  var attributes = _ref.attributes;
  var url = attributes.url,
      caption = attributes.caption,
      type = attributes.type,
      providerNameSlug = attributes.providerNameSlug;

  if (!url) {
    return null;
  }

  var embedClassName = classnames('wp-block-embed', (_classnames = {}, _defineProperty(_classnames, "is-type-".concat(type), type), _defineProperty(_classnames, "is-provider-".concat(providerNameSlug), providerNameSlug), _defineProperty(_classnames, "wp-block-embed-".concat(providerNameSlug), providerNameSlug), _classnames));
  return createElement("figure", {
    className: embedClassName
  }, createElement("div", {
    className: "wp-block-embed__wrapper"
  }, "\n".concat(url, "\n")
  /* URL needs to be on its own line. */
  ), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
}
//# sourceMappingURL=save.js.map