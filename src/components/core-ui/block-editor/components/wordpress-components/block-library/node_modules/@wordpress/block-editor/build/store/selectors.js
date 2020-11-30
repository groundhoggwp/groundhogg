"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getBlockName = getBlockName;
exports.isBlockValid = isBlockValid;
exports.getBlockAttributes = getBlockAttributes;
exports.getBlockCount = getBlockCount;
exports.getSelectionStart = getSelectionStart;
exports.getSelectionEnd = getSelectionEnd;
exports.getBlockSelectionStart = getBlockSelectionStart;
exports.getBlockSelectionEnd = getBlockSelectionEnd;
exports.getSelectedBlockCount = getSelectedBlockCount;
exports.hasSelectedBlock = hasSelectedBlock;
exports.getSelectedBlockClientId = getSelectedBlockClientId;
exports.getSelectedBlock = getSelectedBlock;
exports.getBlockRootClientId = getBlockRootClientId;
exports.getBlockHierarchyRootClientId = getBlockHierarchyRootClientId;
exports.getLowestCommonAncestorWithSelectedBlock = getLowestCommonAncestorWithSelectedBlock;
exports.getAdjacentBlockClientId = getAdjacentBlockClientId;
exports.getPreviousBlockClientId = getPreviousBlockClientId;
exports.getNextBlockClientId = getNextBlockClientId;
exports.getSelectedBlocksInitialCaretPosition = getSelectedBlocksInitialCaretPosition;
exports.getMultiSelectedBlockClientIds = getMultiSelectedBlockClientIds;
exports.getFirstMultiSelectedBlockClientId = getFirstMultiSelectedBlockClientId;
exports.getLastMultiSelectedBlockClientId = getLastMultiSelectedBlockClientId;
exports.isFirstMultiSelectedBlock = isFirstMultiSelectedBlock;
exports.isBlockMultiSelected = isBlockMultiSelected;
exports.getMultiSelectedBlocksStartClientId = getMultiSelectedBlocksStartClientId;
exports.getMultiSelectedBlocksEndClientId = getMultiSelectedBlocksEndClientId;
exports.getBlockOrder = getBlockOrder;
exports.getBlockIndex = getBlockIndex;
exports.isBlockSelected = isBlockSelected;
exports.hasSelectedInnerBlock = hasSelectedInnerBlock;
exports.isBlockWithinSelection = isBlockWithinSelection;
exports.hasMultiSelection = hasMultiSelection;
exports.isMultiSelecting = isMultiSelecting;
exports.isSelectionEnabled = isSelectionEnabled;
exports.getBlockMode = getBlockMode;
exports.isTyping = isTyping;
exports.isDraggingBlocks = isDraggingBlocks;
exports.getDraggedBlockClientIds = getDraggedBlockClientIds;
exports.isBlockBeingDragged = isBlockBeingDragged;
exports.isAncestorBeingDragged = isAncestorBeingDragged;
exports.isCaretWithinFormattedText = isCaretWithinFormattedText;
exports.getBlockInsertionPoint = getBlockInsertionPoint;
exports.isBlockInsertionPointVisible = isBlockInsertionPointVisible;
exports.isValidTemplate = isValidTemplate;
exports.getTemplate = getTemplate;
exports.getTemplateLock = getTemplateLock;
exports.canInsertBlocks = canInsertBlocks;
exports.getBlockListSettings = getBlockListSettings;
exports.getSettings = getSettings;
exports.isLastBlockChangePersistent = isLastBlockChangePersistent;
exports.__unstableIsLastBlockChangeIgnored = __unstableIsLastBlockChangeIgnored;
exports.__experimentalGetLastBlockAttributeChanges = __experimentalGetLastBlockAttributeChanges;
exports.isNavigationMode = isNavigationMode;
exports.hasBlockMovingClientId = hasBlockMovingClientId;
exports.didAutomaticChange = didAutomaticChange;
exports.isBlockHighlighted = isBlockHighlighted;
exports.areInnerBlocksControlled = areInnerBlocksControlled;
exports.__experimentalGetParsedReusableBlock = exports.__experimentalGetBlockListSettingsForBlocks = exports.__experimentalGetAllowedBlocks = exports.hasInserterItems = exports.getInserterItems = exports.canInsertBlockType = exports.isAncestorMultiSelected = exports.getMultiSelectedBlocks = exports.getSelectedBlockClientIds = exports.getBlockParentsByBlockName = exports.getBlockParents = exports.getBlocksByClientId = exports.getGlobalBlockCount = exports.getClientIdsWithDescendants = exports.getClientIdsOfDescendants = exports.__unstableGetBlockTree = exports.__unstableGetBlockWithBlockTree = exports.getBlocks = exports.__unstableGetBlockWithoutInnerBlocks = exports.getBlock = void 0;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _rememo = _interopRequireDefault(require("rememo"));

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * A block selection object.
 *
 * @typedef {Object} WPBlockSelection
 *
 * @property {string} clientId     A block client ID.
 * @property {string} attributeKey A block attribute key.
 * @property {number} offset       An attribute value offset, based on the rich
 *                                 text value. See `wp.richText.create`.
 */
// Module constants
var MILLISECONDS_PER_HOUR = 3600 * 1000;
var MILLISECONDS_PER_DAY = 24 * 3600 * 1000;
var MILLISECONDS_PER_WEEK = 7 * 24 * 3600 * 1000;
var templateIcon = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Rect, {
  x: "0",
  fill: "none",
  width: "24",
  height: "24"
}), (0, _element.createElement)(_components.G, null, (0, _element.createElement)(_components.Path, {
  d: "M19 3H5c-1.105 0-2 .895-2 2v14c0 1.105.895 2 2 2h14c1.105 0 2-.895 2-2V5c0-1.105-.895-2-2-2zM6 6h5v5H6V6zm4.5 13C9.12 19 8 17.88 8 16.5S9.12 14 10.5 14s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm3-6l3-5 3 5h-6z"
})));
/**
 * Shared reference to an empty array for cases where it is important to avoid
 * returning a new array reference on every invocation, as in a connected or
 * other pure component which performs `shouldComponentUpdate` check on props.
 * This should be used as a last resort, since the normalized data should be
 * maintained by the reducer result in state.
 *
 * @type {Array}
 */

var EMPTY_ARRAY = [];
/**
 * Returns a block's name given its client ID, or null if no block exists with
 * the client ID.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {string} Block name.
 */

function getBlockName(state, clientId) {
  var block = state.blocks.byClientId[clientId];
  var socialLinkName = 'core/social-link';

  if (_element.Platform.OS !== 'web' && (block === null || block === void 0 ? void 0 : block.name) === socialLinkName) {
    var attributes = state.blocks.attributes[clientId];
    var service = attributes.service;
    return service ? "".concat(socialLinkName, "-").concat(service) : socialLinkName;
  }

  return block ? block.name : null;
}
/**
 * Returns whether a block is valid or not.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Is Valid.
 */


function isBlockValid(state, clientId) {
  var block = state.blocks.byClientId[clientId];
  return !!block && block.isValid;
}
/**
 * Returns a block's attributes given its client ID, or null if no block exists with
 * the client ID.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {Object?} Block attributes.
 */


function getBlockAttributes(state, clientId) {
  var block = state.blocks.byClientId[clientId];

  if (!block) {
    return null;
  }

  return state.blocks.attributes[clientId];
}
/**
 * Returns a block given its client ID. This is a parsed copy of the block,
 * containing its `blockName`, `clientId`, and current `attributes` state. This
 * is not the block's registration settings, which must be retrieved from the
 * blocks module registration store.
 *
 * getBlock recurses through its inner blocks until all its children blocks have
 * been retrieved. Note that getBlock will not return the child inner blocks of
 * an inner block controller. This is because an inner block controller syncs
 * itself with its own entity, and should therefore not be included with the
 * blocks of a different entity. For example, say you call `getBlocks( TP )` to
 * get the blocks of a template part. If another template part is a child of TP,
 * then the nested template part's child blocks will not be returned. This way,
 * the template block itself is considered part of the parent, but the children
 * are not.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {Object} Parsed block object.
 */


var getBlock = (0, _rememo.default)(function (state, clientId) {
  var block = state.blocks.byClientId[clientId];

  if (!block) {
    return null;
  }

  return _objectSpread(_objectSpread({}, block), {}, {
    attributes: getBlockAttributes(state, clientId),
    innerBlocks: areInnerBlocksControlled(state, clientId) ? EMPTY_ARRAY : getBlocks(state, clientId)
  });
}, function (state, clientId) {
  return [// Normally, we'd have both `getBlockAttributes` dependencies and
  // `getBlocks` (children) dependancies here but for performance reasons
  // we use a denormalized cache key computed in the reducer that takes both
  // the attributes and inner blocks into account. The value of the cache key
  // is being changed whenever one of these dependencies is out of date.
  state.blocks.cache[clientId]];
});
exports.getBlock = getBlock;

var __unstableGetBlockWithoutInnerBlocks = (0, _rememo.default)(function (state, clientId) {
  var block = state.blocks.byClientId[clientId];

  if (!block) {
    return null;
  }

  return _objectSpread(_objectSpread({}, block), {}, {
    attributes: getBlockAttributes(state, clientId)
  });
}, function (state, clientId) {
  return [state.blocks.byClientId[clientId], state.blocks.attributes[clientId]];
});
/**
 * Returns all block objects for the current post being edited as an array in
 * the order they appear in the post. Note that this will exclude child blocks
 * of nested inner block controllers.
 *
 * Note: It's important to memoize this selector to avoid return a new instance
 * on each call. We use the block cache state for each top-level block of the
 * given clientID. This way, the selector only refreshes on changes to blocks
 * associated with the given entity, and does not refresh when changes are made
 * to blocks which are part of different inner block controllers.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {Object[]} Post blocks.
 */


exports.__unstableGetBlockWithoutInnerBlocks = __unstableGetBlockWithoutInnerBlocks;
var getBlocks = (0, _rememo.default)(function (state, rootClientId) {
  return (0, _lodash.map)(getBlockOrder(state, rootClientId), function (clientId) {
    return getBlock(state, clientId);
  });
}, function (state, rootClientId) {
  return (0, _lodash.map)(state.blocks.order[rootClientId || ''], function (id) {
    return state.blocks.cache[id];
  });
});
/**
 * Similar to getBlock, except it will include the entire nested block tree as
 * inner blocks. The normal getBlock selector will exclude sections of the block
 * tree which belong to different entities.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Client ID of the block to get.
 *
 * @return {Object} The block with all
 */

exports.getBlocks = getBlocks;

var __unstableGetBlockWithBlockTree = (0, _rememo.default)(function (state, clientId) {
  var block = state.blocks.byClientId[clientId];

  if (!block) {
    return null;
  }

  return _objectSpread(_objectSpread({}, block), {}, {
    attributes: getBlockAttributes(state, clientId),
    innerBlocks: __unstableGetBlockTree(state, clientId)
  });
}, function (state) {
  return [state.blocks.byClientId, state.blocks.order, state.blocks.attributes];
});
/**
 * Similar to getBlocks, except this selector returns the entire block tree
 * represented in the block-editor store from the given root regardless of any
 * inner block controllers.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {Object[]} Post blocks.
 */


exports.__unstableGetBlockWithBlockTree = __unstableGetBlockWithBlockTree;

var __unstableGetBlockTree = (0, _rememo.default)(function (state) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  return (0, _lodash.map)(getBlockOrder(state, rootClientId), function (clientId) {
    return __unstableGetBlockWithBlockTree(state, clientId);
  });
}, function (state) {
  return [state.blocks.byClientId, state.blocks.order, state.blocks.attributes];
});
/**
 * Returns an array containing the clientIds of all descendants
 * of the blocks given.
 *
 * @param {Object} state Global application state.
 * @param {Array} clientIds Array of blocks to inspect.
 *
 * @return {Array} ids of descendants.
 */


exports.__unstableGetBlockTree = __unstableGetBlockTree;

var getClientIdsOfDescendants = function getClientIdsOfDescendants(state, clientIds) {
  return (0, _lodash.flatMap)(clientIds, function (clientId) {
    var descendants = getBlockOrder(state, clientId);
    return [].concat((0, _toConsumableArray2.default)(descendants), (0, _toConsumableArray2.default)(getClientIdsOfDescendants(state, descendants)));
  });
};
/**
 * Returns an array containing the clientIds of the top-level blocks
 * and their descendants of any depth (for nested blocks).
 *
 * @param {Object} state Global application state.
 *
 * @return {Array} ids of top-level and descendant blocks.
 */


exports.getClientIdsOfDescendants = getClientIdsOfDescendants;
var getClientIdsWithDescendants = (0, _rememo.default)(function (state) {
  var topLevelIds = getBlockOrder(state);
  return [].concat((0, _toConsumableArray2.default)(topLevelIds), (0, _toConsumableArray2.default)(getClientIdsOfDescendants(state, topLevelIds)));
}, function (state) {
  return [state.blocks.order];
});
/**
 * Returns the total number of blocks, or the total number of blocks with a specific name in a post.
 * The number returned includes nested blocks.
 *
 * @param {Object}  state     Global application state.
 * @param {?string} blockName Optional block name, if specified only blocks of that type will be counted.
 *
 * @return {number} Number of blocks in the post, or number of blocks with name equal to blockName.
 */

exports.getClientIdsWithDescendants = getClientIdsWithDescendants;
var getGlobalBlockCount = (0, _rememo.default)(function (state, blockName) {
  var clientIds = getClientIdsWithDescendants(state);

  if (!blockName) {
    return clientIds.length;
  }

  return (0, _lodash.reduce)(clientIds, function (accumulator, clientId) {
    var block = state.blocks.byClientId[clientId];
    return block.name === blockName ? accumulator + 1 : accumulator;
  }, 0);
}, function (state) {
  return [state.blocks.order, state.blocks.byClientId];
});
/**
 * Given an array of block client IDs, returns the corresponding array of block
 * objects.
 *
 * @param {Object}   state     Editor state.
 * @param {string[]} clientIds Client IDs for which blocks are to be returned.
 *
 * @return {WPBlock[]} Block objects.
 */

exports.getGlobalBlockCount = getGlobalBlockCount;
var getBlocksByClientId = (0, _rememo.default)(function (state, clientIds) {
  return (0, _lodash.map)((0, _lodash.castArray)(clientIds), function (clientId) {
    return getBlock(state, clientId);
  });
}, function (state) {
  return [state.blocks.byClientId, state.blocks.order, state.blocks.attributes];
});
/**
 * Returns the number of blocks currently present in the post.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {number} Number of blocks in the post.
 */

exports.getBlocksByClientId = getBlocksByClientId;

function getBlockCount(state, rootClientId) {
  return getBlockOrder(state, rootClientId).length;
}
/**
 * Returns the current selection start block client ID, attribute key and text
 * offset.
 *
 * @param {Object} state Block editor state.
 *
 * @return {WPBlockSelection} Selection start information.
 */


function getSelectionStart(state) {
  return state.selectionStart;
}
/**
 * Returns the current selection end block client ID, attribute key and text
 * offset.
 *
 * @param {Object} state Block editor state.
 *
 * @return {WPBlockSelection} Selection end information.
 */


function getSelectionEnd(state) {
  return state.selectionEnd;
}
/**
 * Returns the current block selection start. This value may be null, and it
 * may represent either a singular block selection or multi-selection start.
 * A selection is singular if its start and end match.
 *
 * @param {Object} state Global application state.
 *
 * @return {?string} Client ID of block selection start.
 */


function getBlockSelectionStart(state) {
  return state.selectionStart.clientId;
}
/**
 * Returns the current block selection end. This value may be null, and it
 * may represent either a singular block selection or multi-selection end.
 * A selection is singular if its start and end match.
 *
 * @param {Object} state Global application state.
 *
 * @return {?string} Client ID of block selection end.
 */


function getBlockSelectionEnd(state) {
  return state.selectionEnd.clientId;
}
/**
 * Returns the number of blocks currently selected in the post.
 *
 * @param {Object} state Global application state.
 *
 * @return {number} Number of blocks selected in the post.
 */


function getSelectedBlockCount(state) {
  var multiSelectedBlockCount = getMultiSelectedBlockClientIds(state).length;

  if (multiSelectedBlockCount) {
    return multiSelectedBlockCount;
  }

  return state.selectionStart.clientId ? 1 : 0;
}
/**
 * Returns true if there is a single selected block, or false otherwise.
 *
 * @param {Object} state Editor state.
 *
 * @return {boolean} Whether a single block is selected.
 */


function hasSelectedBlock(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;
  return !!selectionStart.clientId && selectionStart.clientId === selectionEnd.clientId;
}
/**
 * Returns the currently selected block client ID, or null if there is no
 * selected block.
 *
 * @param {Object} state Editor state.
 *
 * @return {?string} Selected block client ID.
 */


function getSelectedBlockClientId(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;
  var clientId = selectionStart.clientId;

  if (!clientId || clientId !== selectionEnd.clientId) {
    return null;
  }

  return clientId;
}
/**
 * Returns the currently selected block, or null if there is no selected block.
 *
 * @param {Object} state Global application state.
 *
 * @return {?Object} Selected block.
 */


function getSelectedBlock(state) {
  var clientId = getSelectedBlockClientId(state);
  return clientId ? getBlock(state, clientId) : null;
}
/**
 * Given a block client ID, returns the root block from which the block is
 * nested, an empty string for top-level blocks, or null if the block does not
 * exist.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block from which to find root client ID.
 *
 * @return {?string} Root client ID, if exists
 */


function getBlockRootClientId(state, clientId) {
  return state.blocks.parents[clientId] !== undefined ? state.blocks.parents[clientId] : null;
}
/**
 * Given a block client ID, returns the list of all its parents from top to bottom.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block from which to find root client ID.
 * @param {boolean} ascending Order results from bottom to top (true) or top to bottom (false).
 *
 * @return {Array} ClientIDs of the parent blocks.
 */


var getBlockParents = (0, _rememo.default)(function (state, clientId) {
  var ascending = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  var parents = [];
  var current = clientId;

  while (!!state.blocks.parents[current]) {
    current = state.blocks.parents[current];
    parents.push(current);
  }

  return ascending ? parents : parents.reverse();
}, function (state) {
  return [state.blocks.parents];
});
/**
 * Given a block client ID and a block name,
 * returns the list of all its parents from top to bottom,
 * filtered by the given name.
 *
 * @param {Object} state     Editor state.
 * @param {string} clientId  Block from which to find root client ID.
 * @param {string} blockName Block name to filter.
 * @param {boolean} ascending Order results from bottom to top (true) or top to bottom (false).
 *
 * @return {Array} ClientIDs of the parent blocks.
 */

exports.getBlockParents = getBlockParents;
var getBlockParentsByBlockName = (0, _rememo.default)(function (state, clientId, blockName) {
  var ascending = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  var parents = getBlockParents(state, clientId, ascending);
  return (0, _lodash.map)((0, _lodash.filter)((0, _lodash.map)(parents, function (id) {
    return {
      id: id,
      name: getBlockName(state, id)
    };
  }), {
    name: blockName
  }), function (_ref) {
    var id = _ref.id;
    return id;
  });
}, function (state) {
  return [state.blocks.parents];
});
/**
 * Given a block client ID, returns the root of the hierarchy from which the block is nested, return the block itself for root level blocks.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block from which to find root client ID.
 *
 * @return {string} Root client ID
 */

exports.getBlockParentsByBlockName = getBlockParentsByBlockName;

function getBlockHierarchyRootClientId(state, clientId) {
  var current = clientId;
  var parent;

  do {
    parent = current;
    current = state.blocks.parents[current];
  } while (current);

  return parent;
}
/**
 * Given a block client ID, returns the lowest common ancestor with selected client ID.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block from which to find common ancestor client ID.
 *
 * @return {string} Common ancestor client ID or undefined
 */


function getLowestCommonAncestorWithSelectedBlock(state, clientId) {
  var selectedId = getSelectedBlockClientId(state);
  var clientParents = [].concat((0, _toConsumableArray2.default)(getBlockParents(state, clientId)), [clientId]);
  var selectedParents = [].concat((0, _toConsumableArray2.default)(getBlockParents(state, selectedId)), [selectedId]);
  var lowestCommonAncestor;
  var maxDepth = Math.min(clientParents.length, selectedParents.length);

  for (var index = 0; index < maxDepth; index++) {
    if (clientParents[index] === selectedParents[index]) {
      lowestCommonAncestor = clientParents[index];
    } else {
      break;
    }
  }

  return lowestCommonAncestor;
}
/**
 * Returns the client ID of the block adjacent one at the given reference
 * startClientId and modifier directionality. Defaults start startClientId to
 * the selected block, and direction as next block. Returns null if there is no
 * adjacent block.
 *
 * @param {Object}  state         Editor state.
 * @param {?string} startClientId Optional client ID of block from which to
 *                                search.
 * @param {?number} modifier      Directionality multiplier (1 next, -1
 *                                previous).
 *
 * @return {?string} Return the client ID of the block, or null if none exists.
 */


function getAdjacentBlockClientId(state, startClientId) {
  var modifier = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;

  // Default to selected block.
  if (startClientId === undefined) {
    startClientId = getSelectedBlockClientId(state);
  } // Try multi-selection starting at extent based on modifier.


  if (startClientId === undefined) {
    if (modifier < 0) {
      startClientId = getFirstMultiSelectedBlockClientId(state);
    } else {
      startClientId = getLastMultiSelectedBlockClientId(state);
    }
  } // Validate working start client ID.


  if (!startClientId) {
    return null;
  } // Retrieve start block root client ID, being careful to allow the falsey
  // empty string top-level root by explicitly testing against null.


  var rootClientId = getBlockRootClientId(state, startClientId);

  if (rootClientId === null) {
    return null;
  }

  var order = state.blocks.order;
  var orderSet = order[rootClientId];
  var index = orderSet.indexOf(startClientId);
  var nextIndex = index + 1 * modifier; // Block was first in set and we're attempting to get previous.

  if (nextIndex < 0) {
    return null;
  } // Block was last in set and we're attempting to get next.


  if (nextIndex === orderSet.length) {
    return null;
  } // Assume incremented index is within the set.


  return orderSet[nextIndex];
}
/**
 * Returns the previous block's client ID from the given reference start ID.
 * Defaults start to the selected block. Returns null if there is no previous
 * block.
 *
 * @param {Object}  state         Editor state.
 * @param {?string} startClientId Optional client ID of block from which to
 *                                search.
 *
 * @return {?string} Adjacent block's client ID, or null if none exists.
 */


function getPreviousBlockClientId(state, startClientId) {
  return getAdjacentBlockClientId(state, startClientId, -1);
}
/**
 * Returns the next block's client ID from the given reference start ID.
 * Defaults start to the selected block. Returns null if there is no next
 * block.
 *
 * @param {Object}  state         Editor state.
 * @param {?string} startClientId Optional client ID of block from which to
 *                                search.
 *
 * @return {?string} Adjacent block's client ID, or null if none exists.
 */


function getNextBlockClientId(state, startClientId) {
  return getAdjacentBlockClientId(state, startClientId, 1);
}
/**
 * Returns the initial caret position for the selected block.
 * This position is to used to position the caret properly when the selected block changes.
 *
 * @param {Object} state Global application state.
 *
 * @return {?Object} Selected block.
 */


function getSelectedBlocksInitialCaretPosition(state) {
  return state.initialPosition;
}
/**
 * Returns the current selection set of block client IDs (multiselection or single selection).
 *
 * @param {Object} state Editor state.
 *
 * @return {Array} Multi-selected block client IDs.
 */


var getSelectedBlockClientIds = (0, _rememo.default)(function (state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;

  if (selectionStart.clientId === undefined || selectionEnd.clientId === undefined) {
    return EMPTY_ARRAY;
  }

  if (selectionStart.clientId === selectionEnd.clientId) {
    return [selectionStart.clientId];
  } // Retrieve root client ID to aid in retrieving relevant nested block
  // order, being careful to allow the falsey empty string top-level root
  // by explicitly testing against null.


  var rootClientId = getBlockRootClientId(state, selectionStart.clientId);

  if (rootClientId === null) {
    return EMPTY_ARRAY;
  }

  var blockOrder = getBlockOrder(state, rootClientId);
  var startIndex = blockOrder.indexOf(selectionStart.clientId);
  var endIndex = blockOrder.indexOf(selectionEnd.clientId);

  if (startIndex > endIndex) {
    return blockOrder.slice(endIndex, startIndex + 1);
  }

  return blockOrder.slice(startIndex, endIndex + 1);
}, function (state) {
  return [state.blocks.order, state.selectionStart.clientId, state.selectionEnd.clientId];
});
/**
 * Returns the current multi-selection set of block client IDs, or an empty
 * array if there is no multi-selection.
 *
 * @param {Object} state Editor state.
 *
 * @return {Array} Multi-selected block client IDs.
 */

exports.getSelectedBlockClientIds = getSelectedBlockClientIds;

function getMultiSelectedBlockClientIds(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;

  if (selectionStart.clientId === selectionEnd.clientId) {
    return EMPTY_ARRAY;
  }

  return getSelectedBlockClientIds(state);
}
/**
 * Returns the current multi-selection set of blocks, or an empty array if
 * there is no multi-selection.
 *
 * @param {Object} state Editor state.
 *
 * @return {Array} Multi-selected block objects.
 */


var getMultiSelectedBlocks = (0, _rememo.default)(function (state) {
  var multiSelectedBlockClientIds = getMultiSelectedBlockClientIds(state);

  if (!multiSelectedBlockClientIds.length) {
    return EMPTY_ARRAY;
  }

  return multiSelectedBlockClientIds.map(function (clientId) {
    return getBlock(state, clientId);
  });
}, function (state) {
  return [].concat((0, _toConsumableArray2.default)(getSelectedBlockClientIds.getDependants(state)), [state.blocks.byClientId, state.blocks.order, state.blocks.attributes]);
});
/**
 * Returns the client ID of the first block in the multi-selection set, or null
 * if there is no multi-selection.
 *
 * @param {Object} state Editor state.
 *
 * @return {?string} First block client ID in the multi-selection set.
 */

exports.getMultiSelectedBlocks = getMultiSelectedBlocks;

function getFirstMultiSelectedBlockClientId(state) {
  return (0, _lodash.first)(getMultiSelectedBlockClientIds(state)) || null;
}
/**
 * Returns the client ID of the last block in the multi-selection set, or null
 * if there is no multi-selection.
 *
 * @param {Object} state Editor state.
 *
 * @return {?string} Last block client ID in the multi-selection set.
 */


function getLastMultiSelectedBlockClientId(state) {
  return (0, _lodash.last)(getMultiSelectedBlockClientIds(state)) || null;
}
/**
 * Returns true if a multi-selection exists, and the block corresponding to the
 * specified client ID is the first block of the multi-selection set, or false
 * otherwise.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Whether block is first in multi-selection.
 */


function isFirstMultiSelectedBlock(state, clientId) {
  return getFirstMultiSelectedBlockClientId(state) === clientId;
}
/**
 * Returns true if the client ID occurs within the block multi-selection, or
 * false otherwise.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Whether block is in multi-selection set.
 */


function isBlockMultiSelected(state, clientId) {
  return getMultiSelectedBlockClientIds(state).indexOf(clientId) !== -1;
}
/**
 * Returns true if an ancestor of the block is multi-selected, or false
 * otherwise.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Whether an ancestor of the block is in multi-selection
 *                   set.
 */


var isAncestorMultiSelected = (0, _rememo.default)(function (state, clientId) {
  var ancestorClientId = clientId;
  var isMultiSelected = false;

  while (ancestorClientId && !isMultiSelected) {
    ancestorClientId = getBlockRootClientId(state, ancestorClientId);
    isMultiSelected = isBlockMultiSelected(state, ancestorClientId);
  }

  return isMultiSelected;
}, function (state) {
  return [state.blocks.order, state.selectionStart.clientId, state.selectionEnd.clientId];
});
/**
 * Returns the client ID of the block which begins the multi-selection set, or
 * null if there is no multi-selection.
 *
 * This is not necessarily the first client ID in the selection.
 *
 * @see getFirstMultiSelectedBlockClientId
 *
 * @param {Object} state Editor state.
 *
 * @return {?string} Client ID of block beginning multi-selection.
 */

exports.isAncestorMultiSelected = isAncestorMultiSelected;

function getMultiSelectedBlocksStartClientId(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;

  if (selectionStart.clientId === selectionEnd.clientId) {
    return null;
  }

  return selectionStart.clientId || null;
}
/**
 * Returns the client ID of the block which ends the multi-selection set, or
 * null if there is no multi-selection.
 *
 * This is not necessarily the last client ID in the selection.
 *
 * @see getLastMultiSelectedBlockClientId
 *
 * @param {Object} state Editor state.
 *
 * @return {?string} Client ID of block ending multi-selection.
 */


function getMultiSelectedBlocksEndClientId(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;

  if (selectionStart.clientId === selectionEnd.clientId) {
    return null;
  }

  return selectionEnd.clientId || null;
}
/**
 * Returns an array containing all block client IDs in the editor in the order
 * they appear. Optionally accepts a root client ID of the block list for which
 * the order should be returned, defaulting to the top-level block order.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {Array} Ordered client IDs of editor blocks.
 */


function getBlockOrder(state, rootClientId) {
  return state.blocks.order[rootClientId || ''] || EMPTY_ARRAY;
}
/**
 * Returns the index at which the block corresponding to the specified client
 * ID occurs within the block order, or `-1` if the block does not exist.
 *
 * @param {Object}  state        Editor state.
 * @param {string}  clientId     Block client ID.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {number} Index at which block exists in order.
 */


function getBlockIndex(state, clientId, rootClientId) {
  return getBlockOrder(state, rootClientId).indexOf(clientId);
}
/**
 * Returns true if the block corresponding to the specified client ID is
 * currently selected and no multi-selection exists, or false otherwise.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Whether block is selected and multi-selection exists.
 */


function isBlockSelected(state, clientId) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;

  if (selectionStart.clientId !== selectionEnd.clientId) {
    return false;
  }

  return selectionStart.clientId === clientId;
}
/**
 * Returns true if one of the block's inner blocks is selected.
 *
 * @param {Object}  state    Editor state.
 * @param {string}  clientId Block client ID.
 * @param {boolean} deep     Perform a deep check.
 *
 * @return {boolean} Whether the block as an inner block selected
 */


function hasSelectedInnerBlock(state, clientId) {
  var deep = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  return (0, _lodash.some)(getBlockOrder(state, clientId), function (innerClientId) {
    return isBlockSelected(state, innerClientId) || isBlockMultiSelected(state, innerClientId) || deep && hasSelectedInnerBlock(state, innerClientId, deep);
  });
}
/**
 * Returns true if the block corresponding to the specified client ID is
 * currently selected but isn't the last of the selected blocks. Here "last"
 * refers to the block sequence in the document, _not_ the sequence of
 * multi-selection, which is why `state.selectionEnd` isn't used.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {boolean} Whether block is selected and not the last in the
 *                   selection.
 */


function isBlockWithinSelection(state, clientId) {
  if (!clientId) {
    return false;
  }

  var clientIds = getMultiSelectedBlockClientIds(state);
  var index = clientIds.indexOf(clientId);
  return index > -1 && index < clientIds.length - 1;
}
/**
 * Returns true if a multi-selection has been made, or false otherwise.
 *
 * @param {Object} state Editor state.
 *
 * @return {boolean} Whether multi-selection has been made.
 */


function hasMultiSelection(state) {
  var selectionStart = state.selectionStart,
      selectionEnd = state.selectionEnd;
  return selectionStart.clientId !== selectionEnd.clientId;
}
/**
 * Whether in the process of multi-selecting or not. This flag is only true
 * while the multi-selection is being selected (by mouse move), and is false
 * once the multi-selection has been settled.
 *
 * @see hasMultiSelection
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} True if multi-selecting, false if not.
 */


function isMultiSelecting(state) {
  return state.isMultiSelecting;
}
/**
 * Selector that returns if multi-selection is enabled or not.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} True if it should be possible to multi-select blocks, false if multi-selection is disabled.
 */


function isSelectionEnabled(state) {
  return state.isSelectionEnabled;
}
/**
 * Returns the block's editing mode, defaulting to "visual" if not explicitly
 * assigned.
 *
 * @param {Object} state    Editor state.
 * @param {string} clientId Block client ID.
 *
 * @return {Object} Block editing mode.
 */


function getBlockMode(state, clientId) {
  return state.blocksMode[clientId] || 'visual';
}
/**
 * Returns true if the user is typing, or false otherwise.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} Whether user is typing.
 */


function isTyping(state) {
  return state.isTyping;
}
/**
 * Returns true if the user is dragging blocks, or false otherwise.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} Whether user is dragging blocks.
 */


function isDraggingBlocks(state) {
  return !!state.draggedBlocks.length;
}
/**
 * Returns the client ids of any blocks being directly dragged.
 *
 * This does not include children of a parent being dragged.
 *
 * @param {Object} state Global application state.
 *
 * @return {string[]} Array of dragged block client ids.
 */


function getDraggedBlockClientIds(state) {
  return state.draggedBlocks;
}
/**
 * Returns whether the block is being dragged.
 *
 * Only returns true if the block is being directly dragged,
 * not if the block is a child of a parent being dragged.
 * See `isAncestorBeingDragged` for child blocks.
 *
 * @param {Object} state    Global application state.
 * @param {string} clientId Client id for block to check.
 *
 * @return {boolean} Whether the block is being dragged.
 */


function isBlockBeingDragged(state, clientId) {
  return state.draggedBlocks.includes(clientId);
}
/**
 * Returns whether a parent/ancestor of the block is being dragged.
 *
 * @param {Object} state    Global application state.
 * @param {string} clientId Client id for block to check.
 *
 * @return {boolean} Whether the block's ancestor is being dragged.
 */


function isAncestorBeingDragged(state, clientId) {
  // Return early if no blocks are being dragged rather than
  // the more expensive check for parents.
  if (!isDraggingBlocks(state)) {
    return false;
  }

  var parents = getBlockParents(state, clientId);
  return (0, _lodash.some)(parents, function (parentClientId) {
    return isBlockBeingDragged(state, parentClientId);
  });
}
/**
 * Returns true if the caret is within formatted text, or false otherwise.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} Whether the caret is within formatted text.
 */


function isCaretWithinFormattedText(state) {
  return state.isCaretWithinFormattedText;
}
/**
 * Returns the insertion point, the index at which the new inserted block would
 * be placed. Defaults to the last index.
 *
 * @param {Object} state Editor state.
 *
 * @return {Object} Insertion point object with `rootClientId`, `index`.
 */


function getBlockInsertionPoint(state) {
  var rootClientId, index;
  var insertionPoint = state.insertionPoint,
      selectionEnd = state.selectionEnd;

  if (insertionPoint !== null) {
    return insertionPoint;
  }

  var clientId = selectionEnd.clientId;

  if (clientId) {
    rootClientId = getBlockRootClientId(state, clientId) || undefined;
    index = getBlockIndex(state, selectionEnd.clientId, rootClientId) + 1;
  } else {
    index = getBlockOrder(state).length;
  }

  return {
    rootClientId: rootClientId,
    index: index
  };
}
/**
 * Returns true if we should show the block insertion point.
 *
 * @param {Object} state Global application state.
 *
 * @return {?boolean} Whether the insertion point is visible or not.
 */


function isBlockInsertionPointVisible(state) {
  return state.insertionPoint !== null;
}
/**
 * Returns whether the blocks matches the template or not.
 *
 * @param {boolean} state
 * @return {?boolean} Whether the template is valid or not.
 */


function isValidTemplate(state) {
  return state.template.isValid;
}
/**
 * Returns the defined block template
 *
 * @param {boolean} state
 * @return {?Array}        Block Template
 */


function getTemplate(state) {
  return state.settings.template;
}
/**
 * Returns the defined block template lock. Optionally accepts a root block
 * client ID as context, otherwise defaulting to the global context.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional block root client ID.
 *
 * @return {?string} Block Template Lock
 */


function getTemplateLock(state, rootClientId) {
  if (!rootClientId) {
    return state.settings.templateLock;
  }

  var blockListSettings = getBlockListSettings(state, rootClientId);

  if (!blockListSettings) {
    return null;
  }

  return blockListSettings.templateLock;
}
/**
 * Determines if the given block type is allowed to be inserted into the block list.
 * This function is not exported and not memoized because using a memoized selector
 * inside another memoized selector is just a waste of time.
 *
 * @param {Object}  state        Editor state.
 * @param {string}  blockName    The name of the block type, e.g.' core/paragraph'.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {boolean} Whether the given block type is allowed to be inserted.
 */


var canInsertBlockTypeUnmemoized = function canInsertBlockTypeUnmemoized(state, blockName) {
  var rootClientId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

  var checkAllowList = function checkAllowList(list, item) {
    var defaultResult = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

    if ((0, _lodash.isBoolean)(list)) {
      return list;
    }

    if ((0, _lodash.isArray)(list)) {
      // TODO: when there is a canonical way to detect that we are editing a post
      // the following check should be changed to something like:
      // if ( list.includes( 'core/post-content' ) && getEditorMode() === 'post-content' && item === null )
      if (list.includes('core/post-content') && item === null) {
        return true;
      }

      return list.includes(item);
    }

    return defaultResult;
  };

  var blockType = (0, _blocks.getBlockType)(blockName);

  if (!blockType) {
    return false;
  }

  var _getSettings = getSettings(state),
      allowedBlockTypes = _getSettings.allowedBlockTypes;

  var isBlockAllowedInEditor = checkAllowList(allowedBlockTypes, blockName, true);

  if (!isBlockAllowedInEditor) {
    return false;
  }

  var isLocked = !!getTemplateLock(state, rootClientId);

  if (isLocked) {
    return false;
  }

  var parentBlockListSettings = getBlockListSettings(state, rootClientId); // The parent block doesn't have settings indicating it doesn't support
  // inner blocks, return false.

  if (rootClientId && parentBlockListSettings === undefined) {
    return false;
  }

  var parentAllowedBlocks = (0, _lodash.get)(parentBlockListSettings, ['allowedBlocks']);
  var hasParentAllowedBlock = checkAllowList(parentAllowedBlocks, blockName);
  var blockAllowedParentBlocks = blockType.parent;
  var parentName = getBlockName(state, rootClientId);
  var hasBlockAllowedParent = checkAllowList(blockAllowedParentBlocks, parentName);

  if (hasParentAllowedBlock !== null && hasBlockAllowedParent !== null) {
    return hasParentAllowedBlock || hasBlockAllowedParent;
  } else if (hasParentAllowedBlock !== null) {
    return hasParentAllowedBlock;
  } else if (hasBlockAllowedParent !== null) {
    return hasBlockAllowedParent;
  }

  return true;
};
/**
 * Determines if the given block type is allowed to be inserted into the block list.
 *
 * @param {Object}  state        Editor state.
 * @param {string}  blockName    The name of the block type, e.g.' core/paragraph'.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {boolean} Whether the given block type is allowed to be inserted.
 */


var canInsertBlockType = (0, _rememo.default)(canInsertBlockTypeUnmemoized, function (state, blockName, rootClientId) {
  return [state.blockListSettings[rootClientId], state.blocks.byClientId[rootClientId], state.settings.allowedBlockTypes, state.settings.templateLock];
});
/**
 * Determines if the given blocks are allowed to be inserted into the block
 * list.
 *
 * @param {Object}  state        Editor state.
 * @param {string}  clientIds    The block client IDs to be inserted.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {boolean} Whether the given blocks are allowed to be inserted.
 */

exports.canInsertBlockType = canInsertBlockType;

function canInsertBlocks(state, clientIds) {
  var rootClientId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
  return clientIds.every(function (id) {
    return canInsertBlockType(state, getBlockName(state, id), rootClientId);
  });
}
/**
 * Returns information about how recently and frequently a block has been inserted.
 *
 * @param {Object} state Global application state.
 * @param {string} id    A string which identifies the insert, e.g. 'core/block/12'
 *
 * @return {?{ time: number, count: number }} An object containing `time` which is when the last
 *                                            insert occurred as a UNIX epoch, and `count` which is
 *                                            the number of inserts that have occurred.
 */


function getInsertUsage(state, id) {
  return (0, _lodash.get)(state.preferences.insertUsage, [id], null);
}
/**
 * Returns whether we can show a block type in the inserter
 *
 * @param {Object} state Global State
 * @param {Object} blockType BlockType
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {boolean} Whether the given block type is allowed to be shown in the inserter.
 */


var canIncludeBlockTypeInInserter = function canIncludeBlockTypeInInserter(state, blockType, rootClientId) {
  if (!(0, _blocks.hasBlockSupport)(blockType, 'inserter', true)) {
    return false;
  }

  return canInsertBlockTypeUnmemoized(state, blockType.name, rootClientId);
};
/**
 * Return a function to be used to tranform a block variation to an inserter item
 *
 * @param {Object} item Denormalized inserter item
 * @return {Function} Function to transform a block variation to inserter item
 */


var getItemFromVariation = function getItemFromVariation(item) {
  return function (variation) {
    return _objectSpread(_objectSpread({}, item), {}, {
      id: "".concat(item.id, "-").concat(variation.name),
      icon: variation.icon || item.icon,
      title: variation.title || item.title,
      description: variation.description || item.description,
      // If `example` is explicitly undefined for the variation, the preview will not be shown.
      example: variation.hasOwnProperty('example') ? variation.example : item.example,
      initialAttributes: _objectSpread(_objectSpread({}, item.initialAttributes), variation.attributes),
      innerBlocks: variation.innerBlocks,
      keywords: variation.keywords || item.keywords
    });
  };
};
/**
 * Determines the items that appear in the inserter. Includes both static
 * items (e.g. a regular block type) and dynamic items (e.g. a reusable block).
 *
 * Each item object contains what's necessary to display a button in the
 * inserter and handle its selection.
 *
 * The 'frecency' property is a heuristic (https://en.wikipedia.org/wiki/Frecency)
 * that combines block usage frequenty and recency.
 *
 * Items are returned ordered descendingly by their 'utility' and 'frecency'.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {WPEditorInserterItem[]} Items that appear in inserter.
 *
 * @typedef {Object} WPEditorInserterItem
 * @property {string}   id                Unique identifier for the item.
 * @property {string}   name              The type of block to create.
 * @property {Object}   initialAttributes Attributes to pass to the newly created block.
 * @property {string}   title             Title of the item, as it appears in the inserter.
 * @property {string}   icon              Dashicon for the item, as it appears in the inserter.
 * @property {string}   category          Block category that the item is associated with.
 * @property {string[]} keywords          Keywords that can be searched to find this item.
 * @property {boolean}  isDisabled        Whether or not the user should be prevented from inserting
 *                                        this item.
 * @property {number}   frecency          Heuristic that combines frequency and recency.
 */


var getInserterItems = (0, _rememo.default)(function (state) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  var calculateFrecency = function calculateFrecency(time, count) {
    if (!time) {
      return count;
    } // The selector is cached, which means Date.now() is the last time that the
    // relevant state changed. This suits our needs.


    var duration = Date.now() - time;

    switch (true) {
      case duration < MILLISECONDS_PER_HOUR:
        return count * 4;

      case duration < MILLISECONDS_PER_DAY:
        return count * 2;

      case duration < MILLISECONDS_PER_WEEK:
        return count / 2;

      default:
        return count / 4;
    }
  };

  var buildBlockTypeInserterItem = function buildBlockTypeInserterItem(blockType) {
    var id = blockType.name;
    var isDisabled = false;

    if (!(0, _blocks.hasBlockSupport)(blockType.name, 'multiple', true)) {
      isDisabled = (0, _lodash.some)(getBlocksByClientId(state, getClientIdsWithDescendants(state)), {
        name: blockType.name
      });
    }

    var _ref2 = getInsertUsage(state, id) || {},
        time = _ref2.time,
        _ref2$count = _ref2.count,
        count = _ref2$count === void 0 ? 0 : _ref2$count;

    var inserterVariations = blockType.variations.filter(function (_ref3) {
      var scope = _ref3.scope;
      return !scope || scope.includes('inserter');
    });
    return {
      id: id,
      name: blockType.name,
      initialAttributes: {},
      title: blockType.title,
      description: blockType.description,
      icon: blockType.icon,
      category: blockType.category,
      keywords: blockType.keywords,
      variations: inserterVariations,
      example: blockType.example,
      isDisabled: isDisabled,
      utility: 1,
      // deprecated
      frecency: calculateFrecency(time, count)
    };
  };

  var buildReusableBlockInserterItem = function buildReusableBlockInserterItem(reusableBlock) {
    var id = "core/block/".concat(reusableBlock.id);

    var referencedBlocks = __experimentalGetParsedReusableBlock(state, reusableBlock.id);

    var referencedBlockType;

    if (referencedBlocks.length === 1) {
      referencedBlockType = (0, _blocks.getBlockType)(referencedBlocks[0].name);
    }

    var _ref4 = getInsertUsage(state, id) || {},
        time = _ref4.time,
        _ref4$count = _ref4.count,
        count = _ref4$count === void 0 ? 0 : _ref4$count;

    var frecency = calculateFrecency(time, count);
    return {
      id: id,
      name: 'core/block',
      initialAttributes: {
        ref: reusableBlock.id
      },
      title: reusableBlock.title,
      icon: referencedBlockType ? referencedBlockType.icon : templateIcon,
      category: 'reusable',
      keywords: [],
      isDisabled: false,
      utility: 1,
      // deprecated
      frecency: frecency
    };
  };

  var blockTypeInserterItems = (0, _blocks.getBlockTypes)().filter(function (blockType) {
    return canIncludeBlockTypeInInserter(state, blockType, rootClientId);
  }).map(buildBlockTypeInserterItem);
  var reusableBlockInserterItems = canInsertBlockTypeUnmemoized(state, 'core/block', rootClientId) ? getReusableBlocks(state).map(buildReusableBlockInserterItem) : []; // Exclude any block type item that is to be replaced by a default
  // variation.

  var visibleBlockTypeInserterItems = blockTypeInserterItems.filter(function (_ref5) {
    var _ref5$variations = _ref5.variations,
        variations = _ref5$variations === void 0 ? [] : _ref5$variations;
    return !variations.some(function (_ref6) {
      var isDefault = _ref6.isDefault;
      return isDefault;
    });
  });
  var blockVariations = []; // Show all available blocks with variations

  var _iterator = _createForOfIteratorHelper(blockTypeInserterItems),
      _step;

  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var item = _step.value;
      var _item$variations = item.variations,
          variations = _item$variations === void 0 ? [] : _item$variations;

      if (variations.length) {
        var variationMapper = getItemFromVariation(item);
        blockVariations.push.apply(blockVariations, (0, _toConsumableArray2.default)(variations.map(variationMapper)));
      }
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }

  return [].concat((0, _toConsumableArray2.default)(visibleBlockTypeInserterItems), blockVariations, (0, _toConsumableArray2.default)(reusableBlockInserterItems));
}, function (state, rootClientId) {
  return [state.blockListSettings[rootClientId], state.blocks.byClientId, state.blocks.order, state.preferences.insertUsage, state.settings.allowedBlockTypes, state.settings.templateLock, getReusableBlocks(state), (0, _blocks.getBlockTypes)()];
});
/**
 * Determines whether there are items to show in the inserter.
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {boolean} Items that appear in inserter.
 */

exports.getInserterItems = getInserterItems;
var hasInserterItems = (0, _rememo.default)(function (state) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  var hasBlockType = (0, _lodash.some)((0, _blocks.getBlockTypes)(), function (blockType) {
    return canIncludeBlockTypeInInserter(state, blockType, rootClientId);
  });

  if (hasBlockType) {
    return true;
  }

  var hasReusableBlock = canInsertBlockTypeUnmemoized(state, 'core/block', rootClientId) && getReusableBlocks(state).length > 0;
  return hasReusableBlock;
}, function (state, rootClientId) {
  return [state.blockListSettings[rootClientId], state.blocks.byClientId, state.settings.allowedBlockTypes, state.settings.templateLock, getReusableBlocks(state), (0, _blocks.getBlockTypes)()];
});
/**
 * Returns the list of allowed inserter blocks for inner blocks children
 *
 * @param {Object}  state        Editor state.
 * @param {?string} rootClientId Optional root client ID of block list.
 *
 * @return {Array?} The list of allowed block types.
 */

exports.hasInserterItems = hasInserterItems;

var __experimentalGetAllowedBlocks = (0, _rememo.default)(function (state) {
  var rootClientId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  if (!rootClientId) {
    return;
  }

  return (0, _lodash.filter)((0, _blocks.getBlockTypes)(), function (blockType) {
    return canIncludeBlockTypeInInserter(state, blockType, rootClientId);
  });
}, function (state, rootClientId) {
  return [state.blockListSettings[rootClientId], state.blocks.byClientId, state.settings.allowedBlockTypes, state.settings.templateLock, (0, _blocks.getBlockTypes)()];
});
/**
 * Returns the Block List settings of a block, if any exist.
 *
 * @param {Object}  state    Editor state.
 * @param {?string} clientId Block client ID.
 *
 * @return {?Object} Block settings of the block if set.
 */


exports.__experimentalGetAllowedBlocks = __experimentalGetAllowedBlocks;

function getBlockListSettings(state, clientId) {
  return state.blockListSettings[clientId];
}
/**
 * Returns the editor settings.
 *
 * @param {Object} state Editor state.
 *
 * @return {Object} The editor settings object.
 */


function getSettings(state) {
  return state.settings;
}
/**
 * Returns true if the most recent block change is be considered persistent, or
 * false otherwise. A persistent change is one committed by BlockEditorProvider
 * via its `onChange` callback, in addition to `onInput`.
 *
 * @param {Object} state Block editor state.
 *
 * @return {boolean} Whether the most recent block change was persistent.
 */


function isLastBlockChangePersistent(state) {
  return state.blocks.isPersistentChange;
}
/**
 * Returns the Block List settings for an array of blocks, if any exist.
 *
 * @param {Object}  state    Editor state.
 * @param {Array} clientIds Block client IDs.
 *
 * @return {Array} Block List Settings for each of the found blocks
 */


var __experimentalGetBlockListSettingsForBlocks = (0, _rememo.default)(function (state, clientIds) {
  return (0, _lodash.filter)(state.blockListSettings, function (value, key) {
    return clientIds.includes(key);
  });
}, function (state) {
  return [state.blockListSettings];
});
/**
 * Returns the parsed block saved as shared block with the given ID.
 *
 * @param {Object}        state Global application state.
 * @param {number|string} ref   The shared block's ID.
 *
 * @return {Object} The parsed block.
 */


exports.__experimentalGetBlockListSettingsForBlocks = __experimentalGetBlockListSettingsForBlocks;

var __experimentalGetParsedReusableBlock = (0, _rememo.default)(function (state, ref) {
  var reusableBlock = (0, _lodash.find)(getReusableBlocks(state), function (block) {
    return block.id === ref;
  });

  if (!reusableBlock) {
    return null;
  }

  return (0, _blocks.parse)(reusableBlock.content);
}, function (state) {
  return [getReusableBlocks(state)];
});
/**
 * Returns true if the most recent block change is be considered ignored, or
 * false otherwise. An ignored change is one not to be committed by
 * BlockEditorProvider, neither via `onChange` nor `onInput`.
 *
 * @param {Object} state Block editor state.
 *
 * @return {boolean} Whether the most recent block change was ignored.
 */


exports.__experimentalGetParsedReusableBlock = __experimentalGetParsedReusableBlock;

function __unstableIsLastBlockChangeIgnored(state) {
  // TODO: Removal Plan: Changes incurred by RECEIVE_BLOCKS should not be
  // ignored if in-fact they result in a change in blocks state. The current
  // need to ignore changes not a result of user interaction should be
  // accounted for in the refactoring of reusable blocks as occurring within
  // their own separate block editor / state (#7119).
  return state.blocks.isIgnoredChange;
}
/**
 * Returns the block attributes changed as a result of the last dispatched
 * action.
 *
 * @param {Object} state Block editor state.
 *
 * @return {Object<string,Object>} Subsets of block attributes changed, keyed
 *                                 by block client ID.
 */


function __experimentalGetLastBlockAttributeChanges(state) {
  return state.lastBlockAttributesChange;
}
/**
 * Returns the available reusable blocks
 *
 * @param {Object} state Global application state.
 *
 * @return {Array} Reusable blocks
 */


function getReusableBlocks(state) {
  return (0, _lodash.get)(state, ['settings', '__experimentalReusableBlocks'], EMPTY_ARRAY);
}
/**
 * Returns whether the navigation mode is enabled.
 *
 * @param {Object} state Editor state.
 *
 * @return {boolean}     Is navigation mode enabled.
 */


function isNavigationMode(state) {
  return state.isNavigationMode;
}
/**
 * Returns whether block moving mode is enabled.
 *
 * @param {Object} state Editor state.
 *
 * @return {string}     Client Id of moving block.
 */


function hasBlockMovingClientId(state) {
  return state.hasBlockMovingClientId;
}
/**
 * Returns true if the last change was an automatic change, false otherwise.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} Whether the last change was automatic.
 */


function didAutomaticChange(state) {
  return !!state.automaticChangeStatus;
}
/**
 * Returns true if the current highlighted block matches the block clientId.
 *
 * @param {Object} state Global application state.
 * @param {string} clientId The block to check.
 *
 * @return {boolean} Whether the block is currently highlighted.
 */


function isBlockHighlighted(state, clientId) {
  return state.highlightedBlock === clientId;
}
/**
 * Checks if a given block has controlled inner blocks.
 *
 * @param {Object} state Global application state.
 * @param {string} clientId The block to check.
 *
 * @return {boolean} True if the block has controlled inner blocks.
 */


function areInnerBlocksControlled(state, clientId) {
  return !!state.blocks.controlledInnerBlocks[clientId];
}
//# sourceMappingURL=selectors.js.map