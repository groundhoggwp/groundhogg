"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = getBlockContext;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Block context cache, implemented as a WeakMap mapping block types to a
 * WeakMap mapping attributes object to context value.
 *
 * @type {WeakMap<string,WeakMap<string,*>>}
 */
var BLOCK_CONTEXT_CACHE = new WeakMap();
/**
 * Returns a cached context object value for a given set of attributes for the
 * block type.
 *
 * @param {Record<string,*>} attributes Block attributes object.
 * @param {WPBlockType}      blockType  Block type settings.
 *
 * @return {Record<string,*>} Context value.
 */

function getBlockContext(attributes, blockType) {
  if (!BLOCK_CONTEXT_CACHE.has(blockType)) {
    BLOCK_CONTEXT_CACHE.set(blockType, new WeakMap());
  }

  var blockTypeCache = BLOCK_CONTEXT_CACHE.get(blockType);

  if (!blockTypeCache.has(attributes)) {
    var context = (0, _lodash.mapValues)(blockType.providesContext, function (attributeName) {
      return attributes[attributeName];
    });
    blockTypeCache.set(attributes, context);
  }

  return blockTypeCache.get(attributes);
}
//# sourceMappingURL=get-block-context.js.map