"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _createAddHook = _interopRequireDefault(require("./createAddHook"));

var _createRemoveHook = _interopRequireDefault(require("./createRemoveHook"));

var _createHasHook = _interopRequireDefault(require("./createHasHook"));

var _createRunHook = _interopRequireDefault(require("./createRunHook"));

var _createCurrentHook = _interopRequireDefault(require("./createCurrentHook"));

var _createDoingHook = _interopRequireDefault(require("./createDoingHook"));

var _createDidHook = _interopRequireDefault(require("./createDidHook"));

/**
 * Internal dependencies
 */

/**
 * Returns an instance of the hooks object.
 *
 * @return {Object} Object that contains all hooks.
 */
function createHooks() {
  var actions = Object.create(null);
  var filters = Object.create(null);
  actions.__current = [];
  filters.__current = [];
  return {
    addAction: (0, _createAddHook.default)(actions),
    addFilter: (0, _createAddHook.default)(filters),
    removeAction: (0, _createRemoveHook.default)(actions),
    removeFilter: (0, _createRemoveHook.default)(filters),
    hasAction: (0, _createHasHook.default)(actions),
    hasFilter: (0, _createHasHook.default)(filters),
    removeAllActions: (0, _createRemoveHook.default)(actions, true),
    removeAllFilters: (0, _createRemoveHook.default)(filters, true),
    doAction: (0, _createRunHook.default)(actions),
    applyFilters: (0, _createRunHook.default)(filters, true),
    currentAction: (0, _createCurrentHook.default)(actions),
    currentFilter: (0, _createCurrentHook.default)(filters),
    doingAction: (0, _createDoingHook.default)(actions),
    doingFilter: (0, _createDoingHook.default)(filters),
    didAction: (0, _createDidHook.default)(actions),
    didFilter: (0, _createDidHook.default)(filters),
    actions: actions,
    filters: filters
  };
}

var _default = createHooks;
exports.default = _default;
//# sourceMappingURL=createHooks.js.map