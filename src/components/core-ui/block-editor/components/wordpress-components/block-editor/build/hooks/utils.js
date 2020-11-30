"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.cleanEmptyObject = void 0;

var _lodash = require("lodash");

/**
 * External dependencies
 */

/**
 * Removed undefined values from nested object.
 *
 * @param {*} object
 * @return {*} Object cleaned from undefined values
 */
var cleanEmptyObject = function cleanEmptyObject(object) {
  if (!(0, _lodash.isObject)(object)) {
    return object;
  }

  var cleanedNestedObjects = (0, _lodash.pickBy)((0, _lodash.mapValues)(object, cleanEmptyObject), _lodash.identity);
  return (0, _lodash.isEqual)(cleanedNestedObjects, {}) ? undefined : cleanedNestedObjects;
};

exports.cleanEmptyObject = cleanEmptyObject;
//# sourceMappingURL=utils.js.map