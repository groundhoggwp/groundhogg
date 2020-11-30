"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

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
  name: "core/html",
  category: "widgets",
  attributes: {
    content: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    customClassName: false,
    className: false,
    html: false
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Custom HTML'),
  description: (0, _i18n.__)('Add custom HTML code and preview it as you edit.'),
  icon: _icons.html,
  keywords: [(0, _i18n.__)('embed')],
  example: {
    attributes: {
      content: '<marquee>' + (0, _i18n.__)('Welcome to the wonderful world of blocks…') + '</marquee>'
    }
  },
  edit: _edit.default,
  save: _save.default,
  transforms: _transforms.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map