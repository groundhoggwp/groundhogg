"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _onSubKey = _interopRequireDefault(require("./utils/on-sub-key"));

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Reducer returning the next notices state. The notices state is an object
 * where each key is a context, its value an array of notice objects.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
var notices = (0, _onSubKey.default)('context')(function () {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'CREATE_NOTICE':
      // Avoid duplicates on ID.
      return [].concat((0, _toConsumableArray2.default)((0, _lodash.reject)(state, {
        id: action.notice.id
      })), [action.notice]);

    case 'REMOVE_NOTICE':
      return (0, _lodash.reject)(state, {
        id: action.id
      });
  }

  return state;
});
var _default = notices;
exports.default = _default;
//# sourceMappingURL=reducer.js.map