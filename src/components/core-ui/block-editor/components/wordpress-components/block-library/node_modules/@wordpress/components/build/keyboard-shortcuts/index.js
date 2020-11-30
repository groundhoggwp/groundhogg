"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function KeyboardShortcut(_ref) {
  var target = _ref.target,
      callback = _ref.callback,
      shortcut = _ref.shortcut,
      bindGlobal = _ref.bindGlobal,
      eventName = _ref.eventName;
  (0, _compose.useKeyboardShortcut)(shortcut, callback, {
    bindGlobal: bindGlobal,
    target: target,
    eventName: eventName
  });
  return null;
}

function KeyboardShortcuts(_ref2) {
  var children = _ref2.children,
      shortcuts = _ref2.shortcuts,
      bindGlobal = _ref2.bindGlobal,
      eventName = _ref2.eventName;
  var target = (0, _element.useRef)();
  var element = (0, _lodash.map)(shortcuts, function (callback, shortcut) {
    return (0, _element.createElement)(KeyboardShortcut, {
      key: shortcut,
      shortcut: shortcut,
      callback: callback,
      bindGlobal: bindGlobal,
      eventName: eventName,
      target: target
    });
  }); // Render as non-visual if there are no children pressed. Keyboard
  // events will be bound to the document instead.

  if (!_element.Children.count(children)) {
    return element;
  }

  return (0, _element.createElement)("div", {
    ref: target
  }, element, children);
}

var _default = KeyboardShortcuts;
exports.default = _default;
//# sourceMappingURL=index.js.map