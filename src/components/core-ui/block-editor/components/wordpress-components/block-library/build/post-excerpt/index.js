"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _edit = _interopRequireDefault(require("./edit"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/post-excerpt",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    },
    wordCount: {
      type: "number",
      "default": 55
    },
    moreText: {
      type: "string"
    },
    showMoreOnNewLine: {
      type: "boolean",
      "default": true
    }
  },
  usesContext: ["postId", "postType"],
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalFontSize: true,
    __experimentalColor: {
      gradients: true,
      linkColor: true
    },
    __experimentalLineHeight: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Post Excerpt'),
  description: (0, _i18n.__)("Display a post's excerpt."),
  icon: _icons.postExcerpt,
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map