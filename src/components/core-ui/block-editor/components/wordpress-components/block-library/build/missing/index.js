"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _blocks = require("@wordpress/blocks");

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/missing",
  category: "text",
  attributes: {
    originalName: {
      type: "string"
    },
    originalUndelimitedContent: {
      type: "string"
    },
    originalContent: {
      type: "string",
      source: "html"
    }
  },
  supports: {
    className: false,
    customClassName: false,
    inserter: false,
    html: false,
    reusable: false
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  name: name,
  title: (0, _i18n.__)('Unsupported'),
  description: (0, _i18n.__)('Your site doesn’t include support for this block.'),
  __experimentalLabel: function __experimentalLabel(attributes, _ref) {
    var context = _ref.context;

    if (context === 'accessibility') {
      var originalName = attributes.originalName;
      var originalBlockType = originalName ? (0, _blocks.getBlockType)(originalName) : undefined;

      if (originalBlockType) {
        return originalBlockType.settings.title || originalName;
      }

      return '';
    }
  },
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map