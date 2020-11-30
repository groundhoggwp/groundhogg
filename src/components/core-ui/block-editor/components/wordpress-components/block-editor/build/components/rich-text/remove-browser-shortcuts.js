"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.RemoveBrowserShortcuts = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _keycodes = require("@wordpress/keycodes");

var _components = require("@wordpress/components");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Set of keyboard shortcuts handled internally by RichText.
 *
 * @type {Array}
 */
var HANDLED_SHORTCUTS = [_keycodes.rawShortcut.primary('z'), _keycodes.rawShortcut.primaryShift('z'), _keycodes.rawShortcut.primary('y')];
/**
 * An instance of a KeyboardShortcuts element pre-bound for the handled
 * shortcuts. Since shortcuts never change, the element can be considered
 * static, and can be skipped in reconciliation.
 *
 * @type {WPElement}
 */

var SHORTCUTS_ELEMENT = (0, _element.createElement)(_components.KeyboardShortcuts, {
  bindGlobal: true,
  shortcuts: (0, _lodash.fromPairs)(HANDLED_SHORTCUTS.map(function (shortcut) {
    return [shortcut, function (event) {
      return event.preventDefault();
    }];
  }))
});
/**
 * Component which registered keyboard event handlers to prevent default
 * behaviors for key combinations otherwise handled internally by RichText.
 *
 * @return {WPComponent} The component to be rendered.
 */

var RemoveBrowserShortcuts = function RemoveBrowserShortcuts() {
  return SHORTCUTS_ELEMENT;
};

exports.RemoveBrowserShortcuts = RemoveBrowserShortcuts;
//# sourceMappingURL=remove-browser-shortcuts.js.map