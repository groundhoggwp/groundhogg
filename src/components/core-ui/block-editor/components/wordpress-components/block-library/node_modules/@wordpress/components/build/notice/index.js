"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _a11y = require("@wordpress/a11y");

var _icons = require("@wordpress/icons");

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
/**
 * Given a notice status, returns an assumed default politeness for the status.
 * Defaults to 'assertive'.
 *
 * @param {string} [status] Notice status.
 *
 * @return {'polite'|'assertive'} Notice politeness.
 */


function getDefaultPoliteness(status) {
  switch (status) {
    case 'success':
    case 'warning':
    case 'info':
      return 'polite';

    case 'error':
    default:
      return 'assertive';
  }
}

function Notice(_ref) {
  var className = _ref.className,
      _ref$status = _ref.status,
      status = _ref$status === void 0 ? 'info' : _ref$status,
      children = _ref.children,
      _ref$spokenMessage = _ref.spokenMessage,
      spokenMessage = _ref$spokenMessage === void 0 ? children : _ref$spokenMessage,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? _lodash.noop : _ref$onRemove,
      _ref$isDismissible = _ref.isDismissible,
      isDismissible = _ref$isDismissible === void 0 ? true : _ref$isDismissible,
      _ref$actions = _ref.actions,
      actions = _ref$actions === void 0 ? [] : _ref$actions,
      _ref$politeness = _ref.politeness,
      politeness = _ref$politeness === void 0 ? getDefaultPoliteness(status) : _ref$politeness,
      __unstableHTML = _ref.__unstableHTML;
  useSpokenMessage(spokenMessage, politeness);
  var classes = (0, _classnames.default)(className, 'components-notice', 'is-' + status, {
    'is-dismissible': isDismissible
  });

  if (__unstableHTML) {
    children = (0, _element.createElement)(_element.RawHTML, null, children);
  }

  return (0, _element.createElement)("div", {
    className: classes
  }, (0, _element.createElement)("div", {
    className: "components-notice__content"
  }, children, actions.map(function (_ref2, index) {
    var buttonCustomClasses = _ref2.className,
        label = _ref2.label,
        isPrimary = _ref2.isPrimary,
        _ref2$noDefaultClasse = _ref2.noDefaultClasses,
        noDefaultClasses = _ref2$noDefaultClasse === void 0 ? false : _ref2$noDefaultClasse,
        onClick = _ref2.onClick,
        url = _ref2.url;
    return (0, _element.createElement)(_.Button, {
      key: index,
      href: url,
      isPrimary: isPrimary,
      isSecondary: !noDefaultClasses && !url,
      isLink: !noDefaultClasses && !!url,
      onClick: url ? undefined : onClick,
      className: (0, _classnames.default)('components-notice__action', buttonCustomClasses)
    }, label);
  })), isDismissible && (0, _element.createElement)(_.Button, {
    className: "components-notice__dismiss",
    icon: _icons.close,
    label: (0, _i18n.__)('Dismiss this notice'),
    onClick: onRemove,
    showTooltip: false
  }));
}

var _default = Notice;
exports.default = _default;
//# sourceMappingURL=index.js.map