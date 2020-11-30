/**
 * External dependencies
 */
import { pickBy, isEqual, isObject, identity, mapValues } from 'lodash';
/**
 * Removed undefined values from nested object.
 *
 * @param {*} object
 * @return {*} Object cleaned from undefined values
 */

export var cleanEmptyObject = function cleanEmptyObject(object) {
  if (!isObject(object)) {
    return object;
  }

  var cleanedNestedObjects = pickBy(mapValues(object, cleanEmptyObject), identity);
  return isEqual(cleanedNestedObjects, {}) ? undefined : cleanedNestedObjects;
};
//# sourceMappingURL=utils.js.map