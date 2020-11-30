"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

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
  return (0, _compose.createHigherOrderComponent)((0, _data.withSelect)(function (select) {
    return (0, _lodash.mapValues)(queries, function (query) {
      return select('core/viewport').isViewportMatch(query);
    });
  }), 'withViewportMatch');
};

var _default = withViewportMatch;
exports.default = _default;
//# sourceMappingURL=with-viewport-match.native.js.map