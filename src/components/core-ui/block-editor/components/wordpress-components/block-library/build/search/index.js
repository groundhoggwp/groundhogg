"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _edit = _interopRequireDefault(require("./edit"));

var _variations = _interopRequireDefault(require("./variations"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/search",
  category: "widgets",
  attributes: {
    label: {
      type: "string"
    },
    showLabel: {
      type: "bool",
      "default": true
    },
    placeholder: {
      type: "string",
      "default": ""
    },
    width: {
      type: "number"
    },
    widthUnit: {
      type: "string"
    },
    buttonText: {
      type: "string"
    },
    buttonPosition: {
      type: "string",
      "default": "button-outside"
    },
    buttonUseIcon: {
      type: "bool",
      "default": false
    }
  },
  supports: {
    align: ["left", "center", "right"],
    html: false,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Search'),
  description: (0, _i18n.__)('Help visitors find your content.'),
  icon: _icons.search,
  keywords: [(0, _i18n.__)('find')],
  example: {},
  variations: _variations.default,
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map