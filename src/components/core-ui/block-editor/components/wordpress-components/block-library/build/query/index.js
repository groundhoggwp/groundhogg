"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "useQueryContext", {
  enumerable: true,
  get: function get() {
    return _edit.useQueryContext;
  }
});
exports.settings = exports.name = exports.metadata = void 0;

var _i18n = require("@wordpress/i18n");

var _edit = _interopRequireWildcard(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var metadata = {
  name: "core/query",
  category: "design",
  attributes: {
    queryId: {
      type: "number"
    },
    query: {
      type: "object",
      "default": {
        perPage: 3,
        pages: 1,
        offset: 0,
        categoryIds: [],
        tagIds: [],
        order: "desc",
        orderBy: "date",
        author: "",
        search: ""
      }
    }
  },
  providesContext: {
    queryId: "queryId",
    query: "query"
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
  title: (0, _i18n.__)('Query'),
  edit: _edit.default,
  save: _save.default
};
exports.settings = settings;
//# sourceMappingURL=index.js.map