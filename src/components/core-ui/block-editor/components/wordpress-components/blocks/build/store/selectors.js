"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getBlockType = getBlockType;
exports.getBlockStyles = getBlockStyles;
exports.getBlockVariations = getBlockVariations;
exports.getDefaultBlockVariation = getDefaultBlockVariation;
exports.getCategories = getCategories;
exports.getCollections = getCollections;
exports.getDefaultBlockName = getDefaultBlockName;
exports.getFreeformFallbackBlockName = getFreeformFallbackBlockName;
exports.getUnregisteredFallbackBlockName = getUnregisteredFallbackBlockName;
exports.getGroupingBlockName = getGroupingBlockName;
exports.hasBlockSupport = hasBlockSupport;
exports.isMatchingSearchTerm = isMatchingSearchTerm;
exports.hasChildBlocksWithInserterSupport = exports.hasChildBlocks = exports.getBlockSupport = exports.getChildBlockNames = exports.getBlockTypes = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _rememo = _interopRequireDefault(require("rememo"));

var _lodash = require("lodash");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/** @typedef {import('../api/registration').WPBlockVariation} WPBlockVariation */

/** @typedef {import('../api/registration').WPBlockVariationScope} WPBlockVariationScope */

/** @typedef {import('./reducer').WPBlockCategory} WPBlockCategory */

/**
 * Given a block name or block type object, returns the corresponding
 * normalized block type object.
 *
 * @param {Object}          state      Blocks state.
 * @param {(string|Object)} nameOrType Block name or type object
 *
 * @return {Object} Block type object.
 */
var getNormalizedBlockType = function getNormalizedBlockType(state, nameOrType) {
  return 'string' === typeof nameOrType ? getBlockType(state, nameOrType) : nameOrType;
};
/**
 * Returns all the available block types.
 *
 * @param {Object} state Data state.
 *
 * @return {Array} Block Types.
 */


var getBlockTypes = (0, _rememo.default)(function (state) {
  return Object.values(state.blockTypes).map(function (blockType) {
    return _objectSpread({}, blockType, {
      variations: getBlockVariations(state, blockType.name)
    });
  });
}, function (state) {
  return [state.blockTypes, state.blockVariations];
});
/**
 * Returns a block type by name.
 *
 * @param {Object} state Data state.
 * @param {string} name Block type name.
 *
 * @return {Object?} Block Type.
 */

exports.getBlockTypes = getBlockTypes;

function getBlockType(state, name) {
  return state.blockTypes[name];
}
/**
 * Returns block styles by block name.
 *
 * @param {Object} state Data state.
 * @param {string} name  Block type name.
 *
 * @return {Array?} Block Styles.
 */


function getBlockStyles(state, name) {
  return state.blockStyles[name];
}
/**
 * Returns block variations by block name.
 *
 * @param {Object}                state     Data state.
 * @param {string}                blockName Block type name.
 * @param {WPBlockVariationScope} [scope]   Block variation scope name.
 *
 * @return {(WPBlockVariation[]|void)} Block variations.
 */


function getBlockVariations(state, blockName, scope) {
  var variations = state.blockVariations[blockName];

  if (!variations || !scope) {
    return variations;
  }

  return variations.filter(function (variation) {
    return !variation.scope || variation.scope.includes(scope);
  });
}
/**
 * Returns the default block variation for the given block type.
 * When there are multiple variations annotated as the default one,
 * the last added item is picked. This simplifies registering overrides.
 * When there is no default variation set, it returns the first item.
 *
 * @param {Object}                state     Data state.
 * @param {string}                blockName Block type name.
 * @param {WPBlockVariationScope} [scope]   Block variation scope name.
 *
 * @return {?WPBlockVariation} The default block variation.
 */


function getDefaultBlockVariation(state, blockName, scope) {
  var variations = getBlockVariations(state, blockName, scope);
  return (0, _lodash.findLast)(variations, 'isDefault') || (0, _lodash.first)(variations);
}
/**
 * Returns all the available categories.
 *
 * @param {Object} state Data state.
 *
 * @return {WPBlockCategory[]} Categories list.
 */


function getCategories(state) {
  return state.categories;
}
/**
 * Returns all the available collections.
 *
 * @param {Object} state Data state.
 *
 * @return {Object} Collections list.
 */


function getCollections(state) {
  return state.collections;
}
/**
 * Returns the name of the default block name.
 *
 * @param {Object} state Data state.
 *
 * @return {string?} Default block name.
 */


function getDefaultBlockName(state) {
  return state.defaultBlockName;
}
/**
 * Returns the name of the block for handling non-block content.
 *
 * @param {Object} state Data state.
 *
 * @return {string?} Name of the block for handling non-block content.
 */


function getFreeformFallbackBlockName(state) {
  return state.freeformFallbackBlockName;
}
/**
 * Returns the name of the block for handling unregistered blocks.
 *
 * @param {Object} state Data state.
 *
 * @return {string?} Name of the block for handling unregistered blocks.
 */


function getUnregisteredFallbackBlockName(state) {
  return state.unregisteredFallbackBlockName;
}
/**
 * Returns the name of the block for handling unregistered blocks.
 *
 * @param {Object} state Data state.
 *
 * @return {string?} Name of the block for handling unregistered blocks.
 */


function getGroupingBlockName(state) {
  return state.groupingBlockName;
}
/**
 * Returns an array with the child blocks of a given block.
 *
 * @param {Object} state     Data state.
 * @param {string} blockName Block type name.
 *
 * @return {Array} Array of child block names.
 */


var getChildBlockNames = (0, _rememo.default)(function (state, blockName) {
  return (0, _lodash.map)((0, _lodash.filter)(state.blockTypes, function (blockType) {
    return (0, _lodash.includes)(blockType.parent, blockName);
  }), function (_ref) {
    var name = _ref.name;
    return name;
  });
}, function (state) {
  return [state.blockTypes];
});
/**
 * Returns the block support value for a feature, if defined.
 *
 * @param  {Object}          state           Data state.
 * @param  {(string|Object)} nameOrType      Block name or type object
 * @param  {string}          feature         Feature to retrieve
 * @param  {*}               defaultSupports Default value to return if not
 *                                           explicitly defined
 *
 * @return {?*} Block support value
 */

exports.getChildBlockNames = getChildBlockNames;

var getBlockSupport = function getBlockSupport(state, nameOrType, feature, defaultSupports) {
  var blockType = getNormalizedBlockType(state, nameOrType);
  return (0, _lodash.get)(blockType, ['supports'].concat((0, _toConsumableArray2.default)(feature.split('.'))), defaultSupports);
};
/**
 * Returns true if the block defines support for a feature, or false otherwise.
 *
 * @param  {Object}         state           Data state.
 * @param {(string|Object)} nameOrType      Block name or type object.
 * @param {string}          feature         Feature to test.
 * @param {boolean}         defaultSupports Whether feature is supported by
 *                                          default if not explicitly defined.
 *
 * @return {boolean} Whether block supports feature.
 */


exports.getBlockSupport = getBlockSupport;

function hasBlockSupport(state, nameOrType, feature, defaultSupports) {
  return !!getBlockSupport(state, nameOrType, feature, defaultSupports);
}
/**
 * Returns true if the block type by the given name or object value matches a
 * search term, or false otherwise.
 *
 * @param {Object}          state      Blocks state.
 * @param {(string|Object)} nameOrType Block name or type object.
 * @param {string}          searchTerm Search term by which to filter.
 *
 * @return {Object[]} Whether block type matches search term.
 */


function isMatchingSearchTerm(state, nameOrType, searchTerm) {
  var blockType = getNormalizedBlockType(state, nameOrType);
  var getNormalizedSearchTerm = (0, _lodash.flow)([// Disregard diacritics.
  //  Input: "média"
  _lodash.deburr, // Lowercase.
  //  Input: "MEDIA"
  function (term) {
    return term.toLowerCase();
  }, // Strip leading and trailing whitespace.
  //  Input: " media "
  function (term) {
    return term.trim();
  }]);
  var normalizedSearchTerm = getNormalizedSearchTerm(searchTerm);
  var isSearchMatch = (0, _lodash.flow)([getNormalizedSearchTerm, function (normalizedCandidate) {
    return (0, _lodash.includes)(normalizedCandidate, normalizedSearchTerm);
  }]);
  return isSearchMatch(blockType.title) || (0, _lodash.some)(blockType.keywords, isSearchMatch) || isSearchMatch(blockType.category);
}
/**
 * Returns a boolean indicating if a block has child blocks or not.
 *
 * @param {Object} state     Data state.
 * @param {string} blockName Block type name.
 *
 * @return {boolean} True if a block contains child blocks and false otherwise.
 */


var hasChildBlocks = function hasChildBlocks(state, blockName) {
  return getChildBlockNames(state, blockName).length > 0;
};
/**
 * Returns a boolean indicating if a block has at least one child block with inserter support.
 *
 * @param {Object} state     Data state.
 * @param {string} blockName Block type name.
 *
 * @return {boolean} True if a block contains at least one child blocks with inserter support
 *                   and false otherwise.
 */


exports.hasChildBlocks = hasChildBlocks;

var hasChildBlocksWithInserterSupport = function hasChildBlocksWithInserterSupport(state, blockName) {
  return (0, _lodash.some)(getChildBlockNames(state, blockName), function (childBlockName) {
    return hasBlockSupport(state, childBlockName, 'inserter', true);
  });
};

exports.hasChildBlocksWithInserterSupport = hasChildBlocksWithInserterSupport;
//# sourceMappingURL=selectors.js.map