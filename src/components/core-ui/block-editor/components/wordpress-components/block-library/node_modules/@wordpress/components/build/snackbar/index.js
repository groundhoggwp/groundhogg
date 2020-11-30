"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _a11y = require("@wordpress/a11y");

var _i18n = require("@wordpress/i18n");

var _warning = _interopRequireDefault(require("@wordpress/warning"));

var _ = require("../");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var NOTICE_TIMEOUT = 10000;
/** @typedef {import('@wordpress/element').WPElement} WPElement */

/**
 * Custom hook which announces the message with the given politeness, if a
 * valid message is provided.
 *
 * @param {string|WPElement}     [message]  Message to announce.
 * @param {'polite'|'assertive'} politeness Politeness to announce.
 */

function useSpokenMessage(message, politeness) {
  var spokenMessage = typeof message === 'string' ? message : (0, _element.renderToString)(message);
  (0, _element.useEffect)(function () {
    if (spokenMessage) {
      (0, _a11y.speak)(spokenMessage, politeness);
    }
  }, [spokenMessage, politeness]);
}

function Snackbar(_ref, ref) {
  var className = _ref.className,
      children = _ref.children,
      _ref$spokenMessage = _ref.spokenMessage,
      spokenMessage = _ref$spokenMessage === void 0 ? children : _ref$spokenMessage,
      _ref$politeness = _ref.politeness,
      politeness = _ref$politeness === void 0 ? 'polite' : _ref$politeness,
      _ref$actions = _ref.actions,
      actions = _ref$actions === void 0 ? [] : _ref$actions,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? _lodash.noop : _ref$onRemove;
  useSpokenMessage(spokenMessage, politeness);
  (0, _element.useEffect)(function () {
    var timeoutHandle = setTimeout(function () {
      onRemove();
    }, NOTICE_TIMEOUT);
    return function () {
      return clearTimeout(timeoutHandle);
    };
  }, []);
  var classes = (0, _classnames.default)(className, 'components-snackbar');

  if (actions && actions.length > 1) {
    // we need to inform developers that snackbar only accepts 1 action
    typeof process !== "undefined" && process.env && process.env.NODE_ENV !== "production" ? (0, _warning.default)('Snackbar can only have 1 action, use Notice if your message require many messages') : void 0; // return first element only while keeping it inside an array

    actions = [actions[0]];
  }

  return (0, _element.createElement)("div", {
    ref: ref,
    className: classes,
    onClick: onRemove,
    tabIndex: "0",
    role: "button",
    onKeyPress: onRemove,
    "aria-label": (0, _i18n.__)('Dismiss this notice')
  }, (0, _element.createElement)("div", {
    className: "components-snackbar__content"
  }, children, actions.map(function (_ref2, index) {
    var label = _ref2.label,
        _onClick = _ref2.onClick,
        url = _ref2.url;
    return (0, _element.createElement)(_.Button, {
      key: index,
      href: url,
      isTertiary: true,
      onClick: function onClick(event) {
        event.stopPropagation();

        if (_onClick) {
          _onClick(event);
        }
      },
      className: "components-snackbar__action"
    }, label);
  })));
}

var _default = (0, _element.forwardRef)(Snackbar);

exports.default = _default;
//# sourceMappingURL=index.js.map