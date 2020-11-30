import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";

/**
 * External dependencies
 */
import createSelector from 'rememo';
import { compact } from 'lodash';
/**
 * WordPress dependencies
 */

import { displayShortcut, shortcutAriaLabel, rawShortcut } from '@wordpress/keycodes';
/** @typedef {import('./actions').WPShortcutKeyCombination} WPShortcutKeyCombination */

/** @typedef {import('@wordpress/keycodes').WPKeycodeHandlerByModifier} WPKeycodeHandlerByModifier */

/**
 * Shared reference to an empty array for cases where it is important to avoid
 * returning a new array reference on every invocation.
 *
 * @type {Array<any>}
 */

var EMPTY_ARRAY = [];
/**
 * Shortcut formatting methods.
 *
 * @property {WPKeycodeHandlerByModifier} display     Display formatting.
 * @property {WPKeycodeHandlerByModifier} rawShortcut Raw shortcut formatting.
 * @property {WPKeycodeHandlerByModifier} ariaLabel   ARIA label formatting.
 */

var FORMATTING_METHODS = {
  display: displayShortcut,
  raw: rawShortcut,
  ariaLabel: shortcutAriaLabel
};
/**
 * Returns a string representing the key combination.
 *
 * @param {?WPShortcutKeyCombination} shortcut       Key combination.
 * @param {keyof FORMATTING_METHODS}  representation Type of representation
 *                                                   (display, raw, ariaLabel).
 *
 * @return {string?} Shortcut representation.
 */

function getKeyCombinationRepresentation(shortcut, representation) {
  if (!shortcut) {
    return null;
  }

  return shortcut.modifier ? FORMATTING_METHODS[representation][shortcut.modifier](shortcut.character) : shortcut.character;
}
/**
 * Returns the main key combination for a given shortcut name.
 *
 * @param {Object} state Global state.
 * @param {string} name  Shortcut name.
 *
 * @return {WPShortcutKeyCombination?} Key combination.
 */


export function getShortcutKeyCombination(state, name) {
  return state[name] ? state[name].keyCombination : null;
}
/**
 * Returns a string representing the main key combination for a given shortcut name.
 *
 * @param {Object}                   state          Global state.
 * @param {string}                   name           Shortcut name.
 * @param {keyof FORMATTING_METHODS} representation Type of representation
 *                                                  (display, raw, ariaLabel).
 *
 * @return {string?} Shortcut representation.
 */

export function getShortcutRepresentation(state, name) {
  var representation = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'display';
  var shortcut = getShortcutKeyCombination(state, name);
  return getKeyCombinationRepresentation(shortcut, representation);
}
/**
 * Returns the shortcut description given its name.
 *
 * @param {Object} state Global state.
 * @param {string} name  Shortcut name.
 *
 * @return {string?} Shortcut description.
 */

export function getShortcutDescription(state, name) {
  return state[name] ? state[name].description : null;
}
/**
 * Returns the aliases for a given shortcut name.
 *
 * @param {Object} state Global state.
 * @param {string} name  Shortcut name.
 *
 * @return {WPShortcutKeyCombination[]} Key combinations.
 */

export function getShortcutAliases(state, name) {
  return state[name] && state[name].aliases ? state[name].aliases : EMPTY_ARRAY;
}
/**
 * Returns the raw representation of all the keyboard combinations of a given shortcut name.
 *
 * @param {Object} state Global state.
 * @param {string} name  Shortcut name.
 *
 * @return {string[]} Shortcuts.
 */

export var getAllShortcutRawKeyCombinations = createSelector(function (state, name) {
  return compact([getKeyCombinationRepresentation(getShortcutKeyCombination(state, name), 'raw')].concat(_toConsumableArray(getShortcutAliases(state, name).map(function (combination) {
    return getKeyCombinationRepresentation(combination, 'raw');
  }))));
}, function (state, name) {
  return [state[name]];
});
/**
 * Returns the shortcut names list for a given category name.
 *
 * @param {Object} state Global state.
 * @param {string} name  Category name.
 *
 * @return {string[]} Shortcut names.
 */

export var getCategoryShortcuts = createSelector(function (state, categoryName) {
  return Object.entries(state).filter(function (_ref) {
    var _ref2 = _slicedToArray(_ref, 2),
        shortcut = _ref2[1];

    return shortcut.category === categoryName;
  }).map(function (_ref3) {
    var _ref4 = _slicedToArray(_ref3, 1),
        name = _ref4[0];

    return name;
  });
}, function (state) {
  return [state];
});
//# sourceMappingURL=selectors.js.map