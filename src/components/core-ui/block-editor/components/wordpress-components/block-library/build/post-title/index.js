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
  name: "core/post-title",
  category: "design",
  usesContext: ["postId", "postType"],
  attributes: {
    textAlign: {
      type: "string"
    },
    level: {
      type: "number",
      "default": 2
    },
    isLink: {
      type: "boolean",
      "default": false
    },
    rel: {
      type: "string",
      attribute: "rel",
      "default": ""
    },
    linkTarget: {
      type: "string",
      "default": "_blank"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true,
    __experimentalSelector: {
      "core/post-title/h1": "h1",
      "core/post-title/h2": "h2",
      "core/post-title/h3": "h3",
      "core/post-title/h4": "h4",
      "core/post-title/h5": "h5",
      "core/post-title/h6": "h6"
    }
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Post Title'),
  description: (0, _i18n.__)('Add the title of your post.'),
  icon: _icons.postTitle,
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map