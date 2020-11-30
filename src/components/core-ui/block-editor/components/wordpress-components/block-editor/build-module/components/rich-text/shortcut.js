/**
 * WordPress dependencies
 */
import { useKeyboardShortcut } from '@wordpress/compose';
import { rawShortcut } from '@wordpress/keycodes';
export function RichTextShortcut(_ref) {
  var character = _ref.character,
      type = _ref.type,
      onUse = _ref.onUse;

  var callback = function callback() {
    onUse();
    return false;
  };

  useKeyboardShortcut(rawShortcut[type](character), callback, {
    bindGlobal: true
  });
  return null;
}
//# sourceMappingURL=shortcut.js.map