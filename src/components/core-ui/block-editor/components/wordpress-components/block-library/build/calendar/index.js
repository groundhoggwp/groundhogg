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
  name: "core/calendar",
  category: "widgets",
  attributes: {
    month: {
      type: "integer"
    },
    year: {
      type: "integer"
    }
  },
  supports: {
    align: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Calendar'),
  description: (0, _i18n.__)('A calendar of your site’s posts.'),
  icon: _icons.calendar,
  keywords: [(0, _i18n.__)('posts'), (0, _i18n.__)('archive')],
  example: {},
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map