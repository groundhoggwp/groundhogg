"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ClipboardButton;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _classnames = _interopRequireDefault(require("classnames"));

var _compose = require("@wordpress/compose");

var _button = _interopRequireDefault(require("../button"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ClipboardButton(_ref) {
  var className = _ref.className,
      children = _ref.children,
      onCopy = _ref.onCopy,
      onFinishCopy = _ref.onFinishCopy,
      text = _ref.text,
      buttonProps = (0, _objectWithoutProperties2.default)(_ref, ["className", "children", "onCopy", "onFinishCopy", "text"]);
  var ref = (0, _element.useRef)();
  var hasCopied = (0, _compose.useCopyOnClick)(ref, text);
  var lastHasCopied = (0, _element.useRef)(hasCopied);
  (0, _element.useEffect)(function () {
    if (lastHasCopied.current === hasCopied) {
      return;
    }

    if (hasCopied) {
      onCopy();
    } else if (onFinishCopy) {
      onFinishCopy();
    }

    lastHasCopied.current = hasCopied;
  }, [onCopy, onFinishCopy, hasCopied]);
  var classes = (0, _classnames.default)('components-clipboard-button', className); // Workaround for inconsistent behavior in Safari, where <textarea> is not
  // the document.activeElement at the moment when the copy event fires.
  // This causes documentHasSelection() in the copy-handler component to
  // mistakenly override the ClipboardButton, and copy a serialized string
  // of the current block instead.

  var focusOnCopyEventTarget = function focusOnCopyEventTarget(event) {
    event.target.focus();
  };

  return (0, _element.createElement)(_button.default, (0, _extends2.default)({}, buttonProps, {
    className: classes,
    ref: ref,
    onCopy: focusOnCopyEventTarget
  }), children);
}
//# sourceMappingURL=index.js.map