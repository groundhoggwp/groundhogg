"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _icon = _interopRequireDefault(require("./icon"));

var _edit = _interopRequireDefault(require("./edit"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/site-logo",
  category: "layout",
  attributes: {
    align: {
      type: "string"
    },
    width: {
      type: "number"
    }
  },
  supports: {
    html: false,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Site Logo'),
  description: (0, _i18n.__)('Show a site logo'),
  icon: _icon.default,
  supports: {
    align: true,
    alignWide: false
  },
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map