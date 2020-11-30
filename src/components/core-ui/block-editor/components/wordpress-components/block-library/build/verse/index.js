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
  name: "core/verse",
  category: "text",
  attributes: {
    content: {
      type: "string",
      source: "html",
      selector: "pre",
      "default": "",
      __unstablePreserveWhiteSpace: true
    },
    textAlign: {
      type: "string"
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
  title: (0, _i18n.__)('Verse'),
  description: (0, _i18n.__)('Insert poetry. Use special spacing formats. Or quote song lyrics.'),
  icon: _icons.verse,
  example: {
    attributes: {
      /* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
      // translators: Sample content for the Verse block. Can be replaced with a more locale-adequate work.
      content: (0, _i18n.__)('WHAT was he doing, the great god Pan,\n	Down in the reeds by the river?\nSpreading ruin and scattering ban,\nSplashing and paddling with hoofs of a goat,\nAnd breaking the golden lilies afloat\n    With the dragon-fly on the river.')
      /* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

    }
  },
  keywords: [(0, _i18n.__)('poetry'), (0, _i18n.__)('poem')],
  transforms: _transforms.default,
  deprecated: _deprecated.default,
  merge: function merge(attributes, attributesToMerge) {
    return {
      content: attributes.content + attributesToMerge.content
    };
  },
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map