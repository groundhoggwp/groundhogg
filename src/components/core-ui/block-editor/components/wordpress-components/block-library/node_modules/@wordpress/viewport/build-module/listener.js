/**
 * External dependencies
 */
import { reduce, forEach, debounce, mapValues } from 'lodash';
/**
 * WordPress dependencies
 */

import { dispatch } from '@wordpress/data';

var addDimensionsEventListener = function addDimensionsEventListener(breakpoints, operators) {
  /**
   * Callback invoked when media query state should be updated. Is invoked a
   * maximum of one time per call stack.
   */
  var setIsMatching = debounce(function () {
    var values = mapValues(queries, function (query) {
      return query.matches;
    });
    dispatch('core/viewport').setIsMatching(values);
  }, {
    leading: true
  });
  /**
   * Hash of breakpoint names with generated MediaQueryList for corresponding
   * media query.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Window/matchMedia
   * @see https://developer.mozilla.org/en-US/docs/Web/API/MediaQueryList
   *
   * @type {Object<string,MediaQueryList>}
   */

  var queries = reduce(breakpoints, function (result, width, name) {
    forEach(operators, function (condition, operator) {
      var list = window.matchMedia("(".concat(condition, ": ").concat(width, "px)"));
      list.addListener(setIsMatching);
      var key = [operator, name].join(' ');
      result[key] = list;
    });
    return result;
  }, {});
  window.addEventListener('orientationchange', setIsMatching); // Set initial values

  setIsMatching();
  setIsMatching.flush();
};

export default addDimensionsEventListener;
//# sourceMappingURL=listener.js.map