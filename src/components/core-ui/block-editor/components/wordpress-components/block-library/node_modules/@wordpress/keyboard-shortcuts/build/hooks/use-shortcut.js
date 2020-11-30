"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

/**
 * WordPress dependencies
 */

/**
 * Attach a keyboard shortcut handler.
 *
 * @param {string} name       Shortcut name.
 * @param {Function} callback Shortcut callback.
 * @param {Object} options    Shortcut options.
 */
function useShortcut(name, callback, options) {
  var shortcuts = (0, _data.useSelect)(function (select) {
    return select('core/keyboard-shortcuts').getAllShortcutRawKeyCombinations(name);
  }, [name]);
  (0, _compose.useKeyboardShortcut)(shortcuts, callback, options);
}

var _default = useShortcut;
exports.default = _default;
//# sourceMappingURL=use-shortcut.js.map