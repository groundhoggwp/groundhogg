"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.RichTextShortcut = RichTextShortcut;

var _compose = require("@wordpress/compose");

var _keycodes = require("@wordpress/keycodes");

/**
 * WordPress dependencies
 */
function RichTextShortcut(_ref) {
  var character = _ref.character,
      type = _ref.type,
      onUse = _ref.onUse;

  var callback = function callback() {
    onUse();
    return false;
  };

  (0, _compose.useKeyboardShortcut)(_keycodes.rawShortcut[type](character), callback, {
    bindGlobal: true
  });
  return null;
}
//# sourceMappingURL=shortcut.js.map