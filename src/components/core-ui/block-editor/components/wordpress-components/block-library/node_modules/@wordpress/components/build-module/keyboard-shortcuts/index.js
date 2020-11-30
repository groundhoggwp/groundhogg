import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { map } from 'lodash';
/**
 * WordPress dependencies
 */

import { useRef, Children } from '@wordpress/element';
import { useKeyboardShortcut } from '@wordpress/compose';

function KeyboardShortcut(_ref) {
  var target = _ref.target,
      callback = _ref.callback,
      shortcut = _ref.shortcut,
      bindGlobal = _ref.bindGlobal,
      eventName = _ref.eventName;
  useKeyboardShortcut(shortcut, callback, {
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
  var target = useRef();
  var element = map(shortcuts, function (callback, shortcut) {
    return createElement(KeyboardShortcut, {
      key: shortcut,
      shortcut: shortcut,
      callback: callback,
      bindGlobal: bindGlobal,
      eventName: eventName,
      target: target
    });
  }); // Render as non-visual if there are no children pressed. Keyboard
  // events will be bound to the document instead.

  if (!Children.count(children)) {
    return element;
  }

  return createElement("div", {
    ref: target
  }, element, children);
}

export default KeyboardShortcuts;
//# sourceMappingURL=index.js.map