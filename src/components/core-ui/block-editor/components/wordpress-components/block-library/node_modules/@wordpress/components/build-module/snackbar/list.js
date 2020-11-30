import _extends from "@babel/runtime/helpers/esm/extends";
import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { omit, noop } from 'lodash';
import { useTransition, animated } from 'react-spring/web.cjs';
/**
 * WordPress dependencies
 */

import { useReducedMotion } from '@wordpress/compose';
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */

import Snackbar from './';
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
      onRemove = _ref$onRemove === void 0 ? noop : _ref$onRemove;
  var isReducedMotion = useReducedMotion();

  var _useState = useState(function () {
    return new WeakMap();
  }),
      _useState2 = _slicedToArray(_useState, 1),
      refMap = _useState2[0];

  var transitions = useTransition(notices, function (notice) {
    return notice.id;
  }, {
    from: {
      opacity: 0,
      height: 0
    },
    enter: function enter(item) {
      return /*#__PURE__*/function () {
        var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(next) {
          return _regeneratorRuntime.wrap(function _callee$(_context) {
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
        var _ref3 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee2(next) {
          return _regeneratorRuntime.wrap(function _callee2$(_context2) {
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
  className = classnames('components-snackbar-list', className);

  var removeNotice = function removeNotice(notice) {
    return function () {
      return onRemove(notice.id);
    };
  };

  return createElement("div", {
    className: className
  }, children, transitions.map(function (_ref4) {
    var notice = _ref4.item,
        key = _ref4.key,
        style = _ref4.props;
    return createElement(animated.div, {
      key: key,
      style: style
    }, createElement("div", {
      className: "components-snackbar-list__notice-container",
      ref: function ref(_ref5) {
        return _ref5 && refMap.set(notice, _ref5);
      }
    }, createElement(Snackbar, _extends({}, omit(notice, ['content']), {
      onRemove: removeNotice(notice)
    }), notice.content)));
  }));
}

export default SnackbarList;
//# sourceMappingURL=list.js.map