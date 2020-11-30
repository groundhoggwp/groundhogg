"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _edit = _interopRequireDefault(require("./edit"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/template-part",
  category: "design",
  attributes: {
    postId: {
      type: "number"
    },
    slug: {
      type: "string"
    },
    theme: {
      type: "string"
    },
    tagName: {
      type: "string",
      "default": "div"
    }
  },
  supports: {
    align: true,
    html: false,
    lightBlockWrapper: true,
    __experimentalColor: {
      gradients: true,
      linkColor: true
    }
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Template Part'),
  keywords: [(0, _i18n.__)('template part')],
  __experimentalLabel: function __experimentalLabel(_ref) {
    var slug = _ref.slug;
    return (0, _lodash.startCase)(slug);
  },
  edit: _edit.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map