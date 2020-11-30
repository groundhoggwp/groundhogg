import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */

var metadata = {
  name: "core/embed",
  category: "embed",
  attributes: {
    url: {
      type: "string"
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    type: {
      type: "string"
    },
    providerNameSlug: {
      type: "string"
    },
    allowResponsive: {
      type: "boolean",
      "default": true
    },
    responsive: {
      type: "boolean",
      "default": false
    },
    previewable: {
      type: "boolean",
      "default": true
    }
  },
  supports: {
    align: true,
    reusable: false,
    html: false
  }
};
/**
 * WordPress dependencies
 */

import { RichText } from '@wordpress/block-editor';
var blockAttributes = metadata.attributes;
var deprecated = [{
  attributes: blockAttributes,
  save: function save(_ref) {
    var _classnames;

    var _ref$attributes = _ref.attributes,
        url = _ref$attributes.url,
        caption = _ref$attributes.caption,
        type = _ref$attributes.type,
        providerNameSlug = _ref$attributes.providerNameSlug;

    if (!url) {
      return null;
    }

    var embedClassName = classnames('wp-block-embed', (_classnames = {}, _defineProperty(_classnames, "is-type-".concat(type), type), _defineProperty(_classnames, "is-provider-".concat(providerNameSlug), providerNameSlug), _classnames));
    return createElement("figure", {
      className: embedClassName
    }, "\n".concat(url, "\n")
    /* URL needs to be on its own line. */
    , !RichText.isEmpty(caption) && createElement(RichText.Content, {
      tagName: "figcaption",
      value: caption
    }));
  }
}];
export default deprecated;
//# sourceMappingURL=deprecated.js.map