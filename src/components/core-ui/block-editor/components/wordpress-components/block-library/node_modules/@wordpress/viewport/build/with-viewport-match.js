"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Higher-order component creator, creating a new component which renders with
 * the given prop names, where the value passed to the underlying component is
 * the result of the query assigned as the object's value.
 *
 * @see isViewportMatch
 *
 * @param {Object} queries  Object of prop name to viewport query.
 *
 * @example
 *
 * ```jsx
 * function MyComponent( { isMobile } ) {
 * 	return (
 * 		<div>Currently: { isMobile ? 'Mobile' : 'Not Mobile' }</div>
 * 	);
 * }
 *
 * MyComponent = withViewportMatch( { isMobile: '< small' } )( MyComponent );
 * ```
 *
 * @return {Function} Higher-order component.
 */
var withViewportMatch = function withViewportMatch(queries) {
  var useViewPortQueriesResult = function useViewPortQueriesResult() {
    return (0, _lodash.mapValues)(queries, function (query) {
      var _query$split = query.split(' '),
          _query$split2 = (0, _slicedToArray2.default)(_query$split, 2),
          operator = _query$split2[0],
          breakpointName = _query$split2[1];

      if (breakpointName === undefined) {
        breakpointName = operator;
        operator = '>=';
      } // Hooks should unconditionally execute in the same order,
      // we are respecting that as from the static query of the HOC we generate
      // a hook that calls other hooks always in the same order (because the query never changes).
      // eslint-disable-next-line react-hooks/rules-of-hooks


      return (0, _compose.useViewportMatch)(breakpointName, operator);
    });
  };

  return (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
    return (0, _compose.pure)(function (props) {
      var queriesResult = useViewPortQueriesResult();
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, props, queriesResult));
    });
  }, 'withViewportMatch');
};

var _default = withViewportMatch;
exports.default = _default;
//# sourceMappingURL=with-viewport-match.js.map