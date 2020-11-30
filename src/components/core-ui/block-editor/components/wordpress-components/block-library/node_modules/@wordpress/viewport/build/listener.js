"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var addDimensionsEventListener = function addDimensionsEventListener(breakpoints, operators) {
  /**
   * Callback invoked when media query state should be updated. Is invoked a
   * maximum of one time per call stack.
   */
  var setIsMatching = (0, _lodash.debounce)(function () {
    var values = (0, _lodash.mapValues)(queries, function (query) {
      return query.matches;
    });
    (0, _data.dispatch)('core/viewport').setIsMatching(values);
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

  var queries = (0, _lodash.reduce)(breakpoints, function (result, width, name) {
    (0, _lodash.forEach)(operators, function (condition, operator) {
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

var _default = addDimensionsEventListener;
exports.default = _default;
//# sourceMappingURL=listener.js.map