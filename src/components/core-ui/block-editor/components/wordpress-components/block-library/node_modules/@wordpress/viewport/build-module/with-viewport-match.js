import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { mapValues } from 'lodash';
/**
 * WordPress dependencies
 */

import { createHigherOrderComponent, pure, useViewportMatch } from '@wordpress/compose';
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
    return mapValues(queries, function (query) {
      var _query$split = query.split(' '),
          _query$split2 = _slicedToArray(_query$split, 2),
          operator = _query$split2[0],
          breakpointName = _query$split2[1];

      if (breakpointName === undefined) {
        breakpointName = operator;
        operator = '>=';
      } // Hooks should unconditionally execute in the same order,
      // we are respecting that as from the static query of the HOC we generate
      // a hook that calls other hooks always in the same order (because the query never changes).
      // eslint-disable-next-line react-hooks/rules-of-hooks


      return useViewportMatch(breakpointName, operator);
    });
  };

  return createHigherOrderComponent(function (WrappedComponent) {
    return pure(function (props) {
      var queriesResult = useViewPortQueriesResult();
      return createElement(WrappedComponent, _extends({}, props, queriesResult));
    });
  }, 'withViewportMatch');
};

export default withViewportMatch;
//# sourceMappingURL=with-viewport-match.js.map