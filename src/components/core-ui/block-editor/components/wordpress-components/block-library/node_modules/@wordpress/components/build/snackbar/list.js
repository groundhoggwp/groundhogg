"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _asyncToGenerator2 = _interopRequireDefault(require("@babel/runtime/helpers/asyncToGenerator"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _web = require("react-spring/web.cjs");

var _compose = require("@wordpress/compose");

var _ = _interopRequireDefault(require("./"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
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
function SnackbarList(_ref) {
  var notices = _ref.notices,
      className = _ref.className,
      children = _ref.children,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? _lodash.noop : _ref$onRemove;
  var isReducedMotion = (0, _compose.useReducedMotion)();

  var _useState = (0, _element.useState)(function () {
    return new WeakMap();
  }),
      _useState2 = (0, _slicedToArray2.default)(_useState, 1),
      refMap = _useState2[0];

  var transitions = (0, _web.useTransition)(notices, function (notice) {
    return notice.id;
  }, {
    from: {
      opacity: 0,
      height: 0
    },
    enter: function enter(item) {
      return /*#__PURE__*/function () {
        var _ref2 = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee(next) {
          return _regenerator.default.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  _context.next = 2;
                  return next({
                    opacity: 1,
                    height: refMap.get(item).offsetHeight
                  });

                case 2:
                  return _context.abrupt("return", _context.sent);

                case 3:
                case "end":
                  return _context.stop();
              }
            }
          }, _callee);
        }));

        return function (_x) {
          return _ref2.apply(this, arguments);
        };
      }();
    },
    leave: function leave() {
      return /*#__PURE__*/function () {
        var _ref3 = (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee2(next) {
          return _regenerator.default.wrap(function _callee2$(_context2) {
            while (1) {
              switch (_context2.prev = _context2.next) {
                case 0:
                  _context2.next = 2;
                  return next({
                    opacity: 0
                  });

                case 2:
                  _context2.next = 4;
                  return next({
                    height: 0
                  });

                case 4:
                case "end":
                  return _context2.stop();
              }
            }
          }, _callee2);
        }));

        return function (_x2) {
          return _ref3.apply(this, arguments);
        };
      }();
    },
    immediate: isReducedMotion
  });
  className = (0, _classnames.default)('components-snackbar-list', className);

  var removeNotice = function removeNotice(notice) {
    return function () {
      return onRemove(notice.id);
    };
  };

  return (0, _element.createElement)("div", {
    className: className
  }, children, transitions.map(function (_ref4) {
    var notice = _ref4.item,
        key = _ref4.key,
        style = _ref4.props;
    return (0, _element.createElement)(_web.animated.div, {
      key: key,
      style: style
    }, (0, _element.createElement)("div", {
      className: "components-snackbar-list__notice-container",
      ref: function ref(_ref5) {
        return _ref5 && refMap.set(notice, _ref5);
      }
    }, (0, _element.createElement)(_.default, (0, _extends2.default)({}, (0, _lodash.omit)(notice, ['content']), {
      onRemove: removeNotice(notice)
    }), notice.content)));
  }));
}

var _default = SnackbarList;
exports.default = _default;
//# sourceMappingURL=list.js.map