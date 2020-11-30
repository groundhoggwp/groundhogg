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
  name: "core/media-text",
  category: "media",
  attributes: {
    align: {
      type: "string",
      "default": "wide"
    },
    mediaAlt: {
      type: "string",
      source: "attribute",
      selector: "figure img",
      attribute: "alt",
      "default": ""
    },
    mediaPosition: {
      type: "string",
      "default": "left"
    },
    mediaId: {
      type: "number"
    },
    mediaUrl: {
      type: "string",
      source: "attribute",
      selector: "figure video,figure img",
      attribute: "src"
    },
    mediaLink: {
      type: "string"
    },
    linkDestination: {
      type: "string"
    },
    linkTarget: {
      type: "string",
      source: "attribute",
      selector: "figure a",
      attribute: "target"
    },
    href: {
      type: "string",
      source: "attribute",
      selector: "figure a",
      attribute: "href"
    },
    rel: {
      type: "string",
      source: "attribute",
      selector: "figure a",
      attribute: "rel"
    },
    linkClass: {
      type: "string",
      source: "attribute",
      selector: "figure a",
      attribute: "class"
    },
    mediaType: {
      type: "string"
    },
    mediaWidth: {
      type: "number",
      "default": 50
    },
    mediaSizeSlug: {
      type: "string"
    },
    isStackedOnMobile: {
      type: "boolean",
      "default": true
    },
    verticalAlignment: {
      type: "string"
    },
    imageFill: {
      type: "boolean"
    },
    focalPoint: {
      type: "object"
    }
  },
  supports: {
    anchor: true,
    align: ["wide", "full"],
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true,
      linkColor: true
    }
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Media & Text'),
  description: (0, _i18n.__)('Set media and words side-by-side for a richer layout.'),
  icon: _icons.mediaAndText,
  keywords: [(0, _i18n.__)('image'), (0, _i18n.__)('video')],
  example: {
    attributes: {
      mediaType: 'image',
      mediaUrl: 'https://s.w.org/images/core/5.3/Biologia_Centrali-Americana_-_Cantorchilus_semibadius_1902.jpg'
    },
    innerBlocks: [{
      name: 'core/paragraph',
      attributes: {
        content: (0, _i18n.__)('The wren<br>Earns his living<br>Noiselessly.')
      }
    }, {
      name: 'core/paragraph',
      attributes: {
        content: (0, _i18n.__)('— Kobayashi Issa (一茶)')
      }
    }]
  },
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map