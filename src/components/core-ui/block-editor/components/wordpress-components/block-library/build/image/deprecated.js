"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var blockAttributes = {
  align: {
    type: 'string'
  },
  url: {
    type: 'string',
    source: 'attribute',
    selector: 'img',
    attribute: 'src'
  },
  alt: {
    type: 'string',
    source: 'attribute',
    selector: 'img',
    attribute: 'alt',
    default: ''
  },
  caption: {
    type: 'string',
    source: 'html',
    selector: 'figcaption'
  },
  href: {
    type: 'string',
    source: 'attribute',
    selector: 'figure > a',
    attribute: 'href'
  },
  rel: {
    type: 'string',
    source: 'attribute',
    selector: 'figure > a',
    attribute: 'rel'
  },
  linkClass: {
    type: 'string',
    source: 'attribute',
    selector: 'figure > a',
    attribute: 'class'
  },
  id: {
    type: 'number'
  },
  width: {
    type: 'number'
  },
  height: {
    type: 'number'
  },
  linkDestination: {
    type: 'string'
  },
  linkTarget: {
    type: 'string',
    source: 'attribute',
    selector: 'figure > a',
    attribute: 'target'
  }
};
var deprecated = [{
  attributes: blockAttributes,
  save: function save(_ref) {
    var _classnames;

    var attributes = _ref.attributes;
    var url = attributes.url,
        alt = attributes.alt,
        caption = attributes.caption,
        align = attributes.align,
        href = attributes.href,
        width = attributes.width,
        height = attributes.height,
        id = attributes.id;
    var classes = (0, _classnames2.default)((_classnames = {}, (0, _defineProperty2.default)(_classnames, "align".concat(align), align), (0, _defineProperty2.default)(_classnames, 'is-resized', width || height), _classnames));
    var image = (0, _element.createElement)("img", {
      src: url,
      alt: alt,
      className: id ? "wp-image-".concat(id) : null,
      width: width,
      height: height
    });
    return (0, _element.createElement)("figure", {
      className: classes
    }, href ? (0, _element.createElement)("a", {
      href: href
    }, image) : image, !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "figcaption",
      value: caption
    }));
  }
}, {
  attributes: blockAttributes,
  save: function save(_ref2) {
    var attributes = _ref2.attributes;
    var url = attributes.url,
        alt = attributes.alt,
        caption = attributes.caption,
        align = attributes.align,
        href = attributes.href,
        width = attributes.width,
        height = attributes.height,
        id = attributes.id;
    var image = (0, _element.createElement)("img", {
      src: url,
      alt: alt,
      className: id ? "wp-image-".concat(id) : null,
      width: width,
      height: height
    });
    return (0, _element.createElement)("figure", {
      className: align ? "align".concat(align) : null
    }, href ? (0, _element.createElement)("a", {
      href: href
    }, image) : image, !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "figcaption",
      value: caption
    }));
  }
}, {
  attributes: blockAttributes,
  save: function save(_ref3) {
    var attributes = _ref3.attributes;
    var url = attributes.url,
        alt = attributes.alt,
        caption = attributes.caption,
        align = attributes.align,
        href = attributes.href,
        width = attributes.width,
        height = attributes.height;
    var extraImageProps = width || height ? {
      width: width,
      height: height
    } : {};
    var image = (0, _element.createElement)("img", (0, _extends2.default)({
      src: url,
      alt: alt
    }, extraImageProps));
    var figureStyle = {};

    if (width) {
      figureStyle = {
        width: width
      };
    } else if (align === 'left' || align === 'right') {
      figureStyle = {
        maxWidth: '50%'
      };
    }

    return (0, _element.createElement)("figure", {
      className: align ? "align".concat(align) : null,
      style: figureStyle
    }, href ? (0, _element.createElement)("a", {
      href: href
    }, image) : image, !_blockEditor.RichText.isEmpty(caption) && (0, _element.createElement)(_blockEditor.RichText.Content, {
      tagName: "figcaption",
      value: caption
    }));
  }
}];
var _default = deprecated;
exports.default = _default;
//# sourceMappingURL=deprecated.js.map