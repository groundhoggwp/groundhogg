"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.hasSameKeys = hasSameKeys;
exports.isUpdatingSameBlockAttribute = isUpdatingSameBlockAttribute;
exports.isTyping = isTyping;
exports.draggedBlocks = draggedBlocks;
exports.isCaretWithinFormattedText = isCaretWithinFormattedText;
exports.selectionStart = selectionStart;
exports.selectionEnd = selectionEnd;
exports.isMultiSelecting = isMultiSelecting;
exports.isSelectionEnabled = isSelectionEnabled;
exports.initialPosition = initialPosition;
exports.blocksMode = blocksMode;
exports.insertionPoint = insertionPoint;
exports.template = template;
exports.settings = settings;
exports.preferences = preferences;
exports.isNavigationMode = isNavigationMode;
exports.hasBlockMovingClientId = hasBlockMovingClientId;
exports.lastBlockAttributesChange = lastBlockAttributesChange;
exports.automaticChangeStatus = automaticChangeStatus;
exports.highlightedBlock = highlightedBlock;
exports.default = exports.blockListSettings = exports.blocks = void 0;

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _defaults = require("./defaults");

var _array = require("./array");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Given an array of blocks, returns an object where each key is a nesting
 * context, the value of which is an array of block client IDs existing within
 * that nesting context.
 *
 * @param {Array}   blocks       Blocks to map.
 * @param {?string} rootClientId Assumed root client ID.
 *
 * @return {Object} Block order map object.
 */
function mapBlockOrder(blocks) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var result = (0, _defineProperty2.default)({}, rootClientId, []);
  blocks.forEach(function (block) {
    var clientId = block.clientId,
        innerBlocks = block.innerBlocks;
    result[rootClientId].push(clientId);
    Object.assign(result, mapBlockOrder(innerBlocks, clientId));
  });
  return result;
}
/**
 * Given an array of blocks, returns an object where each key contains
 * the clientId of the block and the value is the parent of the block.
 *
 * @param {Array}   blocks       Blocks to map.
 * @param {?string} rootClientId Assumed root client ID.
 *
 * @return {Object} Block order map object.
 */


function mapBlockParents(blocks) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  return blocks.reduce(function (result, block) {
    return Object.assign(result, (0, _defineProperty2.default)({}, block.clientId, rootClientId), mapBlockParents(block.innerBlocks, block.clientId));
  }, {});
}
/**
 * Helper method to iterate through all blocks, recursing into inner blocks,
 * applying a transformation function to each one.
 * Returns a flattened object with the transformed blocks.
 *
 * @param {Array} blocks Blocks to flatten.
 * @param {Function} transform Transforming function to be applied to each block.
 *
 * @return {Object} Flattened object.
 */


function flattenBlocks(blocks) {
  var transform = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _lodash.identity;
  var result = {};
  var stack = (0, _toConsumableArray2.default)(blocks);

  while (stack.length) {
    var _stack$shift = stack.shift(),
        innerBlocks = _stack$shift.innerBlocks,
        block = (0, _objectWithoutProperties2.default)(_stack$shift, ["innerBlocks"]);

    stack.push.apply(stack, (0, _toConsumableArray2.default)(innerBlocks));
    result[block.clientId] = transform(block);
  }

  return result;
}
/**
 * Given an array of blocks, returns an object containing all blocks, without
 * attributes, recursing into inner blocks. Keys correspond to the block client
 * ID, the value of which is the attributes object.
 *
 * @param {Array} blocks Blocks to flatten.
 *
 * @return {Object} Flattened block attributes object.
 */


function getFlattenedBlocksWithoutAttributes(blocks) {
  return flattenBlocks(blocks, function (block) {
    return (0, _lodash.omit)(block, 'attributes');
  });
}
/**
 * Given an array of blocks, returns an object containing all block attributes,
 * recursing into inner blocks. Keys correspond to the block client ID, the
 * value of which is the attributes object.
 *
 * @param {Array} blocks Blocks to flatten.
 *
 * @return {Object} Flattened block attributes object.
 */


function getFlattenedBlockAttributes(blocks) {
  return flattenBlocks(blocks, function (block) {
    return block.attributes;
  });
}
/**
 * Given a block order map object, returns *all* of the block client IDs that are
 * a descendant of the given root client ID.
 *
 * Calling this with `rootClientId` set to `''` results in a list of client IDs
 * that are in the post. That is, it excludes blocks like fetched reusable
 * blocks which are stored into state but not visible. It also excludes
 * InnerBlocks controllers, like template parts.
 *
 * It is important to exclude the full inner block controller and not just the
 * inner blocks because in many cases, we need to persist the previous value of
 * an inner block controller. To do so, it must be excluded from the list of
 * client IDs which are considered to be part of the top-level entity.
 *
 * @param {Object}  blocksOrder  Object that maps block client IDs to a list of
 *                               nested block client IDs.
 * @param {?string} rootClientId The root client ID to search. Defaults to ''.
 * @param {?Object} controlledInnerBlocks The InnerBlocks controller state.
 *
 * @return {Array} List of descendant client IDs.
 */


function getNestedBlockClientIds(blocksOrder) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var controlledInnerBlocks = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  return (0, _lodash.reduce)(blocksOrder[rootClientId], function (result, clientId) {
    if (!!controlledInnerBlocks[clientId]) {
      return result;
    }

    return [].concat((0, _toConsumableArray2.default)(result), [clientId], (0, _toConsumableArray2.default)(getNestedBlockClientIds(blocksOrder, clientId)));
  }, []);
}
/**
 * Returns an object against which it is safe to perform mutating operations,
 * given the original object and its current working copy.
 *
 * @param {Object} original Original object.
 * @param {Object} working  Working object.
 *
 * @return {Object} Mutation-safe object.
 */


function getMutateSafeObject(original, working) {
  if (original === working) {
    return _objectSpread({}, original);
  }

  return working;
}
/**
 * Returns true if the two object arguments have the same keys, or false
 * otherwise.
 *
 * @param {Object} a First object.
 * @param {Object} b Second object.
 *
 * @return {boolean} Whether the two objects have the same keys.
 */


function hasSameKeys(a, b) {
  return (0, _lodash.isEqual)((0, _lodash.keys)(a), (0, _lodash.keys)(b));
}
/**
 * Returns true if, given the currently dispatching action and the previously
 * dispatched action, the two actions are updating the same block attribute, or
 * false otherwise.
 *
 * @param {Object} action     Currently dispatching action.
 * @param {Object} lastAction Previously dispatched action.
 *
 * @return {boolean} Whether actions are updating the same block attribute.
 */


function isUpdatingSameBlockAttribute(action, lastAction) {
  return action.type === 'UPDATE_BLOCK_ATTRIBUTES' && lastAction !== undefined && lastAction.type === 'UPDATE_BLOCK_ATTRIBUTES' && (0, _lodash.isEqual)(action.clientIds, lastAction.clientIds) && hasSameKeys(action.attributes, lastAction.attributes);
}
/**
 * Utility returning an object with an empty object value for each key.
 *
 * @param {Array} objectKeys Keys to fill.
 * @return {Object} Object filled with empty object as values for each clientId.
 */


var fillKeysWithEmptyObject = function fillKeysWithEmptyObject(objectKeys) {
  return objectKeys.reduce(function (result, key) {
    result[key] = {};
    return result;
  }, {});
};
/**
 * Higher-order reducer intended to compute a cache key for each block in the post.
 * A new instance of the cache key (empty object) is created each time the block object
 * needs to be refreshed (for any change in the block or its children).
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


var withBlockCache = function withBlockCache(reducer) {
  return function () {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var action = arguments.length > 1 ? arguments[1] : undefined;
    var newState = reducer(state, action);

    if (newState === state) {
      return state;
    }

    newState.cache = state.cache ? state.cache : {};
    /**
     * For each clientId provided, traverses up parents, adding the provided clientIds
     * and each parent's clientId to the returned array.
     *
     * When calling this function consider that it uses the old state, so any state
     * modifications made by the `reducer` will not be present.
     *
     * @param {Array} clientIds an Array of block clientIds.
     *
     * @return {Array} The provided clientIds and all of their parent clientIds.
     */

    var getBlocksWithParentsClientIds = function getBlocksWithParentsClientIds(clientIds) {
      return clientIds.reduce(function (result, clientId) {
        var current = clientId;

        do {
          result.push(current);
          current = state.parents[current];
        } while (current && !state.controlledInnerBlocks[current]);

        return result;
      }, []);
    };

    switch (action.type) {
      case 'RESET_BLOCKS':
        newState.cache = (0, _lodash.mapValues)(flattenBlocks(action.blocks), function () {
          return {};
        });
        break;

      case 'RECEIVE_BLOCKS':
      case 'INSERT_BLOCKS':
        {
          var updatedBlockUids = (0, _lodash.keys)(flattenBlocks(action.blocks));

          if (action.rootClientId && !state.controlledInnerBlocks[action.rootClientId]) {
            updatedBlockUids.push(action.rootClientId);
          }

          newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds(updatedBlockUids)));
          break;
        }

      case 'UPDATE_BLOCK':
        newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds([action.clientId])));
        break;

      case 'UPDATE_BLOCK_ATTRIBUTES':
        newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds(action.clientIds)));
        break;

      case 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        var parentClientIds = fillKeysWithEmptyObject(getBlocksWithParentsClientIds(action.replacedClientIds));
        newState.cache = _objectSpread(_objectSpread(_objectSpread({}, (0, _lodash.omit)(newState.cache, action.replacedClientIds)), (0, _lodash.omit)(parentClientIds, action.replacedClientIds)), fillKeysWithEmptyObject((0, _lodash.keys)(flattenBlocks(action.blocks))));
        break;

      case 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        newState.cache = _objectSpread(_objectSpread({}, (0, _lodash.omit)(newState.cache, action.removedClientIds)), fillKeysWithEmptyObject((0, _lodash.difference)(getBlocksWithParentsClientIds(action.clientIds), action.clientIds)));
        break;

      case 'MOVE_BLOCKS_TO_POSITION':
        {
          var _updatedBlockUids = (0, _toConsumableArray2.default)(action.clientIds);

          if (action.fromRootClientId) {
            _updatedBlockUids.push(action.fromRootClientId);
          }

          if (action.toRootClientId) {
            _updatedBlockUids.push(action.toRootClientId);
          }

          newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds(_updatedBlockUids)));
          break;
        }

      case 'MOVE_BLOCKS_UP':
      case 'MOVE_BLOCKS_DOWN':
        {
          var _updatedBlockUids2 = [];

          if (action.rootClientId) {
            _updatedBlockUids2.push(action.rootClientId);
          }

          newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds(_updatedBlockUids2)));
          break;
        }

      case 'SAVE_REUSABLE_BLOCK_SUCCESS':
        {
          var _updatedBlockUids3 = (0, _lodash.keys)((0, _lodash.omitBy)(newState.attributes, function (attributes, clientId) {
            return newState.byClientId[clientId].name !== 'core/block' || attributes.ref !== action.updatedId;
          }));

          newState.cache = _objectSpread(_objectSpread({}, newState.cache), fillKeysWithEmptyObject(getBlocksWithParentsClientIds(_updatedBlockUids3)));
        }
    }

    return newState;
  };
};
/**
 * Higher-order reducer intended to augment the blocks reducer, assigning an
 * `isPersistentChange` property value corresponding to whether a change in
 * state can be considered as persistent. All changes are considered persistent
 * except when updating the same block attribute as in the previous action.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


function withPersistentBlockChange(reducer) {
  var lastAction;
  var markNextChangeAsNotPersistent = false;
  return function (state, action) {
    var nextState = reducer(state, action);
    var isExplicitPersistentChange = action.type === 'MARK_LAST_CHANGE_AS_PERSISTENT' || markNextChangeAsNotPersistent; // Defer to previous state value (or default) unless changing or
    // explicitly marking as persistent.

    if (state === nextState && !isExplicitPersistentChange) {
      markNextChangeAsNotPersistent = action.type === 'MARK_NEXT_CHANGE_AS_NOT_PERSISTENT';
      var nextIsPersistentChange = (0, _lodash.get)(state, ['isPersistentChange'], true);

      if (state.isPersistentChange === nextIsPersistentChange) {
        return state;
      }

      return _objectSpread(_objectSpread({}, nextState), {}, {
        isPersistentChange: nextIsPersistentChange
      });
    }

    nextState = _objectSpread(_objectSpread({}, nextState), {}, {
      isPersistentChange: isExplicitPersistentChange ? !markNextChangeAsNotPersistent : !isUpdatingSameBlockAttribute(action, lastAction)
    }); // In comparing against the previous action, consider only those which
    // would have qualified as one which would have been ignored or not
    // have resulted in a changed state.

    lastAction = action;
    markNextChangeAsNotPersistent = action.type === 'MARK_NEXT_CHANGE_AS_NOT_PERSISTENT';
    return nextState;
  };
}
/**
 * Higher-order reducer intended to augment the blocks reducer, assigning an
 * `isIgnoredChange` property value corresponding to whether a change in state
 * can be considered as ignored. A change is considered ignored when the result
 * of an action not incurred by direct user interaction.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


function withIgnoredBlockChange(reducer) {
  /**
   * Set of action types for which a blocks state change should be ignored.
   *
   * @type {Set}
   */
  var IGNORED_ACTION_TYPES = new Set(['RECEIVE_BLOCKS']);
  return function (state, action) {
    var nextState = reducer(state, action);

    if (nextState !== state) {
      nextState.isIgnoredChange = IGNORED_ACTION_TYPES.has(action.type);
    }

    return nextState;
  };
}
/**
 * Higher-order reducer targeting the combined blocks reducer, augmenting
 * block client IDs in remove action to include cascade of inner blocks.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


var withInnerBlocksRemoveCascade = function withInnerBlocksRemoveCascade(reducer) {
  return function (state, action) {
    // Gets all children which need to be removed.
    var getAllChildren = function getAllChildren(clientIds) {
      var result = clientIds;

      for (var i = 0; i < result.length; i++) {
        var _result2;

        if (!state.order[result[i]] || action.keepControlledInnerBlocks && action.keepControlledInnerBlocks[result[i]]) {
          continue;
        }

        if (result === clientIds) {
          result = (0, _toConsumableArray2.default)(result);
        }

        (_result2 = result).push.apply(_result2, (0, _toConsumableArray2.default)(state.order[result[i]]));
      }

      return result;
    };

    if (state) {
      switch (action.type) {
        case 'REMOVE_BLOCKS':
          action = _objectSpread(_objectSpread({}, action), {}, {
            type: 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN',
            removedClientIds: getAllChildren(action.clientIds)
          });
          break;

        case 'REPLACE_BLOCKS':
          action = _objectSpread(_objectSpread({}, action), {}, {
            type: 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN',
            replacedClientIds: getAllChildren(action.clientIds)
          });
          break;
      }
    }

    return reducer(state, action);
  };
};
/**
 * Higher-order reducer which targets the combined blocks reducer and handles
 * the `RESET_BLOCKS` action. When dispatched, this action will replace all
 * blocks that exist in the post, leaving blocks that exist only in state (e.g.
 * reusable blocks and blocks controlled by inner blocks controllers) alone.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


var withBlockReset = function withBlockReset(reducer) {
  return function (state, action) {
    if (state && action.type === 'RESET_BLOCKS') {
      /**
       * A list of client IDs associated with the top level entity (like a
       * post or template). It excludes the client IDs of blocks associated
       * with other entities, like inner block controllers or reusable blocks.
       */
      var visibleClientIds = getNestedBlockClientIds(state.order, '', state.controlledInnerBlocks); // pickBy returns only the truthy values from controlledInnerBlocks

      var controlledInnerBlocks = Object.keys((0, _lodash.pickBy)(state.controlledInnerBlocks));
      /**
       * Each update operation consists of a few parts:
       * 1. First, the client IDs associated with the top level entity are
       *    removed from the existing state key, leaving in place controlled
       *    blocks (like reusable blocks and inner block controllers).
       * 2. Second, the blocks from the reset action are used to calculate the
       *    individual state keys. This will re-populate the clientIDs which
       *    were removed in step 1.
       * 3. In some cases, we remove the recalculated inner block controllers,
       *    letting their old values persist. We need to do this because the
       *    reset block action from a top-level entity is not aware of any
       *    inner blocks inside InnerBlock controllers. So if the new values
       *    were used, it would not take into account the existing InnerBlocks
       *    which already exist in the state for inner block controllers. For
       *    example, `attributes` uses the newly computed value for controllers
       *    since attributes are stored in the top-level entity. But `order`
       *    uses the previous value for the controllers since the new value
       *    does not include the order of controlled inner blocks. So if the
       *    new value was used, template parts would disappear from the editor
       *    whenever you try to undo a change in the top level entity.
       */

      return _objectSpread(_objectSpread({}, state), {}, {
        byClientId: _objectSpread(_objectSpread({}, (0, _lodash.omit)(state.byClientId, visibleClientIds)), getFlattenedBlocksWithoutAttributes(action.blocks)),
        attributes: _objectSpread(_objectSpread({}, (0, _lodash.omit)(state.attributes, visibleClientIds)), getFlattenedBlockAttributes(action.blocks)),
        order: _objectSpread(_objectSpread({}, (0, _lodash.omit)(state.order, visibleClientIds)), (0, _lodash.omit)(mapBlockOrder(action.blocks), controlledInnerBlocks)),
        parents: _objectSpread(_objectSpread({}, (0, _lodash.omit)(state.parents, visibleClientIds)), mapBlockParents(action.blocks)),
        cache: _objectSpread(_objectSpread({}, (0, _lodash.omit)(state.cache, visibleClientIds)), (0, _lodash.omit)((0, _lodash.mapValues)(flattenBlocks(action.blocks), function () {
          return {};
        }), controlledInnerBlocks))
      });
    }

    return reducer(state, action);
  };
};
/**
 * Higher-order reducer which targets the combined blocks reducer and handles
 * the `REPLACE_INNER_BLOCKS` action. When dispatched, this action the state
 * should become equivalent to the execution of a `REMOVE_BLOCKS` action
 * containing all the child's of the root block followed by the execution of
 * `INSERT_BLOCKS` with the new blocks.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


var withReplaceInnerBlocks = function withReplaceInnerBlocks(reducer) {
  return function (state, action) {
    if (action.type !== 'REPLACE_INNER_BLOCKS') {
      return reducer(state, action);
    } // Finds every nested inner block controller. We must check the action blocks
    // and not just the block parent state because some inner block controllers
    // should be deleted if specified, whereas others should not be deleted. If
    // a controlled should not be deleted, then we need to avoid deleting its
    // inner blocks from the block state because its inner blocks will not be
    // attached to the block in the action.


    var nestedControllers = {};

    if (Object.keys(state.controlledInnerBlocks).length) {
      var stack = (0, _toConsumableArray2.default)(action.blocks);

      while (stack.length) {
        var _stack$shift2 = stack.shift(),
            innerBlocks = _stack$shift2.innerBlocks,
            block = (0, _objectWithoutProperties2.default)(_stack$shift2, ["innerBlocks"]);

        stack.push.apply(stack, (0, _toConsumableArray2.default)(innerBlocks));

        if (!!state.controlledInnerBlocks[block.clientId]) {
          nestedControllers[block.clientId] = true;
        }
      }
    } // The `keepControlledInnerBlocks` prop will keep the inner blocks of the
    // marked block in the block state so that they can be reattached to the
    // marked block when we re-insert everything a few lines below.


    var stateAfterBlocksRemoval = state;

    if (state.order[action.rootClientId]) {
      stateAfterBlocksRemoval = reducer(stateAfterBlocksRemoval, {
        type: 'REMOVE_BLOCKS',
        keepControlledInnerBlocks: nestedControllers,
        clientIds: state.order[action.rootClientId]
      });
    }

    var stateAfterInsert = stateAfterBlocksRemoval;

    if (action.blocks.length) {
      stateAfterInsert = reducer(stateAfterInsert, _objectSpread(_objectSpread({}, action), {}, {
        type: 'INSERT_BLOCKS',
        index: 0
      })); // We need to re-attach the block order of the controlled inner blocks.
      // Otherwise, an inner block controller's blocks will be deleted entirely
      // from its entity..

      stateAfterInsert.order = _objectSpread(_objectSpread({}, stateAfterInsert.order), (0, _lodash.reduce)(nestedControllers, function (result, value, key) {
        if (state.order[key]) {
          result[key] = state.order[key];
        }

        return result;
      }, {}));
    }

    return stateAfterInsert;
  };
};
/**
 * Higher-order reducer which targets the combined blocks reducer and handles
 * the `SAVE_REUSABLE_BLOCK_SUCCESS` action. This action can't be handled by
 * regular reducers and needs a higher-order reducer since it needs access to
 * both `byClientId` and `attributes` simultaneously.
 *
 * @param {Function} reducer Original reducer function.
 *
 * @return {Function} Enhanced reducer function.
 */


var withSaveReusableBlock = function withSaveReusableBlock(reducer) {
  return function (state, action) {
    if (state && action.type === 'SAVE_REUSABLE_BLOCK_SUCCESS') {
      var id = action.id,
          updatedId = action.updatedId; // If a temporary reusable block is saved, we swap the temporary id with the final one

      if (id === updatedId) {
        return state;
      }

      state = _objectSpread({}, state);
      state.attributes = (0, _lodash.mapValues)(state.attributes, function (attributes, clientId) {
        var name = state.byClientId[clientId].name;

        if (name === 'core/block' && attributes.ref === id) {
          return _objectSpread(_objectSpread({}, attributes), {}, {
            ref: updatedId
          });
        }

        return attributes;
      });
    }

    return reducer(state, action);
  };
};
/**
 * Reducer returning the blocks state.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


var blocks = (0, _lodash.flow)(_data.combineReducers, withSaveReusableBlock, // needs to be before withBlockCache
withBlockCache, // needs to be before withInnerBlocksRemoveCascade
withInnerBlocksRemoveCascade, withReplaceInnerBlocks, // needs to be after withInnerBlocksRemoveCascade
withBlockReset, withPersistentBlockChange, withIgnoredBlockChange)({
  byClientId: function byClientId() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'RESET_BLOCKS':
        return getFlattenedBlocksWithoutAttributes(action.blocks);

      case 'RECEIVE_BLOCKS':
      case 'INSERT_BLOCKS':
        return _objectSpread(_objectSpread({}, state), getFlattenedBlocksWithoutAttributes(action.blocks));

      case 'UPDATE_BLOCK':
        // Ignore updates if block isn't known
        if (!state[action.clientId]) {
          return state;
        } // Do nothing if only attributes change.


        var changes = (0, _lodash.omit)(action.updates, 'attributes');

        if ((0, _lodash.isEmpty)(changes)) {
          return state;
        }

        return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, action.clientId, _objectSpread(_objectSpread({}, state[action.clientId]), changes)));

      case 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        if (!action.blocks) {
          return state;
        }

        return _objectSpread(_objectSpread({}, (0, _lodash.omit)(state, action.replacedClientIds)), getFlattenedBlocksWithoutAttributes(action.blocks));

      case 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        return (0, _lodash.omit)(state, action.removedClientIds);
    }

    return state;
  },
  attributes: function attributes() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'RESET_BLOCKS':
        return getFlattenedBlockAttributes(action.blocks);

      case 'RECEIVE_BLOCKS':
      case 'INSERT_BLOCKS':
        return _objectSpread(_objectSpread({}, state), getFlattenedBlockAttributes(action.blocks));

      case 'UPDATE_BLOCK':
        // Ignore updates if block isn't known or there are no attribute changes.
        if (!state[action.clientId] || !action.updates.attributes) {
          return state;
        }

        return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, action.clientId, _objectSpread(_objectSpread({}, state[action.clientId]), action.updates.attributes)));

      case 'UPDATE_BLOCK_ATTRIBUTES':
        {
          // Avoid a state change if none of the block IDs are known.
          if (action.clientIds.every(function (id) {
            return !state[id];
          })) {
            return state;
          }

          var next = action.clientIds.reduce(function (accumulator, id) {
            return _objectSpread(_objectSpread({}, accumulator), {}, (0, _defineProperty2.default)({}, id, (0, _lodash.reduce)(action.attributes, function (result, value, key) {
              // Consider as updates only changed values.
              if (value !== result[key]) {
                result = getMutateSafeObject(state[id], result);
                result[key] = value;
              }

              return result;
            }, state[id])));
          }, {});

          if (action.clientIds.every(function (id) {
            return next[id] === state[id];
          })) {
            return state;
          }

          return _objectSpread(_objectSpread({}, state), next);
        }

      case 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        if (!action.blocks) {
          return state;
        }

        return _objectSpread(_objectSpread({}, (0, _lodash.omit)(state, action.replacedClientIds)), getFlattenedBlockAttributes(action.blocks));

      case 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        return (0, _lodash.omit)(state, action.removedClientIds);
    }

    return state;
  },
  order: function order() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'RESET_BLOCKS':
        return mapBlockOrder(action.blocks);

      case 'RECEIVE_BLOCKS':
        return _objectSpread(_objectSpread({}, state), (0, _lodash.omit)(mapBlockOrder(action.blocks), ''));

      case 'INSERT_BLOCKS':
        {
          var _action$rootClientId = action.rootClientId,
              rootClientId = _action$rootClientId === void 0 ? '' : _action$rootClientId;
          var subState = state[rootClientId] || [];
          var mappedBlocks = mapBlockOrder(action.blocks, rootClientId);
          var _action$index = action.index,
              index = _action$index === void 0 ? subState.length : _action$index;
          return _objectSpread(_objectSpread(_objectSpread({}, state), mappedBlocks), {}, (0, _defineProperty2.default)({}, rootClientId, (0, _array.insertAt)(subState, mappedBlocks[rootClientId], index)));
        }

      case 'MOVE_BLOCKS_TO_POSITION':
        {
          var _objectSpread7;

          var _action$fromRootClien = action.fromRootClientId,
              fromRootClientId = _action$fromRootClien === void 0 ? '' : _action$fromRootClien,
              _action$toRootClientI = action.toRootClientId,
              toRootClientId = _action$toRootClientI === void 0 ? '' : _action$toRootClientI,
              clientIds = action.clientIds;

          var _action$index2 = action.index,
              _index = _action$index2 === void 0 ? state[toRootClientId].length : _action$index2; // Moving inside the same parent block


          if (fromRootClientId === toRootClientId) {
            var _subState = state[toRootClientId];

            var fromIndex = _subState.indexOf(clientIds[0]);

            return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, toRootClientId, (0, _array.moveTo)(state[toRootClientId], fromIndex, _index, clientIds.length)));
          } // Moving from a parent block to another


          return _objectSpread(_objectSpread({}, state), {}, (_objectSpread7 = {}, (0, _defineProperty2.default)(_objectSpread7, fromRootClientId, _lodash.without.apply(void 0, [state[fromRootClientId]].concat((0, _toConsumableArray2.default)(clientIds)))), (0, _defineProperty2.default)(_objectSpread7, toRootClientId, (0, _array.insertAt)(state[toRootClientId], clientIds, _index)), _objectSpread7));
        }

      case 'MOVE_BLOCKS_UP':
        {
          var _clientIds = action.clientIds,
              _action$rootClientId2 = action.rootClientId,
              _rootClientId = _action$rootClientId2 === void 0 ? '' : _action$rootClientId2;

          var firstClientId = (0, _lodash.first)(_clientIds);
          var _subState2 = state[_rootClientId];

          if (!_subState2.length || firstClientId === (0, _lodash.first)(_subState2)) {
            return state;
          }

          var firstIndex = _subState2.indexOf(firstClientId);

          return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, _rootClientId, (0, _array.moveTo)(_subState2, firstIndex, firstIndex - 1, _clientIds.length)));
        }

      case 'MOVE_BLOCKS_DOWN':
        {
          var _clientIds2 = action.clientIds,
              _action$rootClientId3 = action.rootClientId,
              _rootClientId2 = _action$rootClientId3 === void 0 ? '' : _action$rootClientId3;

          var _firstClientId = (0, _lodash.first)(_clientIds2);

          var lastClientId = (0, _lodash.last)(_clientIds2);
          var _subState3 = state[_rootClientId2];

          if (!_subState3.length || lastClientId === (0, _lodash.last)(_subState3)) {
            return state;
          }

          var _firstIndex = _subState3.indexOf(_firstClientId);

          return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, _rootClientId2, (0, _array.moveTo)(_subState3, _firstIndex, _firstIndex + 1, _clientIds2.length)));
        }

      case 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        {
          var _clientIds3 = action.clientIds;

          if (!action.blocks) {
            return state;
          }

          var _mappedBlocks = mapBlockOrder(action.blocks);

          return (0, _lodash.flow)([function (nextState) {
            return (0, _lodash.omit)(nextState, action.replacedClientIds);
          }, function (nextState) {
            return _objectSpread(_objectSpread({}, nextState), (0, _lodash.omit)(_mappedBlocks, ''));
          }, function (nextState) {
            return (0, _lodash.mapValues)(nextState, function (subState) {
              return (0, _lodash.reduce)(subState, function (result, clientId) {
                if (clientId === _clientIds3[0]) {
                  return [].concat((0, _toConsumableArray2.default)(result), (0, _toConsumableArray2.default)(_mappedBlocks['']));
                }

                if (_clientIds3.indexOf(clientId) === -1) {
                  result.push(clientId);
                }

                return result;
              }, []);
            });
          }])(state);
        }

      case 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        return (0, _lodash.flow)([// Remove inner block ordering for removed blocks
        function (nextState) {
          return (0, _lodash.omit)(nextState, action.removedClientIds);
        }, // Remove deleted blocks from other blocks' orderings
        function (nextState) {
          return (0, _lodash.mapValues)(nextState, function (subState) {
            return _lodash.without.apply(void 0, [subState].concat((0, _toConsumableArray2.default)(action.removedClientIds)));
          });
        }])(state);
    }

    return state;
  },
  // While technically redundant data as the inverse of `order`, it serves as
  // an optimization for the selectors which derive the ancestry of a block.
  parents: function parents() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'RESET_BLOCKS':
        return mapBlockParents(action.blocks);

      case 'RECEIVE_BLOCKS':
        return _objectSpread(_objectSpread({}, state), mapBlockParents(action.blocks));

      case 'INSERT_BLOCKS':
        return _objectSpread(_objectSpread({}, state), mapBlockParents(action.blocks, action.rootClientId || ''));

      case 'MOVE_BLOCKS_TO_POSITION':
        {
          return _objectSpread(_objectSpread({}, state), action.clientIds.reduce(function (accumulator, id) {
            accumulator[id] = action.toRootClientId || '';
            return accumulator;
          }, {}));
        }

      case 'REPLACE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        return _objectSpread(_objectSpread({}, (0, _lodash.omit)(state, action.replacedClientIds)), mapBlockParents(action.blocks, state[action.clientIds[0]]));

      case 'REMOVE_BLOCKS_AUGMENTED_WITH_CHILDREN':
        return (0, _lodash.omit)(state, action.removedClientIds);
    }

    return state;
  },
  controlledInnerBlocks: function controlledInnerBlocks() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    var _ref = arguments.length > 1 ? arguments[1] : undefined,
        type = _ref.type,
        clientId = _ref.clientId,
        hasControlledInnerBlocks = _ref.hasControlledInnerBlocks;

    if (type === 'SET_HAS_CONTROLLED_INNER_BLOCKS') {
      return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, clientId, hasControlledInnerBlocks));
    }

    return state;
  }
});
/**
 * Reducer returning typing state.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {boolean} Updated state.
 */

exports.blocks = blocks;

function isTyping() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'START_TYPING':
      return true;

    case 'STOP_TYPING':
      return false;
  }

  return state;
}
/**
 * Reducer returning dragged block client id.
 *
 * @param {string[]} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {string[]} Updated state.
 */


function draggedBlocks() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'START_DRAGGING_BLOCKS':
      return action.clientIds;

    case 'STOP_DRAGGING_BLOCKS':
      return [];
  }

  return state;
}
/**
 * Reducer returning whether the caret is within formatted text.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {boolean} Updated state.
 */


function isCaretWithinFormattedText() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'ENTER_FORMATTED_TEXT':
      return true;

    case 'EXIT_FORMATTED_TEXT':
      return false;
  }

  return state;
}
/**
 * Internal helper reducer for selectionStart and selectionEnd. Can hold a block
 * selection, represented by an object with property clientId.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


function selection() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'CLEAR_SELECTED_BLOCK':
      {
        if (state.clientId) {
          return {};
        }

        return state;
      }

    case 'SELECT_BLOCK':
      if (action.clientId === state.clientId) {
        return state;
      }

      return {
        clientId: action.clientId
      };

    case 'REPLACE_INNER_BLOCKS': // REPLACE_INNER_BLOCKS and INSERT_BLOCKS should follow the same logic.

    case 'INSERT_BLOCKS':
      {
        // REPLACE_INNER_BLOCKS can be called with an empty array.
        if (!action.updateSelection || !action.blocks.length) {
          return state;
        }

        return {
          clientId: action.blocks[0].clientId
        };
      }

    case 'REMOVE_BLOCKS':
      if (!action.clientIds || !action.clientIds.length || action.clientIds.indexOf(state.clientId) === -1) {
        return state;
      }

      return {};

    case 'REPLACE_BLOCKS':
      {
        if (action.clientIds.indexOf(state.clientId) === -1) {
          return state;
        }

        var indexToSelect = action.indexToSelect || action.blocks.length - 1;
        var blockToSelect = action.blocks[indexToSelect];

        if (!blockToSelect) {
          return {};
        }

        if (blockToSelect.clientId === state.clientId) {
          return state;
        }

        var newState = {
          clientId: blockToSelect.clientId
        };

        if (typeof action.initialPosition === 'number') {
          newState.initialPosition = action.initialPosition;
        }

        return newState;
      }
  }

  return state;
}
/**
 * Reducer returning the block selection's start.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


function selectionStart() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SELECTION_CHANGE':
      return {
        clientId: action.clientId,
        attributeKey: action.attributeKey,
        offset: action.startOffset
      };

    case 'RESET_SELECTION':
      return action.selectionStart;

    case 'MULTI_SELECT':
      return {
        clientId: action.start
      };
  }

  return selection(state, action);
}
/**
 * Reducer returning the block selection's end.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


function selectionEnd() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SELECTION_CHANGE':
      return {
        clientId: action.clientId,
        attributeKey: action.attributeKey,
        offset: action.endOffset
      };

    case 'RESET_SELECTION':
      return action.selectionEnd;

    case 'MULTI_SELECT':
      return {
        clientId: action.end
      };
  }

  return selection(state, action);
}
/**
 * Reducer returning whether the user is multi-selecting.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {boolean} Updated state.
 */


function isMultiSelecting() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'START_MULTI_SELECT':
      return true;

    case 'STOP_MULTI_SELECT':
      return false;
  }

  return state;
}
/**
 * Reducer returning whether selection is enabled.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {boolean} Updated state.
 */


function isSelectionEnabled() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'TOGGLE_SELECTION':
      return action.isSelectionEnabled;
  }

  return state;
}
/**
 * Reducer returning the intial block selection.
 *
 * Currently this in only used to restore the selection after block deletion and
 * pasting new content.This reducer should eventually be removed in favour of setting
 * selection directly.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {?number} Initial position: -1 or undefined.
 */


function initialPosition(state, action) {
  if (action.type === 'REPLACE_BLOCKS' && typeof action.initialPosition === 'number') {
    return action.initialPosition;
  } else if (action.type === 'SELECT_BLOCK') {
    return action.initialPosition;
  } else if (action.type === 'REMOVE_BLOCKS') {
    return state;
  } else if (action.type === 'START_TYPING') {
    return state;
  } // Reset the state by default (for any action not handled).

}

function blocksMode() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;

  if (action.type === 'TOGGLE_BLOCK_MODE') {
    var clientId = action.clientId;
    return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, clientId, state[clientId] && state[clientId] === 'html' ? 'visual' : 'html'));
  }

  return state;
}
/**
 * Reducer returning the block insertion point visibility, either null if there
 * is not an explicit insertion point assigned, or an object of its `index` and
 * `rootClientId`.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


function insertionPoint() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SHOW_INSERTION_POINT':
      var rootClientId = action.rootClientId,
          index = action.index;
      return {
        rootClientId: rootClientId,
        index: index
      };

    case 'HIDE_INSERTION_POINT':
      return null;
  }

  return state;
}
/**
 * Reducer returning whether the post blocks match the defined template or not.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {boolean} Updated state.
 */


function template() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    isValid: true
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_TEMPLATE_VALIDITY':
      return _objectSpread(_objectSpread({}, state), {}, {
        isValid: action.isValid
      });
  }

  return state;
}
/**
 * Reducer returning the editor setting.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


function settings() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _defaults.SETTINGS_DEFAULTS;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'UPDATE_SETTINGS':
      return _objectSpread(_objectSpread({}, state), action.settings);
  }

  return state;
}
/**
 * Reducer returning the user preferences.
 *
 * @param {Object}  state                 Current state.
 * @param {Object}  action                Dispatched action.
 *
 * @return {string} Updated state.
 */


function preferences() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _defaults.PREFERENCES_DEFAULTS;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'INSERT_BLOCKS':
    case 'REPLACE_BLOCKS':
      return action.blocks.reduce(function (prevState, block) {
        var id = block.name;
        var insert = {
          name: block.name
        };

        if ((0, _blocks.isReusableBlock)(block)) {
          insert.ref = block.attributes.ref;
          id += '/' + block.attributes.ref;
        }

        return _objectSpread(_objectSpread({}, prevState), {}, {
          insertUsage: _objectSpread(_objectSpread({}, prevState.insertUsage), {}, (0, _defineProperty2.default)({}, id, {
            time: action.time,
            count: prevState.insertUsage[id] ? prevState.insertUsage[id].count + 1 : 1,
            insert: insert
          }))
        });
      }, state);
  }

  return state;
}
/**
 * Reducer returning an object where each key is a block client ID, its value
 * representing the settings for its nested blocks.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */


var blockListSettings = function blockListSettings() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    // Even if the replaced blocks have the same client ID, our logic
    // should correct the state.
    case 'REPLACE_BLOCKS':
    case 'REMOVE_BLOCKS':
      {
        return (0, _lodash.omit)(state, action.clientIds);
      }

    case 'UPDATE_BLOCK_LIST_SETTINGS':
      {
        var clientId = action.clientId;

        if (!action.settings) {
          if (state.hasOwnProperty(clientId)) {
            return (0, _lodash.omit)(state, clientId);
          }

          return state;
        }

        if ((0, _lodash.isEqual)(state[clientId], action.settings)) {
          return state;
        }

        return _objectSpread(_objectSpread({}, state), {}, (0, _defineProperty2.default)({}, clientId, action.settings));
      }
  }

  return state;
};
/**
 * Reducer returning whether the navigation mode is enabled or not.
 *
 * @param {string} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {string} Updated state.
 */


exports.blockListSettings = blockListSettings;

function isNavigationMode() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  // Let inserting block always trigger Edit mode.
  if (action.type === 'INSERT_BLOCKS') {
    return false;
  }

  if (action.type === 'SET_NAVIGATION_MODE') {
    return action.isNavigationMode;
  }

  return state;
}
/**
 * Reducer returning whether the block moving mode is enabled or not.
 *
 * @param {string|null} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {string|null} Updated state.
 */


function hasBlockMovingClientId() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  // Let inserting block always trigger Edit mode.
  if (action.type === 'SET_BLOCK_MOVING_MODE') {
    return action.hasBlockMovingClientId;
  }

  return state;
}
/**
 * Reducer return an updated state representing the most recent block attribute
 * update. The state is structured as an object where the keys represent the
 * client IDs of blocks, the values a subset of attributes from the most recent
 * block update. The state is always reset to null if the last action is
 * anything other than an attributes update.
 *
 * @param {Object<string,Object>} state  Current state.
 * @param {Object}                action Action object.
 *
 * @return {[string,Object]} Updated state.
 */


function lastBlockAttributesChange(state, action) {
  switch (action.type) {
    case 'UPDATE_BLOCK':
      if (!action.updates.attributes) {
        break;
      }

      return (0, _defineProperty2.default)({}, action.clientId, action.updates.attributes);

    case 'UPDATE_BLOCK_ATTRIBUTES':
      return action.clientIds.reduce(function (accumulator, id) {
        return _objectSpread(_objectSpread({}, accumulator), {}, (0, _defineProperty2.default)({}, id, action.attributes));
      }, {});
  }

  return null;
}
/**
 * Reducer returning automatic change state.
 *
 * @param {boolean} state  Current state.
 * @param {Object}  action Dispatched action.
 *
 * @return {string} Updated state.
 */


function automaticChangeStatus(state, action) {
  switch (action.type) {
    case 'MARK_AUTOMATIC_CHANGE':
      return 'pending';

    case 'MARK_AUTOMATIC_CHANGE_FINAL':
      if (state === 'pending') {
        return 'final';
      }

      return;

    case 'SELECTION_CHANGE':
      // As long as the state is not final, ignore any selection changes.
      if (state !== 'final') {
        return state;
      }

      return;
    // Undoing an automatic change should still be possible after mouse
    // move.

    case 'STOP_TYPING':
      return state;
  } // Reset the state by default (for any action not handled).

}
/**
 * Reducer returning current highlighted block.
 *
 * @param {boolean} state  Current highlighted block.
 * @param {Object}  action Dispatched action.
 *
 * @return {string} Updated state.
 */


function highlightedBlock(state, action) {
  switch (action.type) {
    case 'TOGGLE_BLOCK_HIGHLIGHT':
      var clientId = action.clientId,
          isHighlighted = action.isHighlighted;

      if (isHighlighted) {
        return clientId;
      } else if (state === clientId) {
        return null;
      }

      return state;

    case 'SELECT_BLOCK':
      if (action.clientId !== state) {
        return null;
      }

  }

  return state;
}

var _default = (0, _data.combineReducers)({
  blocks: blocks,
  isTyping: isTyping,
  draggedBlocks: draggedBlocks,
  isCaretWithinFormattedText: isCaretWithinFormattedText,
  selectionStart: selectionStart,
  selectionEnd: selectionEnd,
  isMultiSelecting: isMultiSelecting,
  isSelectionEnabled: isSelectionEnabled,
  initialPosition: initialPosition,
  blocksMode: blocksMode,
  blockListSettings: blockListSettings,
  insertionPoint: insertionPoint,
  template: template,
  settings: settings,
  preferences: preferences,
  lastBlockAttributesChange: lastBlockAttributesChange,
  isNavigationMode: isNavigationMode,
  hasBlockMovingClientId: hasBlockMovingClientId,
  automaticChangeStatus: automaticChangeStatus,
  highlightedBlock: highlightedBlock
});

exports.default = _default;
//# sourceMappingURL=reducer.js.map