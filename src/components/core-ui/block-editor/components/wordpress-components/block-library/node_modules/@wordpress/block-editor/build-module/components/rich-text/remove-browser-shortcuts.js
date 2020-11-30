import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { fromPairs } from 'lodash';
/**
 * WordPress dependencies
 */

import { rawShortcut } from '@wordpress/keycodes';
import { KeyboardShortcuts } from '@wordpress/components';
/**
 * Set of keyboard shortcuts handled internally by RichText.
 *
 * @type {Array}
 */

var HANDLED_SHORTCUTS = [rawShortcut.primary('z'), rawShortcut.primaryShift('z'), rawShortcut.primary('y')];
/**
 * An instance of a KeyboardShortcuts element pre-bound for the handled
 * shortcuts. Since shortcuts never change, the element can be considered
 * static, and can be skipped in reconciliation.
 *
 * @type {WPElement}
 */

var SHORTCUTS_ELEMENT = createElement(KeyboardShortcuts, {
  bindGlobal: true,
  shortcuts: fromPairs(HANDLED_SHORTCUTS.map(function (shortcut) {
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

export var RemoveBrowserShortcuts = function RemoveBrowserShortcuts() {
  return SHORTCUTS_ELEMENT;
};
//# sourceMappingURL=remove-browser-shortcuts.js.map