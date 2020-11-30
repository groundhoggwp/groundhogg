/** @typedef {import('@wordpress/keycodes').WPKeycodeModifier} WPKeycodeModifier */

/**
 * Keyboard key combination.
 *
 * @typedef {Object} WPShortcutKeyCombination
 *
 * @property {string}                      character Character.
 * @property {WPKeycodeModifier|undefined} modifier  Modifier.
 */

/**
 * Configuration of a registered keyboard shortcut.
 *
 * @typedef {Object} WPShortcutConfig
 *
 * @property {string}                     name           Shortcut name.
 * @property {string}                     category       Shortcut category.
 * @property {string}                     description    Shortcut description.
 * @property {WPShortcutKeyCombination}   keyCombination Shortcut key combination.
 * @property {WPShortcutKeyCombination[]} [aliases]      Shortcut aliases.
 */

/**
 * Returns an action object used to register a new keyboard shortcut.
 *
 * @param {WPShortcutConfig} config Shortcut config.
 *
 * @return {Object} action.
 */
export function registerShortcut(_ref) {
  var name = _ref.name,
      category = _ref.category,
      description = _ref.description,
      keyCombination = _ref.keyCombination,
      aliases = _ref.aliases;
  return {
    type: 'REGISTER_SHORTCUT',
    name: name,
    category: category,
    keyCombination: keyCombination,
    aliases: aliases,
    description: description
  };
}
/**
 * Returns an action object used to unregister a keyboard shortcut.
 *
 * @param {string} name Shortcut name.
 *
 * @return {Object} action.
 */

export function unregisterShortcut(name) {
  return {
    type: 'UNREGISTER_SHORTCUT',
    name: name
  };
}
//# sourceMappingURL=actions.js.map