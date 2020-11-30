"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _deprecated = _interopRequireDefault(require("./deprecated"));

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

var _transforms = _interopRequireDefault(require("./transforms"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/image",
  category: "media",
  attributes: {
    align: {
      type: "string"
    },
    url: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "src"
    },
    alt: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "alt",
      "default": ""
    },
    caption: {
      type: "string",
      source: "html",
      selector: "figcaption"
    },
    title: {
      type: "string",
      source: "attribute",
      selector: "img",
      attribute: "title"
    },
    href: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "href"
    },
    rel: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "rel"
    },
    linkClass: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "class"
    },
    id: {
      type: "number"
    },
    width: {
      type: "number"
    },
    height: {
      type: "number"
    },
    sizeSlug: {
      type: "string"
    },
    linkDestination: {
      type: "string"
    },
    linkTarget: {
      type: "string",
      source: "attribute",
      selector: "figure > a",
      attribute: "target"
    }
  },
  supports: {
    anchor: true,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Image'),
  description: (0, _i18n.__)('Insert an image to make a visual statement.'),
  icon: _icons.image,
  keywords: ['img', // "img" is not translated as it is intended to reflect the HTML <img> tag.
  (0, _i18n.__)('photo'), (0, _i18n.__)('picture')],
  example: {
    attributes: {
      sizeSlug: 'large',
      url: 'https://s.w.org/images/core/5.3/MtBlanc1.jpg',
      // translators: Caption accompanying an image of the Mont Blanc, which serves as an example for the Image block.
      caption: (0, _i18n.__)('Mont Blanc appears—still, snowy, and serene.')
    }
  },
  styles: [{
    name: 'default',
    label: (0, _i18n._x)('Default', 'block style'),
    isDefault: true
  }, {
    name: 'rounded',
    label: (0, _i18n._x)('Rounded', 'block style')
  }],
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      var caption = attributes.caption,
          alt = attributes.alt,
          url = attributes.url;

      if (!url) {
        return (0, _i18n.__)('Empty');
      }

      if (!alt) {
        return caption || '';
      } // This is intended to be read by a screen reader.
      // A period simply means a pause, no need to translate it.


      return alt + (caption ? '. ' + caption : '');
    }
  },
  getEditWrapperProps: function getEditWrapperProps(attributes) {
    return {
      'data-align': attributes.align
    };
  },
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map