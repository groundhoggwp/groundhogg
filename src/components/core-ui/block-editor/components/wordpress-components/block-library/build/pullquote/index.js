"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _shared = require("./shared");

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
  name: "core/pullquote",
  category: "text",
  attributes: {
    value: {
      type: "string",
      source: "html",
      selector: "blockquote",
      multiline: "p"
    },
    citation: {
      type: "string",
      source: "html",
      selector: "cite",
      "default": ""
    },
    mainColor: {
      type: "string"
    },
    customMainColor: {
      type: "string"
    },
    textColor: {
      type: "string"
    },
    customTextColor: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    align: ["left", "right", "wide", "full"]
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Pullquote'),
  description: (0, _i18n.__)('Give special visual emphasis to a quote from your text.'),
  icon: _icons.pullquote,
  example: {
    attributes: {
      value: '<p>' + // translators: Quote serving as example for the Pullquote block. Attributed to Matt Mullenweg.
      (0, _i18n.__)('One of the hardest things to do in technology is disrupt yourself.') + '</p>',
      citation: (0, _i18n.__)('Matt Mullenweg')
    }
  },
  styles: [{
    name: 'default',
    label: (0, _i18n._x)('Default', 'block style'),
    isDefault: true
  }, {
    name: _shared.SOLID_COLOR_STYLE_NAME,
    label: (0, _i18n.__)('Solid color')
  }],
  transforms: _transforms.default,
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map