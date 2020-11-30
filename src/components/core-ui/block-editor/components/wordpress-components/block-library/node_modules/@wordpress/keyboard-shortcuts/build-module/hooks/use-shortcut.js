/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useKeyboardShortcut } from '@wordpress/compose';
/**
 * Attach a keyboard shortcut handler.
 *
 * @param {string} name       Shortcut name.
 * @param {Function} callback Shortcut callback.
 * @param {Object} options    Shortcut options.
 */

function useShortcut(name, callback, options) {
  var shortcuts = useSelect(function (select) {
    return select('core/keyboard-shortcuts').getAllShortcutRawKeyCombinations(name);
  }, [name]);
  useKeyboardShortcut(shortcuts, callback, options);
}

export default useShortcut;
//# sourceMappingURL=use-shortcut.js.map