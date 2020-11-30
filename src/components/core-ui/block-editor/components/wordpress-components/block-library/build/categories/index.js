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
  name: "core/categories",
  category: "widgets",
  attributes: {
    displayAsDropdown: {
      type: "boolean",
      "default": false
    },
    showHierarchy: {
      type: "boolean",
      "default": false
    },
    showPostCounts: {
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
  title: (0, _i18n.__)('Categories'),
  description: (0, _i18n.__)('Display a list of all categories.'),
  icon: _icons.category,
  example: {},
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map