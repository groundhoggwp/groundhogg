"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getShortcutKeyCombination = getShortcutKeyCombination;
exports.getShortcutRepresentation = getShortcutRepresentation;
exports.getShortcutDescription = getShortcutDescription;
exports.getShortcutAliases = getShortcutAliases;
exports.getCategoryShortcuts = exports.getAllShortcutRawKeyCombinations = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _rememo = _interopRequireDefault(require("rememo"));

var _lodash = require("lodash");

var _keycodes = require("@wordpress/keycodes");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

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
  display: _keycodes.displayShortcut,
  raw: _keycodes.rawShortcut,
  ariaLabel: _keycodes.shortcutAriaLabel
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


function getShortcutKeyCombination(state, name) {
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


function getShortcutRepresentation(state, name) {
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


function getShortcutDescription(state, name) {
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


function getShortcutAliases(state, name) {
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


var getAllShortcutRawKeyCombinations = (0, _rememo.default)(function (state, name) {
  return (0, _lodash.compact)([getKeyCombinationRepresentation(getShortcutKeyCombination(state, name), 'raw')].concat((0, _toConsumableArray2.default)(getShortcutAliases(state, name).map(function (combination) {
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

exports.getAllShortcutRawKeyCombinations = getAllShortcutRawKeyCombinations;
var getCategoryShortcuts = (0, _rememo.default)(function (state, categoryName) {
  return Object.entries(state).filter(function (_ref) {
    var _ref2 = (0, _slicedToArray2.default)(_ref, 2),
        shortcut = _ref2[1];

    return shortcut.category === categoryName;
  }).map(function (_ref3) {
    var _ref4 = (0, _slicedToArray2.default)(_ref3, 1),
        name = _ref4[0];

    return name;
  });
}, function (state) {
  return [state];
});
exports.getCategoryShortcuts = getCategoryShortcuts;
//# sourceMappingURL=selectors.js.map