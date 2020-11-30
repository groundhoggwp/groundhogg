"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _edit = _interopRequireDefault(require("./edit"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/block",
  category: "reusable",
  attributes: {
    ref: {
      type: "number"
    }
  },
  supports: {
    customClassName: false,
    html: false,
    inserter: false
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Reusable Block'),
  description: (0, _i18n.__)('Create and save content to reuse across your site. Update the block, and the changes apply everywhere it’s used.'),
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map