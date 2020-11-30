"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _ = _interopRequireDefault(require("./"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Renders a list of notices.
 *
 * @param  {Object}   $0           Props passed to the component.
 * @param  {Array}    $0.notices   Array of notices to render.
 * @param  {Function} $0.onRemove  Function called when a notice should be removed / dismissed.
 * @param  {Object}   $0.className Name of the class used by the component.
 * @param  {Object}   $0.children  Array of children to be rendered inside the notice list.
 * @return {Object}                The rendered notices list.
 */
function NoticeList(_ref) {
  var notices = _ref.notices,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? _lodash.noop : _ref$onRemove,
      className = _ref.className,
      children = _ref.children;

  var removeNotice = function removeNotice(id) {
    return function () {
      return onRemove(id);
    };
  };

  className = (0, _classnames.default)('components-notice-list', className);
  return (0, _element.createElement)("div", {
    className: className
  }, children, (0, _toConsumableArray2.default)(notices).reverse().map(function (notice) {
    return (0, _element.createElement)(_.default, (0, _extends2.default)({}, (0, _lodash.omit)(notice, ['content']), {
      key: notice.id,
      onRemove: removeNotice(notice.id)
    }), notice.content);
  }));
}

var _default = NoticeList;
exports.default = _default;
//# sourceMappingURL=list.js.map