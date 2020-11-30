"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _edit = _interopRequireDefault(require("./edit"));

var _icon = _interopRequireDefault(require("./icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/site-tagline",
  category: "design",
  attributes: {
    textAlign: {
      type: "string"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true
    },
    __experimentalFontSize: true,
    __experimentalLineHeight: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Site Tagline'),
  keywords: [(0, _i18n.__)('description')],
  icon: _icon.default,
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map