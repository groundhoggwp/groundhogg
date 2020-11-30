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
  name: "core/post-comment-date",
  category: "design",
  attributes: {
    format: {
      type: "string"
    }
  },
  usesContext: ["commentId"],
  supports: {
    html: false
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Post Comment Date'),
  description: (0, _i18n.__)('Post Comment Date'),
  icon: _icons.postDate,
  edit: _edit.default,
  parent: ['core/post-comment']
};
exports.settings = settings;
//# sourceMappingURL=index.js.map