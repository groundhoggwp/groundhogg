"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _element = require("@wordpress/element");

var _priorityQueue = require("@wordpress/priority-queue");

/**
 * WordPress dependencies
 */

/**
 * Returns the first items from list that are present on state.
 *
 * @param {Array} list  New array.
 * @param {Array} state Current state.
 * @return {Array} First items present iin state.
 */
function getFirstItemsPresentInState(list, state) {
  var firstItems = [];

  for (var i = 0; i < list.length; i++) {
    var item = list[i];

    if (!state.includes(item)) {
      break;
    }

    firstItems.push(item);
  }

  return firstItems;
}
/**
 * Reducer keeping track of a list of appended items.
 *
 * @param {Array}  state  Current state
 * @param {Object} action Action
 *
 * @return {Array} update state.
 */


function listReducer(state, action) {
  if (action.type === 'reset') {
    return action.list;
  }

  if (action.type === 'append') {
    return [].concat((0, _toConsumableArray2.default)(state), [action.item]);
  }

  return state;
}
/**
 * React hook returns an array which items get asynchronously appended from a source array.
 * This behavior is useful if we want to render a list of items asynchronously for performance reasons.
 *
 * @param {Array} list Source array.
 * @return {Array} Async array.
 */


function useAsyncList(list) {
  var _useReducer = (0, _element.useReducer)(listReducer, []),
      _useReducer2 = (0, _slicedToArray2.default)(_useReducer, 2),
      current = _useReducer2[0],
      dispatch = _useReducer2[1];

  (0, _element.useEffect)(function () {
    // On reset, we keep the first items that were previously rendered.
    var firstItems = getFirstItemsPresentInState(list, current);
    dispatch({
      type: 'reset',
      list: firstItems
    });
    var asyncQueue = (0, _priorityQueue.createQueue)();

    var append = function append(index) {
      return function () {
        if (list.length <= index) {
          return;
        }

        dispatch({
          type: 'append',
          item: list[index]
        });
        asyncQueue.add({}, append(index + 1));
      };
    };

    asyncQueue.add({}, append(firstItems.length));
    return function () {
      return asyncQueue.reset();
    };
  }, [list]);
  return current;
}

var _default = useAsyncList;
exports.default = _default;
//# sourceMappingURL=index.js.map