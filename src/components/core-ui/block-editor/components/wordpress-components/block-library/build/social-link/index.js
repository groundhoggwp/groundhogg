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
  name: "core/social-link",
  category: "widgets",
  parent: ["core/social-links"],
  attributes: {
    url: {
      type: "string"
    },
    service: {
      type: "string"
    },
    label: {
      type: "string"
    }
  },
  usesContext: ["openInNewTab"],
  supports: {
    reusable: false,
    html: false,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Social Icon'),
  icon: _icons.share,
  edit: _edit.default,
  description: (0, _i18n.__)('Display an icon linking to a social media profile or website.'),
  variations: _variations.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map