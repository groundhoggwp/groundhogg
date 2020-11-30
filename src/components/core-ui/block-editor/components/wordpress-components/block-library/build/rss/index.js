"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _edit = _interopRequireDefault(require("./edit"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/rss",
  category: "widgets",
  attributes: {
    columns: {
      type: "number",
      "default": 2
    },
    blockLayout: {
      type: "string",
      "default": "list"
    },
    feedURL: {
      type: "string",
      "default": ""
    },
    itemsToShow: {
      type: "number",
      "default": 5
    },
    displayExcerpt: {
      type: "boolean",
      "default": false
    },
    displayAuthor: {
      type: "boolean",
      "default": false
    },
    displayDate: {
      type: "boolean",
      "default": false
    },
    excerptLength: {
      type: "number",
      "default": 55
    }
  },
  supports: {
    align: true,
    html: false
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('RSS'),
  description: (0, _i18n.__)('Display entries from any RSS or Atom feed.'),
  icon: _icons.rss,
  keywords: [(0, _i18n.__)('atom'), (0, _i18n.__)('feed')],
  example: {
    attributes: {
      feedURL: 'https://wordpress.org'
    }
  },
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map