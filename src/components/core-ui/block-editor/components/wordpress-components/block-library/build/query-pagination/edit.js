"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = QueryPaginationEdit;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _query = require("../query");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function QueryPaginationEdit(_ref) {
  var _ref$context = _ref.context,
      _ref$context$query$pa = _ref$context.query.pages,
      pages = _ref$context$query$pa === void 0 ? 1 : _ref$context$query$pa,
      queryContext = _ref$context.queryContext;

  var _ref2 = (0, _query.useQueryContext)() || queryContext,
      _ref3 = (0, _slicedToArray2.default)(_ref2, 2),
      page = _ref3[0].page,
      setQueryContext = _ref3[1];

  var previous;

  if (page > 1) {
    previous = (0, _element.createElement)(_components.Button, {
      isPrimary: true,
      icon: _icons.chevronLeft,
      onClick: function onClick() {
        return setQueryContext({
          page: page - 1
        });
      }
    }, (0, _i18n.__)('Previous'));
  }

  var next;

  if (page < pages) {
    next = (0, _element.createElement)(_components.Button, {
      isPrimary: true,
      icon: _icons.chevronRight,
      onClick: function onClick() {
        return setQueryContext({
          page: page + 1
        });
      }
    }, (0, _i18n.__)('Next'));
  }

  return previous || next ? (0, _element.createElement)(_components.ButtonGroup, null, previous, next) : (0, _i18n.__)('No pages to paginate.');
}
//# sourceMappingURL=edit.js.map