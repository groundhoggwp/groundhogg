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

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/latest-posts",
  category: "widgets",
  attributes: {
    categories: {
      type: "array",
      items: {
        type: "object"
      }
    },
    selectedAuthor: {
      type: "number"
    },
    postsToShow: {
      type: "number",
      "default": 5
    },
    displayPostContent: {
      type: "boolean",
      "default": false
    },
    displayPostContentRadio: {
      type: "string",
      "default": "excerpt"
    },
    excerptLength: {
      type: "number",
      "default": 55
    },
    displayAuthor: {
      type: "boolean",
      "default": false
    },
    displayPostDate: {
      type: "boolean",
      "default": false
    },
    postLayout: {
      type: "string",
      "default": "list"
    },
    columns: {
      type: "number",
      "default": 3
    },
    order: {
      type: "string",
      "default": "desc"
    },
    orderBy: {
      type: "string",
      "default": "date"
    },
    displayFeaturedImage: {
      type: "boolean",
      "default": false
    },
    featuredImageAlign: {
      type: "string",
      "enum": ["left", "center", "right"]
    },
    featuredImageSizeSlug: {
      type: "string",
      "default": "thumbnail"
    },
    featuredImageSizeWidth: {
      type: "number",
      "default": null
    },
    featuredImageSizeHeight: {
      type: "number",
      "default": null
    },
    addLinkToFeaturedImage: {
      type: "boolean",
      "default": false
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
  title: (0, _i18n.__)('Latest Posts'),
  description: (0, _i18n.__)('Display a list of your most recent posts.'),
  icon: _icons.postList,
  keywords: [(0, _i18n.__)('recent posts')],
  example: {},
  edit: _edit.default,
  deprecated: _deprecated.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map