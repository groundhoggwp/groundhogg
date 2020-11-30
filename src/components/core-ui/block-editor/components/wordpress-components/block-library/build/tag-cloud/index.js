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
  name: "core/tag-cloud",
  category: "widgets",
  attributes: {
    taxonomy: {
      type: "string",
      "default": "post_tag"
    },
    showTagCounts: {
      type: "boolean",
      "default": false
    }
  },
  supports: {
    html: false,
    align: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Tag Cloud'),
  description: (0, _i18n.__)('A cloud of your most used tags.'),
  icon: _icons.tag,
  example: {},
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map