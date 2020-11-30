"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

var _transforms = _interopRequireDefault(require("./transforms"));

var _variations = _interopRequireDefault(require("./variations"));

var _deprecated = _interopRequireDefault(require("./deprecated"));

var _icons = require("./icons");

var _i18n = require("@wordpress/i18n");

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
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n._x)('Embed', 'block title'),
  description: (0, _i18n.__)('Add a block that displays content pulled from other sites, like Twitter, Instagram or YouTube.'),
  icon: _icons.embedContentIcon,
  edit: _edit.default,
  save: _save.default,
  transforms: _transforms.default,
  variations: _variations.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map