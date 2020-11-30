"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

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

    var embedClassName = (0, _classnames2.default)('wp-block-embed', (_classnames = {}, (0, _defineProperty2.default)(_classnames, "is-type-".concat(type), type), (0, _defineProperty2.default)(_classnames, "is-provider-".concat(providerNameSlug), providerNameSlug), _classnames));
    return (0, _element.createElement)("figure", {
      className: embedClassName
    }, "\n".concat(url, "\n")
    /* URL needs to be on its own line. */
    , !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "figcaption",
      value: caption
    }));
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map